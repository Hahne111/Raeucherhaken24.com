package de.hahnedigital.rh24manager;

import java.io.File;
import java.io.FileInputStream;
import java.util.HashSet;
import java.util.Set;
import java.util.zip.ZipEntry;
import java.util.zip.ZipInputStream;

final class ZipValidator {
    static final class Result {
        final int fileCount;
        final long uncompressedBytes;
        final String stripPrefix;
        Result(int fileCount, long uncompressedBytes, String stripPrefix) {
            this.fileCount = fileCount;
            this.uncompressedBytes = uncompressedBytes;
            this.stripPrefix = stripPrefix;
        }
    }

    static Result validate(File file) throws Exception {
        int files = 0;
        long bytes = 0;
        boolean rootFile = false;
        Set<String> top = new HashSet<>();
        try (ZipInputStream zin = new ZipInputStream(new FileInputStream(file))) {
            ZipEntry entry;
            while ((entry = zin.getNextEntry()) != null) {
                String name = entry.getName().replace('\\', '/');
                if (!isSafe(name)) throw new IllegalArgumentException("Unsicherer ZIP-Pfad: " + name);
                String clean = trimSlashes(name);
                if (!clean.isEmpty()) {
                    int slash = clean.indexOf('/');
                    if (slash < 0) {
                        rootFile = !entry.isDirectory();
                        top.add(clean);
                    } else {
                        top.add(clean.substring(0, slash));
                    }
                }
                if (!entry.isDirectory()) {
                    files++;
                    if (entry.getSize() > 0) bytes += entry.getSize();
                    if (files > 20000) throw new IllegalArgumentException("ZIP enthält zu viele Dateien.");
                    if (bytes > 1024L * 1024L * 1024L) throw new IllegalArgumentException("Entpackte ZIP-Daten sind größer als 1 GB.");
                }
                zin.closeEntry();
            }
        }
        if (files == 0) throw new IllegalArgumentException("ZIP enthält keine Dateien.");
        String prefix = (!rootFile && top.size() == 1) ? top.iterator().next() + "/" : null;
        return new Result(files, bytes, prefix);
    }

    private static boolean isSafe(String name) {
        if (name == null || name.isEmpty() || name.indexOf('\0') >= 0) return false;
        if (name.startsWith("/") || name.matches("^[A-Za-z]:/.*")) return false;
        for (String part : name.split("/")) {
            if ("..".equals(part)) return false;
        }
        return true;
    }

    private static String trimSlashes(String value) {
        int start = 0, end = value.length();
        while (start < end && value.charAt(start) == '/') start++;
        while (end > start && value.charAt(end - 1) == '/') end--;
        return value.substring(start, end);
    }
}
