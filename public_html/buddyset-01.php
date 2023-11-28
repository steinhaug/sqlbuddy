<?php

$id = 99;

$sql = new sqlbuddy;
$sql->que($_POST['name'], $_POST['value'], 'string');

echo htmlentities(
    "UPDATE `table` SET " . $sql->output('set') . " WHERE `id`=" . (int) $id
);


