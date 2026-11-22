# Svar: forhåndssjekk av kolonner (schema lookup)

**Kort svar:** Ja, gjør det, men som opt-in. Det løser NOW() og lengde fullstendig og NULL delvis, og
det fikser i tillegg to feil jeg fant under dokumentasjonen. Kostnaden er neglisjerbar med cache.

---

## 1. Hva sqlbuddy allerede er forberedt på

`que()` har standardverdiene `'autostring'` og `'autoNO'`, ikke `'string'` og `false`. Det betyr at sqlbuddy
**allerede vet** forskjellen på «du sa string» og «du sa ingenting». Det er akkurat det skillet en
skjemaoppslag trenger:

| Situasjon | Hvem bestemmer |
|-----------|----------------|
| Type gitt i `que()` (`'int'`, `'datetime'`, `'strornull'` …) | **Utvikleren.** Skjemaet overstyrer aldri. |
| Type = `autostring` (ikke gitt) | **Skjemaet** velger type |
| nullable = `autoNO` (ikke gitt) | **Skjemaet** (`IS_NULLABLE`) |
| Ingen `:n` | **Skjemaet** (`CHARACTER_MAXIMUM_LENGTH`) |

Dermed er det bakoverkompatibelt for all kode som allerede sender type. Bare de «late» kallene
`que('col', $v)` får ny oppførsel, og det er nettopp dem vi vil forbedre.

---

## 2. Hva oppslaget gir

```sql
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE,
       CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, COLUMN_DEFAULT, EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
```

(`SHOW FULL COLUMNS FROM \`t\`` gir det samme og er like raskt. I_S er lettere å parse.)

Målt på utviklermaskinen (MySQL 8.0.17, tabellen `buddytest`):

| Kilde | Tid |
|-------|-----|
| `INFORMATION_SCHEMA.COLUMNS` | ~2,1 ms |
| `SHOW FULL COLUMNS` | ~2,1 ms |
| Diskcache (`include` av `var_export`-fil, uten opcache) | ~0,4 ms |
| Statisk cache i samme request | ~0 |

**Tåler man den ekstra spørringen?** Ja. 2 ms én gang per tabell per request er mindre enn de fleste
enkelt-INSERTs. Med en statisk cache betaler en løkke med 1000 inserts det bare én gang. Diskcache er
egentlig bare nødvendig hvis det skal være helt gratis.

---

## 3. Hva det løser, punkt for punkt

### 3.1 NOW(): løst fullstendig

| Kolonnetype | `que('c', 'NOW()')` |
|-------------|----------------------|
| `datetime`, `timestamp`, `date`, `time` | `NOW()` rått |
| alt annet | `'NOW()'` som streng |

Bonus: `now()` med små bokstaver og `CURRENT_TIMESTAMP` kan trygt godtas på datokolonner, fordi vi vet at
kolonnen er en dato. I dag blir `que('c', 'now()', 'datetime')` til `'0000-00-00 00:00:00'`.

### 3.2 Lengde: løst, med ett forbehold

`CHARACTER_MAXIMUM_LENGTH` er i **tegn** for `CHAR`/`VARCHAR`, og det passer med `mb_substr()` som sqlbuddy
allerede bruker. Men for `TINYTEXT`/`TEXT`/`MEDIUMTEXT` er grensen i **bytes** (255 / 65 535 / 16 MB).
Der må man kutte med `mb_strcut($v, 0, $bytes, 'UTF-8')`, ellers kan en tekst med æøå eller emoji
fortsatt bli for lang.

Designvalg: **kutte stille, eller feile?** I strict mode feiler MySQL i dag med *Data too long*. Auto-kutting
gjør at insert går gjennom, men kan skjule feil (f.eks. et ordrenummer som blir kuttet). Forslag: kutt,
men logg det (gjenbruk `logerror()` hvis den finnes).

### 3.3 NULL: delvis løst, og det er viktig å være ærlig her

Skjemaet forteller **om** kolonnen *kan* være NULL. Det forteller ikke om brukeren *mente* NULL.

| Verdi | Kolonne `NULL` | Kolonne `NOT NULL` |
|-------|----------------|--------------------|
| PHP `null` | `NULL` ✅ | `''` / `0` / standardverdi ✅ |
| `'NULL'` | `NULL` | ⚠ i dag `NULL` → feil i strict mode. Bør bli `''` eller kolonnens default |
| `''` | ? | `''` ✅ |
| `'null'` (fra WYSIWYG) | ? | `'null'` ✅ |

Skjemaet fjerner hele høyre kolonne som feilkilde. Det gjelder blant annet `Column 'x' cannot be null`,
som skjedde med `prepared_build()` og PHP `null` før 1.4.3.

Venstre kolonne er fortsatt et forretningsvalg. Mitt forslag for auto-modus:

* **Kun ekte PHP `null` og `'NULL'` blir NULL.** `''` og `'null'` lagres som tekst.
* Vil du at tom streng skal bli NULL, sier du det eksplisitt (`strornull` / `true`) slik som i dag.

Begrunnelse: WYSIWYG-problemet ditt («null» lagret som tekst) går **begge veier**. Noen skriver faktisk
ordet «null». Ingen metadata kan skille de to. Derfor bør bare den utvetydige PHP-verdien tolkes automatisk.

### 3.4 Type: auto-mapping

