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
  - [3.5 Prepared statements](#35-prepared-statements)
- [4. Information](#4-information)
  - [4.1 License](#41-license)
  - [4.2 Feel generous?](#42-feel-generous)
  - [4.3 Author](#43-author)

</div>

# 1. Description

A class that handles the data that should be inserted into the database, including some fuzzy logic. 
The class builds the entire SQL query and makes sure that all data is escaped correctly.

Since v1.4.0 the same queued data can be emitted two ways: as a finished, escaped SQL string via `->build()` (the classic mode), or as a prepared-statement tuple via `->prepared_build()`. Both share one internal engine, so they always agree on how a value is treated.

# 2. Version History

    v1.4.1 - Updated 25 june 2026
    + Added prepared_build() - emits [query, types, params] for prepared statements.
      Shares the normalize() engine with build(), so value handling is identical.
      Inline-only modes (NULL, NOW(), col/column, raw) stay literal in the query;
      everything else becomes a bound ? parameter.

    v1.4.0 - Updated 25 june 2026
    * Refactored output() onto a single normalize() engine. Removed ~200 lines of
      duplicated type-switch between the values and set emitters.
    * Bugfix: the INSERT value path now matches the documented test set for nullable
      date/datetime - it previously emitted '0000-00-00' where it should emit NULL.
    * Bugfix: mb_detect_encoding() is no longer called on a null value in the set path.

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
    $sql->que('first','Kim');  
    $sql->que('last','Steinhaug');  
    $sql->que('age','44','int');  
    echo $sql->build('update','users','id=1');  
    echo $sql->build('insert','users');

    // outputs:
    UPDATE `users` SET `first`='Kim', `last`='Steinhaug', `age`=44 WHERE id=1;
    INSERT INTO `demo` (`first`, `last`, `age`) VALUES ('Kim', 'Steinhaug', 44)

## 3.5 Prepared statements

`->prepared_build()` is the prepared-statement sibling of `->build()`. The data is queued exactly the same way with `->que()` / `->push()`; only the final call differs. It returns a three-element array ready for a prepared-statement wrapper:

    // [ (string) query, (string) types, (array) values ]
    $parts = $sql->prepared_build('insert', 'table_name');
    $parts = $sql->prepared_build('update', 'table_name', 'id=' . $id);

`types` uses the mysqli letters: `i` integer, `s` string, `d` double. Decimal and float both bind as `d`.

Drop-in against a wrapper that accepts the tuple:

    $inserted_id   = $mysqli->prepared_insert( $sql->prepared_build('insert', 'table_name') );
    $affected_rows = $mysqli->prepared_insert( $sql->prepared_build('update', 'table_name', 'id=' . $id) );

**What stays inline vs. what becomes a parameter**

Most values become a bound `?`. Four cases can never be bound and are written literally into the query string instead:

- `raw` mode (e.g. an explicit SQL function or literal you placed yourself)
- `col` / `column` mode (a backtick-quoted column reference)
- a real `NULL` (from `nullable` handling, the `*ornull` modes, or the literal string `'NULL'`)
- `NOW()` **but only** in `date`, `datetime` or `raw` mode

Note the `NOW()` rule: it is auto-recognized as a raw SQL function only inside the date/datetime/raw modes. In `string` / `autostring` mode the text `'NOW()'` is treated as an ordinary string value and becomes a bound parameter - it is not turned into the SQL function. Only the literal `'NULL'` is recognized across every mode.

Example:

    $sql = new sqlbuddy;
    $sql->que('name',    'Kim');
    $sql->que('status',  'NULL', 'ornull');   // real NULL  -> inline
    $sql->que('created', 'NOW()', 'datetime'); // SQL NOW()  -> inline
    $sql->que('score',   '1.5', 'decimal');
    $sql->que('hits',    0, 'int');

    $parts = $sql->prepared_build('insert', 'stats');

    // $parts:
    // [
    //   'INSERT INTO `stats` (`name`, `status`, `created`, `score`, `hits`)
    //        VALUES (?, NULL, NOW(), ?, ?)',
    //   'sdi',
    //   ['Kim', 1.5, 0]
    // ]

Values are bound **unescaped** - mysqli escapes them at bind time. The `WHERE` clause passed to an update is taken raw and is **not** parameterized, identical to `->build()`.

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