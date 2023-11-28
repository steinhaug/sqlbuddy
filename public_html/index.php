<?php
header("Content-Type: text/html; charset=UTF-8");

define('BR', '<br>');
require '../vendor/autoload.php';

// The $host,$us,$password,$store
require '../mysqli_credentials.inc';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = mysqli_connect($host, $us, $password, $store);
if (mysqli_connect_errno()) {
    echo 'Problemer med databasen, sjekk konfigurasjon!';
    exit();
}
if ($mysqli->character_set_name() != 'utf8') {
    if (!$mysqli->set_charset("utf8")) {
        printf("Error loading character set utf8: %s\n", $mysqli->error);
        exit();
    }
}

$modes = [
    'ornull', 'strornull', 'intornull', 'dateornull', 'datetimeornull',
    'str', 'string', 'text', 'autostring', 
    'int', 'tinyint', 
    'float', 
    'dec', 'decimal', 
    'date', 
    'time',
    'datetime',
    'raw',
    'boolean', 
    'email', 
    'col', 'column',
];

echo '<h1>sqlbuddy test suit v1.3.0</h1>';

$sql = new sqlbuddy();

echo $sql->flush();

echo '<style>
div.blocks pre {
    width: 300px;
    display: block;
    border: 1px solid #888;
    float: left;
}
div.blocks::after {
  content: "";
  clear: both;
  display: table;
}
</style>
';




/*
$data = ['updated' => 'NULL'];
echo $sql->flush();
$sql->que('d1', $data['updated']);
$sql->que('d2', $data['updated'], 'date');
$sql->que('d3', $data['updated'], 'dateornull');


echo $sql->build('UPDATE', 'demo', 'id=1') . BR;
*/












echo '<div class="blocks"><pre>';

echo '<b>MODES WITH VALUE NULL:</b>' . "\n";
foreach ($modes as $mode) {
    $sql->que($mode, 'NULL', $mode);
}
echo $sql->lb('set')->build('UPDATE', 'demo', 'id=1') . "\n\n";
echo $sql->flush();

echo "</pre><pre>";

echo '<b>MODES WITH VALUE 1 and nullable:</b>' . "\n";
foreach ($modes as $mode) {
    $sql->que($mode, '1', $mode, true);
}
echo $sql->lb('set')->build('UPDATE', 'demo', 'id=1') . "\n\n";
echo $sql->flush();

echo "</pre><pre>";

echo '<b>MODES WITH VALUE a and nullable:</b>' . "\n";
foreach ($modes as $mode) {
    $sql->que($mode, 'a', $mode, true);
}
echo $sql->lb('set')->build('UPDATE', 'demo', 'id=1') . "\n\n";
echo $sql->flush();

echo "</pre><pre>";

echo '<b>MODES WITH VALUE a:</b>' . "\n";
foreach ($modes as $mode) {
    $sql->que($mode, 'a', $mode);
}
echo $sql->lb('set')->build('UPDATE', 'demo', 'id=1') . "\n\n";

echo "</pre></div>";



echo '<br>';


echo '<div class="blocks"><pre>';
echo $sql->flush();
$sql->que('_', 'now()');
$sql->que('true', 'now()', true);
$sql->que('raw', 'now()', 'raw');
$sql->que('dateornull', 'now()', 'dateornull');
$sql->que('datetimeornull', 'now()', 'datetimeornull');
echo $sql->lb('set')->build('UPDATE', 'demo', 'id=1') . "\n\n";

echo "</pre><pre>";

echo $sql->flush();
echo '<b>MODES WITH VALUE NOW():</b>' . "\n";
foreach ($modes as $mode) {
    $sql->que($mode, 'NOW()', $mode);
}
echo $sql->lb('set')->build('UPDATE', 'demo', 'id=1') . "\n\n";

echo "</pre><pre>";

echo $sql->flush();
echo '<b>MODES WITH VALUE NOW() and nullable:</b>' . "\n";
foreach ($modes as $mode) {
    $sql->que($mode, 'NOW()', $mode, true);
}
echo $sql->lb('set')->build('UPDATE', 'demo', 'id=1') . "\n\n";

echo "</pre><pre>";

echo $sql->flush();
echo '<b>MODES WITH VALUE Kim\'s "!":</b>' . "\n";
foreach ($modes as $mode) {
    $sql->que($mode, 'Kim\'s "!"', $mode);
}
echo $sql->lb('set')->build('UPDATE', 'demo', 'id=1') . "\n\n";

echo "</pre><pre>";

echo $sql->flush();
echo '<b>MODES WITH VALUE Kim\'s "!" and nullable:</b>' . "\n";
foreach ($modes as $mode) {
    $sql->que($mode, 'Kim\'s "!"', $mode, true);
}
echo $sql->lb('set')->build('UPDATE', 'demo', 'id=1') . "\n\n";


echo "</pre></div>";

/*
echo $sql->build('INSERT','demo') . BR;
echo $sql->build('UPDATE','demo','id=1') . BR;
*/
