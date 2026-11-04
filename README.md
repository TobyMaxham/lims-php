# php-lims

[![Tests](https://github.com/TobyMaxham/php-lims/actions/workflows/tests.yml/badge.svg)](https://github.com/TobyMaxham/php-lims/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/tobymaxham/php-lims/v)](https://packagist.org/packages/tobymaxham/php-lims)
[![Total Downloads](https://poser.pugx.org/tobymaxham/php-lims/downloads)](https://packagist.org/packages/tobymaxham/php-lims)
[![PHP Version Require](https://poser.pugx.org/tobymaxham/php-lims/require/php)](https://packagist.org/packages/tobymaxham/php-lims)
[![License](https://poser.pugx.org/tobymaxham/php-lims/license)](https://packagist.org/packages/tobymaxham/php-lims)

🇩🇪 Deutsch · [🇬🇧 English](README-en.md)

Ein schlanker, framework-unabhängiger PHP-Client für die **LiMS-API** (Lizenzmanagementsystem) des
[DOSB-Wissensnetzes](https://wissensnetz.dosb.de). Lizenzen anlegen, korrigieren, verlängern, abrufen,
löschen, Lizenzvordrucke herunterladen, Umzüge zwischen Verbänden abwickeln sowie Organisationen,
Ausbildungsgänge und Statistiken abrufen – mit einer einzigen Klasse.

> [!NOTE]
> Dieses Package ist **kein offizielles Angebot des DOSB** und steht in keiner Verbindung zum DOSB oder zum
> Betreiber der LiMS-API. Es bindet lediglich die dokumentierte Schnittstelle an und wird unabhängig davon gepflegt.
> API und Dokumentation können sich jederzeit unabhängig von diesem Package ändern.

## Inhalt

- [Features](#features)
- [Installation](#installation)
- [Schnellstart](#schnellstart)
- [Zugangsdaten und Testumgebung](#zugangsdaten-und-testumgebung)
- [Lizenzen anlegen, korrigieren und verlängern](#lizenzen-anlegen-korrigieren-und-verlängern)
- [Lizenzen laden und suchen](#lizenzen-laden-und-suchen)
- [Lizenzen löschen](#lizenzen-löschen)
- [Lizenzvordrucke herunterladen](#lizenzvordrucke-herunterladen)
- [Lizenzumzüge](#lizenzumzüge)
- [Organisationen und Ausbildungsgänge](#organisationen-und-ausbildungsgänge)
- [Statistiken](#statistiken)
- [Fehlerbehandlung](#fehlerbehandlung)
- [Eigenen HTTP-Client verwenden](#eigenen-http-client-verwenden)
- [Verwendung mit Laravel](#verwendung-mit-laravel)
- [Upgrade von 2.x auf 3.0](#upgrade-von-2x-auf-30)
- [Tests](#tests)

## Features

- ✅ **Alle Endpunkte der LiMS-API**: `request`, `load`, `lookup`, `delete`, `download`, `download_multiple`, alle Umzugs-Endpunkte sowie `lookup_organisations`, `lookup_training_courses` und `stats`
- ✅ **Keine Pflicht-Abhängigkeit zu einem HTTP-Client**: läuft mit dem eingebauten curl-Client oder jedem [PSR-18](https://www.php-fig.org/psr/psr-18/)-Client (Guzzle, Symfony HttpClient, …)
- ✅ **Validierung vor dem Senden**: fehlerhafte Lizenzdaten fallen auf, bevor ein Request an die API geht
- ✅ **Sprechende Exceptions** pro HTTP-Statuscode mit einer gemeinsamen Basisklasse
- ✅ **Automatisches Blättern** durch alle Organisationen und Ausbildungsgänge
- ✅ **Live- und Demo-System** per Schalter
- ✅ Getestet mit PHPUnit auf PHP 8.3 bis 8.5

## Installation

```bash
composer require tobymaxham/php-lims
```

**Voraussetzungen**

- PHP `^8.3`
- `ext-curl` **oder** ein beliebiger PSR-18-HTTP-Client

Ist die curl-Extension aktiv (bei fast allen PHP-Installationen der Fall), funktioniert das Package direkt nach
`composer require` – ganz ohne Guzzle oder andere zusätzliche Pakete. Mehr dazu unter
[Eigenen HTTP-Client verwenden](#eigenen-http-client-verwenden).

## Schnellstart

```php
use TobiasMaxham\PhpLims\Client;
use TobiasMaxham\PhpLims\Exceptions\LimsException;

$lims = new Client(username: 'mein-api-user', password: 'geheim');

try {
    $license = $lims->load('TEST-T-A-0000001');

    echo $license->firstname.' '.$license->lastname;
    echo 'Gültig bis: '.date('d.m.Y', $license->valid_until);
} catch (LimsException $e) {
    // Netzwerk-, API- oder Validierungsfehler
}
```

## Zugangsdaten und Testumgebung

Die Zugangsdaten für die API (und die Demo-Umgebung) erhältst du über den Support des
[DOSB-Wissensnetzes](https://wissensnetz.dosb.de/schnellstart-lims-anbindung).

> [!TIP]
> Der Benutzername ist **nicht** deine E-Mail-Adresse. Meist ist es der Teil der E-Mail-Adresse vor dem `@`.

Teste deine Integration zuerst auf dem **Demo-System**. Dort kannst du Lizenzen deiner Organisation anlegen und
ändern, ohne produktive Daten zu verändern. Das Demo-System wird von Zeit zu Zeit zurückgesetzt.

```php
// Demo-System direkt beim Erstellen …
$lims = new Client('mein-api-user', 'geheim', testMode: true);

// … oder nachträglich umschalten
$lims->setTestMode();       // Demo:  https://demo-dosb.wissensnetz.eu/api/lims
$lims->setTestMode(false);  // Live:  https://wissensnetz.dosb.de/api/lims
```

> [!WARNING]
> IDs für Ausbildungsgänge (`training_course_id`) und Verbände (`organisation_id`) können sich zwischen Demo- und
> Live-System unterscheiden. Prüfe sie beim Umstieg auf das Live-System.

## Lizenzen anlegen, korrigieren und verlängern

Alle drei Methoden nutzen den Endpunkt `/request`. Die Daten werden vorher geprüft (Pflichtfelder, Datumsfelder
als Timestamps, Geschlecht `m`/`w`/`u`, E-Mail, …). Bei ungültigen Daten wird eine `InvalidInputDataException`
geworfen und **kein** Request gesendet.

```php
// Neue Lizenz anlegen
$license = $lims->create([
    'organisation_id'    => 999,
    'firstname'          => 'Max',
    'lastname'           => 'Mustermann',
    'birthdate'          => strtotime('1980-01-01 12:00'),
    'gender'             => 'm',
    'street'             => 'Waldstraße 1',
    'postal'             => '10115',
    'city'               => 'Berlin',
    'issue_date'         => strtotime('2025-01-01 12:00'),
    'training_course_id' => 123,
    'issue_place'        => 'Berlin',
    'honor_code'         => true,
    'first_aid'          => true,
]);

$license->lid;                 // 1000001
$license->license_number_dosb; // "TEST-T-A-0000001"

// Bestehende Lizenz korrigieren – per DOSB-Lizenznummer oder LiMS-ID (lid)
$lims->correct(['firstname' => 'Erika', 'gender' => 'w'], 'TEST-T-A-0000001');
$lims->correct(['street' => 'Bergweg 2'], 1000001);

// Bestehende Lizenz verlängern – neues Ausstellungsdatum und neues Gültigkeitsdatum
$lims->extend([
    'issue_date'  => strtotime('2026-11-04 12:00'),
    'valid_until' => strtotime('2030-11-03 12:00'), // Ausstellungsdatum + Gültigkeitsdauer - 1 Tag
], 'TEST-T-A-0000001');
```

> [!WARNING]
> Gib beim Verlängern auch `valid_until` mit. Ohne dieses Feld übernimmt die API zwar das neue
> Ausstellungsdatum, das Gültigkeitsdatum bleibt aber unverändert. Die Gültigkeitsdauer eines Ausbildungsgangs
> (`validity_period`) bekommst du über [`lookupTrainingCourses()`](#organisationen-und-ausbildungsgänge).

> [!IMPORTANT]
> Datumsfelder werden als **Unix-Timestamp** übergeben. Die API-Dokumentation empfiehlt eine Uhrzeit von
> **12:00 Uhr** statt Mitternacht, um Probleme mit der Sommer-/Winterzeit zu vermeiden.

Weitere Hinweise:

- Bei `extend()` dürfen `organisation_id`, `first_issue_date` und `training_course_id` nicht geändert werden, bei `correct()` nicht die `organisation_id`.
- Für einen **Bestandsimport** gibst du zusätzlich `first_issue_date` an. Bei einer Neuausstellung nur `issue_date`.
- Das erlaubte Alter für `birthdate` ist standardmäßig 0 bis 120 Jahre und lässt sich anpassen:

```php
$lims->setMinYears(16);
$lims->setMaxYears(100);
```

Eine Übersicht aller Felder findest du in der [API-Dokumentation](docs/lims-api.md#felddefinitionen).

## Lizenzen laden und suchen

```php
// Eine Lizenz mit allen Feldern laden
$license = $lims->load('TEST-T-A-0000001');
$license->toArray(); // alle Felder als Array

// Lizenzen suchen – alle Parameter sind optional
$licenses = $lims->lookup(
    organisationId: 9004,
    offset: 0,
    limit: 100,                        // max. 1000 (bei idsOnly max. 20000)
    validationStatus: true,            // true = gültige, false = abgelaufene Lizenzen
    isObscured: false,                 // true = nur anonymisierte Lizenzen
    licenseNumberOrganisation: 'V-123' // Lizenznummer des Verbandes
);

foreach ($licenses as $license) {
    echo $license->license_number_dosb;
}

// Nur die DOSB-Lizenznummern abrufen
$numbers = $lims->lookup(organisationId: 9004, idsOnly: true); // ['TEST-T-A-0000001', …]
```

> [!NOTE]
> Die API liefert manche Felder anders zurück, als sie beim Anlegen übergeben werden. `gender` kommt z. B. als
> `männlich`/`weiblich` statt `m`/`w`. Anonymisierte Lizenzen (`is_obscured`) enthalten leere Namen und eine
> gekürzte PLZ. Lizenznummern werden unverändert durchgereicht, auch wenn sie (in Altdaten) Leerzeichen oder
> Tabs enthalten. Nur so lassen sie sich wieder laden.

## Lizenzen löschen

```php
$deleted = $lims->delete('TEST-T-A-0000001');
$deleted->lid;
$deleted->license_number_dosb;
```

## Lizenzvordrucke herunterladen

`dina4` und `card` werden als PDF geliefert, `signet` als JPG.

```php
// Einzelne Lizenz
$pdf = $lims->download('TEST-T-A-0000001');           // dina4
$pdf = $lims->download('TEST-T-A-0000001', 'card');
$jpg = $lims->download('TEST-T-A-0000001', 'signet');

file_put_contents('lizenz.pdf', $pdf);

// Nur die Download-URL statt der Datei
$url = $lims->download('TEST-T-A-0000001', 'dina4', urlOnly: true);

// Mehrere Lizenzen in einem PDF (dina4 oder card)
$pdf = $lims->downloadMultiple(['TEST-T-A-0000001', 'TEST-T-A-0000002'], 'card');
```

> [!NOTE]
> Abgelaufene oder anonymisierte Lizenzen können nicht heruntergeladen werden, die API antwortet dann mit
> HTTP 406 (`NotAcceptableException`). Beim Format `signet` liefert die API (Stand: November 2026) teilweise eine
> leere Datei. In dem Fall wird eine `InvalidResponseException` geworfen, mit `urlOnly: true` erhältst du aber
> eine Download-URL.

## Lizenzumzüge

Lizenzen können innerhalb desselben Dachverbandes (z. B. zwischen zwei Landesverbänden eines Spitzenverbandes)
in einen anderen Verband umziehen. Der Ablauf hat zwei Seiten:

1. Der **Zielverband** fordert die Lizenz an (`migrationRequest`).
2. Der **bisherige Verband** sieht die Anfrage (`migrationGet`) und nimmt sie an oder lehnt sie ab.

```php
// 1. Zielverband: Lizenz anfordern (organisationId = eigener Verband, also der Zielverband)
$zielverband = new Client('api-user-zielverband', 'geheim');
$status = $zielverband->migrationRequest('TEST-T-C-0000007', organisationId: 9003);
// "Ihre Anfrage wurde weitergeleitet." oder, bei Berechtigung zum direkten Umzug, "The license has been migrated."

// 2. Bisheriger Verband: offene Anfragen für die eigenen Lizenzen abholen …
$bisherigerVerband = new Client('api-user-bisheriger-verband', 'geheim');

foreach ($bisherigerVerband->migrationGet(organisationId: 9002) as $migration) {
    $migration->migration_id;        // 1000007 (entspricht der lid der Lizenz)
    $migration->license_number_dosb; // "TEST-T-C-0000007"
    $migration->organisation_id;     // 9003 – der anfragende Zielverband

    // … und annehmen oder ablehnen
    $bisherigerVerband->migrationAccept($migration->migration_id);  // "The license has been migrated successfully."
    // $bisherigerVerband->migrationDecline($migration->migration_id); // "Migrate request has been declined."
}
```

> [!NOTE]
> - Der bisherige Verband kann keinen Umzug anstoßen (HTTP 403), und nur er kann annehmen oder ablehnen.
> - Pro Lizenz kann nur eine Anfrage offen sein, eine zweite wirft eine `AccessDeniedException`.
> - Gibt es keine offenen Anfragen, antwortet die API mit HTTP 404, `migrationGet()` wirft dann eine `NotFoundException`.
> - Die offizielle API-Dokumentation beschreibt die Richtung des Umzugs nicht eindeutig. Der Ablauf oben ist im
>   Demo-System getestet (Stand: November 2026).

## Organisationen und Ausbildungsgänge

Über diese Abrufe erhältst du die IDs, die du für `organisation_id` und `training_course_id` brauchst.
Pro Abruf liefert die API höchstens 100 Einträge. Die `…All`-Methoden blättern automatisch durch alle Seiten.

```php
use TobiasMaxham\PhpLims\Enums\FederalState;
use TobiasMaxham\PhpLims\Enums\OrganisationCategory;

// Organisationen (Verbände) abrufen – alle Filter sind optional
$organisations = $lims->lookupOrganisations(
    offset: 0,
    limit: 100,
    federalState: FederalState::Niedersachsen,               // oder 'Niedersachsen'
    organisationParentId: 9001,                              // nur direkte Untergliederungen
    organisationCategory: OrganisationCategory::Spitzenverband, // SV, LSB, VmbA, UG
);

foreach ($organisations as $organisation) {
    echo $organisation->organisation_id.': '.$organisation->name;
}

// Alle Untergliederungen eines Verbandes, ohne selbst zu blättern
$all = $lims->lookupAllOrganisations(organisationParentId: 9001);

// DOSB-Ausbildungsgänge abrufen
$courses = $lims->lookupTrainingCourses(
    assignedOrganisationId: 9001, // für diese Organisation freigegeben
    level: 1,                     // Lizenzstufe
    trainingCourse: 'T-C/B',      // z. B. T-C/B, ÜL-B, VM-C
    sport: 'Kanu',
);

foreach ($courses as $course) {
    echo "{$course->id}: {$course->title} ({$course->validity_period} Jahre gültig)";
}

// Alle Ausbildungsgänge einer Organisation
$all = $lims->lookupAllTrainingCourses(assignedOrganisationId: 9001);
```

Für Bundesland und Verbändegruppe gibt es die Enums `FederalState` und `OrganisationCategory`. Strings
funktionieren genauso.

## Statistiken

Statistiken zu DOSB-Ausbildungsgängen für einen Zeitraum, gruppiert nach Ausbildungsgang und Geschlecht:

```php
use TobiasMaxham\PhpLims\Enums\AgeGroup;

$stats = $lims->stats(
    startDate: new DateTimeImmutable('2025-01-01 12:00'),
    endDate: new DateTimeImmutable('2025-12-31 12:00'),
    // optionale Filter:
    birthdate: AgeGroup::From19To26,     // Altersgruppe 1–5, bezogen auf das Startdatum
    postal: '301',                       // PLZ-Raum, max. 3 Stellen
    gender: 'w',
    trainingCourse: 'T-B/L',
    federalState: FederalState::Niedersachsen,
    organisationId: 9001,
);

// ['T-B/L' => ['new' => ['m' => '30', 'w' => '30', 'u' => '10', 'g' => '70'], 'extend' => [...], 'valid' => [...]], ...]
// new = Neuausstellungen, extend = Verlängerungen, valid = gültige Lizenzen, g = gesamt (Werte als String)
```

> [!NOTE]
> Weitere Filter: `trainingCourseId`, `level`, `sport`, `profile`, `focus`, `discipline`. Laut API-Doku sollte
> `trainingCourseId` allein genutzt werden, in Kombination mit anderen Detail-Filtern kann das Ergebnis 0 sein.
> Die Datumsangaben werden wie alle Datumsfelder der API als Unix-Timestamp gesendet.

## Fehlerbehandlung

Alle Exceptions erben von `TobiasMaxham\PhpLims\Exceptions\LimsException`. Fehler der API (`ApiException` und
Unterklassen) enthalten zusätzlich `$statusCode` und `$responseBody`.

| Exception                        | Bedeutung                                                           |
|----------------------------------|---------------------------------------------------------------------|
| `UnauthorizedException`          | 401 – Zugangsdaten falsch                                           |
| `AccessDeniedException`          | 403 – Zugriff verweigert (z. B. fehlende Berechtigung)              |
| `NotFoundException`              | 404 – Lizenz oder Umzugsanfrage nicht gefunden                      |
| `NotAcceptableException`         | 406 – z. B. Pflichtfeld fehlt, Lizenz oder Ausbildungsgang ungültig |
| `UnprocessableEntityException`   | 422 – Lizenzdaten wurden von der API abgelehnt                      |
| `ApiException`                   | Sonstiger Fehlerstatus der API (Basisklasse der obigen)             |
| `InvalidResponseException`       | Antwort der API konnte nicht verarbeitet werden                     |
| `ConnectionException`            | Netzwerkfehler (DNS, Timeout, Verbindung abgelehnt, …)              |
| `InvalidInputDataException`      | Ungültige Eingabedaten, es wurde kein Request gesendet              |
| `InvalidDownloadFormatException` | Ungültiges Download-Format                                          |

```php
use TobiasMaxham\PhpLims\Exceptions\ApiException;
use TobiasMaxham\PhpLims\Exceptions\InvalidInputDataException;
use TobiasMaxham\PhpLims\Exceptions\NotFoundException;

try {
    $lims->extend(['issue_date' => strtotime('2027-01-01 12:00')], 'TEST-T-A-0000001');
} catch (InvalidInputDataException $e) {
    // Daten korrigieren
} catch (NotFoundException $e) {
    // Lizenz existiert nicht
} catch (ApiException $e) {
    error_log("LiMS-Fehler {$e->statusCode}: {$e->getMessage()}");
}
```

## Eigenen HTTP-Client verwenden

Das Package benötigt nur die PSR-Interfaces für HTTP ([PSR-18](https://www.php-fig.org/psr/psr-18/),
[PSR-17](https://www.php-fig.org/psr/psr-17/), [PSR-7](https://www.php-fig.org/psr/psr-7/)). Wird kein Client
übergeben, wird automatisch gewählt:

1. ein bereits im Projekt installierter PSR-18-Client, sofern [`php-http/discovery`](https://github.com/php-http/discovery) installiert ist (z. B. Guzzle oder Symfony HttpClient),
2. sonst der eingebaute, schlanke curl-Client (Timeout: 60 Sekunden, anpassbar über `timeout:`).

Für eigene Konfiguration (Proxy, Logging, Retries, Timeouts) oder in Tests übergibst du Client und Factories selbst:

```php
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\HttpFactory;

$factory = new HttpFactory();

$lims = new Client(
    username: 'mein-api-user',
    password: 'geheim',
    httpClient: new Guzzle(['timeout' => 60]),
    requestFactory: $factory,
    streamFactory: $factory,
);
```

Mit Symfony HttpClient:

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$factory = new Psr17Factory();

$lims = new Client('mein-api-user', 'geheim', httpClient: new Psr18Client(), requestFactory: $factory, streamFactory: $factory);
```

Nur den Timeout des eingebauten curl-Clients ändern. Einige Abrufe wie `lookup()` brauchen auf der API
teils über 20 Sekunden, ein zu kurzer Timeout führt dann zu einer `ConnectionException`:

```php
$lims = new Client('mein-api-user', 'geheim', timeout: 120);
```

## Verwendung mit Laravel

Der Client ist framework-unabhängig und lässt sich z. B. in einem Service Provider registrieren:

```php
// app/Providers/AppServiceProvider.php
use TobiasMaxham\PhpLims\Client;

public function register(): void
{
    $this->app->singleton(Client::class, fn () => new Client(
        username: config('services.lims.username'),
        password: config('services.lims.password'),
        testMode: (bool) config('services.lims.test_mode', false),
    ));
}
```

```php
// config/services.php
'lims' => [
    'username'  => env('LIMS_USERNAME'),
    'password'  => env('LIMS_PASSWORD'),
    'test_mode' => env('LIMS_TEST_MODE', false),
],
```

Danach kannst du `Client` überall per Dependency Injection nutzen.

## Upgrade von 2.x auf 3.0

Version 3 ist ein Major-Release mit folgenden Breaking Changes:

- **PHP 8.3** ist die neue Mindestversion. PHP 8.1 und 8.2 werden nicht mehr unterstützt (Support-Ende 31.12.2025 bzw. 31.12.2026).
- **Guzzle ist keine Abhängigkeit mehr.** Wer Guzzle weiter nutzen will, installiert es selbst und übergibt es (siehe oben) oder installiert zusätzlich `php-http/discovery`.
- Der Package-Name ist jetzt einheitlich `tobymaxham/php-lims`.
- Der Konstruktor hat neue, optionale Parameter: `testMode`, `httpClient`, `requestFactory`, `streamFactory`, `timeout`.
- `lookup()`: `$licenseNumberOrganisation` ist jetzt ein `string` (die Lizenznummer des Verbandes), kein `bool` mehr.
- Alle Exceptions erben von `LimsException` (statt `\Exception`). API-Exceptions bieten `$statusCode` und `$responseBody`.
- Netzwerkfehler werfen eine `ConnectionException` statt einer allgemeinen `\Exception`.
- `MissingOrganisationIdException` wurde entfernt (wurde nicht verwendet).
- `migrationRequest()` funktioniert jetzt und gibt die Statusmeldung der API zurück. `migrationGet()` liefert `Migration`-Objekte.

## API-Dokumentation

Einstiegspunkt für die Anbindung ist die Seite
[Schnellstart LiMS-Anbindung](https://wissensnetz.dosb.de/schnellstart-lims-anbindung) im DOSB-Wissensnetz.
Dort findest du die aktuelle Schnittstellen-Dokumentation als PDF und das Abnahmeprotokoll für die Schnittstellennutzung.

Eine Kopie der Dokumentation (Stand: 26.10.2026) liegt in [docs/lims-api.md](docs/lims-api.md).

> [!NOTE]
> Die frühere Dokumentation unter `gitlab.ghostthinker.de` ist nicht mehr erreichbar. Verweise darauf in der Kopie
> stammen aus dem Original-Dokument. Der direkte Link auf die PDF im Wissensnetz kann sich ändern, nutze daher
> immer die Schnellstart-Seite.

## Tests

```bash
composer install
composer test
```

Die Tests laufen ohne Zugangsdaten und ohne Verbindung zur echten API.

## Changelog

Alle Änderungen findest du im [CHANGELOG](CHANGELOG.md).

## Sicherheit

Sicherheitslücken bitte nicht über den Issue-Tracker melden, sondern per E-Mail an [git@maxham.net](mailto:git@maxham.net).

## Lizenz

MIT. Siehe [LICENSE.md](LICENSE.md).
