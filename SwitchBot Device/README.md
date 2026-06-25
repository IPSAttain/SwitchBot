# SwitchBot Device
Beschreibung des Moduls.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

*

### 2. Voraussetzungen

- IP-Symcon ab Version 8.0

### 3. Software-Installation

* Über den Module Store das 'SwitchBot Device'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'SwitchBot Device'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
         |
         |

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name          | Typ     | Beschreibung
------------- | ------- | ------------
lockState     | Boolean | Schloss-Status (true = verriegelt, false = entriegelt). Wird für **Lock**, **Smart Lock Pro** und **Smart Lock Ultra** angelegt. Bei Lock/Smart Lock Pro ist die Variable **schaltbar** (Boolean-Aktion `lock`/`unlock`). Beim **Smart Lock Ultra** dient sie als **reine Statusanzeige** – die Bedienung erfolgt dort über `lockControl`.
lockControl   | Integer | **Nur** **Smart Lock Ultra**: 0 = Tür öffnen (Cloud-Command `unlock`, zieht die Falle → Tür geht auf), 1 = abschließen (`lock`, Riegel voll ausfahren), 2 = aufschließen (`deadbolt`, Riegel zurück — Falle hält die Tür zu). Die Zuordnung spiegelt das tatsächlich beobachtete Verhalten eines SwitchBot Lock Ultra mit Night-Latch und weicht bewusst von der HA-Dokumentation ab, die `deadbolt` als „Open Door" beschreibt. Die Variable wird **nicht** aus Webhooks aktualisiert, sondern behält den zuletzt gesendeten Befehl, weil die Cloud nach `unlock` irrtümlich einen `LOCKED`-Status sendet. Den Live-Status zeigt `lockState`. Nutzt eine **Darstellung** (Aufzählung, `VARIABLE_PRESENTATION_ENUMERATION`). Status `jammed` erzeugt lediglich eine Log-Warnung.

#### Profile

Dieses Modul verwendet keine eigenen Variablenprofile – für `lockControl` kommt eine Darstellung vom Typ „Aufzählung" (ab Symcon 8.0) zum Einsatz.

### 6. WebFront

Die Funktionalität, die das Modul im WebFront bietet.

### 7. PHP-Befehlsreferenz

`boolean SWB_BeispielFunktion(integer $InstanzID);`
Erklärung der Funktion.

Beispiel:
`SWB_BeispielFunktion(12345);`