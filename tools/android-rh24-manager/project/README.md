# RH24 STRATO Manager für Android

Persönliche Android-App für die Veröffentlichung von Räucherhaken24-Updates auf einem STRATO-Webspace per SSH/SFTP.

## Funktionen
- direkte SSH/SFTP-Verbindung ohne STRATO-Webmanager im Browser
- ZIP-Auswahl über Androids sicheren Dateidialog
- lokale ZIP-Pfadprüfung gegen Zip-Slip
- TOFU-Serverfingerprint: erste Verbindung bestätigen, danach Schlüsseländerungen blockieren
- SFTP-Passwort optional verschlüsselt im Android Keystore
- automatisches vollständiges Server-Backup vor jedem Update
- Staging-Entpacken, danach nur vorhandene/neue Dateien aus dem ZIP kopieren
- Dateien, die nicht im ZIP stehen, werden nicht gelöscht
- letztes Backup direkt aus der App wiederherstellen
- HTTP-Funktionstest von Startseite und shop.html

## Sicherheitsprinzip
Das SSH-Passwort ist niemals im Quellcode hinterlegt. Beim ersten Start werden Host, Benutzer, Zielordner und Passwort eingetragen. Das Passwort kann über AES-GCM mit einem nicht exportierbaren Schlüssel im Android Keystore gespeichert werden.

## Servervoraussetzungen
SSH/SFTP-Zugang sowie die Shell-Programme `tar`, `unzip` und `cp` müssen verfügbar sein.

## Build
Android Gradle Plugin 8.7.3, compileSdk 35, minSdk 26, Java 17.

Der Release-Build wird als unsigned APK erzeugt. Das finale Installationspaket wird außerhalb des öffentlichen GitHub-Repositories signiert.
