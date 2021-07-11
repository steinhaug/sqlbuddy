<?php


/**
 * My personal SQL friend, making sure insert's and update's doesnt mess anything up.
 * 
 * (c) Kim Steinhaug
 * http://steinhaug.no/
 * 
 */
class sqlbuddy {

    const version = '1.0.2';

    private $keys  = [];
    private $vals  = [];
    private $ints  = [];
    private $nulls = [];

    private $safehtml = false;
    private $lb_mode = false;

    public function __construct(){
    }

    public function flush(){
        $this->keys  = [];
        $this->vals  = [];
        $this->ints  = [];
        $this->nulls = [];
    }

    public function is_nullable($var){

        if($var === true)
            return true;

        if($var === false)
            return false;

        $var = strtolower($var);

        if($var == 'yes')
            return true;

        return false;
    }

    public function considered_null($val){

        if( $val === false)
            return true;

        if( !strlen($val) )
            return true;

        if( strtolower($val) === 'null')
            return true;

        return false;
    }

    public function count_keys(){
        return count($this->keys);
    }

    public function que($k,$v,$i='string',$n='NO'){
    
        if( mb_strpos($i,':')!==false ){
            $tmp = explode(':',$i);
            $i = $tmp[0];
            $len = (int) $tmp[1];
            if( $len AND mb_strlen($v) AND (mb_strlen($v) > $len) ){
                $v = mb_substr($v,0,$len);
            }
        }

        array_push($this->keys,     $k);
        array_push($this->vals,     $v);
        array_push($this->ints,     $i);
        array_push($this->nulls,    $this->is_nullable($n));
    }

    /**
     * Alias of que
     */
    public function push($k,$v,$i='string',$n='NO'){
    
        if( mb_strpos($i,':')!==false ){
            $tmp = explode(':',$i);
            $i = $tmp[0];
            $len = (int) $tmp[1];
            if( $len AND mb_strlen($v) AND (mb_strlen($v) > $len) ){
                $v = mb_substr($v,0,$len);
            }
        }

        array_push($this->keys,     $k);
        array_push($this->vals,     $v);
        array_push($this->ints,     $i);
        array_push($this->nulls,    $this->is_nullable($n));
    }

