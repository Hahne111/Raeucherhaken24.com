package de.hahnedigital.rh24manager;

import com.jcraft.jsch.ChannelExec;
import com.jcraft.jsch.ChannelSftp;
import com.jcraft.jsch.HostKey;
import com.jcraft.jsch.JSch;
import com.jcraft.jsch.Session;

import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.nio.charset.StandardCharsets;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
import java.util.Properties;

final class SftpDeployer {
    interface Logger { void log(String message); }

    static final class Config {
        final String host;
        final int port;
        final String username;
        final String password;
        final String targetPath;
        final String expectedFingerprint;
        Config(String host, int port, String username, String password, String targetPath, String expectedFingerprint) {
            this.host = host;
            this.port = port;
            this.username = username;
            this.password = password;
            this.targetPath = targetPath;
            this.expectedFingerprint = expectedFingerprint;
        }
    }

    static final class ConnectionResult {
        final String fingerprint;
        final String absoluteTarget;
        ConnectionResult(String fingerprint, String absoluteTarget) {
            this.fingerprint = fingerprint;
            this.absoluteTarget = absoluteTarget;
        }
    }

    static final class DeployResult {
        final String backupName;
        final String absoluteTarget;
        DeployResult(String backupName, String absoluteTarget) {
            this.backupName = backupName;
            this.absoluteTarget = absoluteTarget;
        }
    }

    static ConnectionResult test(Config cfg) throws Exception {
        Session session = connect(cfg, false);
        try {
            String fingerprint = fingerprint(session);
            if (cfg.expectedFingerprint != null && !cfg.expectedFingerprint.isEmpty()
                    && !cfg.expectedFingerprint.equalsIgnoreCase(fingerprint)) {
                throw new SecurityException("Server-Schlüssel hat sich geändert. Verbindung abgebrochen.\nErwartet: "
                        + cfg.expectedFingerprint + "\nErhalten: " + fingerprint);
            }
            String target = resolveTarget(session, cfg.targetPath);
            return new ConnectionResult(fingerprint, target);
        } finally {
            session.disconnect();
        }
    }

    static DeployResult deploy(Config cfg, File zipFile, ZipValidator.Result zipInfo, Logger logger) throws Exception {
        if (cfg.expectedFingerprint == null || cfg.expectedFingerprint.isEmpty()) {
            throw new SecurityException("Bitte zuerst 'Verbindung testen' und den Server-Schlüssel bestätigen.");
        }
        Session session = connect(cfg, true);
        try {
            verifyFingerprint(session, cfg.expectedFingerprint);
            String target = resolveTarget(session, cfg.targetPath);
            logger.log("Zielverzeichnis: " + target);
            preflight(session);

            String stamp = new SimpleDateFormat("yyyyMMdd-HHmmss", Locale.GERMANY).format(new Date());
            String manager = target + "/.rh24-manager-android";
            String incoming = manager + "/incoming";
            String staging = manager + "/staging/deploy-" + stamp;
            String backupDir = manager + "/backups";
            String remoteZip = incoming + "/update-" + stamp + ".zip";
            String backupName = "backup-" + stamp + ".tar.gz";
            String backupPath = backupDir + "/" + backupName;

            logger.log("Server-Verzeichnisse vorbereiten …");
            exec(session, "mkdir -p " + q(incoming) + " " + q(staging) + " " + q(backupDir), 60);

            logger.log("Backup des aktuellen Shop-Standes erstellen …");
            exec(session, "cd " + q(target) + " && tar --exclude='./.rh24-manager-android' -czf "
                    + q(backupPath) + " .", 240);
            logger.log("Backup erstellt: " + backupName);

            logger.log("ZIP sicher per SFTP hochladen …");
            ChannelSftp sftp = (ChannelSftp) session.openChannel("sftp");
            sftp.connect(20000);
            try (FileInputStream in = new FileInputStream(zipFile)) {
                sftp.put(in, remoteZip, ChannelSftp.OVERWRITE);
            } finally {
                sftp.disconnect();
            }

            logger.log("Update in Staging-Verzeichnis entpacken …");
            exec(session, "unzip -oq " + q(remoteZip) + " -d " + q(staging), 180);

            String source = staging;
            if (zipInfo.stripPrefix != null) {
                String prefix = zipInfo.stripPrefix.substring(0, zipInfo.stripPrefix.length() - 1);
                source = staging + "/" + prefix;
            }
            logger.log("Dateien veröffentlichen – vorhandene Dateien werden ersetzt, andere bleiben erhalten …");
            exec(session, "cp -a " + q(source + "/.") + " " + q(target + "/"), 240);

            logger.log("Temporäre Update-Dateien entfernen …");
            exec(session, "rm -rf " + q(staging) + " " + q(remoteZip), 60);
            return new DeployResult(backupName, target);
        } finally {
            session.disconnect();
        }
    }

