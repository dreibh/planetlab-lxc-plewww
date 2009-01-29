<?php

// $Id$

require_once 'plc_functions.php';

// the rationale behind having function names with _text is that
// the first functions that we had were actually printing the stuff instead of returning it
// so basically the foo (...) function should do ``print (foo_text(...))''

function plc_form_hidden_text ($key,$value) {
  return "<input type=hidden name='$key' value='$value'/>";  
}
function plc_form_hidden ($key,$value) { print plc_form_hidden_text($key,$value); }
  
// options unused so far
function plc_form_start ($url, $values, $options=array()) {
  $method = array_key_exists('method',$options) ? $options['method'] : 'POST';
  print "<form method=$method action='$url' enctype='multipart/form-data'>";
  foreach ($values as $key=>$value) {
    print plc_form_hidden_text($ke,$value);
  }
}

function plc_form_end($options=array()) {
  print "</form>";
}

function plc_form_checkbox_text ($name,$value,$selected=false) {
  if ($selected) $xtra=" selected=selected";
  return "<input type=checkbox name='$name' value='$value'$xtra/>";
}

function plc_form_submit_text ($name,$display) {
  return "<input type=submit name='$name' value='$display'/>";
}
function plc_form_submit ($name, $display) { print plc_form_submit_text($name,$display); }
  
function plc_form_file_text ($name,$size) {
  return "<input type=file name='$name' size=$size/>";
}

function plc_form_label_text ($name,$display) {
  return "<label for=$name>$display </label>";
}

function plc_form_text_text ($name,$value,$size) {
  return "<input type=text name='$name' size=$size value='$value'>";
}
function plc_form_textarea_text ($name,$value,$cols,$rows) {
  return "<textarea name='$name' cols=$cols rows=$rows>$value</textarea>";
}
 
function plc_form_select_text ($name,$values,$label="") {
  $selector="";
  $selector.="<select name='$name'>";
  if ($label) 
    $selector.="<option value=''>$label</option>";
  foreach ($values as $chunk) {
    $display=$chunk['display'];
    $value=$chunk['value'];
    $selector .= "<option value='$value'";
    if ($chunk['selected']) $selector .= " selected=selected";
    $selector .= ">$display</option>\n";
  }
  $selector .= "</select>";
  return $selector;
}

function plc_form_simple_button ($url,$text,$method="POST") {
  $button=plc_form_submit_text("anonymous",$text);
  return "<form method=$method action=$url>$button</form>";
}

?>
