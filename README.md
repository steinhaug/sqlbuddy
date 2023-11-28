# sqlbuddy

Helper class for making sure SQL inserts and updates are not crashing anything.

## VERSION

v1.3.0 - Updated 28 nov 2023

\* Oppdatert og klart for PHP 8.1  
\+ Added time  

v1.2.0 - Updated 21 des 2021

\* Oppdatert og klart for PHP 8.0  

v1.1.1 - Updated 20 aug 2021

\* rewrote parsing logic, now all parsing will assume: col, val, type, has_null  
\+ Typical values as NULL and NOW() will automatically get set without quotes, automagically.  

v1.0.2 - Updated 2 mai 2021

\* Better handling of NULL.

v1.0.1 - Updated 27 mai 2020

\+ Any type can be forcefully cut on given length by adding suffix :n. Example: string:128 will be a string cut to 128 characters max.

v1.0.0 - Updated 14 may 2020

## USAGE

**Syntax:**

    $sql->que($k, $v, ?$t, ?$n);  

$k = DB Column,  
$v = Value,  
$t = optional - Variable type, int string float etc.  
$n = optional - (bool) has_null. If true evaluates $v as NULL when appropriate  

**Specials**

When using 3'rd param as true, 3 params only:

    $sql->que($k, $v, true);  

Will evaluate as:

    $sql->que($k, $v, 'string', true);  

**Example of usage, with basic features out of the box:**

    $sql = new sqlbuddy;  
    $sql->que('first','Kim Stalsberg');  
    $sql->que('last','Steinhaug');  
    $sql->que('age','44','int');  
    echo $sql->build('update','users','id=1');  
    echo $sql->build('insert','users');

**Outputs:**  

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