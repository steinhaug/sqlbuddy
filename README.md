# sqlbuddy

Helper class for making sure SQL inserts and updates are not crashing anything.

<div class="show_none">

# Table of Contents

- [sqlbuddy](#sqlbuddy)
- [Table of Contents](#table-of-contents)
- [1. Description](#1-description)
- [2. Version History](#2-version-history)
- [3. Usage](#3-usage)
  - [3.1 Syntax](#31-syntax)
  - [3.2 -\>que()](#32--que)
  - [3.3 Specials](#33-specials)
  - [3.4 Example](#34-example)
- [4. Information](#4-information)
  - [4.1 License](#41-license)
  - [4.2 Feel generous?](#42-feel-generous)
  - [4.3 Author](#43-author)

</div>

# 1. Description

A class that handles the data that should be inserted into the database, including some fuzzy logic. 
The class builds the entire SQL query and makes sure that all data is escaped correctly.

# 2. Version History

    v1.3.6 - Updated 1 november 2024
    - Updated readme

    v1.3.5 - Updated 29 august 2024
    - Removed deprecation notice when value passed was null

    v1.3.4 - Updated 22 august 2024
    - Deprecation notice, make sure NULL is not passed to the mb_detect_encoding()

    v1.3.3 - Updated 16 august 2024
    - Bugfix, pseudo logic fix for null values when using string:(int)n

    v1.3.2 - Updated 30 april 2024
    - Property safehtml set to public.  v1.3.1 - Updated 6 des 2023
    + Added unshift()  

    v1.3.0 - Updated 28 nov 2023

    * Oppdatert og klart for PHP 8.1  
    + Added time  ****

    v1.2.0 - Updated 21 des 2021

    * Oppdatert og klart for PHP 8.0  

    v1.1.1 - Updated 20 aug 2021

    * rewrote parsing logic, now all parsing will assume: col, val, type, has_null  
    + Typical values as NULL and NOW() will automatically get set without quotes, automagically.  

    v1.0.2 - Updated 2 mai 2021

    * Better handling of NULL.

    v1.0.1 - Updated 27 mai 2020

    + Any type can be forcefully cut on given length by adding suffix :n. Example: string:128 will be a string cut to 128 characters max.

    v1.0.0 - Updated 14 may 2020

# 3. Usage

## 3.1 Syntax

    $sql->que($k, $v, ?$t, ?$n);  

$k = DB Column,  
$v = Value,  
$t = optional - Variable type, int string float etc.  
$n = optional - (bool) has_null. If true evaluates $v as NULL when appropriate  

## 3.2 ->que()

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


## 3.3 Specials

When using 3'rd param as true, 3 params only:

    $sql->que($k, $v, true);  

Will evaluate as:

    $sql->que($k, $v, 'string', true);  

## 3.4 Example

    // typical usage
    $sql = new sqlbuddy;  
    $sql->que('first','Kim Stalsberg');  
    $sql->que('last','Steinhaug');  
    $sql->que('age','44','int');  
    echo $sql->build('update','users','id=1');  
    echo $sql->build('insert','users');

    // outputs:
    UPDATE `users` SET `first`='Kim', `last`='Steinhaug', `age`=44 WHERE id=1;
    INSERT INTO `demo` (`first`, `last`, `age`) VALUES ('Kim', 'Steinhaug', 44)

# 4. Information

## 4.1 License

This project is licensed under the terms of the  [MIT](http://www.opensource.org/licenses/mit-license.php) License. Enjoy!

## 4.2 Feel generous?

Buy me a beer, [donate](https://steinhaug.com/donate/).

## 4.3 Author

Kim Steinhaug, steinhaug at gmail dot com.

**Sosiale lenker:**
[LinkedIn](https://www.linkedin.com/in/steinhaug/), [SoundCloud](https://soundcloud.com/steinhaug), [Instagram](https://www.instagram.com/steinhaug), [Youtube](https://www.youtube.com/@kimsteinhaug), [X](https://x.com/steinhaug), [Ko-Fi](https://ko-fi.com/steinhaug), [Github](https://github.com/steinhaug), [Gitlab](https://gitlab.com/steinhaug)

**Generative AI lenker:**
[Udio](https://www.udio.com/creators/Steinhaug), [Suno](https://suno.com/@steinhaug), [Huggingface](https://huggingface.co/steinhaug)

**Resurser og hjelpesider:**
[Linktr.ee/steinhaugai](https://linktr.ee/steinhaugai), [Linktr.ee/stainhaug](https://linktr.ee/stainhaug), [pinterest/steinhaug](https://no.pinterest.com/steinhaug/), [pinterest/stainhaug](https://no.pinterest.com/stainhaug/)
