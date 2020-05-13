# sqlbuddy

Helper class for making sure SQL inserts and updates are not crashing anything.

$sql = new sqlbuddy;  
$sql->que('first',  'Kim Stalsberg');  
$sql->que('last',   'Steinhaug');  
$sql->que('age',    '44');  
$sql = $sql->build('update','users','id=1');  

Outputs:  
`UPDATE` \`users\` `SET` \`first\`='Kim', \`last\`='Steinhaug', \`age\`=44 `WHERE` id=1;


