<?php
/**
 * Regression checks for normalize() via prepared_build() - no database needed.
 * Run: php test/regression.php
 */
require dirname(__DIR__) . '/sqlbuddy.php';

$fail = 0;
function check($label, $mode, $val, $nullable, $expectSlot, $expectParams = [])
{
    global $fail;
    $s = new sqlbuddy();
    $s->que('c', $val, $mode, $nullable);
    [$q, , $p] = $s->prepared_build('insert', 't');
    $slot = substr($q, strpos($q, 'VALUES (') + 8, -1);
    if ($slot !== $expectSlot or $p !== $expectParams) {
        $fail++;
        echo "FAIL $label: got $slot " . json_encode($p) . ", want $expectSlot " . json_encode($expectParams) . "\n";
    }
}

// datetime: lenient input, padded output
check('datetime G-hour',     'datetime', '2026-11-22 3:32:05', false, '?', ['2026-11-22 03:32:05']);
check('datetime no seconds', 'datetime', '2026-11-22 10:30',   false, '?', ['2026-11-22 10:30:00']);
check('datetime only date',  'datetime', '2026-11-22',         false, '?', ['2026-11-22 00:00:00']);
check('datetime-local',      'datetime', '2026-11-22T10:30',   false, '?', ['2026-11-22 10:30:00']);
check('datetime norwegian',  'datetime', '22.11.2026 10:30',   false, '?', ['2026-11-22 10:30:00']);
check('datetime invalid',    'datetime', 'abc',                false, '?', ['0000-00-00 00:00:00']);
check('datetime invalid null','datetimeornull', 'abc',         false, 'NULL');
check('date impossible',     'dateornull', '2026-02-30',       false, 'NULL');
check('date norwegian',      'date', '1/2/26',                 false, '?', ['2026-02-01']);
check('time from datetime',  'time', '2026-11-22 3:32:05',     false, '?', ['03:32:05']);
check('time duration kept',  'time', '25:00',                  false, '?', ['25:00:00']);

// NOW() any case, in date/datetime/time
check('now() datetime',      'datetime', 'now()', false, 'NOW()');
check('now() date',          'date',     'Now()', false, 'NOW()');
check('NOW() string stays',  'string',   'NOW()', false, '?', ['NOW()']);

// NULL rules
check("'NULL' nullable",     'strornull', 'NULL', false, 'NULL');
check("'NULL' not nullable", 'string',    'NULL', false, '?', ['NULL']);
check("'null' raw",          'raw',       'null', false, 'null');
check('php null not nullable binds empty', 'string', null, false, '?', ['']);
check("'0' strornull kept",  'strornull', '0', false, '?', ['0']);
check("'' strornull",        'strornull', '',  false, 'NULL');
check('0 intornull kept',    'intornull', '0', false, '?', [0]);
check('raw null',            'raw', null, false, 'NULL');
check('raw empty',           'raw', '',   false, "''");

// decimal keeps precision
check('decimal big',         'decimal', '99999999999999.999999', false, '?', ['99999999999999.999999']);
check('decimal NOK',         'dec',     '1 234,50', false, '?', ['1234.50']);
check('decimal junk',        'dec',     'abc',      false, '?', ['0']);

// identifiers
check('col backtick',        'col', 'a`b', false, '`a``b`');

// sloppydate() public actions
$sd = new sqlbuddy();
foreach ([
    ['22.11.26', 'ddmmyyyy', '22-11-2026'],
    ['2026-11-22 10:30:00', 'ddmmyyyy', '22-11-2026'],
    ['22 11 2026', 'ddmmyyyy', '22-11-2026'],      // was a fatal ValueError
    ['abc', 'ddmmyyyy', '00-00-0000'],              // was a fatal ValueError
    ['22.11.2026 kl 10:30', 'sql', '2026-11-22'],
    ['31.02.2026', 'sql', '0000-00-00'],
    ['2026-11-22', 'reverse', '22/11/2026'],
    ['1.2.2026', 'reverse', '2026/02/01'],
    ['22.11.2026', 'int', 20261122],
    ['abc', 'valid', false],                        // was true
    ['', 'valid', true],
    ['22.11.2026 10.30', 'datetime2time', '10:30:00'],
    ['2026-11-22 9:5', 'datetime2time', '09:05:00'],
] as [$in, $action, $want]) {
    if (($got = $sd->sloppydate($in, $action)) !== $want) {
        $fail++;
        echo "FAIL sloppydate('$in', '$action'): got " . var_export($got, true) . ', want ' . var_export($want, true) . "\n";
    }
}

try {
    $s = new sqlbuddy(); $s->que('price', 1, 'bogus'); $s->prepared_build('insert', 't');
    $fail++; echo "FAIL unknown handler: no exception\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), '`price`') === false) { $fail++; echo "FAIL unknown handler message: {$e->getMessage()}\n"; }
}

echo $fail ? "$fail failed\n" : "all passed\n";
exit($fail ? 1 : 0);