    public function post($k, $v, $mode, $null_enabled=false){

        $string_max_length = 0;
        if( mb_strpos($mode,':')!==false ){
            $tmp = explode(':',$mode);
            $mode = $tmp[0];
            $string_max_length = (int) $tmp[1];
        }

        switch ($mode) {
            case 'checkbox':
                if( !isset($_POST[$v]) OR (isset($_POST[$v]) AND !$_POST[$v]) ){
                    if($null_enabled){
                        array_push($this->keys, $k);
                        array_push($this->vals, 'null');
                        array_push($this->ints, 'raw');
                        array_push($this->nulls, true);
                        break;
                    } else {
                        array_push($this->keys, $k);
                        array_push($this->vals, 0);
                        array_push($this->ints, 'int');
                        array_push($this->nulls, false);
                        break;
                    }
                }
                array_push($this->keys, $k);
                array_push($this->vals, 1);
                array_push($this->ints, 'int');
                array_push($this->nulls, false);
                break;
            case 'str':
            case 'string':
            case 'text':
            case 'input':
                if( !isset($_POST[$v]) OR (isset($_POST[$v]) AND (strlen($_POST[$v]) == 0)) ){
                    if($null_enabled){
                        array_push($this->keys, $k);
                        array_push($this->vals, 'null');
                        array_push($this->ints, 'raw');
                        array_push($this->nulls, true);
                        break;
                    } else {
                        array_push($this->keys, $k);
                        array_push($this->vals, '');
                        array_push($this->ints, 'string');
                        array_push($this->nulls, false);
                        break;
                    }
                }
                if($null_enabled AND $this->considered_null($_POST[$v])){
                    array_push($this->keys, $k);
                    array_push($this->vals, 'null');
                    array_push($this->ints, 'raw');
                    array_push($this->nulls, true);
                    break;
                } else {
                    if($string_max_length){
                        $_POST[$v] = mb_substr($_POST[$v],0,$string_max_length);
                    }
                    array_push($this->keys, $k);
                    array_push($this->vals, $_POST[$v]);
                    array_push($this->ints, 'string');
                    array_push($this->nulls, false);
                    break;
                }
                break;
            case 'int':
                if( !isset($_POST[$v]) ){
                    if($null_enabled){
                        array_push($this->keys, $k);
                        array_push($this->vals, 'null');
                        array_push($this->ints, 'raw');
                        array_push($this->nulls, true);
                        break;
                    } else {
                        array_push($this->keys, $k);
                        array_push($this->vals, 0);
                        array_push($this->ints, 'int');
                        array_push($this->nulls, false);
                        break;
                    }
                }
                if($null_enabled AND $this->considered_null($_POST[$v])){
                    array_push($this->keys, $k);
                    array_push($this->vals, 'null');
                    array_push($this->ints, 'raw');
                    array_push($this->nulls, true);
                    break;
                } else {
                    if( !is_numeric($_POST[$v]) ){
                        logerror('sqlbuddy post mode int, not numeric: ' . htmlentities($_POST[$v]));
                    }
                    array_push($this->keys, $k);
                    array_push($this->vals, $_POST[$v]);
                    array_push($this->ints, 'int');
                    array_push($this->nulls, false);
                    break;
                }
                break;
            case 'column':
            case 'col':
                    array_push($this->keys, $k);
                    array_push($this->vals, $_POST[$v]);
                    array_push($this->ints, 'column');
                    array_push($this->nulls, false);
                    break;
                default:
                logerror('sqlbuddy post mode does not exist: ' . $mode);
                if( !isset($_POST[$v]) ){
                    if($null_enabled){
                        array_push($this->keys, $k);
                        array_push($this->vals, 'null');
                        array_push($this->ints, 'raw');
                        array_push($this->nulls, true);
                        break;
                    } else {
                        array_push($this->keys, $k);
                        array_push($this->vals, '');
                        array_push($this->ints, 'string');
                        array_push($this->nulls, false);
                        break;
                    }
                }
                break;
        }

    }

    public function status(){
        if(count($this->keys))
            return true;
            else
            return false;
    }

