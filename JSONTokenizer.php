<?php

/**
 * Class JSONTokenizer
 * @version 1.0.0
 * @author sans_amour <https://github.com/sansamour>
 * @website www.tutorialspots.com
 * @date 10/17/2020
 */

class JSONTokenizer{

    public static $control_character_map = array(
    	'"'   => '\"', '\\' => '\\\\', '/'  => '\/', "\x8" => '\b',
    	"\xC" => '\f', "\n" => '\n',   "\r" => '\r', "\t"  => '\t'
    );

    public static $accept = array(
        '[', # Array begin
        ']', # Array end
        '{', # Object begin
        '}', # Object end
        # Float # Integer
        '(-?(?:0|[1-9]\d*)(?:\.\d*(?:[eE][+\-]?\d+)?|(?:[eE][+\-]?\d+))|-?(?:0|[1-9]\d*))',    
        'true',  # True
        'false', # False
        'null',  # Null
        ',',     # Member separator for arrays and objects
        ':',     # Value separator for objects
        # String
        '"([^"]*|([^"]*(?<=\\\\)"[^"]*)+)(?<!\\\\)"', 
        # Whitespace
        '\s'
    );

    public $ret = array();
    public $mainType = '';
    public $json = '';
    //public $arr = array();
    
    public function __construct($json){
        $this->json = $json;
    }
    
    public function decode($tokenizer=true){
        if($tokenizer){
            $this->tokenizer();
            $this->removeSpace();
            $this->fixString();
        }    
        $s = (string)$this; 
        
        return json_decode($s);
    }

    public function getLastNotSpace()
    {
        //global $ret;
        for ($i = count($this->ret) - 1; $i >= 0; $i--) {
            if ($this->ret[$i][3] == 'space') {
    
            } else {
                return $i;
                break;
            }
        }
        return false;
    }

    public function findLastParentByLevel($level_parent)
    {
        //global $ret, $mainType;
        if ($level_parent == 1)
            return $this->mainType;
    
        for ($i = count($this->ret) - 1; $i >= 0; $i--) {
            if (($this->ret[$i][3] == 'begin-object' || $this->ret[$i][3] == 'begin-array') && $this->ret[$i][4] ==
                $level_parent - 1) {
                return substr($this->ret[$i][3], 6);
            }
        }
        return false;
    }
    
    //http://tutorialspots.com/php-convert-javascript-escape-string-to-utf8-474.html
    public static function je2utf8($jed)
    {        
        return preg_replace_callback("/\\\u([a-fA-F0-9]{4})/",function($m){
            $v = html_entity_decode(('&#'.hexdec($m[1]).';'),ENT_QUOTES,'UTF-8');
            if($v=='"') return '\\'.'"';
        }, $jed);
    }
    
    //http://tutorialspots.com/php-function-ord-of-unicode-character-2-4019.html
    public static function uniord($c) { 
        $t = mb_convert_encoding($c, 'UCS-2LE', 'UTF-8'); 
        return (ord($t{1}) << 8) + ord($t{0}); 
    } 
    
    public function removeSpace(){
        $this->ret = array_filter($this->ret, function($a){
            return $a[3] != 'space';
        });
    }
    
    public function __toString(){
        return implode("",array_map(function($a){return $a[2];},$this->ret));
    }
    
    public static function stripslashes($s){
        return str_replace("\/","/",$s);
    }
    
    public function fixString(){
        foreach($this->ret as &$s){
            if($s[3]=='string'){
                eval('$str='.self::stripslashes(self::je2utf8($s[2])).';');                
                $str = preg_replace_callback('/./us',function($a){
                    $ord = self::uniord($a[0]);
                    return isset(self::$control_character_map[$a[0]])?self::$control_character_map[$a[0]]:(
                       !preg_match("/^[a-z0-9A-Z `'\"!@\#\$\%^\&\*\(\)\-\+=\|_;\?\/:,<>~\.]$/",  
                        $a[0]) ? '\\'.'u'.sprintf('%04s',dechex($ord)) : $a[0]
                    );
                },$str);
                $s[2] = '"'.$str.'"';
            }
        }
    }
    
