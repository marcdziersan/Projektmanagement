# IT Projektmanagement – Refactoring & Erweiterung

Dieses Repository basiert auf CyberGreyRat/Projektmanagement und dokumentiert mein Refactoring, meine UI-Anpassungen, CRUD-Erweiterungen und Installer-Integration.

## Refactoring - und Anpassungen vorgenommen.

---

## Inhaltsverzeichnis

- [Funktionsumfang](#funktionsumfang)
- [Technik](#technik)
- [Systemvoraussetzungen](#systemvoraussetzungen)
- [Installation](#installation)
- [Update / Migration](#update--migration)
- [Erster Start](#erster-start)
- [Bedienung](#bedienung)
- [Projektstruktur](#projektstruktur)
- [Datenbanktabellen](#datenbanktabellen)
- [API-Endpunkte](#api-endpunkte)
- [Statuswerte](#statuswerte)
- [Sicherheitshinweise](#sicherheitshinweise)
- [Entwicklung und Prüfung](#entwicklung-und-prüfung)
- [Changelog](#changelog)

---

## Funktionsumfang

### Allgemein

- Login und Registrierung
- Session-Handling mit robuster Prüfung des aktuellen Nutzers
- First-Run-Installer für die Erstinstallation
- automatische Erstellung der `config.php`
- Datenbankverbindung über Installer konfigurierbar
- erste Admin-Anlage während der Installation
- zentrale Datenbank- und Migrationslogik
- moderne dunkle Oberfläche im kompakten Glassmorphism-Stil
- kompakter Anwendungstitel und reduzierte Topbar

### Projekte

- Projekt anlegen
- Projekt bearbeiten
- Projekt löschen
- Projektbeschreibung pflegen
- Projektfarbe auswählen
- Projektstatus setzen
- Projektübersicht mit Kartenansicht
- Projektliste in der Sidebar
- direkter Bearbeiten-Button in der Sidebar
- Aufgaben pro Projekt anzeigen
- Aufgabe direkt aus Projektansicht erstellen

Projektlöschung erfolgt als Soft-Delete. Das Projekt bleibt in der Datenbank erhalten, wird aber im normalen Betrieb ausgeblendet.

### Aufgaben

- Aufgabe anlegen
- Aufgabe bearbeiten
- Aufgabe löschen
- Titel, Beschreibung, Projekt, Zeitraum, Zuweisung und Status bearbeiten
- Aufgabenstatus auswählen
- Aufgabenliste innerhalb eines Projekts
- Bearbeiten- und Löschen-Aktionen direkt in der Aufgabenliste
- kompakte getrennte Datums- und Uhrzeitfelder statt großer `datetime-local`-Auswahl
- sinnvolle Standardwerte für neue Aufgaben

Aufgabenlöschung erfolgt ebenfalls als Soft-Delete.

### Kalender

- Monatskalender
- Navigation zwischen Monaten
- Heute-Button
- Aufgabenanzeige in Kalendertagen
- farbliche Statusdarstellung
- Klick auf Aufgabenkarte öffnet die Aufgabe zur Bearbeitung

### Zeiterfassung

- Timer pro Aufgabe starten
- Timer stoppen
- pro Nutzer nur ein aktiver Timer gleichzeitig
- offene Timer werden bei Aufgabenabschluss oder Löschung sauber beendet
- Zeitlogs werden projekt- und aufgabenbezogen gespeichert

### Auswertung

- Jahresauswertung nach Projektzeit
- Übersicht der erfassten Zeiten
- Grundlage für spätere Monatszettel oder Abrechnungsfunktionen

### Verlauf / Audit-Log

- zentrale Protokollierung wichtiger Aktionen
- Nutzerbezug
- Zeitstempel
- strukturierte JSON-Daten im Log

---

## Technik

Verwendete Technologien:

- PHP 8.x
- MySQL oder MariaDB
- PDO
- HTML5
- CSS3
- Vanilla JavaScript
- Font Awesome über CDN
- Google Font `Outfit`

Es werden keine großen Frontend-Frameworks wie React, Vue, Angular oder Build-Tools wie Vite benötigt.

---

## Systemvoraussetzungen

Empfohlen:

- PHP 8.1 oder neuer
- MySQL 5.7+ oder MariaDB 10.4+
- Apache oder Nginx
- aktivierte PHP-Erweiterung `pdo_mysql`
- Schreibrechte im Projektordner für die automatische Erstellung von `config.php`

Lokale Entwicklung unter Windows:

- WAMP
- XAMPP
- Laragon

Beispielpfad bei WAMP:

```text
C:\wamp64\www\pml
```

---

## Installation

### 1. Projekt kopieren

Projektordner auf den Webserver kopieren, zum Beispiel:

```text
C:\wamp64\www\pml
```

oder auf einem Linux-Server beispielsweise:

```text
/var/www/html/pml
```

### 2. Installer öffnen

Im Browser öffnen:

```text
http://localhost/pml/install.php
```

Falls noch keine gültige `config.php` vorhanden ist, leitet `index.php` automatisch auf den Installer weiter.

### 3. Datenbankdaten eintragen

Im Installer werden abgefragt:

- Datenbank-Host
- Port
- Datenbankname
- Datenbank-Benutzer
- Datenbank-Passwort
- Charset
- Anwendungsname
- erster Admin-Benutzer
- Admin-Passwort

Standardwerte für lokale WAMP-/XAMPP-Installationen sind häufig:

```text
Host: 127.0.0.1
Port: 3306
Datenbank: projektmanagement
Benutzer: root
Passwort: leer
Charset: utf8mb4
```

### 4. Installation starten

Der Installer erledigt automatisch:

- Datenbankverbindung testen
- Datenbank erstellen, sofern sie noch nicht existiert und der DB-Nutzer die Rechte besitzt
- Tabellen erstellen
- bestehende Tabellen migrieren
- `config.php` erzeugen
- ersten Admin-Nutzer anlegen, falls noch kein Nutzer existiert

### 5. Anmelden

Nach erfolgreicher Installation öffnen:

```text
http://localhost/pml/index.php
```

Danach mit dem im Installer angelegten Admin anmelden.

---

## Update / Migration

Wenn das Projekt bereits installiert war und nur aktualisiert wurde, kann die Migration erneut ausgeführt werden:

```text
http://localhost/pml/setup_db.php
```

`setup_db.php` verwendet dieselbe zentrale Migrationslogik wie der Installer.

Wichtig:

- Vor Updates immer ein Backup der Datenbank erstellen.
- Vor Updates auch den Projektordner sichern.
- Die lokale `config.php` nicht überschreiben, wenn bereits echte Zugangsdaten eingetragen sind.

---

## Erster Start

Nach der Anmeldung sieht man die Hauptoberfläche mit:

- Sidebar
- Benutzerprofil
- Navigation
- Projektliste
- Kalenderansicht
- Button für neue Aufgaben

Die Hauptbereiche sind:

```text
Kalender
Projekte
Auswertung
Verlauf
```

---

## Bedienung

### Projekt anlegen

1. Bereich `Projekte` öffnen.
2. Button `Projekt anlegen` klicken.
3. Name, Beschreibung, Farbe und Status eintragen.
4. Speichern.

### Projekt bearbeiten

1. Projektübersicht öffnen.
2. Beim gewünschten Projekt auf Bearbeiten klicken.
3. Daten ändern.
4. Speichern.

Alternativ kann ein Projekt direkt über den Bearbeiten-Button in der Sidebar geöffnet werden.

### Projekt löschen

1. Projekt bearbeiten.
2. Button `Löschen` verwenden.
3. Sicherheitsabfrage bestätigen.

Beim Löschen werden zugehörige Aufgaben ebenfalls weich gelöscht und offene Timer beendet.

### Aufgabe anlegen

1. Button `Neue Aufgabe` klicken.
2. Titel eintragen.
3. Projekt auswählen.
4. Startdatum, Startzeit, Fälligkeitsdatum und Fälligkeitszeit setzen.
5. Optional Zuweisung, Status und Beschreibung eintragen.
6. Speichern.

Wird eine Aufgabe aus einer Projektansicht erstellt, ist das Projekt bereits vorausgewählt.

### Aufgabe bearbeiten

1. Aufgabe im Kalender oder in der Projektansicht anklicken.
2. Daten ändern.
3. Speichern.

### Aufgabe löschen

1. Aufgabe öffnen.
2. Button `Löschen` klicken.
3. Sicherheitsabfrage bestätigen.

### Timer nutzen

1. Aufgabe öffnen.
2. Timer starten.
3. Timer stoppen, wenn die Arbeit beendet ist.

Pro Nutzer kann nur ein Timer gleichzeitig aktiv sein.

---

## Projektstruktur

```text
.
├── api/
│   ├── api_header.php
│   ├── check_auth.php
│   ├── delete_project.php
│   ├── delete_task.php
│   ├── get_audit_logs.php
│   ├── get_projects.php
│   ├── get_tasks.php
│   ├── get_users.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── save_project.php
│   ├── save_task.php
│   └── time_action.php
│
├── css/
│   └── style.css
│
├── js/
│   ├── api.js
│   ├── app.js
│   └── calendar.js
│
├── logik/
│   ├── Auth.php
│   ├── Database.php
│   ├── Installer.php
│   ├── Logger.php
│   ├── ProjectManager.php
│   ├── TaskManager.php
│   └── TimeTracker.php
│
├── tests/
│   ├── test_auth.php
│   └── test_project.php
│
├── index.php
├── install.php
├── setup_db.php
├── config.sample.php
├── INSTALLATION.md
├── REFACTORING_NOTES.md
├── CRUD_EXTENSION_NOTES.md
├── verify_backend.php
└── verify_filtering.php
```

### Wichtige Dateien

| Datei | Aufgabe |
|---|---|
| `index.php` | Hauptanwendung und Loginansicht |
| `install.php` | grafischer First-Run-Installer |
| `setup_db.php` | Migrationen erneut ausführen |
| `config.sample.php` | Beispielkonfiguration |
| `config.php` | echte lokale Konfiguration, wird vom Installer erzeugt |
| `logik/Installer.php` | Installation, Tabellenanlage und Migrationen |
| `logik/Database.php` | zentrale PDO-Datenbankverbindung |
| `logik/Auth.php` | Login, Logout, Session und Userdaten |
| `logik/ProjectManager.php` | Projektlogik |
| `logik/TaskManager.php` | Aufgabenlogik |
| `logik/TimeTracker.php` | Zeitlogik und Timer |
| `logik/Logger.php` | Audit-Logging |
| `js/app.js` | Hauptlogik im Frontend |
| `js/api.js` | API-Kommunikation |
| `js/calendar.js` | Kalenderdarstellung |
| `css/style.css` | Layout und Design |

---

## Datenbanktabellen

Das System verwendet aktuell folgende Haupttabellen:

| Tabelle | Zweck |
|---|---|
| `users` | Benutzerkonten und Rollen |
| `projects` | Projekte |
| `tasks` | Aufgaben |
| `time_logs` | Zeitbuchungen und Timerdaten |
| `audit_logs` | Verlauf / Audit-Protokoll |

Die Tabellen werden vom Installer automatisch erstellt.

---

## API-Endpunkte

Die API liegt im Ordner `api/` und arbeitet überwiegend mit JSON.

| Endpunkt | Zweck |
|---|---|
| `api/login.php` | Anmeldung |
| `api/logout.php` | Abmeldung |
| `api/register.php` | Registrierung |
| `api/check_auth.php` | Loginstatus prüfen |
| `api/get_users.php` | Benutzer abrufen |
| `api/get_projects.php` | Projekte abrufen |
| `api/save_project.php` | Projekt anlegen oder bearbeiten |
| `api/delete_project.php` | Projekt weich löschen |
| `api/get_tasks.php` | Aufgaben abrufen |
| `api/save_task.php` | Aufgabe anlegen oder bearbeiten |
| `api/delete_task.php` | Aufgabe weich löschen |
| `api/time_action.php` | Timer starten/stoppen |
| `api/get_audit_logs.php` | Audit-Logs abrufen |

Gemeinsame API-Helfer liegen in:

```text
api/api_header.php
```

Darin enthalten sind unter anderem:

- JSON-Ausgabe
- Fehlerausgabe
- Loginprüfung
- JSON-Body-Verarbeitung
- optionale Integer-Parameter

---

## Statuswerte

### Aufgabenstatus

Intern verwendete Werte:

```text
new
in_progress
completed_success
completed_fail
deleted
```

Legacy-Wert:

```text
completed
```

Der alte Wert `completed` wird bei Migrationen nach `completed_success` überführt.

### Projektstatus

```text
active
completed
deleted
```

Gelöschte Projekte und Aufgaben werden im Normalbetrieb ausgefiltert.

---

## Sicherheitshinweise

### `config.php`

Die Datei `config.php` enthält echte Datenbankzugangsdaten und darf nicht öffentlich in ein Repository geladen werden.

Empfehlung für `.gitignore`:

```gitignore
config.php
*.log
.DS_Store
Thumbs.db
```

### Installer nach Installation schützen

Nach erfolgreicher Installation sollte `install.php` auf einem öffentlichen Server gelöscht oder serverseitig gesperrt werden.

Beispiel:

```text
install.php entfernen oder Zugriff per Serverkonfiguration blockieren
```

### Keine Standardzugänge

Das System legt keinen unsicheren Standardnutzer wie `admin / admin123` mehr an. Der erste Admin wird bewusst im Installer erstellt.

### Produktivbetrieb

Für öffentlichen Betrieb empfohlen:

- HTTPS verwenden
- starke Datenbankpasswörter verwenden
- Debug-Ausgaben deaktivieren
- PHP-Fehler nicht öffentlich anzeigen
- regelmäßige Backups durchführen
- Schreibrechte auf das notwendige Minimum begrenzen

---

## Entwicklung und Prüfung

### PHP-Syntax prüfen

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Unter Windows alternativ einzelne Dateien prüfen:

```bash
php -l index.php
php -l install.php
php -l setup_db.php
```

### JavaScript-Syntax prüfen

```bash
node --check js/api.js
node --check js/app.js
node --check js/calendar.js
```

### Backend-Verifikation

```text
verify_backend.php
verify_filtering.php
```

Diese Dateien dienen der einfachen technischen Prüfung einzelner Backendbereiche.

---

## Changelog

### Refactoring

- Datenbankschema, Backend und Frontend konsolidiert
- Aufgabenstatus vereinheitlicht
- `completed_at` ergänzt
- Migrationen verbessert
- Sessionhandling stabilisiert
- API-Helfer zentralisiert
- API-Fehlerausgaben verbessert
- Frontend-Ausgaben gegen ungültige Werte robuster gemacht
- Timerlogik bereinigt

### First-Run-Installer

- `install.php` ergänzt
- Datenbankdaten über Oberfläche eingebbar
- automatische Datenbankanlage, sofern Rechte vorhanden
- automatische Tabellenerstellung
- automatische Migrationen
- automatische `config.php`-Erstellung
- bewusste Anlage des ersten Admin-Nutzers

### CRUD-Erweiterung

- Projekt anlegen, bearbeiten und löschen
- Aufgabe anlegen, bearbeiten und löschen
- Projektmodal erweitert
- Aufgabenmodal erweitert
- Projektübersicht erweitert
- Sidebar-Aktionen ergänzt
- Soft-Delete für Projekte und Aufgaben
- offene Timer werden bei Löschung beendet

### UI-Überarbeitung

- Modale kompakter gestaltet
- Topbar reduziert
- Anwendungstitel kompakter gemacht
- Projektkarten verbessert
- Aufgabenlisten verbessert
- große `datetime-local`-Auswahl durch kompakte getrennte Datums- und Uhrzeitfelder ersetzt

---