| DATA_TYPE | sqlbuddy-modus |
|-----------|----------------|
| `tinyint`, `smallint`, `mediumint`, `int`, `bigint` | `int` (`tinyint(1)` kan eventuelt bli `boolean`) |
| `decimal` | `decimal`, men **bundet som streng** (se 3.5) |
| `float`, `double` | `float` |
| `date` / `datetime`, `timestamp` / `time` | `date` / `datetime` / `time` |
| `char`, `varchar`, `*text` | `string` |
| `enum`, `set` | `string` + valider mot `COLUMN_TYPE` |
| `json` | `string` (evt. `json_encode` hvis array) |
| `binary`, `blob`, `bit`, spatial … | `string`, ingen tolkning |

### 3.5 Gratis bonuser

1. **Decimal-skala.** Presisjonen er rettet i 1.4.3 (sendes som streng). Med `NUMERIC_PRECISION`/`NUMERIC_SCALE`
   kan vi i tillegg avrunde til riktig antall desimaler og avvise verdier som er for store, før MySQL gjør det.
2. **Ukjente kolonner.** En skrivefeil i kolonnenavnet kan gi en tydelig exception *før* spørringen
   (`sqlbuddy: column 'emial' does not exist in 'customers'`). Det er også en liten sikkerhetsgevinst,
   fordi kolonnenavn i dag ikke escapes. Med skjema kan de hvitlistes.
3. **`enum`.** Ugyldig verdi kan avvises eller settes til default i stedet for en kryptisk MySQL-feil.

---

## 4. Hvor i koden

Tabellnavnet er først kjent i `build()`/`prepared_build()`, ikke i `que()`. Det passer, fordi
all tolkning allerede skjer i `normalize()`, som kalles fra begge. Endringen blir:

```
build($what, $table)  ──►  $schema = $this->schema($table)   (null hvis av)
                           normalize($i, $schema[$key] ?? null)
```

* `que()` endres ikke, den lagrer fortsatt bare råverdiene.
* `normalize()` får én ekstra parameter. Hvis den er `null`, er oppførselen **identisk med i dag**.
* `:n` fra `que()` kuttes i dag allerede i `que()`. Skjema-kutt må skje i `normalize()`.
  Eksplisitt `:n` vinner fordi den allerede er brukt.

API-forslag (minst mulig):

```php
// globalt, én gang i bootstrap
sqlbuddy::$schema = true;                        // per-request cache
sqlbuddy::$schema_cache_dir = __DIR__ . '/cache'; // valgfritt: diskcache

// eller per objekt
$sql = new sqlbuddy(schema: true);

sqlbuddy::schema_flush();          // tøm cache (deploy / migrering)
sqlbuddy::schema_flush('customers');
```

Tilkoblingen tas fra `global $mysqli` som `build()` allerede bruker, og `prepared_build()` får da samme
avhengighet når skjema er på.

---

## 5. Cache og invalidering

* **Nivå 1: statisk array i klassen.** Gratis, og alltid på når skjema er aktivert.
* **Nivå 2: disk:** `cache/sqlbuddy.<db>.<table>.php` med `<?php return [...]`. Med opcache er
  dette i praksis gratis. Skriv atomisk (temp-fil + `rename`).
* **Invalidering:** Det finnes ikke noe pålitelig «skjema endret»-tidsstempel i MySQL. `UPDATE_TIME`
  gjelder data, og `CREATE_TIME` endres ikke alltid ved *instant* ALTER i 8.0. Derfor:
  1. manuell `schema_flush()` (eller slett mappen) ved migrering eller deploy,
  2. **selvreparasjon:** hvis spørringen feiler med `1054 Unknown column` eller `1406 Data too long`,
     tømmes cachen for tabellen, og så prøver man én gang til,
  3. eventuelt en lang TTL (f.eks. 24 t) som sikkerhetsnett.

---

## 6. Risiko

| Risiko | Tiltak |
|--------|--------|
| Biblioteket brukes i mange systemer, og endret oppførsel bryter noe | Opt-in. Av = 100 % dagens kode. Ny minor-versjon (1.5). |
| `que('c', $v)` på `int`-kolonne gir `(int)` i stedet for `'tekst'` | Det er ønsket, men kjør testmatrisen med skjema av/på og sammenlign. |
| `db.table` / andre skjemaer | Splitt på `.` og bruk `TABLE_SCHEMA = ?`. |
| Views og temporære tabeller | Temporære tabeller finnes ikke i I_S. Fall tilbake til dagens oppførsel uten feil. |
| Kolonne-case (`Name` vs `name`) | Slå opp case-insensitivt (MySQL-kolonner er case-insensitive). |

---

## 7. Anbefalt rekkefølge

1. ~~**Først, uavhengig av skjema:** fiks null-binding i `prepared_build()` og tilgivende `datetime`.~~
   **Gjort i 1.4.3**, sammen med decimal som streng, `NOW()` i alle fasonger og `'NULL'` kun for nullable.
2. `schema()` + statisk cache + type, NOW() og lengde i `normalize()` (opt-in).
3. NULL-regelen for auto-modus (3.3) og decimal som streng (3.5).
4. Diskcache og selvreparasjon.
5. Kolonne-hvitlisting / exception for ukjente kolonner (kan være eget flagg, fordi den er strengest).
