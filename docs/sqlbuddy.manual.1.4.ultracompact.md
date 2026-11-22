# sqlbuddy 1.4 – AI reference (v1.4.4)

Global class `sqlbuddy` (composer `steinhaug/sqlbuddy`). Builds INSERT/UPDATE for MySQL from queued column/value pairs, interpreting values leniently.

## API
```php
$sql = new sqlbuddy;
$sql->que($col, $val, $type = 'autostring', $nullable = false); // push() = alias
$sql->unshift($col, $val, $type = 'string', $nullable = false); // adds first
$sql->post($col, $postKey, $mode, $nullable = false);           // reads $_POST[$postKey]
$sql->build('insert'|'update', $table, $where = null);          // SQL string, escaped. Needs global $mysqli
$sql->prepared_build('insert'|'update', $table, $where = null); // [query, types, params]. No global needed
$sql->flush(); $sql->status(); $sql->count_keys();              // reset / has columns / count
```
- `$where` required for update, inserted RAW (never user input; cast: `'id=' . (int)$id`).
- Column and table names: backticked; backticks in column names escaped. Never let user input choose a column.
- `que($col, $val, true)` = `que($col, $val, 'string', true)`.
- `$nullable` is only `true` or `'yes'`. `1`/`'true'` = not nullable.

## With Mysqli2 v2.1 (preferred in projects that have it)
```php
$id   = $mysqli->insert('table', $sql);                  // prefix added, returns insert_id
$rows = $mysqli->update('table', $sql, 'id = ?', [$id]); // returns changed rows (0 = no change, not error)
```
Do not call build()/prepared_build() yourself then. Check `$sql->status()` first when columns are added conditionally: empty update = SQL error, empty insert = silent row of defaults.

## Types (3rd arg, case-sensitive)
| type | result | bind |
|---|---|---|
| `autostring` `str` `string` `text` | string | s |
| `int` `tinyint` | (int) cast, `'12 abc'`→12, `''`→0 | i |
| `float` | make_number() float | d |
| `dec` `decimal` | make_number() as numeric string, exact (`'1 234,50'`→`1234.50`, `'500,-'`→500) | s |
| `date` | `Y-m-d` | s |
| `datetime` | `Y-m-d H:i:s` (padded) | s |
| `time` | `H:i:s`; durations like `25:00` kept | s |
| `boolean` | 1/0 via _bool() | i |
| `email` | valid email, else `''` (NULL if nullable) | s |
| `col` `column` | backticked column reference, inline | – |
| `raw` | inserted verbatim, inline. `null`→NULL, `''`→`''` | – |

Suffixes: `ornull` = nullable (`strornull`, `intornull`, `dateornull`, `datetimeornull`, `timeornull`, `decornull`, `floatornull`, `emailornull`; `ornull` alone = strornull). `:n` = cut to n chars before parsing (`string:100`, `strornull:20`). Order: `strornull:20`.

## NULL
- Preferred: PHP `null` + nullable type, or `que($col, 'null', 'raw')`.
- Nullable → NULL for: `null`, `false`, `''`, `'null'`/`'NULL'` (any case). date/datetime also: `0000-00-00…` and invalid dates (invalid time → `00:00:00`, not NULL). Int keeps `0`/`'0'` as 0. String keeps `'0'`.
- Not nullable → never NULL (except raw): `null`→`''` (string) / 0 (int); `'NULL'` stored as text.

## NOW()
`'NOW()'` (any case) → SQL NOW() only in `date`/`datetime`/`time`/`raw`. In string types it is the text `'NOW()'`. Other SQL expressions: `raw` (`'views+1'`, `'CURRENT_TIMESTAMP'`).

## Dates (date/datetime/time)
Accepted: `2026-11-22 03:32:05`, `2026-11-22 3:32:05` (date('G')), `2026-11-22 10:30`, `2026-11-22T10:30` (datetime-local), `2026-11-22`, `22.11.2026 10:30`, `22.11.2026 kl 10:30`, `1/2/26`. Day first unless 4-digit year first. 2-digit year → 20xx. Not supported: month names, `mm/dd/yyyy`, `20261122`. Validated with checkdate. Invalid → NULL if nullable, else `'0000-00-00'` / `'0000-00-00 00:00:00'` (rejected by MySQL with NO_ZERO_DATE → use `*ornull` for optional dates).

## post() modes
`checkbox` (1/0, NULL if nullable+unchecked), `str|string|text|input` (`''` or NULL if nullable), `int` (0 or NULL if nullable), `col|column`. Requires global function `logerror($msg)` (called on non-numeric int / unknown mode). Unknown mode adds nothing if the POST key exists.

## prepared_build() output
```php
$sql->que('name','Kim'); $sql->que('status',null,'ornull'); $sql->que('created','NOW()','datetime'); $sql->que('score','1.5','decimal');
// ['INSERT INTO `t` (`name`, `status`, `created`, `score`) VALUES (?, NULL, NOW(), ?)', 'ss', ['Kim', '1.5']]
```
All-inline query → types `''`, params `[]` (don't call bind_param with empty types).

## Helpers (public)
- `_bool($v)`: false for `false null [] '' 'false' 'no' 'off' 'null' '[]' '{}' '0' '00' 0 0.0` (any case, trimmed); objects and everything else true.
- `make_number($s, $as_string = false)`: strips letters/spaces/`,-`/`.-`, `,`→`.`. `'1.234,50'`→1.234 (dot thousands separator unsupported).
- `sloppydate($s, $action = 'ddmmyyyy')`: `ddmmyyyy`→`22-11-2026`|`00-00-0000`; `sql`/`yyyymmdd`→`2026-11-22`|`0000-00-00`; `int`→20261122|0; `valid`→bool (`''` valid); `datetime2time`→`10:30:00`; `reverse` swaps order with `/`.
- `make_time($s)`: `'9:5'`→`09:05:00`.

## Debug
`$sql->lb()->build(...)`: line breaks. `$sql->lb('set')->build('update',…)`: HTML table view, not SQL. lb() stays on for the object.