    public function output($m){
        global $mysqli;

        if($this->safehtml){
            require_once $GLOBALS['serverpaths']['ecms.lib'] . '/index.HTMLSax3.cleanup.php';
        }

        $output = '';
        if($m=='keys'){

            for($i=0;$i<count($this->keys);$i++){
                if($i)
                    $output .= ', ';
                $output .= '`' . $this->keys[$i] . '`';
            }

        } else if($m=='values'){

            for($i=0;$i<count($this->vals);$i++){

                if($i) $output .= ', ';
                if($this->safehtml){ // XSS_FIX
                    $safehtml = new safehtml();
                    $this->vals[$i] = $safehtml->parse($this->vals[$i]);
                    unset($safehtml);
                }

                // If the detection fails, we re-detect with a different set of encodings to check for 
                $from = mb_detect_encoding($this->vals[$i]);
                if( $from === false ){
                    $from = mb_detect_encoding($this->vals[$i], 'CP1252, ISO-8859-1, Windows-1251, ASCII, UTF-8');
                }
                if($from != 'UTF-8')
                    $this->vals[$i] = mb_convert_encoding($this->vals[$i], 'UTF-8', $from);

                switch ($this->ints[$i]) {
                    case 'str':
                    case 'string':
                    case 'text':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) ){
                            $output .= "NULL";
                        } else {
                            $output .= "'" . mysqli_real_escape_string($mysqli, $this->vals[$i]) . "'";
                        }
                        break;
                    case 'email':
                        if (filter_var($this->vals[$i], FILTER_VALIDATE_EMAIL)){
                            $output .= "'" . mysqli_real_escape_string($mysqli, $this->vals[$i]) . "'";
                        } else if($this->nulls[$i]){
                            $output .= "NULL";
                        } else {
                            $output .= "''";
                        }
                        break;
                    case 'float':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= "'" . (float) $this->make_number($this->vals[$i]) . "'";
                        break;
                    case 'ornull':
                    case 'strornull':
                        if(empty($this->vals[$i]))
                            $output .= "NULL";
                            else
                            $output .= "'" . mysqli_real_escape_string($mysqli, $this->vals[$i]) . "'";
                        break;
                    case 'int':
                    case 'tinyint':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= (int) $this->vals[$i];
                        break;
                    case 'intornull':
                        if($this->vals[$i] === 0){
                            $output .= (int) $this->vals[$i];
                        } else if($this->vals[$i] === '0'){
                            $output .= (int) $this->vals[$i];
                        } else if($this->considered_null( $this->vals[$i] )){
                            $output .= "NULL";
                        } else if(empty($this->vals[$i])){
                            $output .= "NULL";
                        } else {
                            $output .= (int) $this->vals[$i];
                        }
                        break;
                    case 'dec':
                    case 'decimal':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= $this->make_number($this->vals[$i]);
                        break;
                    case 'date':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= "'" . $this->sloppydate($this->vals[$i],'sql') . "'";
                        break;
                    case 'dateornull':
                        if($this->considered_null( $this->vals[$i] )){
                            $output .= "NULL";
                        } else if($this->vals[$i] == '0000-00-00'){
                            $output .= "NULL";
                        } else {
                            $output .= "'" . $this->sloppydate($this->vals[$i],'sql') . "'";
                        }
                        break;
                    case 'datetime':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= "'" . $this->sloppydate($this->vals[$i],'sql') . ' ' . $this->sloppydate($this->vals[$i],'datetime2time') . "'";
                        break;
                    case 'datetimeornull':
                        if($this->considered_null( $this->vals[$i] )){
                            $output .= "NULL";
                        } else {
                            $output .= "'" . $this->sloppydate($this->vals[$i],'sql') . ' ' . $this->sloppydate($this->vals[$i],'datetime2time') . "'";
                        }
                        break;
                    case 'raw':
                        $output .= $this->vals[$i];
                        break;
                    case 'boolean':
                        if( $this->_bool($this->vals[$i]) )
                            $output .= 1;
                            else
                            $output .= 0;
                        break;
                    case 'column':
                    case 'col':
                        $output .= "`" . $this->vals[$i] . "`";
                        break;

                    default:
                        throw new Exception('sqlbuddy unknown handler: "' . $this->ints[$i] . '"');
                        break;
                }
            }
        } else if($m=='set'){

            for($i=0;$i<count($this->keys);$i++){

                if($this->lb_mode AND $i) $output .= ', ' . "\n";
                else if($i) $output .= ', ';

                $output .= '`' . $this->keys[$i] . '`';
                $output .= '=';
                if($this->safehtml){ // XSS_FIX
                    $safehtml = new safehtml();
                    $this->vals[$i] = $safehtml->parse($this->vals[$i]);
                    unset($safehtml);
                }

                $from = mb_detect_encoding($this->vals[$i]);
                if( $from === false ){
                    $from = mb_detect_encoding($this->vals[$i], 'CP1252, ISO-8859-1, Windows-1251, ASCII, UTF-8');
                }
                if($from != 'UTF-8')
                    $this->vals[$i] = mb_convert_encoding($this->vals[$i],'UTF-8',$from);

                switch ($this->ints[$i]) {
                    case 'str':
                    case 'string':
                    case 'text':
                    if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) ){
                            $output .= "NULL";
                        } else {
                            $output .= "'" . mysqli_real_escape_string($mysqli, $this->vals[$i]) . "'";
                        }
                        break;
                    case 'email':
                        if (filter_var($this->vals[$i], FILTER_VALIDATE_EMAIL)) {
                            $output .= "'" . mysqli_real_escape_string($mysqli, $this->vals[$i]) . "'";
                        } else if($this->nulls[$i]){
                            $output .= "NULL";
                        } else {
                            $output .= "''";
                        }
                        break;
                    case 'float':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= "'" . (float) $this->make_number($this->vals[$i]) . "'";
                        break;
                    case 'ornull':
                    case 'strornull':
                        if(empty($this->vals[$i]))
                            $output .= "NULL";
                            else
                            $output .= "'" . mysqli_real_escape_string($mysqli, $this->vals[$i]) . "'";
                        break;
                    case 'int':
                    case 'tinyint':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= (int) $this->vals[$i];
                        break;
                    case 'intornull':
                        if($this->vals[$i] === 0){
                            $output .= (int) $this->vals[$i];
                        } else if($this->vals[$i] === '0'){
                            $output .= (int) $this->vals[$i];
                        } else if($this->considered_null( $this->vals[$i] )){
                            $output .= "NULL";
                        } else if(empty($this->vals[$i])){
                            $output .= "NULL";
                        } else {
                            $output .= (int) $this->vals[$i];
                        }
                        break;
                    case 'dec':
                    case 'decimal':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= $this->make_number($this->vals[$i]);
                        break;
                    case 'date':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= "'" . $this->sloppydate($this->vals[$i],'sql') . "'";
                        break;
                    case 'dateornull':
                        if($this->considered_null( $this->vals[$i] )){
                            $output .= "NULL";
                        } else if($this->vals[$i] == '0000-00-00'){
                            $output .= "NULL";
                        } else {
                            $output .= "'" . $this->sloppydate($this->vals[$i],'sql') . "'";
                        }
                        break;
                    case 'datetime':
                        if( $this->nulls[$i] AND $this->considered_null( $this->vals[$i] ) )
                            $output .= "NULL";
                            else
                            $output .= "'" . $this->sloppydate($this->vals[$i],'sql') . ' ' . $this->sloppydate($this->vals[$i],'datetime2time') . "'";
                        break;
                    case 'datetimeornull':
                        if($this->considered_null( $this->vals[$i] )){
                            $output .= "NULL";
                        } else {
                            $output .= "'" . $this->sloppydate($this->vals[$i],'sql') . ' ' . $this->sloppydate($this->vals[$i],'datetime2time') . "'";
                        }
                        break;
                    case 'raw':
                        $output .= $this->vals[$i];
                        break;
                    case 'boolean':
                        if( $this->_bool($this->vals[$i]) )
                            $output .= 1;
                            else
                            $output .= 0;
                        break;
                    case 'column':
                    case 'col':
                        $output .= "`" . $this->vals[$i] . "`";
                        break;
                    default:
                        throw new Exception('sqlbuddy unknown handler: "' . $this->ints[$i] . '"');
                        break;
                }
            }
        }

        return $output;
    }

    public function build($what, $tablename, $where_match = null){

        if( strtolower($what) == 'update' ){
            if( $where_match === null )
                throw new Exception('sqlbuddy missing where match for building update query');

            $sql  = 'UPDATE `' . $tablename . '` SET ';

            if($this->lb_mode)
                $sql .= "\n";

            $sql .= $this->output('set') . ' ';

            if($this->lb_mode)
                $sql .= "\n";

            $sql .= 'WHERE ' . $where_match;

            return $sql;

        } else if( strtolower($what) == 'insert' ){

            $sql = 'INSERT INTO `' . $tablename . '` (' . $this->output('keys') . ') VALUES (' . $this->output('values') . ')';
            return $sql;

        } else {

            throw new Exception('sqlbuddy build error, invalid type. Only UPDATE and INSERT allowed');

        }

    }

    public function lb(){

        $this->lb_mode = true;

        return $this;
    }

    /**
     * Force numerical float by fuzzy logic
     * 
     * @param {string} $string The number
     * @return mixed Either an int or float.
     */
    public function make_number($string){

        $string = trim(preg_replace("/[A-Za-z]/", "", (string) $string));

        // Remove whitespace from the string
        $string = str_replace("\xa0",' ',$string); // Just in case!
        $string = trim(preg_replace("/\s/", "", $string));

        if(preg_match("/,-$/",$string))  // Remove typical NOK setup: 500,-
            $string = substr($string,0,-2);
        if(preg_match("/.-$/",$string))  // Remove typical SEK setup: 500.-
            $string = substr($string,0,-2);

        $string = str_replace(',','.',$string);

        $pos = strpos($string, '.');
        if($pos === false)
            return (int) $string;
            else
            return (float) $string;
    }


    function sloppydate($k,$action='ddmmyyyy',$boolean=true){
        $del = '-';
        $k = trim($k);

        if(strpos($k, 'T')!==false)
            $k = str_replace('T',' ',$k);

        if($action=='fix'){
            // If we have 0000-00-00 00:00:00 we need to remove the time
            if(preg_match('/:/',$k)){ // I expect there to be a space between date and time
                $t = explode(' ',$k);
                if(count($t)!=2)
                return '0000-00-00';
                $k = $t[0];
            }

            $k = str_replace('.','/',$k);
            $k = str_replace(',','/',$k);
            $k = str_replace(' ','/',$k);
            $k = str_replace('-','/',$k);

            // If no slash probably a number,
            // so we failsafe atleast 0000-00-00
            if(!preg_match('/\//',$k))
                return '0000-00-00';

            $t = explode('/',$k);
            if(count($t) != 3)
                return '0000-00-00';
            if(strlen($t[0])!=4){
                if(strlen($t[2])==2) $t[2] = '20' . $t[2]; else
                if(strlen($t[2])==1) $t[2] = '200' . $t[2];
                return str_pad($t[0], 2, '0', STR_PAD_LEFT) . '/' . str_pad($t[1], 2, '0', STR_PAD_LEFT) . '/' . $t[2];
            } else {
                return $t[0] . '/' . str_pad($t[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($t[2], 2, '0', STR_PAD_LEFT);
            }
        } else if($action=='reverse') {
            $t = $this->sloppydate($k,'splitt');
            return $t[2] . '/' . $t[1] . '/' . $t[0];
        } else if($action=='ddmmyyyy') {
            $t = $this->sloppydate($k,'splitt');
            if(strlen($t[2])==4)
                return $t[0] . $del . $t[1] . $del . $t[2];
                else
                return $t[2] . $del . $t[1] . $del . $t[0];
        } else if($action=='yyyymmdd') {
            $t = $this->sloppydate($k,'splitt');
            if(strlen($t[2])==4)
                return $t[2] . $del . $t[1] . $del . $t[0];
                else
                return $t[0] . $del . $t[1] . $del . $t[2];
        } else if($action=='sql') {
            $k = $this->sloppydate($k,'fix');
            return $this->sloppydate($k,'yyyymmdd');
        } else if($action == 'splitt') {
            if( preg_match('/-/',$k) ) $splitter = "-";
            if( preg_match("/\./",$k) ) $splitter = '.';
            if( preg_match('/\//',$k) ) $splitter = '/';
                $t = explode($splitter,$k);
            return $t;
        } else if($action=='int'){
            $t = $this->sloppydate($k,'splitt');
            return (int) ($t[0] + $t[1] + $t[2]);
        } else if($action=='test'){
            $output = "First date = $k<br>\r\n";
            $output .= "-&gt; fix : " . $this->sloppydate($k,'fix') . "<br>\r\n";
            $output .= "-&gt; reverse : " . $this->sloppydate($k,'reverse') . "<br>\r\n";
            $output .= "-&gt; ddmmyyyy : " . $this->sloppydate($k,'ddmmyyyy') . "<br>\r\n";
            $output .= "-&gt; yyyymmdd : " . $this->sloppydate($k,'yyyymmdd') . "<br>\r\n";
            $output .= "-&gt; sql : " . $this->sloppydate($k,'sql') . "<br>\r\n";
            $k = '31.9.2005';
            $output .= "Second date = $k<br>\r\n";
            $output .= "-&gt; fix : " . $this->sloppydate($k,'fix') . "<br>\r\n";
            $output .= "-&gt; reverse : " . $this->sloppydate($k,'reverse') . "<br>\r\n";
            $output .= "-&gt; ddmmyyyy : " . $this->sloppydate($k,'ddmmyyyy') . "<br>\r\n";
            $output .= "-&gt; yyyymmdd : " . $this->sloppydate($k,'yyyymmdd') . "<br>\r\n";
            $output .= "-&gt; sql : " . $this->sloppydate($k,'sql') . "<br>\r\n";
            $k = '2005.5.31';
            $output .= "Third date = $k<br>\r\n";
            $output .= "-&gt; fix : " . $this->sloppydate($k,'fix') . "<br>\r\n";
            $output .= "-&gt; reverse : " . $this->sloppydate($k,'reverse') . "<br>\r\n";
            $output .= "-&gt; ddmmyyyy : " . $this->sloppydate($k,'ddmmyyyy') . "<br>\r\n";
            $output .= "-&gt; yyyymmdd : " . $this->sloppydate($k,'yyyymmdd') . "<br>\r\n";
            $output .= "-&gt; sql : " . $this->sloppydate($k,'sql') . "<br>\r\n";
            return $output;
        } else if($action=='datetime2date'){
            $t = explode(' ',$k);
            return $t[0];
        } else if($action=='datetime2time'){
            $t = explode(' ',$k);
            if(isset($t[1])){
                $t[1] = str_replace('.',':',$t[1]);
                $t[1] = str_replace(',',':',$t[1]);
                $t2 = explode(':',$t[1]);
                return (int) $t2[0] . ':' . (int) $t2[1] . ':' . (int) $t2[2];
            } else {
                return '00:00:00';
            }
        } else if($action=='valid'){
            if($boolean AND !strlen($k)) return true;
            $test = explode($del,$this->sloppydate($k,'sql'));
            if( (count($test)>=3) AND (strlen($test[0])==4) AND (strlen($test[1]) AND (strlen($test[1])<=3)) AND (strlen($test[2]) AND (strlen($test[2])<=3)))
                return true;
                else
                return false;
        }
    }

    /**
     * Kim's (bool) v1.0, last update 27 april 2006
     * After having so much problems with (bool) and what my own head thought (bool) worked, this
     * personal class is the only way to go. _bool() will return the logical true/false based on
     * what you evaluate. You can evaluate whatever.
     * 
     * update 27 april 2006
     * 
     * @param {mixed} $var Expression to check
     * @return boolean True or false boolean
     */
    function _bool($var, $var_ref=null){

        if( $var_ref == '_SESSION' ){
            if( !isset($_SESSION[$var]) )
                return false;

            $var = $_SESSION[$var];
        } 

        if(is_bool($var)){

            return $var;

        } else if($var === NULL || $var === 'NULL' || $var === 'null'){

            return false;

        } else if(is_string($var)){

            $var = trim($var);

            if($var=='false'){ return false;
            } else if($var=='true'){ return true;
            } else if($var=='no'){ return false;
            } else if($var=='yes'){ return true;
            } else if($var=='off'){ return false;
            } else if($var=='on'){ return true;
            } else if($var==''){ return false;
            } else if(ctype_digit($var)){
                if((int) $var)
                    return true;
                    else
                    return false;
            } else { return true; }

        } else if(ctype_digit((string) $var)){

            if((int) $var)
                return true;
                else
                return false;

        } else if(is_array($var)){

            if(count($var))
                return true;
                else
                return false;

        } else if(is_object($var)){

            return true;// No reason to (bool) an object, we assume OK for crazy logic

        } else {

            return true;// Whatever came though must be something,  OK for crazy logic

        }
    }

}
