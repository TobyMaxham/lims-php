Dokumentation der LiMS Schnittstelle
====================================

> Kopie der offiziellen Dokumentation, Stand: 26.10.2026. Quelle: PDF-Ausdruck der früheren GitLab-Seite
> `pub/lims_connect`, verlinkt auf https://wissensnetz.dosb.de/schnellstart-lims-anbindung.
> Maßgeblich ist immer die aktuelle Version im DOSB-Wissensnetz.
>
> Die im Text enthaltenen Links auf `gitlab.ghostthinker.de` stammen aus dem Original und sind nicht mehr erreichbar.

## Allgemeine Informationen

Die LiMS Schnittstelle des DOSB Wissensnetz ermöglicht Ihnen unter anderem Lizenzdaten aus Ihrem Bestandssystem in das LiMS zu importieren. Außedem können Sie

* neue Lizenzen anfordern,
* vorhandene Lizenzen verlängern und korrigieren,
* vorhandene Lizenz aus LiMs laden
* PDF Lizenzvordrucke laden

Alle Anfragen müssen im produktiv Betrieb an die URL *https://wissensnetz.dosb.de/api/lims* gestellt werden. Bitte beachten Sie, dass es sich hier um produktive Daten handelt. Darum testen sie Ihre Implementierung am besten zunächst auf unserer Testumgebung. Diese kann analog zum Live-System über die URL *https://demo-dosb.wissensnetz.eu/api/lims* adressiert werden.

In der aktuellen Version 1.0 erfolgen die Anfragen via **REST**. *SOAP*, *WSDL* und *XML-RPC* können unter Umständen nachträglich bereitgestellt werden.

## Testumgebung und API Zugriff

In der Demo-Umgebung haben Sie Zugriff auf alle Lizenzdaten Ihrer Organisation und können diese verändern, ohne dass die produktiven Datenbestände davon betroffen sind. Wenn sie Zugriff auf die API und auf unsere Demo-Umgebung (Sandbox) benötigen, kontaktieren sie uns einfach über die Supportformular des Wissensnetzes (Benutzerkonto im Wissensnetzes erforderlich) oder alternativ per E-Mail an support@bildungsnetz.dosb.de.


## Request Header und Authentifizierung

Die LiMS Schnittstelle verwendet eine HTTP-Authentifizierung, jede Anfrage muss mit einer Kombination aus "benutzername:passwort" ausgestattet sein. Diese Kombination muss base64 encodiert sein. Sessions/Cookies werden nicht unterstützt.
Bsp.

----

**WICHTIGER HINWEIS ZUM BENUTZERNAME**
Bitte beachte Sie, dass der Benutzername hier nicht gleich Ihrer E-Mail-Adresse ist. In den meisten Fällen handelt es sich um die Zeichenfolge Ihrer E-Mail-Adresse bis zum "@" am besten Sie Fragen jedoch noch einmal beim Bildungs-Team nach, wenn Sie Probleme haben sollten sich zu authentifizieren.

----


