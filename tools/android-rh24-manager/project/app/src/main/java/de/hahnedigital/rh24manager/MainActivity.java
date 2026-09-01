package de.hahnedigital.rh24manager;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.graphics.Typeface;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.provider.OpenableColumns;
import android.text.InputType;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.CheckBox;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class MainActivity extends Activity {
    private static final int PICK_ZIP = 1001;
    private static final String PREFS = "rh24_settings";

    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    private final Handler main = new Handler(Looper.getMainLooper());

    private EditText hostField, portField, userField, passwordField, targetField, websiteField;
    private CheckBox rememberPassword;
    private TextView fileStatus, connectionStatus, logView;
    private ProgressBar progress;
    private Button testButton, chooseButton, deployButton, restoreButton;
    private File selectedZip;
    private ZipValidator.Result selectedZipInfo;
    private String selectedZipName = "";
    private SharedPreferences prefs;
    private SecureStore secureStore;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        prefs = getSharedPreferences(PREFS, MODE_PRIVATE);
        secureStore = new SecureStore(this);
        setContentView(buildUi());
        loadSettings();
    }

    private View buildUi() {
        ScrollView scroll = new ScrollView(this);
        scroll.setFillViewport(true);
        LinearLayout root = column();
        root.setPadding(dp(16), dp(18), dp(16), dp(40));
        root.setBackgroundColor(Color.rgb(243, 244, 246));
        scroll.addView(root);

        LinearLayout hero = column();
        hero.setPadding(dp(18), dp(18), dp(18), dp(18));
        hero.setBackgroundColor(Color.rgb(17, 24, 39));
        TextView title = text("RH24 STRATO Manager", 26, Color.WHITE, true);
        TextView sub = text("Samsung / Android · SFTP · Backup · Direktupdate", 14, Color.rgb(209, 213, 219), false);
        hero.addView(title); hero.addView(sub);
        root.addView(hero, matchWrap(dp(12)));

        LinearLayout connectionCard = card("1 · STRATO-Verbindung");
        hostField = field("SSH-Host");
        portField = field("Port (22)"); portField.setInputType(InputType.TYPE_CLASS_NUMBER);
        userField = field("Benutzername");
        passwordField = field("SFTP/SSH-Passwort"); passwordField.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_PASSWORD);
        targetField = field("Zielverzeichnis auf dem Server, z. B. ~/webshop");
        websiteField = field("Website-URL für Funktionstest");
        rememberPassword = new CheckBox(this);
        rememberPassword.setText("Passwort verschlüsselt auf diesem Samsung speichern");
        rememberPassword.setChecked(true);
        connectionCard.addView(hostField); connectionCard.addView(portField); connectionCard.addView(userField);
        connectionCard.addView(passwordField); connectionCard.addView(targetField); connectionCard.addView(websiteField);
        connectionCard.addView(rememberPassword);
        testButton = action("VERBINDUNG TESTEN", Color.rgb(15, 118, 110));
        connectionCard.addView(testButton);
        connectionStatus = text("Noch nicht geprüft.", 13, Color.DKGRAY, false);
        connectionCard.addView(connectionStatus);
        root.addView(connectionCard, matchWrap(dp(12)));

        LinearLayout updateCard = card("2 · Update auswählen");
        fileStatus = text("Noch keine ZIP-Datei ausgewählt.", 14, Color.DKGRAY, false);
        chooseButton = action("ZIP-DATEI AUSWÄHLEN", Color.rgb(217, 119, 6));
        updateCard.addView(fileStatus); updateCard.addView(chooseButton);
        root.addView(updateCard, matchWrap(dp(12)));

        LinearLayout deployCard = card("3 · Sicher veröffentlichen");
        TextView note = text("Ablauf: ZIP prüfen → Server-Backup → SFTP-Upload → Staging → vorhandene Dateien ersetzen. Nicht im ZIP enthaltene Shop-Dateien werden nicht gelöscht.", 14, Color.rgb(55, 65, 81), false);
        deployCard.addView(note);
        deployButton = action("RÄUCHERHAKEN24 AKTUALISIEREN", Color.rgb(5, 150, 105));
        restoreButton = action("LETZTES BACKUP WIEDERHERSTELLEN", Color.rgb(153, 27, 27));
        deployCard.addView(deployButton); deployCard.addView(restoreButton);
        progress = new ProgressBar(this);
        progress.setVisibility(View.GONE);
        deployCard.addView(progress);
        root.addView(deployCard, matchWrap(dp(12)));

        LinearLayout logCard = card("Protokoll");
        logView = text("Bereit.", 13, Color.rgb(31, 41, 55), false);
        logView.setTypeface(Typeface.MONOSPACE);
        logCard.addView(logView);
        root.addView(logCard, matchWrap(0));

        TextView footer = text("Power by Hahne Digital · Zugangsdaten werden nicht in ZIP-Dateien oder Protokolle geschrieben.", 12, Color.GRAY, false);
        footer.setGravity(Gravity.CENTER);
        root.addView(footer, matchWrap(dp(12)));

        testButton.setOnClickListener(v -> testConnection());
        chooseButton.setOnClickListener(v -> chooseZip());
        deployButton.setOnClickListener(v -> confirmDeploy());
        restoreButton.setOnClickListener(v -> confirmRestore());
        return scroll;
    }

    private void loadSettings() {
        hostField.setText(prefs.getString("host", ""));
        portField.setText(String.valueOf(prefs.getInt("port", 22)));
        userField.setText(prefs.getString("user", ""));
        targetField.setText(prefs.getString("target", ""));
        websiteField.setText(prefs.getString("website", "https://raeucherhaken24.com"));
        if (secureStore.hasPassword()) passwordField.setHint("Passwort sicher im Android-Keystore gespeichert");
        String fp = prefs.getString("fingerprint", "");
        if (!fp.isEmpty()) connectionStatus.setText("Gespeicherter Server-Schlüssel: " + fp);
    }

    private void saveNonSecretSettings() {
        int port = parsePort();
        prefs.edit()
                .putString("host", hostField.getText().toString().trim())
                .putInt("port", port)
                .putString("user", userField.getText().toString().trim())
                .putString("target", targetField.getText().toString().trim())
                .putString("website", websiteField.getText().toString().trim())
                .apply();
    }

    private String currentPassword() {
        String typed = passwordField.getText().toString();
        return typed.isEmpty() ? secureStore.loadPassword() : typed;
    }

    private void persistPasswordIfRequested() throws Exception {
        String typed = passwordField.getText().toString();
        if (rememberPassword.isChecked()) {
            if (!typed.isEmpty()) secureStore.savePassword(typed);
        } else {
            secureStore.clearPassword();
        }
    }

    private SftpDeployer.Config config(boolean withFingerprint) {
        return new SftpDeployer.Config(
                hostField.getText().toString().trim(),
                parsePort(),
                userField.getText().toString().trim(),
                currentPassword(),
                targetField.getText().toString().trim(),
                withFingerprint ? prefs.getString("fingerprint", "") : ""
        );
    }

    private int parsePort() {
        try { return Integer.parseInt(portField.getText().toString().trim()); }
        catch (Exception ex) { return 22; }
    }

    private void testConnection() {
        try {
            saveNonSecretSettings();
            persistPasswordIfRequested();
        } catch (Exception ex) {
            error(ex.getMessage()); return;
        }
        busy(true); log("Verbindung wird geprüft …");
        SftpDeployer.Config cfg = config(false);
        executor.submit(() -> {
            try {
                SftpDeployer.ConnectionResult result = SftpDeployer.test(cfg);
                main.post(() -> {
                    busy(false);
                    new AlertDialog.Builder(this)
                            .setTitle("Server-Schlüssel bestätigen")
                            .setMessage("SSH-Fingerprint:\n\n" + result.fingerprint + "\n\nZiel:\n" + result.absoluteTarget
                                    + "\n\nNur bestätigen, wenn du diese STRATO-Verbindung erwartest. Der Schlüssel wird danach für weitere Uploads fest gespeichert.")
                            .setNegativeButton("Abbrechen", null)
                            .setPositiveButton("Vertrauen", (d, w) -> {
                                prefs.edit().putString("fingerprint", result.fingerprint).apply();
                                connectionStatus.setText("Verbunden · Server-Schlüssel gespeichert\n" + result.fingerprint);
                                log("Verbindung erfolgreich. Server-Schlüssel gespeichert.");
                            }).show();
                });
            } catch (Exception ex) {
                main.post(() -> { busy(false); error(ex.getMessage()); });
            }
        });
    }

    private void chooseZip() {
        Intent intent = new Intent(Intent.ACTION_OPEN_DOCUMENT);
        intent.addCategory(Intent.CATEGORY_OPENABLE);
        intent.setType("application/zip");
        intent.putExtra(Intent.EXTRA_MIME_TYPES, new String[]{"application/zip", "application/octet-stream"});
        startActivityForResult(intent, PICK_ZIP);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode != PICK_ZIP || resultCode != RESULT_OK || data == null || data.getData() == null) return;
        Uri uri = data.getData();
        try { getContentResolver().takePersistableUriPermission(uri, Intent.FLAG_GRANT_READ_URI_PERMISSION); } catch (Exception ignored) {}
        busy(true); log("ZIP wird lokal geprüft …");
        executor.submit(() -> {
            try {
                String name = displayName(uri);
                File cache = new File(getCacheDir(), "selected-update.zip");
                try (InputStream in = getContentResolver().openInputStream(uri); FileOutputStream out = new FileOutputStream(cache)) {
                    if (in == null) throw new IllegalStateException("Datei konnte nicht geöffnet werden.");
                    byte[] buffer = new byte[64 * 1024];
                    int n;
                    long total = 0;
                    while ((n = in.read(buffer)) > 0) {
                        total += n;
                        if (total > 350L * 1024L * 1024L) throw new IllegalArgumentException("ZIP ist größer als 350 MB.");
                        out.write(buffer, 0, n);
                    }
                }
                ZipValidator.Result info = ZipValidator.validate(cache);
                main.post(() -> {
                    selectedZip = cache;
                    selectedZipInfo = info;
                    selectedZipName = name;
                    fileStatus.setText(name + "\n" + info.fileCount + " Dateien geprüft"
                            + (info.stripPrefix != null ? " · Hauptordner wird automatisch erkannt" : ""));
                    log("ZIP-Prüfung erfolgreich: " + info.fileCount + " Dateien.");
                    busy(false);
                });
            } catch (Exception ex) {
                main.post(() -> { busy(false); error(ex.getMessage()); });
            }
        });
    }

    private void confirmDeploy() {
        if (selectedZip == null || selectedZipInfo == null) {
            error("Bitte zuerst eine ZIP-Datei auswählen."); return;
        }
        if (prefs.getString("fingerprint", "").isEmpty()) {
            error("Bitte zuerst die STRATO-Verbindung testen und den Server-Schlüssel bestätigen."); return;
        }
        new AlertDialog.Builder(this)
                .setTitle("Update veröffentlichen?")
                .setMessage(selectedZipName + "\n\nVor dem Überschreiben wird automatisch ein vollständiges Server-Backup erstellt.")
                .setNegativeButton("Abbrechen", null)
                .setPositiveButton("Jetzt aktualisieren", (d, w) -> deploy())
                .show();
    }

    private void deploy() {
        try { saveNonSecretSettings(); persistPasswordIfRequested(); }
        catch (Exception ex) { error(ex.getMessage()); return; }
        busy(true); log("=== UPDATE START ===");
        SftpDeployer.Config cfg = config(true);
        executor.submit(() -> {
            try {
                SftpDeployer.DeployResult result = SftpDeployer.deploy(cfg, selectedZip, selectedZipInfo, this::logFromWorker);
                String webTest = testWebsite(websiteField.getText().toString().trim());
                main.post(() -> {
                    busy(false);
                    log("Update erfolgreich. Backup: " + result.backupName);
                    log(webTest);
                    new AlertDialog.Builder(this)
                            .setTitle("Update abgeschlossen")
                            .setMessage("Räucherhaken24 wurde veröffentlicht.\n\nBackup: " + result.backupName + "\n\n" + webTest)
                            .setPositiveButton("OK", null).show();
                });
            } catch (Exception ex) {
                main.post(() -> {
                    busy(false);
                    error("Update abgebrochen: " + ex.getMessage() + "\n\nEin eventuell bereits erstelltes Backup bleibt auf dem Server erhalten.");
                });
            }
        });
    }

    private void confirmRestore() {
        if (prefs.getString("fingerprint", "").isEmpty()) {
            error("Bitte zuerst die Verbindung testen."); return;
        }
        new AlertDialog.Builder(this)
                .setTitle("Letztes Backup wiederherstellen?")
                .setMessage("Vorhandene Dateien werden mit dem letzten RH24-Backup überschrieben. Diese Aktion nur verwenden, wenn ein Update fehlerhaft ist.")
                .setNegativeButton("Abbrechen", null)
                .setPositiveButton("Wiederherstellen", (d, w) -> restore())
                .show();
    }

    private void restore() {
        try { saveNonSecretSettings(); persistPasswordIfRequested(); }
        catch (Exception ex) { error(ex.getMessage()); return; }
        busy(true); log("=== BACKUP RESTORE ===");
        SftpDeployer.Config cfg = config(true);
        executor.submit(() -> {
            try {
                String output = SftpDeployer.restoreLatest(cfg, this::logFromWorker);
                String test = testWebsite(websiteField.getText().toString().trim());
                main.post(() -> { busy(false); log(output); log(test); toast("Backup wiederhergestellt."); });
            } catch (Exception ex) {
                main.post(() -> { busy(false); error(ex.getMessage()); });
            }
        });
    }

    private String testWebsite(String base) {
        if (base == null || base.isEmpty()) return "Website-Test übersprungen.";
        String normalized = base.endsWith("/") ? base.substring(0, base.length() - 1) : base;
        StringBuilder result = new StringBuilder("Website-Test:");
        String[] paths = new String[]{"/", "/shop.html"};
        for (String path : paths) {
            HttpURLConnection c = null;
            try {
                c = (HttpURLConnection) new URL(normalized + path).openConnection();
                c.setRequestMethod("GET");
                c.setConnectTimeout(12000); c.setReadTimeout(12000);
                c.setInstanceFollowRedirects(true);
                c.setRequestProperty("User-Agent", "RH24-Android-Manager/1.0");
                int code = c.getResponseCode();
                result.append("\n").append(path).append(" → HTTP ").append(code);
            } catch (Exception ex) {
                result.append("\n").append(path).append(" → Fehler: ").append(ex.getClass().getSimpleName());
            } finally {
                if (c != null) c.disconnect();
            }
        }
        return result.toString();
    }

    private String displayName(Uri uri) {
        try (android.database.Cursor cursor = getContentResolver().query(uri, new String[]{OpenableColumns.DISPLAY_NAME}, null, null, null)) {
            if (cursor != null && cursor.moveToFirst()) return cursor.getString(0);
        } catch (Exception ignored) {}
        return "update.zip";
    }

    private void busy(boolean value) {
        progress.setVisibility(value ? View.VISIBLE : View.GONE);
        testButton.setEnabled(!value); chooseButton.setEnabled(!value); deployButton.setEnabled(!value); restoreButton.setEnabled(!value);
    }

    private void logFromWorker(String message) { main.post(() -> log(message)); }
    private void log(String message) { logView.append("\n" + message); }
    private void toast(String message) { Toast.makeText(this, message, Toast.LENGTH_LONG).show(); }
    private void error(String message) { log("FEHLER: " + message); new AlertDialog.Builder(this).setTitle("Fehler").setMessage(message).setPositiveButton("OK", null).show(); }

    private LinearLayout column() {
        LinearLayout l = new LinearLayout(this); l.setOrientation(LinearLayout.VERTICAL); return l;
    }
    private LinearLayout card(String heading) {
        LinearLayout card = column(); card.setPadding(dp(16), dp(16), dp(16), dp(16)); card.setBackgroundColor(Color.WHITE);
        TextView h = text(heading, 19, Color.rgb(17,24,39), true); card.addView(h, matchWrap(dp(10))); return card;
    }
    private EditText field(String hint) {
        EditText e = new EditText(this); e.setHint(hint); e.setTextSize(15); e.setSingleLine(true);
        e.setPadding(dp(12), dp(12), dp(12), dp(12));
        e.setLayoutParams(matchWrap(dp(8))); return e;
    }
    private Button action(String label, int color) {
        Button b = new Button(this); b.setText(label); b.setTextColor(Color.WHITE); b.setTextSize(14); b.setTypeface(Typeface.DEFAULT_BOLD);
        b.setBackgroundColor(color); b.setMinHeight(dp(52)); b.setAllCaps(false); b.setLayoutParams(matchWrap(dp(10))); return b;
    }
    private TextView text(String value, int sp, int color, boolean bold) {
        TextView t = new TextView(this); t.setText(value); t.setTextSize(sp); t.setTextColor(color); if (bold) t.setTypeface(Typeface.DEFAULT_BOLD);
        t.setLineSpacing(0, 1.08f); return t;
    }
    private LinearLayout.LayoutParams matchWrap(int bottom) {
        LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT);
        p.bottomMargin = bottom; return p;
    }
    private int dp(int value) { return Math.round(value * getResources().getDisplayMetrics().density); }

    @Override protected void onDestroy() { executor.shutdownNow(); super.onDestroy(); }
}