    public function tokenizer(){
        $json = $this->json;
        $i = 0; //current pos in json
        $j = 0; //current index of ret
        $k = 0; //current level
        $n = false; //array object (current in)
        $blank = false;
        $l = strlen($json);
        
        //$count = 0;
         
        while (/*$count++ < 10000 && */$i < $l) {
        
            if ($i == 0) {
                //todo tfn"-0-9
                if ($json[$i] != '{' && $json[$i] != '[') {
                    throw new Exception('Invalid JSON');
                }
        
                if ($json[$i] == '{') {
                    array_push($this->ret, array(
                        0,
                        1,
                        '{',
                        'begin-object',
                        $k++));
                    $j++;
                    $this->mainType = $n = 'object';
                } else {
                    array_push($this->ret, array(
                        0,
                        1,
                        '[',
                        'begin-array',
                        $k++));
                    $j++;
                    $this->mainType = $n = 'array';
                }
                $i++;
            } else {
                $la = $this->getLastNotSpace();  
                if ($this->ret[$la][3] == 'begin-object') {
                    //" }
                    if ($json[$i] != '}' && $json[$i] != '"') {
                        throw new Exception('Invalid JSON');
                    }
                    if ($json[$i] == '}') {
                        array_push($this->ret, array(
                            $i,
                            $i + 1,
                            '}',
                            'end-object',
                            --$k));
                        $j++;
                        $i++;
                        $n = $this->findLastParentByLevel($k);  
                    } else {
                        if (!preg_match("/^" . self::$accept[10] . "/", substr($json, $i), $cc)) {
                            throw new Exception('Invalid JSON');
                        }
                        array_push($this->ret, array(
                            $i,
                            $i + strlen($cc[0]),
                            $cc[0],
                            'string',
                            $k));
                        $j++;
                        $i += strlen($cc[0]);
                    }
                } elseif ($this->ret[$la][3] == 'begin-array') {
                    //number, true false null string, [ {     ]
                    if (!preg_match("/[\[\]\{\"tfn\-0-9]/", $json[$i])) {
                        throw new Exception('Invalid JSON');
                    }
                    //number
                    if (preg_match("/[\-0-9]/", $json[$i])) {
                        if (!preg_match("/^" . self::$accept[4] . "/", substr($json, $i), $cc)) {
                            throw new Exception('Invalid JSON');
                        }
                        
                        array_push($this->ret, array(
                            $i,
                            $i + strlen($cc[0]),
                            $cc[0],
                            'number',
                            $k));
                        $j++;
                        $i += strlen($cc[0]);
                    }
                    //string
                    elseif ($json[$i] == '"') {
                        if (!preg_match("/^" . self::$accept[10] . "/", substr($json, $i), $cc)) {
                            throw new Exception('Invalid JSON');
                        }
                        array_push($this->ret, array(
                            $i,
                            $i + strlen($cc[0]),
                            $cc[0],
                            'string',
                            $k));
                        $j++;
                        $i += strlen($cc[0]);
                    }
                    // [
                    elseif ($json[$i] == '[') {
                        array_push($this->ret, array(
                            0,
                            1,
                            '[',
                            'begin-array',
                            $k++));
                        $j++;
                        $i++;
                        $n = 'array';
                    } elseif ($json[$i] == ']') {
                        array_push($this->ret, array(
                            $i,
                            $i + 1,
                            ']',
                            'end-array',
                            --$k));
                        $j++;
                        $i++;
                        $n = $this->findLastParentByLevel($k);  
                    } elseif ($json[$i] == '{') {
                        array_push($this->ret, array(
                            $i,
                            $i + 1,
                            '{',
                            'begin-object',
                            $k++));
                        $j++;
                        $i++;
                        $n = 'object';
                    } else {
                        if (!preg_match("/^(" . self::$accept[5] . '|' . self::$accept[6] . '|' . self::$accept[7] . ")/",
                            substr($json, $i), $cc)) {
                            throw new Exception('Invalid JSON');
                        }
                        array_push($this->ret, array(
                            $i,
                            $i + strlen($cc[0]),
                            $cc[0],
                            'trueFalseNull',
                            $k));
                        $j++;
                        $i += strlen($cc[0]);
                    }
                } elseif ($this->ret[$la][3] == 'string') {
                    //echo $n.'~~';
                    if ($n == 'object') {
                        //      ->>>    , :  }
                        if ($json[$i] != ',' && $json[$i] != '}' && $json[$i] != ':') {
                            throw new Exception('Invalid JSON');
                        }
                        if ($json[$i] == '}') {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                '}',
                                'end-object',
                                --$k));
                            $j++;
                            $i++;
                            $n = $this->findLastParentByLevel($k);  
                        } else {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                $json[$i],
                                $json[$i],
                                $k));
                            $j++;
                            $i++;
                        }
                    } else {
                        //array ->>>    , ]
                        if ($json[$i] != ',' && $json[$i] != ']') {
                             
                            echo $i;
                            throw new Exception('Invalid JSON');
                        }
        
                        if ($json[$i] == ',') {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                $json[$i],
                                $json[$i],
                                $k));
                            $j++;
                            $i++;
                        } else {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                ']',
                                'end-array',
                                --$k));
                            $j++;
                            $i++;
                            $n = $this->findLastParentByLevel($k);  
                        }
                    }
                } elseif ($this->ret[$la][3] == ',') {
                    //echo $n;
                    if ($n == 'object') {
                        //"
                        if ($json[$i] != '"') {
                            throw new Exception('Invalid JSON');
                        }
        
                        if (!preg_match("/^" . self::$accept[10] . "/", substr($json, $i), $cc)) {
                            throw new Exception('Invalid JSON');
                        }
                        array_push($this->ret, array(
                            $i,
                            $i + strlen($cc[0]),
                            $cc[0],
                            'string',
                            $k));
                        $j++;
                        $i += strlen($cc[0]);
        
                    } else {
                        //number, true false null string, [ {
                        if (!preg_match("/[\[\]\{\"tfn\-0-9]/", $json[$i])) {
                            throw new Exception('Invalid JSON');
                        }
                        //number
                        if (preg_match("/[\-0-9]/", $json[$i])) {
                            if (!preg_match("/^" . self::$accept[4] . "/", substr($json, $i), $cc)) {
                                throw new Exception('Invalid JSON');
                            }
                             
                            array_push($this->ret, array(
                                $i,
                                $i + strlen($cc[0]),
                                $cc[0],
                                'number',
                                $k));
                            $j++;
                            $i += strlen($cc[0]);
                        }
                        //string
                        elseif ($json[$i] == '"') {
                            if (!preg_match("/^" . self::$accept[10] . "/", substr($json, $i), $cc)) {
                                throw new Exception('Invalid JSON');
                            }
                            array_push($this->ret, array(
                                $i,
                                $i + strlen($cc[0]),
                                $cc[0],
                                'string',
                                $k));
                            $j++;
                            $i += strlen($cc[0]);
                        }
                        // [
                        elseif ($json[$i] == '[') {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                '[',
                                'begin-array',
                                $k++));
                            $j++;
                            $i++;
                            $n = 'array';
                        } elseif ($json[$i] == '{') {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                '{',
                                'begin-object',
                                $k++));
                            $j++;
                            $i++;
                            $n = 'object';
                        } else {
                            if (!preg_match("/^(" . self::$accept[5] . '|' . self::$accept[6] . '|' . self::$accept[7] . ")/",
                                substr($json, $i), $cc)) {
                                throw new Exception('Invalid JSON');
                            }
                            array_push($this->ret, array(
                                $i,
                                $i + strlen($cc[0]),
                                $cc[0],
                                'trueFalseNull',
                                $k));
                            $j++;
                            $i += strlen($cc[0]);
                        }
                    }
                } elseif (
                    $this->ret[$la][3] == 'end-object' || 
                    $this->ret[$la][3] == 'end-array' || 
                    $this->ret[$la][3] == 'number' || 
                    $this->ret[$la][3] == 'trueFalseNull'
                ) {
                    //, ] }
                     
                    if ($n == 'object') {
                        if ($json[$i] != ',' && $json[$i] != '}') {
                            throw new Exception('Invalid JSON');
                        }
                        if ($json[$i] == '}') {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                '}',
                                'end-object',
                                --$k));
                            $j++;
                            $i++;
                            $n = $this->findLastParentByLevel($k);  
                        } else {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                $json[$i],
                                $json[$i],
                                $k));
                            $j++;
                            $i++;
                        }
                    } else {
                        
                        if ($json[$i] != ',' && $json[$i] != ']') {                             
                            throw new Exception('Invalid JSON');
                        }
                        if ($json[$i] == ',') {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                $json[$i],
                                $json[$i],
                                $k));
                            $j++;
                            $i++;
                        } else {
                            array_push($this->ret, array(
                                $i,
                                $i + 1,
                                ']',
                                'end-array',
                                --$k));
                            $j++;
                            $i++;
                            $n = $this->findLastParentByLevel($k);  
                        }
                    }
                } elseif ($this->ret[$la][3] == ':') {
                    //number, true false null string, [ {
                    if (!preg_match("/[\[\]\{\"tfn\-0-9]/", $json[$i])) {
                        throw new Exception('Invalid JSON');
                    }
                    //number
                    if (preg_match("/[\-0-9]/", $json[$i])) {
                        if (!preg_match("/^" . self::$accept[4] . "/", substr($json, $i), $cc)) {
                            throw new Exception('Invalid JSON');
                        }
                         
                        array_push($this->ret, array(
                            $i,
                            $i + strlen($cc[0]),
                            $cc[0],
                            'number',
                            $k));
                        $j++;
                        $i += strlen($cc[0]);
                    }
                    //string
                    elseif ($json[$i] == '"') {
                        if (!preg_match("/^" . self::$accept[10] . "/", substr($json, $i), $cc)) {
                            throw new Exception('Invalid JSON');
                        }
                        array_push($this->ret, array(
                            $i,
                            $i + strlen($cc[0]),
                            $cc[0],
                            'string',
                            $k));
                        $j++;
                        $i += strlen($cc[0]);
                    }
                    // [
                    elseif ($json[$i] == '[') {
                        array_push($this->ret, array(
                            $i,
                            $i + 1,
                            '[',
                            'begin-array',
                            $k++));
                        $j++;
                        $i++;
                        $n = 'array';
                    } elseif ($json[$i] == '{') {
                        array_push($this->ret, array(
                            $i,
                            $i + 1,
                            '{',
                            'begin-object',
                            $k++));
                        $j++;
                        $i++;
                        $n = 'object';
                    } else {
                        if (!preg_match("/^(" . self::$accept[5] . '|' . self::$accept[6] . '|' . self::$accept[7] . ")/",
                            substr($json, $i), $cc)) {
                            throw new Exception('Invalid JSON');
                        }
                        array_push($this->ret, array(
                            $i,
                            $i + strlen($cc[0]),
                            $cc[0],
                            'trueFalseNull',
                            $k));
                        $j++;
                        $i += strlen($cc[0]);
                    }
                }else{
                    throw new Exception('Invalid JSON');
                }
            }
            if ($i < $l) {
                $blank = false;
                while (preg_match('/' . self::$accept[11] . '/', $json[$i])) {
                    $blank = true;
                    $i++;
                }
                if ($blank) {
                    array_push($this->ret, array(
                        $this->ret[$j - 1][1],
                        $i,
                        substr($json, $this->ret[$j - 1][1], $i - $this->ret[$j - 1][1]),
                        'space',
                        $k));
                    $j++;
                    $blank = false;
                }
            }
        }
    }
}
