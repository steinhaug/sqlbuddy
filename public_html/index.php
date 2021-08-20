<?php

#$string = 'datetimeornull';
#echo substr($string,-6);
#echo '<br>';
#echo substr($string,0, -6);
#exit;


define('BR','<br>');
require '../vendor/autoload.php';

// The $host,$us,$password,$store
require '../mysqli_credentials.inc';

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





$data = ['updated'=>'NULL'];
echo $sql->flush();
$sql->que('d1', $data['updated']);
$sql->que('d2', $data['updated'], 'date');
$sql->que('d3', $data['updated'], 'dateornull');


echo $sql->build('UPDATE','demo','id=1') . BR;













echo '<div class="blocks"><pre>';

echo '<b>MODES WITH VALUE NULL:</b>' . "\n";
foreach($modes AS $mode){
    $sql->que($mode, 'NULL', $mode);
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



$data = [
    'first' => '', 'last'=>'','age'=>'', 'updated'=>'NOW()'
];
echo $sql->flush();
$sql->que('string',           $data['updated']);
$sql->que('string',           $data['updated'],   true);
$sql->que('date',           $data['updated'],   'datetime');
$sql->que('date',         $data['updated'],   'dateornull');
$sql->que('date',     $data['updated'],   'datetimeornull');
echo $sql->build('UPDATE','demo','id=1') . BR;
$data = [
    'first' => 'NULL', 'last'=>'NULL','age'=>'NULL', 'updated'=>'NULL'
];
echo $sql->flush();
$sql->que('string',           $data['updated']);
$sql->que('string',           $data['updated'],   true);
$sql->que('date',           $data['updated'],   'datetime');
$sql->que('date',         $data['updated'],   'dateornull');
$sql->que('date',     $data['updated'],   'datetimeornull');


echo $sql->build('UPDATE','demo','id=1') . BR;

/*
echo $sql->build('INSERT','demo') . BR;
echo $sql->build('UPDATE','demo','id=1') . BR;
*/




