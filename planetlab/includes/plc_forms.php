<?php

// $Id: plc_details.php 11645 2009-01-21 23:09:49Z thierry $

require_once 'plc_functions.php';

// the rationale behind having function names with _text is that
// the first functions that we had were actually printing the stuff instead of returning it
// so basically the foo (...) function should do ``print (foo_text(...))''

function plc_form_start ($url, $values, $options=array()) {
  $method = array_key_exists('method',$options) ? $options['method'] : 'POST';
  print "<form method=$method action='$url' enctype='multipart/form-data'>";
  foreach ($values as $key=>$value) {
    print "<input type=hidden name='$key' value='$value'>";
  }
}

function plc_form_end($options=array()) {
  print "</form>";
}

function plc_form_checkbox_text ($name,$value,$selected=false) {
  if ($selected) $xtra=" selected=selected";
  return "<input type=checkbox name='$name' value='$value'$xtra>";
}

function plc_form_submit_text ($name,$display) {
  return "<input type=submit name='$name' value='$display'>";
}
  
function plc_form_file_text ($name,$size) {
  return "<input type=file name='$name' size=$size>";
}

function plc_form_label_text ($name,$display) {
  return "<label for=$name>$display </label>";
}
 
function plc_form_select_text ($name,$values,$label="") {
  $selector="";
  $selector.="<select name='$name'>";
  if ($label) 
    $selector.="<option value=''>$label</option>";
  foreach ($values as $chunk) {
    $display=$chunk['display'];
    $value=$chunk['value'];
    $selector .= "<option value='$value'>$display</option>\n";
  }
  $selector .= "</select>";
  return $selector;
}

?>
