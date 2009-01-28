<?php

// $Id: plc_details.php 11645 2009-01-21 23:09:49Z thierry $

require_once 'plc_functions.php';

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

function plc_form_label_text ($label,$name) {
  return "<label for=$name>$text </label>";
}
 
?>