```    
    Authorization: Basic RXMgaGVpw590IE51a3VsYXIh
```
Zudem können Sie angeben, ob json oder xml geladen werden soll. */*
```
    Accept application/json
    Accept application/xml
    
```
Falls die Authentifizierung fehlschlägt, werden folgende Status Codes zurückgeliefert:
```
  400 Bad Request : Benutzername/Passwort sind falsch
  403 Forbidden : Ihre Benutzerdaten sind korrekt, Sie wurden jedoch nicht für den API Zugriff freigeschaltet
```

Bitte verwenden Sie für die Anfragen immer **UTF-8** als Zeichenkodierung.

## Dienste ##

### Lizenz anfordern ###
----
Aufruf zum Erstellen oder Aktualisieren einer Lizenz.

**URL**

/request

**Method:**

`POST`

**URL Params**

Keine

**Data Params für Neue Lizenzen**

**Required:**

     Alle nicht mit * markierte Felder aus der Felddefinitionsliste

**Optional:**

 	 Alle mit * markierte Felder aus der Felddefinitionsliste
 	 Das Feld valid_until wird im Status "created" und "requested" automatisch auf den maximalmöglichen Wert gesetzt, falls das Feld leer übermittelt wird. 
     Das Feld first_issue_date kann gesetzt werden, falls es sich um Bestandsdaten handelt. 
     Ansonsten wird hierfür der selbe Wert wie für issue_date verwendet.
     Die Felder training_activity_1 und training_activity_2, falls sie angegeben sind, überschreiben z.B. beim Aktualisieren die Fortbildungsmaßnahmen.
     Es werden keine Haken "Berechtigt zur Verlängerung", wie bspw. bei der Weboberfläche, berücksichtigt.

**Invalid:**

    Folgende Felder können nicht gesetzt werden, wenn eine neue Lizenz erstellt werden soll

    `license_number_dosb` oder `lid`

### Lizenz verlängern ###
----
Werden geänderte Lizenzdaten übermittelt, erfolgt automatisch eine Verlängerung der Lizenz.

**Invalid:**

     Folgende Felder können nicht gesetzt werden, wenn eine bestehende Lizenz verlängert werden soll

     `first_issue_date`
     `organisation_id`
     `training_course_id`

     Fehlermeldung: "Invalid input for training_course_id: xxx (Not allowed field for this operation)"



### Lizenz korrigieren ###
----
Werden ausschließlich geänderte Daten des Lizenzhalters übermittelt, erfolgt automatisch eine Korrektur der Lizenz.
Werden geänderte Lizenzdaten, übermittelt erfolgt automatisch eine Verlängerung der Lizenz (s. oben)


**Data Params für vorhandene Lizenzen**

**Required:**

     Eine der beiden IDs muss angegeben werden

     `license_number_dosb` oder `lid`

**Optional:**

 	 Alle Felder aus der Felddefinitionsliste, bzw. nur die Felder, die geändert werden sollen.

**Invalid:**

     Folgende Felder können nicht gesetzt werden, wenn eine bestehende Lizenz verändert werden soll

     `organisation_id`
     `first_issue_date` Ausnahme: Das Erstausstellungsdatum kann bei Bestandsdaten über "Bearbeiten" und "Korrigieren" geändert werden.


### Wertetabelle ###

|  Einzellizenz | Ausstellungsdatum | | | Gültigkeitsdatum | | |
|---|---|---|---|---|---|---|
|                                       | min|max|Vorschlag|min|max|Vorschlag|
|Standard (Neu)                         | Beliebig DP=aktuelles Jahr - 50 | +30 Tage | -     | Ausstellungsdatum | Ausstellungsdatum + Gültigkeitsdauer - 1d | Maximal gültiger Wert = Ausstellungsdatum + Gültigkeitsdauer - 1d |
|Bestandsdaten (Neu)                    | 13.12.1901 | +30 Tage | - | Ausstellungsdatum |19.01.2038 | Ausstellungsdatum + Gültigkeitsdauer - 1d |
|Standard / Bestandsdaten (Verlängerung)| Beliebig DP=aktuelles Jahr - 50 | +30 Tage | heute | Ausstellungsdatum | Ausstellungsdatum + Gültigkeitsdauer + 92d | Ausstellungsdatum + Gültigkeitsdauer - 1d |

*Quartalskulanz letzter Tag im Quartal

### Beispiele ###

Ausgangslage:

Eine C-Trainer-Lizenz (1. Lizenzstufe) ist gültig vom 28.03.2016 bis 27.03.2020.

Die 1. Lizenzstufe hat einen Gültigkeitszeitraum von 4 Jahren.



Beispiel 1: Verlängerung am 15.08.2020:

neue Gültigkeit laut Vorschlag: 14.08.2024.

Beispiel 2: Verlängerung am 08.02.2023

neue Gültigkeit laut Vorschlag: 07.02.2027.


#### Statuscodes ####

**Success Response:**

Bei Erfolg werden sowohl dei interne LiMS-ID (lid) als auch die DOSB-Lizenznummer (license_number_dosb) zurückgegeben

**Code:** 200 <br />
**Content:** `{ lid : 12, license_number_dosb: AB-CD-E-xxxxxxxxxx }`

**Error Response:**


**Code:** 401 UNAUTHORIZED <br />
**Content:** `[ Invalid login credentials ]`

**Code:** 403 ACCESS DENIED <br />
**Content:** `[ Permission denied for organisation %og_nid ]`

**Code:** 406 Not Acceptable <br />
**Content:** `[ Missing  %fieldname ]`

**Code:** 406 Not Acceptable <br />
**Content:** `[ License number invalid or license not found ]`

**Code:** 406 Not Acceptable <br />
**Content:** `[ Training course id is not valid in this organisation ]`

**Code:** 422 UNPROCESSABLE ENTRY  <br />
**Content:** `[ Invalid input for %key: %val ]`


**Notes:**

### Lizenzenvordruck laden mit DOSB Lizenznummer ###
----
Aufruf eines Lizenzvordrucks im DinA4 Format andhand der DOSB Lizenznummer

**URL**

/download

**Method:**

`POST`

**URL Params**

`license_number_dosb`


**Data Params für vorhandene Lizenzen**

**Required:**
keine

**Optional:**
`format`, `file_url_only`<br /><br />
`format` => `dina4`, `card` oder `signet`<br />
`file_url_only`=> `1`

    Bitte beachten Sie, dass DinA4 und Card als PDF zurückgegeben werden und Signet als JPG.

## Aggregierten Lizenzenvordruck laden  ##

Aufruf mehrerer Lizenzvordrucker im DinA4 Format anhand meherer DOSB Lizenznummern

**URL**

/download_multiple

**Method:**

`POST`

**URL Params**

keine


**Data Params für vorhandene Lizenzen**

**Required:**
`license_numbers_dosb` kommaseparierte Liste mit DOSB Lizenznummern

**Optional:**
`format`, `file_url_only`<br /><br />
`format` => `dina4` oder `card`<br />
`file_url_only`=> `1`

    Bitte beachten Sie, dass DinA4 und Card als PDF zurückgegeben werden.



#### Statuscodes ####

**Success Response:**

Bei Erfolg wird die Lizenz im angegeben Format zurückgegeben.

**Code:** 200 <br />
**Content-Type:** application/json oder application/xml <br />
**Content:** Lizenzen im angebenen Format

**Error Response:**

**Code:** 401 UNAUTHORIZED <br />
**Content:** `[ Invalid login credentials" ]`

**Code:** 403 ACCESS DENIED <br />
**Content:** `["Access denied for user %username" ]`

**Code:** 404 NOT FOUND <br />
**Content:** `["License not found" ]`

**Notes:**
Ein Beispiel dazu befindet sich hier: https://gitlab.ghostthinker.de/pub/lims_connect/blob/master/php_sample_client/sample_download_license.php


### Abruf von Lizenzen
----
Abruf von Lizenzen anhand der optionalen Parameter.

**URL**

/lookup

**Method:**

`POST`

**URL Params**
keine

**Data Params für Lizenzen-Abruf**

**Required:**
keine

**Optional:** `organisation_id` , `offset` , `limit` , `validation_status` , `is_obscured` , `ids_only`, `license_number_organisation`

`offset` = Startwert der Abfrage

`limit` = Anzahl der DOSB-Lizenzen für die Abfrage (max. 1000), bei DOSB-Lizenznummern (Lizenz-IDs) max. 20000

`validation_status => 0` = Abgelaufene Lizenzen <br />
`validation_status => 1` = Gültige Lizenzen

Mögliche Werte für `is_obscured` => <br />
`ja, Ja, j, 1, TRUE, true` = liefert nur anonymisierte DOSB-Lizenzen zurück <br />
`nein, Nein, n, 0, FALSE, false` = liefert nur vollständige DOSB-Lizenzen zurück <br />

`ids_only => TRUE` = liefert eine Liste von DOSB-Lizenznummern (Lizenz-IDs)

`license_number_organisation` = gibt nur Lizenzen mit der Lizenznummer (Verband) zurück

**Success Response:**

Bei Erfolg wird die Anzahl der enthaltenen Ergebnisse (size), der übermittelte Offset-Wert (offset), die Größe des Abfrage-Ergebnisses (total)
und die Lizenzen oder nur die Lizenz-IDs zurückgegeben.
Die Felder bzw Bezeichnung sind die selben wie beim Anfordern einer Lizenz.

**Code:** 200 <br />
**Content-Type:** application/json <br />
**Content:** size, offset, total mit Lizenz-IDs oder Lizenzen <br />

**Error Response:**

**Code:** 401 UNAUTHORIZED <br />
**Content:** `[ Invalid login credentials ]`

**Code:** 403 ACCESS DENIED <br />
**Content:** `["Access denied for user %username ]`


### Abruf von Organisationen

Abruf von Organisationen.

**URL**

/lookup_organisations

**Method:**

`POST`

**URL Params**
keine

**Data Params für Organisations-Abruf**

**Required:**
keine

**Optional:** `offset` , `limit` , `federal_state` , `organisation_parent_id`

`offset` = Startwert der Abfrage
`limit` = Anzahl der Organisationen für die Abfrage (max. 100)

`federal_state` = Bundesland als String

    Baden-Württemberg
    Bayern
    Berlin
    Brandenburg
    Bremen
    Hamburg
    Hessen
    Mecklenburg-Vorpommern
    Niedersachsen
    Nordrhein-Westfalen
    Rheinland-Pfalz
    Saarland
    Sachsen
    Sachsen-Anhalt
    Schleswig-Holstein
    Thüringen
    Bundesverband

`organisation_parent_id` = nur die direkten Untergliederungen (erste Ebene!)

`organisation_category` = Verbändegruppe (wir nutzen hier die Abkürzungen vor den | also z.B. SV)

    SV|Spitzenverbände
    LSB|Landessportbünde/-verbände
    VmbA|Verbände mit besonderen Aufgaben
    UG|Untergliederung


### Abruf von DOSB-Ausbildungsgängen

Abruf von DOSB-Ausbildungsgängen.

**URL**

/lookup_training_courses

**Method:**

`POST`

**URL Params**
keine

**Data Params für Organisations-Abruf**

**Required:**
keine

**Optional:** `offset` , `limit` , `federal_state` , `assigned_organisation_id` , `level` , `training_course` , `sport`

`offset` = Startwert der Abfrage
`limit` = Anzahl der Ausbildungsgänge für die Abfrage (max. 100)

`federal_state` = Bundesland als String

    Baden-Württemberg
    Bayern
    Berlin
    Brandenburg
    Bremen
    Hamburg
    Hessen
    Mecklenburg-Vorpommern
    Niedersachsen
    Nordrhein-Westfalen
    Rheinland-Pfalz
    Saarland
    Sachsen
    Sachsen-Anhalt
    Schleswig-Holstein
    Thüringen
    Bundesverband

`assigned_organisation_id` = nur die direkte freigebene Organisation (erste Ebene!)

`level` = Lizenzstufe

`training_course` = Ausbildungsgang als String

    T-A/B
    T-A/L
    T-B/B
    T-B/L
    T-C/B
    T-C/L
    ÜL-B
    ÜL-B/P
    ÜL-B/R
    ÜL-C
    VM-B
    VM-C

`sport` = Sportart als String (z.B. Basketball, Ju-Jutsu oder Fußball usw.)

**Rückgabewerte**

| Feld | Beschreibung | Typ |
|---|---|---|
| id | Ausbildungsgang Id | string |
| title | Bezeichnung | string |
| level | Lizenzstufe | int |
| course | Ausbildungsgang | string |
| sport | Sportart | string |
| profile | Profil | string |
| discipline | Disziplin | string |
| focus | Schwerpunkt | string |
| validity_period | Gültigkeitsdauer (Jahre) | int |


### Statistiken von DOSB-Ausbildungsgängen

Statistiken von DOSB-Ausbildungsgängen.

**URL**

/stats

**Method:**

`POST`

**URL Params**
keine

**Data Params für Statistiken-Abruf**

**Required:** `start_date` , `end_date`

`start_date` = Startdatum der Abfrage (von)
`end_date` = Enddatum der Abfrage (bis)

**Optional:** `birthdate` , `postal` , `gender` , `training_course_id` , `training_course` , `level` , `sport` , `profile` , `focus` , `discipline` , `federal_state` , `organisation_id`

`birthdate` = Integer 1-5 für die Altersgruppe (Relation zum Startdatum)

    bis 18 = 1
    19-26 = 2
    27-40 = 3
    41-60 = 4
    über 60 = 5

`postal` = Postleitzahl-Raum, erste drei Stellen (max. 3)

`gender` = Buchstabe für Geschlecht

    männlich = m
    weiblich = w
    unbekannt = u

`training_course_id` = nur der DOSB-Ausbildungsgang (via Id)
Dieser Filter sollte allein genutzt werden, mit weiteren Detail-Filtern (siehe nächste) in nicht korrekter Kombination könnten die Rückgabewerte bei 0 stehen.

`training_course` = Ausbildungsgang als String

    T-A/B
    T-A/L
    T-B/B
    T-B/L
    T-C/B
    T-C/L
    ÜL-B
    ÜL-B/P
    ÜL-B/R
    ÜL-C
    VM-B
    VM-C
    ...

`level` = Lizenzstufe (Integer)

`sport` = Sportart als String (z.B. Basketball, Ju-Jutsu oder Fußball usw.)

`profile` = Profil als String

`focus` = Fokus als String (z.B. Rennsport usw.)

`discipline` = Disziplin als String (z.B. Rennsport usw.)

`federal_state` = Bundesland als String

    Baden-Württemberg
    Bayern
    Berlin
    Brandenburg
    Bremen
    Hamburg
    Hessen
    Mecklenburg-Vorpommern
    Niedersachsen
    Nordrhein-Westfalen
    Rheinland-Pfalz
    Saarland
    Sachsen
    Sachsen-Anhalt
    Schleswig-Holstein
    Thüringen
    Bundesverband

`organisation_id` = nur die direkte ausstellende Organisation

**Success Response:**

**Code:** 200 <br />
**Content-Type:** application/json <br />
**Content:** ...

Beispiel Rückgabewerte (als json)

```
{'T-B/L': {'new': {'m': 30, 'w': '30', 'u':10 }, 'extend': {'m': 30, 'w': '30', 'u':10 }, 'valid': {'m': 30, 'w': '30', 'u':10 }, ...}
```


### Lizenz laden mit DOSB Lizenznummer ###
----
Aufruf zu Laden aller Felder einer Lizenz anhand der DOSB Lizenznummer.

**URL**

/load

**Method:**

`POST`

**URL Params**

`license_number_dosb`

Beispiel: https://demo-dosb.wissensnetz.eu/api/lims/load/GT-JL-C-1234567

**Success Response:**

Bei Erfolg werden die Felder der Lizenz zurückgegeben. Die Felder bzw Bezeichnung sind die selben wie beim anfordern.

**Code:** 200 <br />
**Content-Type:** application/json <br />
**Content:** Felder der Lizenz

**Error Response:**

**Code:** 401 UNAUTHORIZED <br />
**Content:** `[ Invalid login credentials ]`

**Code:** 403 ACCESS DENIED <br />
**Content:** `["Access denied for user %username ]`


## Sequenzdiagramm

![alt text](doc/sequence_diagram.jpg "Sequenz Diagramm")

## Felddefinitionen
Diese Felder können beim Erstellen von Lizenzen ausgefüllt werden.

Bitte beachten Sie, dass die Felder vom Typ "Date" als Unixzeit, also dem POSIX-Standard entsprechend (z.B. 497274993), übergeben werden müssen. Um auf Nummer sicher zu gehen, dass es zu keinem Winterzeitproblem kommt, sollten Sie eine Uhrzeit um 12:00Uhr Mittags wählen und nicht 00:00 Uhr.

| Feld                        | Beschreibung                                                                                                                  | Typ    | input / min, max                                                                                                              |
|-----------------------------|-------------------------------------------------------------------------------------------------------------------------------|--------|-------------------------------------------------------------------------------------------------------------------------------|
| firstname                   | Vorname                                                                                                                       | string | 1,255                                                                                                                         |
| lastname                    | Nachname                                                                                                                      | string | 1,255                                                                                                                         |
| academic_title*             | Titel                                                                                                                         | string | 1,255                                                                                                                         |
| birthdate                   | Geburtsdatum                                                                                                                  | date   | -, NOW                                                                                                                        |
| gender                      | Geschlecht                                                                                                                    | string | w,m,u                                                                                                                         |
| street                      | Straße                                                                                                                        | string | 1,255                                                                                                                         |
| postal                      | PLZ                                                                                                                           | string | 5,8 - z.B. {12345}, {AB}-{12345} z.B. AT-1234 (Österreich) oder nach ISO-Norm-3166-1-alpha-2 z.B. GB-EC1A 1BB (Great Britain) |
| city                        | Ort                                                                                                                           | string | 1,255                                                                                                                         |
| mail*                       | E-Mail                                                                                                                        | string | 1,255                                                                                                                         |
| phone*                      | Telefon                                                                                                                       | string | 1,255                                                                                                                         |
| issue_date                  | Ausstellungsdatum                                                                                                             | date   | s. Wertetabelle                                                                                                               |
| first_issue_date*           | Erstausstellungsdatum                                                                                                         | date   | -, issue_date                                                                                                                 |
| training_course_id          | Ausbildungsgang                                                                                                               | int    |                                                                                                                               |
| issue_place                 | Ausstellungsort                                                                                                               | string | 1,255                                                                                                                         |
| valid_until*                | Gültig bis                                                                                                                    | date   | s. Wertetabelle                                                                                                               |
| honor_code                  | Ehrencodex                                                                                                                    | int    | 1,0                                                                                                                           |
| honor_code_date*            | Ehrencodex Datum                                                                                                              | date   | -, NOW                                                                                                                        |
| first_aid                   | Erste-Hilfe-Ausbildung                                                                                                        | int    | 1,0                                                                                                                           |
| first_aid_date*             | Erste-Hilfe-Ausbildung Datum                                                                                                  | date   | -, NOW                                                                                                                        |
| license_number_organisation | Lizenznummer(Verband)                                                                                                         | string | 1,255                                                                                                                         |
| prequalification_type*      | Vorqualifikationsart                                                                                                          | string | 1,255 - z.B. DOSB, Anderer LSB, Bundeswehr, Bundespolizei, International, Sonstiges                                           |
| prequalification_number**   | DOSB-Lizenznummer Vorqualifikation                                                                                            | string | 1,255                                                                                                                         |
| organisation_id             | Auszustellender Verband                                                                                                       | int    | 0,-                                                                                                                           |
| lid                         | LiMS ID                                                                                                                       | int    | 0,-                                                                                                                           |
| license_number_dosb         | DOSB-Lizenznummer                                                                                                             | string | 1,255                                                                                                                         |
| custom_1*                   | Zusatzfeld 1                                                                                                                  | string | 0,1024                                                                                                                        |
| custom_2*                   | Zusatzfeld 2                                                                                                                  | string | 0,1024                                                                                                                        |
| additional_info*            | Ergänzungseintrag (wird auf der Lizenz ausgegeben) - Wichtig: Auf dem Lizenz-Vordruck wird dieser Wert auf 75 Zeichen gekürzt | string | 0,1024                                                                                                                        |
| training_activity_1*        | Fortbildungsmaßnahme 1                                                                                                        | string | 0,127                                                                                                                         |
| training_activity_2*        | Fortbildungsmaßnahme 2                                                                                                        | string | 0,127                                                                                                                         |

\* Felder mit sind optional.

** Muss eine gültige DOSB-Lizenznummer sein

### Lizenzen löschen ###
----
Aufruf zum Löschen einer Lizenz anhand der DOSB Lizenznummer.

**URL**

/delete

**Method:**

`POST`

**URL Params**

`license_number_dosb`


**Data Params für vorhandene Lizenzen**

**Required:**
keine

**Optional:**
keine

#### Statuscodes ####

**Success Response:**

Bei Erfolg werden die ID-Felder (`lid`, `license_number_dosb`) der Lizenz zurückgegeben.

**Code:** 200 <br />
**Content-Type:** application/json <br />
**Content:** ID-Felder

**Error Response:**

**Code:** 401 UNAUTHORIZED <br />
**Content:** `[ Invalid login credentials" ]`

**Code:** 403 ACCESS DENIED <br />
**Content:** `["Access denied for user %username" ]`

**Code:** 404 NOT FOUND <br />
**Content:** `["License not found" ]`


## Umzugsanfrage stellen  ##

Umzugsanfrage stellen, um eine Lizenz in einen anderen Zielverband zu verschieben.
Hinweis: Die Lizenz wird bei ausreichender Berechtigung direkt umgezogen.
(Nur möglich unter dem selben Dachverband)

**URL**

/migration_request

**Method:**

`POST`

**URL Params**

keine


**Data Params für vorhandene Lizenzen**

**Required:** `license_number_dosb`, `organisation_id`

`license_number_dosb` = DOSB Lizenznummer

`organisation_id`  = ID des Zielverbandes

#### Statuscodes ####

**Success Response:**

Bei Erfolg wird eine entsprechende Message zurückgegeben:
"Your request has been submitted." oder "The license has been migrated.", wenn die Berechtigung für direkten Umzug vorliegen.

**Code:** 200 <br/>
**Content-Type:** application/json <br/>
**Content:** migration_status<br/>

**Error Response:**

**Code:** 401 UNAUTHORIZED <br/>
**Content:** `[Invalid login credentials]`

**Code:** 403 ACCESS DENIED <br/>
**Content:** `[Access has been denied.]`

**Code:** 403 ACCESS DENIED <br/>
**Content:** `[Migration request already open for this license.]`

**Code:** 403 ACCESS DENIED <br/>
**Content:** `[License [license_number_dosb] or target organisation [organisation_id] missing.]`


## Umzugsanfragen abholen ##

Umzugsanfragen für einen Verband abholen.

**URL**

/migration_get

**Method:**

`POST`

**URL Params**

`organisation_id`


**Data Params für vorhandene Lizenzen**

**Required:**
keine

**Optional:**
`offset` , `limit`

`offset` = Startwert der Abfrage  (default: 0)

`limit` = Anzahl der Umzugsanfragen für die Abfrage (default: 100)


#### Statuscodes ####

**Success Response:**

Bei Erfolg wird die Anzahl der enthaltenen Ergebnisse (size), der übermittelte Offset-Wert (offset), die Größe des Abfrage-Ergebnisses (total)
und die Migrationsanfragen (migrations) zurückgegeben.
Die Felder bzw Bezeichnung sind die selben wie beim Anfordern einer Lizenz.

**Code:** 200 <br/>
**Content-Type:** application/json <br/>
**Content:** size, offset, total, migrations<br/>

**Error Response:**

**Code:** 401 UNAUTHORIZED <br/>
**Content:** `[Invalid login credentials]`

**Code:** 403 ACCESS DENIED <br/>
**Content:** `[Access has been denied.]`

**Code:** 404 NOT FOUND  <br/>
**Content:** `[No migration requests available!]`



## Umzugsanfrage ablehnen  ##

Umzugsanfrage ablehnen, um den Umzug einer Lizenz zu widersprechen.

**URL**

/migration_decline

**Method:**

`POST`

**URL Params**

`migration_id` (Lizenz-Id)


#### Statuscodes ####

**Success Response:**

Bei Erfolg wird eine entsprechende Message (migration_status) zurückgegeben:
"Migrate request has been declined."

**Code:** 200 <br/>
**Content-Type:** application/json <br/>
**Content:** migration_status<br/>

**Error Response:**

**Code:** 401 UNAUTHORIZED <br/>
**Content:** `[Invalid login credentials]`

**Code:** 403 ACCESS DENIED <br/>
**Content:** `[Access has been denied.]`

**Code:** 404 NOT FOUND  <br/>
**Content:** `[Migrate request not found!]`



## Umzugsanfrage annehmen  ##

Umzugsanfrage annehmen, um eine Lizenz in einen Zielverband zu verschieben.

**URL**

/migration_accept

**Method:**

`POST`

**URL Params**

`migration_id` (Lizenz-Id)

**Success Response:**

Bei Erfolg wird eine entsprechende Message (migration_status) zurückgegeben: <br />
"The license has been migrated successfully."

#### Statuscodes ####


**Code:** 200 <br/>
**Content-Type:** application/json <br/>
**Content:** migration_status<br/>

**Error Response:**

**Code:** 401 UNAUTHORIZED <br/>
**Content:** `[Invalid login credentials]`

**Code:** 403 ACCESS DENIED <br/>
**Content:** `[Access has been denied.]`

**Code:** 404 NOT FOUND  <br/>
**Content:** `[Migrate request not found!]`


## FAQ

1. Frage: Wenn die Daten per Schnittstelle übertragen werden, wie gelangen dann die erstellten Lizenz-pdf-Dateien wieder zum absendenden Verband zurück (Mail, welche Mailadresse)?
* Antwort: Die Übermittlung der Daten erfolgt pro Lizenz und als Antwort des LiMS erhält dann der Verband direkt die PDF (siehe technische Dokumentation).
2. Frage: Brauche ich als LSB oder SV der für seine LFV Lizenzen ausstellt ein zusätzliches Feld für den Verband der Lizenz?
* Antwort:  Sie sollten in Ihrem System auch die ID des auszustellender Verbandes (=organisation_id, siehe technische Dokumentation) hinterlegen, wenn Sie nicht nur für sich Lizenzn anfordern.  Wenn Ihr Verband _nur_ seine eigenen Lizenzen pflegt reicht die Definition eines fixen Wertes in der Schnittstelle.
3. Frage: D.h. ein Zusatzfeld für DOSB-Lizenznummer und eines für ausstellenden Verband? Ist das auch so aus der Schnittstellenbeschreibung herauszulesen?
* Antwort: Im Grunde genommen ja - nämlich, wenn die MOs einen Abgleich ihrer eigenen Felder und der Liste Felddefinitionen in der technischen Dokumentation machen.
4. Frage: Wie genau gelange ich zur Testumgebung? Eingeloggt bin ich derzeit unter https://demo-dosb.wissensnetz.eu. Kann ich hier mit meinen Testdaten arbeiten?
* Antwort: Ganz genau. Es handelt sich bei demo-dosb.wissensnetz.eu bereits um die Testumgebung.
5. Frage: Können diese nach Abschluss der Testphase wieder entfernt werden?
* Antwort: Richtig. Das Test/Demosystem wird von zur Zeit zur Zeit wieder zurückgesetzt und damit die Beispieldaten automatisch weggeworfen.
6. Frage: Beim Anlegen einer neuen Lizenz wird unter Lizenzdaten der „DOSB-Lizenz Ausbildungsgang“ erfasst. Werden die Ausbildungsgänge für jeden ausbildenden Verband, entsprechend der beim DOSB bestätigten Ausbildungskonzeptionen, vorgegeben oder arbeiten wir Ihnen diese zu?
* Antwort: Die DOSB-Lizenz Ausbildungsgänge werden initial vom DOSB für die jeweilige Mitgliedsorganisation festgelegt. Spitzenverbände und natürlich auch Sie als LSB können dann für Ihre Untergliederung wiederum die Auswahl bei Bedarf weiter begrenzen. Dies geschiehet direkt beim Bearbeiten der jeweiligen Organisation im Wissensnetz.
7. Frage: Werden Falscheingaben (unplausible Zeiträume) bei der Gültigkeit der Lizenz vom System gefiltert
* Antwort: Alle Daten werden wie in Spalte "input / min, max" unter https://gitlab.ghostthinker.de/gt/lims_connect#felddefinitionen vermerkt validiert. Dies betrifft insbesondere wichtige Zeiträume, wie Gültig bis.
8. Frage: Wir möchten langfristig gesehen ausschließlich mit den DOSB-Lizenznummern arbeiten. Ist es möglich, Ihnen ein Script mit allen aktuell gültigen Lizenzen zur Verfügung zu stellen, um diese im LIMS mit einer DOSB-Lizenznummer auszustatten, die dann zurückgeschickt und automatisch im eigenen System hinterlegt werden?
* Antwort: Dies können Sie sehr gerne über einen initialen Bestandsimport (hier ist das Erstausstellungsdatum = first_issue_date mitanzugeben) vornehmen. Dann erhalten Sie zu jeder Lizenz automatisch eine DOSB-Lizenznummer.
9. Frage: 1.) Gibt es Ihrerseits eine Schnittstelle, über die wir von unserem System aus auf die Druckfunktion einer Lizenz zugreifen können?
* Antwort: Genau diese Funktion bietet der zweite Aufruf "download" der Schnittstelle, siehe  https://gitlab.ghostthinker.de/gt/lims_connect#lizenzenvordruck-laden-mit-dosb-lizenznummer. Sie erhalten als Rückmeldung direkt die PDF als Datei.
10. Frage: Wie ist ein initialer Import bestimmter Lizenzen aus unserem System möglich wäre, um für alle eine DOSB-Lizenznummer zu generieren. Kann ich hierfür einfach ein PHP-Script auf unserem Server erstellen, welches alle Lizenzen einmal aus der Datenbank aufruft und an das Wissensnetz über die bekannte Schnittstelle sendet? Oder ist hierfür eine andere Importfunktion vorgesehen?
* Antwort: Das können Sie direkt über den Aufuf "request" der Schnittstelle machen. Für den Bestandsdatenimport dann bitte das Erstausstellungsdatum (=first_issue_date) angeben.
11. Frage: Welches Datum ist bei der Neuanlage einer Lizenz von Bedeutung?
* Antwort: Das Feld Ausstellungsdatum = issue_date.
12. Frage: Wieso erscheint das Feld Erstausstellungsdatum nicht in der Auflistung der Lizenzdaten?
* Antwort: Bisher wurde der Wert dort nicht benötigt, auch weil er ja nicht geändert werden kann.
13. Frage: Wieso muss das issue_date (welches bei Neueingabe den gleichen Wert hat wie das first_issue_date) noch mit übergeben werden?
* Antwort: Bei einer Neueingabe (also keinem Bestandsimport) darf nur das issue_date angegeben werden, jedoch nicht first_issue_date.
14. Frage: Welche Schritte sind nun im Anschluss nötig, um im realen System zu arbeiten? Muss lediglich der Link der Schnittstelle angepasst werden?
* Antwort: Die URL des Host / Service muss vom Demo-System auf das Live-System umgestellt werden.
* Hinweis: Bitte überprüfen Sie im Zuge dessen auch die in Ihrem System hinterlegten IDs für DOSB-Lizenz Ausbildungsgänge und Verband inkl. Untergliederungen. In seltenen Fällen kann es zu unterschiedlichen Werten auf Live und Demo-System kommen.
15. Frage: Wie kann ich die Umlaute umwandeln?
* Umlaute im Web-API-Aufruf müssen codiert werden, und zwar mit dem HEX-Wert der UTF8-Zeichentabelle.
* Beispiele:
* Ä -> %C3%84
* Ö -> %C3%96
* Ü -> %C3%9C
* ä -> %C3%A4
* ö -> %C3%B6
* ü -> %C3%BC
* Eine Beschreibung dazu finden Sie hier: http://www.andre-jochim.de/url-encode.htm
  Die UFT8-Zeichentabele finden Sie hier: http://www.utf8-zeichentabelle.de/
16. Frage: Ich nutze eine aktuelle libcurl Bibliothek. Wie erzwinge ich trotzdem eine Verbindung via HTTP 1.1 mit LiMS.
* Das erreichen Sie indem Sie folgenden den Optionsparameter `CURLOPT_HTTP_VERSION` setzen
* `curl_setopt($process, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);`

Weitere FAQs und wichtige Infos https://wissensnetz.dosb.de/schnellstart-lims-anbindung
