# sqlbuddy 1.4 – manual

> Gjelder sqlbuddy **v1.4.x** (testet på v1.4.3, PHP 8.3, MySQL 8.0).
> Alle eksempler i denne manualen er kjørt mot klassen og viser faktisk output.

---

## Innhold

1. [Hva er sqlbuddy](#1-hva-er-sqlbuddy)
2. [Installasjon og krav](#2-installasjon-og-krav)
3. [Kom i gang](#3-kom-i-gang)
4. [Hvordan sqlbuddy jobber](#4-hvordan-sqlbuddy-jobber)
5. [que() – legge til en kolonne](#5-que--legge-til-en-kolonne)
6. [Typer (parameter 3)](#6-typer-parameter-3)
7. [NULL-håndtering](#7-null-håndtering)
8. [NOW() og andre SQL-uttrykk](#8-now-og-andre-sql-uttrykk)
9. [Lengdebegrensning `:n`](#9-lengdebegrensning-n)
10. [build() – ferdig SQL-streng](#10-build--ferdig-sql-streng)
11. [prepared_build() – prepared statements](#11-prepared_build--prepared-statements)
12. [post() – direkte fra skjema](#12-post--direkte-fra-skjema)
13. [Øvrige metoder](#13-øvrige-metoder)
14. [Hjelpefunksjoner](#14-hjelpefunksjoner)
15. [Sikkerhet – hva som escapes og hva som ikke gjør det](#15-sikkerhet--hva-som-escapes-og-hva-som-ikke-gjør-det)
16. [Fallgruver og kjente begrensninger](#16-fallgruver-og-kjente-begrensninger)
17. [Oppskrifter](#17-oppskrifter)
18. [Hurtigreferanse](#18-hurtigreferanse)

---

## 1. Hva er sqlbuddy

sqlbuddy er en liten query-bygger for **INSERT** og **UPDATE** i MySQL/MariaDB. Du legger til
kolonner og verdier én og én, sier (valgfritt) hvilken type verdien er, og sqlbuddy
lager en komplett og escapet SQL-spørring.

Klassen er laget for butikk- og CRM-løsninger der data ofte kommer rett fra brukeren. Derfor
er den bevisst «tilgivende». `"500,-"` blir `500`, `"31.12.2024"` blir `2024-12-31`, `"Yes"` blir `1`
og en tom streng kan bli `NULL`. Det er altså mer enn en escaper. Den tolker verdien
slik et menneske ville gjort.

Siden v1.4.1 kan de samme dataene leveres på to måter:

| Metode               | Resultat                                         | Bruk                                      |
|----------------------|--------------------------------------------------|-------------------------------------------|
| `->build()`          | Ferdig SQL-streng, verdiene escapet og quotet    | `$mysqli->query($sql)` (klassisk)          |
| `->prepared_build()` | `[query, types, params]` med `?` for verdiene    | Prepared statements (f.eks. via Mysqli2)  |

Begge går gjennom samme interne motor (`normalize()`), så de tolker en verdi likt.

---

## 2. Installasjon og krav

```bash
composer require steinhaug/sqlbuddy
```

```php
require 'vendor/autoload.php';
$sql = new sqlbuddy();
```

Klassen ligger i det globale navnerommet (ingen `namespace`) og lastes via PSR-0.

**Krav**

| Krav | Hvorfor |
|------|---------|
| PHP ≥ 8.0 | `match`, `str_contains`, `str_starts_with` |
| `ext-mbstring` | Lengdekutting og tegnsett-konvertering |
| **Global `$mysqli`** (mysqli-tilkobling) | `build()` og `output()` bruker `global $mysqli` for `mysqli_real_escape_string()`. Uten den feiler `build()`. `prepared_build()` trenger den **ikke**. |
| Global funksjon `logerror($msg)` | Brukes **kun** av `post()` når en verdi ikke er numerisk eller modus er ukjent. Mangler den, får du fatal error akkurat da. |

---

## 3. Kom i gang

```php
$sql = new sqlbuddy();
$sql->que('first', 'Kim');
$sql->que('last',  'Steinhaug');
$sql->que('age',   '51', 'int');

echo $sql->build('insert', 'users');
// INSERT INTO `users` (`first`, `last`, `age`) VALUES ('Kim', 'Steinhaug', 51)

echo $sql->build('update', 'users', 'id=1');
// UPDATE `users` SET `first`='Kim', `last`='Steinhaug', `age`=51 WHERE id=1
```

Samme data som prepared statement:

```php
[$query, $types, $params] = $sql->prepared_build('insert', 'users');
// 'INSERT INTO `users` (`first`, `last`, `age`) VALUES (?, ?, ?)'
// 'ssi'
// ['Kim', 'Steinhaug', 51]

$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
```

---

## 4. Hvordan sqlbuddy jobber

```
que()/post()/unshift()  →  kø: [kolonne, verdi, type, nullable]
                                        │
                                 normalize()   ← all tolkning skjer her, én gang
                                        │
                ┌───────────────────────┴───────────────────────┐
          build()/output()                               prepared_build()
   'verdi' escapes + quotes                       ?  + type (i/s/d) + param
```

`normalize()` gjør hver kø-rad om til én av to ting:

* **inline**: skrives rett inn i SQL-en og kan aldri bli et `?`. Gjelder `NULL`, `NOW()`, kolonnereferanser
  (`col`) og `raw`.
* **value**: en vanlig verdi. `build()` escaper og (for strenger) quoter den, og `prepared_build()`
  binder den som parameter.

Rekkefølgen kolonnene legges til i, er rekkefølgen de får i SQL-en (`unshift()` legger først).

---

## 5. que() – legge til en kolonne

```php
$sql->que(string $column, mixed $value, string|bool $type = 'autostring', mixed $nullable = 'autoNO');
```

| Parameter   | Beskrivelse |
|-------------|-------------|
| `$column`   | Kolonnenavn. Pakkes i backticks, men **escapes ikke** (se [15](#15-sikkerhet--hva-som-escapes-og-hva-som-ikke-gjør-det)). |
| `$value`    | Verdien. Tolkes etter `$type`. |
| `$type`     | Se [6](#6-typer-parameter-3). Standard `autostring` (= string). Kan få suffiks `ornull` og/eller `:n`. |
| `$nullable` | Skal en «tom» verdi bli SQL `NULL`? Godtar **kun** `true` eller strengen `'yes'` (alle fasonger). Alt annet er *ikke nullable*. |

**Snarvei:** `que($k, $v, true)` med bare tre parametre betyr `que($k, $v, 'string', true)`.

```php
$sql->que('note', '', true);     // `note`=NULL
$sql->que('note', 'x', true);    // `note`='x'
```

`push()` er et alias for `que()`.

> ⚠ `$nullable = 1` eller `'true'` er **ikke** nullable (de matcher verken `true` eller `'yes'`).
> Bruk `true`, eller `ornull`-suffikset på typen.

---

## 6. Typer (parameter 3)

Typenavn er **case-sensitive**. `'INT'` gir exception `sqlbuddy unknown handler: "INT" for column ...` (med kolonnenavnet).

| Type | Tolkning | `build()` | PS-type | Eksempel → resultat |
|------|----------|-----------|---------|---------------------|
| `str`, `string`, `text`, `autostring` | Tekst | `'quotet'` | `s` | `"Kim's"` → `'Kim\'s'` |
| `int`, `tinyint` | `(int)` cast | bar | `i` | `'12 abc'` → `12`, `''` → `0` |
| `float` | `make_number()` → float | `'quotet'` | `d` | `'1 234,50'` → `'1234.5'` |
| `dec`, `decimal` | `make_number()` som tallstreng (full presisjon) | bar | `s` | `'kr 99,90'` → `99.90`, `'500,-'` → `500` |
| `date` | Tilgivende (se [16.2](#162-dato-og-tid)) → `Y-m-d` | `'quotet'` | `s` | `'31.12.2024'` → `'2024-12-31'`, `'1/2/24'` → `'2024-02-01'` |
| `time` | Tilgivende → `H:i:s` | `'quotet'` | `s` | `'9:5'` → `'09:05:00'`, `'2026-11-22 3:32:05'` → `'03:32:05'` |
| `datetime` | Tilgivende → `Y-m-d H:i:s` | `'quotet'` | `s` | `'2026-11-22 3:32:05'` → `'2026-11-22 03:32:05'`, `'22.11.2026'` → `'2026-11-22 00:00:00'` |
| `boolean` | `_bool()` → 1/0 | bar | `i` | `'Yes'` → `1`, `'FALSE'` → `0`, `''` → `0` |
| `email` | `FILTER_VALIDATE_EMAIL` | `'quotet'` | `s` | ugyldig → `''` (eller `NULL` hvis nullable) |
| `col`, `column` | Kolonnereferanse (backtick escapes) | `` `verdi` `` | inline | `'other_col'` → `` `other_col` `` |
| `raw` | Skrives rått (`null` → `NULL`, `''` → `''`) | som den er | inline | `'hits+1'` → `hits+1` |

### `ornull`-suffikset

Legg `ornull` til en type for å gjøre den nullable uten fjerde parameter:
`strornull`, `intornull`, `floatornull`, `decornull`, `dateornull`, `timeornull`,
`datetimeornull`, `emailornull` … `ornull` alene = `strornull`.

```php
$sql->que('a', '', 'strornull');   // `a`=NULL
$sql->que('a', '', 'string', true); // samme
```

> Suffikset kombineres med lengde slik: `strornull:20` (ikke `str:20ornull`).

---

## 7. NULL-håndtering

NULL er den vanskeligste delen av sqlbuddy, så her er reglene samlet.

### 7.1 Regel 1: `raw` skriver NULL slik du skriver det

Den sikreste og mest brukte måten:

```php
$sql->que('name', 'null', 'raw');   // `name`=null
$sql->que('name', null,   'raw');   // `name`=NULL
```

Strengen `'NULL'` gir også NULL i alle **nullable** typer (`que('name', 'NULL', 'strornull')`).
I en **ikke-nullable** type lagres den som tekst (`'NULL'`). Den gamle regelen «`'NULL'` vinner alltid»
er fjernet i 1.4.3, fordi den fikk spørringen til å krasje på `NOT NULL`-kolonner. Det verste som kan skje
nå, er at teksten «NULL» blir lagret.

### 7.2 Regel 2: uten nullable blir det aldri NULL (bortsett fra `raw`)

```php
$sql->que('name', 'null');   // `name`='null'   (en streng)
$sql->que('name', 'NULL');   // `name`='NULL'   (en streng)
$sql->que('name', null);     // `name`=''       (også i prepared_build)
$sql->que('age',  null, 'int'); // `age`=0
```

### 7.3 Regel 3: med nullable blir «tomme» verdier NULL

Med nullable (`true`, `'yes'` eller `*ornull`) gjelder dette:

| Type | Blir NULL når verdien er |
|------|---------------------------|
| string-typene | `null`, `false`, `''`, `'null'` (alle fasonger). `'0'` beholdes som `'0'`. |
| `int`, `tinyint` | `null`, `false`, `''`, `'null'`. Merk: `0` / `'0'` blir **`0`**, ikke NULL. |
| `float`, `dec`, `decimal`, `time` | `null`, `false`, `''`, `'null'` |
| `date`, `datetime` | som over, + `'0000-00-00'`, `'0000-00-00 00:00:00'`, + alt som ikke er en gyldig dato (f.eks. `'2026-02-30'`) |
| `email` | ugyldig e-postadresse |
| `boolean`, `col` | bare for strengen `'NULL'` |
| `raw` | `null` (og `''` når nullable) |

```php
$sql->que('n', 'null', 'string', true);  // NULL
$sql->que('n', 'null', 'raw');           // null  (rått, skrives som det står)
$sql->que('n', '0', 'strornull');        // '0'
$sql->que('n', '0', 'intornull');        // 0
```

### 7.4 Oversikt: de vanligste variantene

| Kall | `build()` | `prepared_build()` |
|------|-----------|--------------------|
| `que('n', 'null', 'raw')` | `null` | inline `null` |
| `que('n', 'null')` | `'null'` | `?` = `"null"` |
| `que('n', 'NULL')` | `'NULL'` | `?` = `"NULL"` |
| `que('n', null)` | `''` | `?` = `""` |
| `que('n', null, 'string', true)` | `NULL` | inline `NULL` |
| `que('n', 'null', true)` | `NULL` | inline `NULL` |

**Anbefaling:** Bruk ekte PHP `null` + nullable (`*ornull`) når du mener NULL. Da er intensjonen
tydelig og uavhengig av hva brukeren skrev.

---

## 8. NOW() og andre SQL-uttrykk

`'NOW()'` (store eller små bokstaver) blir SQL-funksjonen `NOW()` **kun** i typene
`date`, `datetime`, `time` (og `raw`, som alltid er rått):

```php
$sql->que('created', 'NOW()', 'datetime');  // `created`=NOW()
$sql->que('created', 'now()', 'date');      // `created`=NOW()
$sql->que('created', 'NOW()');              // `created`='NOW()'   (en streng!)
```

Andre SQL-uttrykk må gå via `raw`:

```php
$sql->que('hits',    'hits+1',            'raw');  // `hits`=hits+1
$sql->que('updated', 'CURRENT_TIMESTAMP', 'raw');
$sql->que('copy',    'other_col',         'col');  // `copy`=`other_col`
```

---

## 9. Lengdebegrensning `:n`

Alle typer kan få suffikset `:n`. Da kuttes verdien til maks *n* **tegn** (multibyte-sikkert)
før den tolkes:

```php
$sql->que('code', 'Kimsteinhaug', 'string:3');  // 'Kim'
$sql->que('code', 'Æøåæøå',       'string:3');  // 'Æøå'
$sql->que('num',  '12345',        'int:3');     // 123
$sql->que('code', null,           'string:3');  // ''     (null → '' i que() med :n)
$sql->que('code', null,           'strornull:3'); // NULL
```

---

## 10. build() – ferdig SQL-streng

```php
$sql->build(string $what, string $table, ?string $where = null): string
```

* `$what`: `'insert'` eller `'update'` (case-insensitive). Annet gir exception.
* `$table`: pakkes i backticks, escapes ikke.
* `$where`: **påkrevd** for update (exception uten). Settes inn **rått**, du er selv ansvarlig for escaping.

```php
echo $sql->build('update', 'users', 'id=' . (int) $id);
```

### Linjeskift / debug: `lb()`

```php
echo $sql->lb()->build('update', 't', 'id=1');
// UPDATE `t` SET
// `a`='x',
// `b`=2
// WHERE id=1

echo $sql->lb('set')->build('update', 't', 'id=1');
// Returnerer en HTML-formatert, justert oversikt (kolonne = verdi), IKKE gyldig SQL.
// Kun til feilsøking i nettleser.
```

`lb()` er «klistrete». Den gjelder resten av objektets levetid (også etter `flush()`).

---

## 11. prepared_build() – prepared statements

```php
$sql->prepared_build(string $what, string $table, ?string $where = null): array
// returnerer [string $query, string $types, array $params]
```

* Vanlige verdier blir `?`, typen samles i `$types` (`i`, `s`, `d`), og verdien legges **uescapet** i `$params`.
* Inline-verdier (`NULL`, `NOW()`, `col`, `raw`) skrives rett inn i spørringen og bindes ikke.
* `$where` er rått, akkurat som i `build()`.
* Trenger ikke global `$mysqli`.

```php
$sql = new sqlbuddy();
$sql->que('name',    'Kim');
$sql->que('status',  null, 'ornull');
$sql->que('created', 'NOW()', 'datetime');
$sql->que('score',   '1.5', 'decimal');
$sql->que('hits',    0, 'int');

$sql->prepared_build('insert', 'stats');
// [
//   'INSERT INTO `stats` (`name`, `status`, `created`, `score`, `hits`) VALUES (?, NULL, NOW(), ?, ?)',
//   'ssi',
//   ['Kim', '1.5', 0]
// ]
```

Med Mysqli2-klassen (egen pakke):

```php
$id   = $mysqli->prepared_insert($sql->prepared_build('insert', 'stats'));
$rows = $mysqli->prepared_insert($sql->prepared_build('update', 'stats', 'id=' . (int) $id));
```

Hvis alle verdiene er inline, blir `$types` `''` og `$params` `[]`. Da må du ikke kalle
`bind_param()` (mysqli feiler på tom typestreng).

---

## 12. post() – direkte fra skjema

```php
$sql->post(string $column, string $postKey, string $mode, bool $nullable = false);
```

Leser `$_POST[$postKey]` og legger den i køen.

| Modus | Mangler / tom | Med nullable | Ellers |
|-------|---------------|--------------|--------|
| `checkbox` | `0` | `null` (rått) | `1` hvis truthy |
| `str`, `string`, `text`, `input` | `''` | `null` hvis tom eller `'null'` | strengen (`:n` kutter) |
| `int` | `0` hvis mangler | `null` hvis tom/`'null'` | `(int)`. Logger via `logerror()` hvis ikke numerisk |
| `col`, `column` | (warning hvis mangler) | – | kolonnereferanse |

```php
// $_POST = ['chk' => 'on', 'name' => 'Kim', 'empty' => '']
$sql->post('active', 'chk',   'checkbox');      // 1
$sql->post('name',   'name',  'string:2');      // 'Ki'
$sql->post('note',   'empty', 'string', true);  // null
```

> ⚠ Ukjent modus logges med `logerror()`, og verdien legges **bare til hvis den mangler** i `$_POST`.
> Er den satt, legges ingenting til.
> ⚠ `post()` skriver NULL som rå `null` (små bokstaver), ikke `NULL`. SQL-messig er det likt.

---

## 13. Øvrige metoder

| Metode | Beskrivelse |
|--------|-------------|
| `unshift($k, $v, $type = 'string', $nullable = 'NO')` | Som `que()`, men legger kolonnen **først**. |
| `push(...)` | Alias for `que()`. |
| `flush()` | Tømmer køen så objektet kan gjenbrukes. (`lb()`-modus beholdes.) |
| `count_keys()` | Antall kolonner i køen. |
| `status()` | `true` hvis køen har minst én kolonne. Nyttig før `build()` på en tom update. |
| `output('keys' \| 'values' \| 'set')` | Lavnivå: bare kolonnelisten, verdilisten eller SET-delen. |
| `$sql->safehtml` | Offentlig egenskap, **har ingen effekt** i 1.4 (reservert). |
| `sqlbuddy::version` | Versjonskonstant, f.eks. `'1.4.3'`. |

```php
$sql = new sqlbuddy();
$sql->que('a', 'x'); $sql->que('b', 2, 'int');
$sql->output('keys');    // `a`, `b`
$sql->output('values');  // 'x', 2
$sql->output('set');     // `a`='x', `b`=2
```

---

## 14. Hjelpefunksjoner

Alle er `public` og kan brukes direkte.

### `_bool($var): bool`

Boolsk tolkning med «menneskelogikk», uavhengig av store og små bokstaver.

| false | true |
|-------|------|
| `false`, `null`, `[]`, `''`, `'   '`, `'false'`, `'no'`, `'off'`, `'null'`, `'[]'`, `'{}'`, `'0'`, `'00'`, `0`, `0.0` | objekter, ikke-tomme arrays, `'true'`, `'yes'`, `'on'`, tall ≠ 0, alle andre strenger (`'abc'`, `'-1'`, `'0.0'`) |

### `make_number($s, $as_string = false): int|float|string`

Tolker beløp: fjerner bokstaver og mellomrom samt `,-` / `.-` på slutten, og gjør `,` om til `.`.

```php
make_number('1 234,50'); // 1234.5
make_number('500,-');    // 500
make_number('abc');      // 0
make_number('1.234,50'); // 1.234   ⚠ punktum som tusenskille støttes ikke
make_number('1 234,50', true); // '1234.50'   (tallstreng, brukes av decimal)
```

### `sloppydate($s, $action = 'ddmmyyyy')`

Tolker datoer med `.`, `/`, `-`, `,` eller mellomrom som skille. Med `'sql'` returneres `Y-m-d`:

```php
sloppydate('31.12.2024', 'sql'); // 2024-12-31
sloppydate('1/2/24', 'sql');     // 2024-02-01   (dd/mm/yy, år 20xx)
sloppydate('2024.5.31', 'sql');  // 2024-05-31
sloppydate('31122024', 'sql');   // 0000-00-00
```

Andre actions: `fix`, `reverse`, `ddmmyyyy`, `yyyymmdd`, `splitt`, `datetime2date`, `datetime2time`, `valid`, `test`.

### `make_time($s)`, `isValidDateTime($s)`

`make_time('9:5')` gir `'09:05:00'`, og alt uten `:` gir `'00:00:00'`.
`isValidDateTime()` er true kun for nøyaktig `Y-m-d H:i:s` (brukes ikke lenger internt).

---

## 15. Sikkerhet – hva som escapes og hva som ikke gjør det

| Del | Beskyttet? |
|-----|------------|
| Verdier i string/date/time/float/email-typer | ✅ `mysqli_real_escape_string` (build) / bundet (prepared) |
| Verdier i int/tinyint/boolean | ✅ castet til tall |
| Verdier i dec/decimal | ✅ `make_number()` gir alltid tall |
| Kolonnenavn (`$column`) | ✅ backticks, og backtick i navnet dobles |
| **Tabellnavn** | ❌ kun backticks |
| **WHERE** | ❌ settes inn rått |
| Verdier i **`raw`** | ❌ settes inn rått |
| Verdier i `col`/`column` | ✅ som kolonnenavn |

Kolonnenavn kan ikke lenger bryte ut av backticks, men brukerdata bør likevel ikke bestemme *hvilken* kolonne som skrives.

**Tommelfingerregel:** brukerdata skal aldri inn i kolonnenavn, tabellnavn, WHERE, `raw` eller `col`
uten at du har hvitlistet eller castet dem selv (`'id=' . (int) $id`).
`public_html/buddyset-01.php` viser et eksempel på hva man **ikke** skal gjøre (`$_POST['name']` som kolonnenavn).

---

## 16. Fallgruver og kjente begrensninger

Alt her er verifisert i v1.4.3.

### 16.1 Rettet i 1.4.3

Disse var feil i 1.4.2 og tidligere. Har du kode som omgår dem, kan omgåelsen fjernes:

| Før (≤ 1.4.2) | Nå (1.4.3) |
|---------------|------------|
| `datetime` krevde nøyaktig `Y-m-d H:i:s`, så `date('Y-m-d G:i:s')` før kl. 10, `T`, uten sekunder og norsk format ble `0000-00-00 00:00:00` | Tilgivende parsing, se 16.2 |
| `datetime` skrev `'2024-12-31 9:5:3'` | `'2024-12-31 09:05:03'` |
| `'now()'` (små bokstaver) i `date`/`datetime` ble nulldato | `NOW()` |
| `'NULL'` ga NULL i alle typer, og krasjet på `NOT NULL` | NULL bare når nullable, ellers tekst |
| `'0'` ble NULL i nullable string | `'0'` |
| `prepared_build()` bandt PHP `null` som NULL (`Column cannot be null`) | binder `''`, som `build()` |
| `decimal` mistet presisjon (`1.0E+14`) | tallstreng, eksakt |
| `raw` med `null` / `''` ga `VALUES ()` | `NULL` / `''` |
| Backtick i kolonnenavn ble ikke escapet | escapes |
| `time` med datetime-verdi ga hele datetime-strengen | bare klokkeslettet |

### 16.2 Dato og tid

Typene `date`, `datetime` og `time` godtar:

| Input | `datetime` | `date` | `time` |
|-------|------------|--------|--------|
| `'2026-11-22 03:32:05'` | `'2026-11-22 03:32:05'` | `'2026-11-22'` | `'03:32:05'` |
| `'2026-11-22 3:32:05'` (`date('G')`) | `'2026-11-22 03:32:05'` | `'2026-11-22'` | `'03:32:05'` |
| `'2026-11-22 10:30'` | `'2026-11-22 10:30:00'` | `'2026-11-22'` | `'10:30:00'` |
| `'2026-11-22T10:30'` (HTML `datetime-local`) | `'2026-11-22 10:30:00'` | `'2026-11-22'` | `'10:30:00'` |
| `'2026-11-22'` | `'2026-11-22 00:00:00'` | `'2026-11-22'` | `'00:00:00'` |
| `'22.11.2026 10:30'` | `'2026-11-22 10:30:00'` | `'2026-11-22'` | `'10:30:00'` |
| `'1/2/26'` | `'2026-02-01 00:00:00'` | `'2026-02-01'` | `'00:00:00'` |
| `'9:5'` | ugyldig | ugyldig | `'09:05:00'` |
| `'25:00'` (varighet) | ugyldig | ugyldig | `'25:00:00'` |

Datodelen tolkes med `sloppydate()` (dag først: `dd.mm.yyyy`, `dd/mm/yy`, eller `yyyy-mm-dd`, `yyyy.m.d`) og
valideres med `checkdate()`, så `'2026-02-30'` er ugyldig. Amerikansk `mm/dd/yyyy` støttes ikke.

**Ugyldig dato:** blir `NULL` hvis nullable, ellers `'0000-00-00'` / `'0000-00-00 00:00:00'`.
MySQL i strict mode med `NO_ZERO_DATE` avviser nulldato, så bruk `dateornull`/`datetimeornull` på felt som kan være tomme.

### 16.3 Øvrig

* Typenavn er case-sensitive (`'INT'` gir exception).
* `$nullable` godtar bare `true` / `'yes'`.
* `make_number('1.234,50')` gir `1.234` (punktum som tusenskille støttes ikke).
* `post()` krever global `logerror()`, og `build()` krever global `$mysqli`.
* `lb()` kan ikke slås av igjen på samme objekt.
* Tabellnavnet escapes ikke (bare backticks).
* `email` med ugyldig adresse blir `''` uten advarsel (eller `NULL` når nullable).

---

## 17. Oppskrifter

### Insert og hent id

```php
$sql = new sqlbuddy();
$sql->que('email',   $email, 'email');
$sql->que('name',    $name, 'string:100');
$sql->que('phone',   $phone, 'strornull:20');
$sql->que('created', 'NOW()', 'datetime');
$mysqli->query($sql->build('insert', 'customers'));
$id = $mysqli->insert_id;
```

### Update – bare hvis noe er endret

```php
$sql = new sqlbuddy();
if ($name !== $old['name'])  $sql->que('name', $name);
if ($price !== $old['price']) $sql->que('price', $price, 'decimal');

if ($sql->status()) {
    $mysqli->query($sql->build('update', 'products', 'id=' . (int) $id));
}
```

### Gjenbruk i løkke

```php
$sql = new sqlbuddy();
foreach ($rows as $r) {
    $sql->flush();
    $sql->que('sku', $r['sku'], 'string:32');
    $sql->que('qty', $r['qty'], 'int');
    $mysqli->query($sql->build('insert', 'stock'));
}
```

### Prepared statement uten Mysqli2

```php
[$q, $types, $params] = $sql->prepared_build('update', 'users', 'id=' . (int) $id);
$stmt = $mysqli->prepare($q);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
```

### Skjema med checkbox og valgfrie felt

```php
$sql = new sqlbuddy();
$sql->post('newsletter', 'newsletter', 'checkbox');
$sql->post('company',    'company',    'string:100', true); // tom → NULL
$sql->post('employees',  'employees',  'int', true);
$mysqli->query($sql->build('update', 'customers', 'id=' . (int) $id));
```

### Teller / uttrykk

```php
$sql->que('views', 'views+1', 'raw');
$sql->que('last_seen', 'NOW()', 'datetime');
```

---

## 18. Hurtigreferanse

```php
$sql = new sqlbuddy();

$sql->que($col, $val);                    // string
$sql->que($col, $val, 'int');             // int, tinyint, float, dec, decimal
$sql->que($col, $val, 'date');            // date, time, datetime
$sql->que($col, $val, 'boolean');         // 1/0 via _bool()
$sql->que($col, $val, 'email');
$sql->que($col, $val, 'raw');             // rått SQL-uttrykk
$sql->que($col, $val, 'col');             // `kolonne`
$sql->que($col, $val, 'strornull');       // tom → NULL   (*ornull for alle typer)
$sql->que($col, $val, 'string', true);    // samme som over
$sql->que($col, $val, true);              // samme som over
$sql->que($col, $val, 'string:50');       // kutt til 50 tegn
$sql->que($col, 'null', 'raw');           // NULL
$sql->que($col, 'NOW()', 'datetime');     // NOW()  (også date/time, alle fasonger)

$sql->build('insert', $table);
$sql->build('update', $table, $where);
$sql->prepared_build('insert', $table);              // [query, types, params]
$sql->prepared_build('update', $table, $where);

$sql->flush(); $sql->status(); $sql->count_keys();
```
