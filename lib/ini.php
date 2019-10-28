<?php
class INI {
/** INI file path
 * @var String
 */
var $file = NULL;

/** INI data
 * @var Array
 */
var $data = array();

/** Process sections
 * @var Boolean
 */
var $sections = TRUE;

/** Parse INI file
 * @param
String
   $file
    - INI file path
 * @param
Boolean
   $sections
- Process sections
 */
function INI() {
    if (func_num_args()) {

   $args = func_get_args();

   call_user_func_array(array($this, 'read'), $args);
    }
}

/** Parse INI file
 * @param
String
   $file
    - INI file path
 * @param
Boolean
   $sections
- Process sections
 */
function read($file = NULL, $sections = TRUE) {
    $this->file = ($file) ? $file : $this->file;
    $this->sections = $sections;
    $this->data = parse_ini_file(realpath($this->file), $this->sections);
    return $this->data;
}

/** Write INI file
 * @param
String
   $file
    - INI file path
 * @param
Array
   $data
    - Data (Associative Array)
 * @param
Boolean
   $sections
- Process sections
 */
function write($file = NULL, $data = array(), $sections = TRUE) {
    $this->data = (!empty($data)) ? $data : $this->data;
    $this->file = ($file) ? $file : $this->file;
    $this->sections = $sections;
    $content = NULL;


   if ($this->sections) {

   foreach ($this->data as $section => $data) {


  $content .= '[' . $section . ']' . PHP_EOL;


  foreach ($data as $key => $val) {



 if (is_array($val)) {




foreach ($val as $v) {




    $content .= $key . '[] = ' . (is_numeric($v) ? $v : '"' . $v . '"') . PHP_EOL;




}



 } elseif (empty($val)) {




$content .= $key . ' = ' . PHP_EOL;



 } else {




$content .= $key . ' = ' . (is_numeric($val) ? $val : '"' . $val . '"') . PHP_EOL;



 }


  }


  $content .= PHP_EOL;

   }
    } else {

   foreach ($this->data as $key => $val) {


  if (is_array($val)) {



 foreach ($val as $v) {




$content .= $key . '[] = ' . (is_numeric($v) ? $v : '"' . $v . '"') . PHP_EOL;



 }


  } elseif (empty($val)) {



 $content .= $key . ' = ' . PHP_EOL;


  } else {



 $content .= $key . ' = ' . (is_numeric($val) ? $val : '"' . $val . '"') . PHP_EOL;


  }

   }
    }


   return (($handle = fopen($this->file, 'w')) && fwrite($handle, trim($content)) && fclose($handle)) ? TRUE : FALSE;
} }?>