# sqlbuddy

Helper class for making sure SQL inserts and updates are not crashing anything.

## VERSION

v1.0.0 - Updated 14 may 2020

## USAGE

Example of usage, with basic features out of the box:

    $sql = new sqlbuddy;  
    $sql->que('first','Kim Stalsberg');  
    $sql->que('last','Steinhaug');  
    $sql->que('age','44','int');  
    echo $sql->build('update','users','id=1');  
    echo $sql->build('insert','users');

Outputs:  

    UPDATE `users` SET `first`='Kim', `last`='Steinhaug', `age`=44 WHERE id=1;
    INSERT INTO `demo` (`first`, `last`, `age`) VALUES ('Kim', 'Steinhaug', 44)

## SYNTAX

### ->que()

_$sql->que(_ `string` $columnName, `string` $value, `string` $valueType, `boolean` $nullable )

`columnName`  
Name of column to insert/ update  

`value`  
the value to be inserted  

`valueType`  
Optional, default string. 

Possible values are **str**, **string**, **text**, **email**, **float**, **ornull**, **strornull**, **int**, **tinyint**, **intornull**, **dec**, **decimal**, **date**, **dateornull**, **datetime**, **datetimeornull**, **raw**, **boolean**, **column**, **col**.

`nullable`  
Boolean statment for the value being considered a NULL, in which the insert or update will insert a real mysql NULL.

## INSTALLATION

Install the [composer package](https://packagist.org/packages/steinhaug/sqlbuddy):

    > composer require steinhaug/sqlbuddy

Or download the [latest release](https://github.com/steinhaug/sqlbuddy/releases/latest) and include sqlbuddy.php.

## AUTHORS

[Kim Steinhaug](https://github.com/steinhaug) \([@steinhaug](https://twitter.com/steinhaug)\)


## LICENSE

This library is released under the MIT license.

## Feel generous?

Buy me a beer, [donate](https://steinhaug.com/donate/).