    static String restoreLatest(Config cfg, Logger logger) throws Exception {
        if (cfg.expectedFingerprint == null || cfg.expectedFingerprint.isEmpty()) {
            throw new SecurityException("Kein bestätigter Server-Schlüssel gespeichert.");
        }
        Session session = connect(cfg, true);
        try {
            verifyFingerprint(session, cfg.expectedFingerprint);
            String target = resolveTarget(session, cfg.targetPath);
            String backupDir = target + "/.rh24-manager-android/backups";
            String command = "latest=$(ls -1t " + q(backupDir) + "/backup-*.tar.gz 2>/dev/null | head -n 1); "
                    + "test -n \"$latest\" || { echo 'NO_BACKUP'; exit 44; }; "
                    + "echo \"$latest\"; tar -xzf \"$latest\" -C " + q(target);
            logger.log("Letztes Backup wird wiederhergestellt …");
            String output = exec(session, command, 300).trim();
            logger.log("Wiederherstellung abgeschlossen.");
            return output;
        } finally {
            session.disconnect();
        }
    }

    private static Session connect(Config cfg, boolean requireExpectedFingerprint) throws Exception {
        validateConfig(cfg);
        JSch jsch = new JSch();
        Session session = jsch.getSession(cfg.username, cfg.host, cfg.port);
        session.setPassword(cfg.password);
        Properties props = new Properties();
        props.put("StrictHostKeyChecking", "no");
        props.put("PreferredAuthentications", "password,keyboard-interactive");
        session.setConfig(props);
        session.setServerAliveInterval(15000);
        session.setServerAliveCountMax(3);
        session.connect(20000);
        if (requireExpectedFingerprint) verifyFingerprint(session, cfg.expectedFingerprint);
        return session;
    }

    private static void validateConfig(Config cfg) {
        if (cfg.host == null || cfg.host.trim().isEmpty()) throw new IllegalArgumentException("SSH-Host fehlt.");
        if (cfg.username == null || cfg.username.trim().isEmpty()) throw new IllegalArgumentException("Benutzername fehlt.");
        if (cfg.password == null || cfg.password.isEmpty()) throw new IllegalArgumentException("Passwort fehlt.");
        if (cfg.targetPath == null || cfg.targetPath.trim().isEmpty()) throw new IllegalArgumentException("Zielverzeichnis fehlt.");
        if (cfg.port < 1 || cfg.port > 65535) throw new IllegalArgumentException("Ungültiger Port.");
    }

    private static void verifyFingerprint(Session session, String expected) throws Exception {
        if (expected == null || expected.isEmpty()) throw new SecurityException("Kein Server-Fingerprint gespeichert.");
        String current = fingerprint(session);
        if (!expected.equalsIgnoreCase(current)) {
            throw new SecurityException("Server-Schlüssel stimmt nicht mit dem gespeicherten Schlüssel überein.\nErwartet: "
                    + expected + "\nErhalten: " + current);
        }
    }

    private static String fingerprint(Session session) throws Exception {
        HostKey hostKey = session.getHostKey();
        return hostKey.getFingerPrint(new JSch());
    }

    private static String resolveTarget(Session session, String requested) throws Exception {
        String expression;
        String value = requested.trim();
        if (value.equals("~")) expression = "$HOME";
        else if (value.startsWith("~/")) expression = "$HOME/" + q(value.substring(2));
        else expression = q(value);
        String resolved = exec(session, "cd " + expression + " && pwd", 30).trim();
        if (resolved.isEmpty() || !resolved.startsWith("/")) throw new IllegalStateException("Zielverzeichnis konnte nicht aufgelöst werden.");
        return resolved;
    }

    private static void preflight(Session session) throws Exception {
        exec(session, "command -v tar >/dev/null && command -v unzip >/dev/null && command -v cp >/dev/null", 30);
    }

    private static String exec(Session session, String command, int timeoutSeconds) throws Exception {
        ChannelExec channel = (ChannelExec) session.openChannel("exec");
        channel.setCommand(command);
        channel.setInputStream(null);
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        ByteArrayOutputStream err = new ByteArrayOutputStream();
        channel.setOutputStream(out);
        channel.setErrStream(err);
        channel.connect(15000);
        long deadline = System.currentTimeMillis() + timeoutSeconds * 1000L;
        while (!channel.isClosed()) {
            if (System.currentTimeMillis() > deadline) {
                channel.disconnect();
                throw new IllegalStateException("Server-Befehl hat das Zeitlimit überschritten.");
            }
            Thread.sleep(100);
        }
        int status = channel.getExitStatus();
        channel.disconnect();
        String stdout = out.toString(StandardCharsets.UTF_8.name());
        String stderr = err.toString(StandardCharsets.UTF_8.name()).trim();
        if (status != 0) {
            throw new IllegalStateException("Server-Befehl fehlgeschlagen (Code " + status + ")."
                    + (stderr.isEmpty() ? "" : "\n" + stderr));
        }
        return stdout;
    }

    private static String q(String value) {
        return "'" + value.replace("'", "'\\''") + "'";
    }
}
