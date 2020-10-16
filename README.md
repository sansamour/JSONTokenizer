JSONTokenizer
======
JSONTokenizer

Requirements
------
* PHP >= 5

Example Usage
------
```php
<?php
$json = '["a{}", "Révolution\r\n",{"b" :[]}]';
 
$JSONTokenizer = new JSONTokenizer($json);
$JSONTokenizer->tokenizer();

var_dump($JSONTokenizer->ret);

$JSONTokenizer->removeSpace();
$JSONTokenizer->fixString();

var_dump($JSONTokenizer->decode(false));
```

Result:

```
array(14) {
  [0]=>
  array(5) {
    [0]=>
    int(0)
    [1]=>
    int(1)
    [2]=>
    string(1) "["
    [3]=>
    string(11) "begin-array"
    [4]=>
    int(0)
  }
  [1]=>
  array(5) {
    [0]=>
    int(1)
    [1]=>
    int(6)
    [2]=>
    string(5) ""a{}""
    [3]=>
    string(6) "string"
    [4]=>
    int(1)
  }
  [2]=>
  array(5) {
    [0]=>
    int(6)
    [1]=>
    int(7)
    [2]=>
    string(1) ","
    [3]=>
    string(1) ","
    [4]=>
    int(1)
  }
  [3]=>
  array(5) {
    [0]=>
    int(7)
    [1]=>
    int(8)
    [2]=>
    string(1) " "
    [3]=>
    string(5) "space"
    [4]=>
    int(1)
  }
  [4]=>
  array(5) {
    [0]=>
    int(8)
    [1]=>
    int(25)
    [2]=>
    string(17) ""Révolution\r\n""
    [3]=>
    string(6) "string"
    [4]=>
    int(1)
  }
  [5]=>
  array(5) {
    [0]=>
    int(25)
    [1]=>
    int(26)
    [2]=>
    string(1) ","
    [3]=>
    string(1) ","
    [4]=>
    int(1)
  }
  [6]=>
  array(5) {
    [0]=>
    int(26)
    [1]=>
    int(27)
    [2]=>
    string(1) "{"
    [3]=>
    string(12) "begin-object"
    [4]=>
    int(1)
  }
  [7]=>
  array(5) {
    [0]=>
    int(27)
    [1]=>
    int(30)
    [2]=>
    string(3) ""b""
    [3]=>
    string(6) "string"
    [4]=>
    int(2)
  }
  [8]=>
  array(5) {
    [0]=>
    int(30)
    [1]=>
    int(31)
    [2]=>
    string(1) " "
    [3]=>
    string(5) "space"
    [4]=>
    int(2)
  }
  [9]=>
  array(5) {
    [0]=>
    int(31)
    [1]=>
    int(32)
    [2]=>
    string(1) ":"
    [3]=>
    string(1) ":"
    [4]=>
    int(2)
  }
  [10]=>
  array(5) {
    [0]=>
    int(32)
    [1]=>
    int(33)
    [2]=>
    string(1) "["
    [3]=>
    string(11) "begin-array"
    [4]=>
    int(2)
  }
  [11]=>
  array(5) {
    [0]=>
    int(33)
    [1]=>
    int(34)
    [2]=>
    string(1) "]"
    [3]=>
    string(9) "end-array"
    [4]=>
    int(2)
  }
  [12]=>
  array(5) {
    [0]=>
    int(34)
    [1]=>
    int(35)
    [2]=>
    string(1) "}"
    [3]=>
    string(10) "end-object"
    [4]=>
    int(1)
  }
  [13]=>
  array(5) {
    [0]=>
    int(35)
    [1]=>
    int(36)
    [2]=>
    string(1) "]"
    [3]=>
    string(9) "end-array"
    [4]=>
    int(0)
  }
}
array(3) {
  [0]=>
  string(3) "a{}"
  [1]=>
  string(13) "Révolution
"
  [2]=>
  object(stdClass)#2 (1) {
    ["b"]=>
    array(0) {
    }
  }
}
```

Detail
------
[PHP: Class JSON Tokenizer](http://tutorialspots.com/php-class-json-tokenizer-6393.html)
