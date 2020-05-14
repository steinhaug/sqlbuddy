<?php

define('BR','<br>');
require '../vendor/autoload.php';

// The $host,$us,$password,$store
require 'mysqli_credentials.inc';

//header("Content-Type: text/plain");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = mysqli_connect($host,$us,$password,$store);
if (mysqli_connect_errno()){
    echo 'Problemer med databasen, sjekk konfigurasjon!';
    exit();
}
if( $mysqli->character_set_name() != 'utf8' ){
    if (!$mysqli->set_charset("utf8")) {
        printf("Error loading character set utf8: %s\n", $mysqli->error);
        exit();
    }
}

$modes = ['str','string','text','email','float','ornull','strornull','int','tinyint','intornull','dec','decimal','date','dateornull','datetime','datetimeornull','raw','boolean','column','col'];


echo '<h1>sqlbuddy test suit v1.0.0</h1>';

$sql = new sqlbuddy;

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

echo '<div class="blocks"><pre>';

echo '<b>MODES WITH VALUE 1:</b>' . "\n";
foreach($modes AS $mode){
    $sql->que($mode, '1', $mode);
}
echo htmlentities( $sql->lb()->build('UPDATE','demo','id=1') . "\n\n" );
echo $sql->flush();
echo "</pre><pre>";

echo '<b>MODES WITH VALUE 1 and nullable:</b>' . "\n";
foreach($modes AS $mode){
    $sql->que($mode, '1', $mode, true);
}
echo htmlentities( $sql->lb()->build('UPDATE','demo','id=1') . "\n\n" );
echo $sql->flush();
echo "</pre><pre>";

echo '<b>MODES WITH VALUE a and nullable:</b>' . "\n";
foreach($modes AS $mode){
    $sql->que($mode, 'a', $mode, true);
}
echo htmlentities( $sql->lb()->build('UPDATE','demo','id=1') . "\n\n" );
echo $sql->flush();
echo "</pre><pre>";

echo '<b>MODES WITH VALUE a:</b>' . "\n";
foreach($modes AS $mode){
    $sql->que($mode, 'a', $mode);
}
echo htmlentities( $sql->lb()->build('UPDATE','demo','id=1') . "\n\n" );

echo "</pre></div>";

echo '<br>';

echo $sql->flush();
$sql->que('first','Kim');
$sql->que('last','Steinhaug');

echo $sql->build('INSERT','demo') . BR;
echo $sql->build('UPDATE','demo','id=1') . BR;
