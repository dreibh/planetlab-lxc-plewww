<?php

// $Id$

require_once 'plc_functions.php';

// the rationale behind having function names with _html is that
// the first functions that we had were actually printing the stuff instead of returning it
// so basically the foo (...) function should just do ``print (foo_html(...))''

class PlcForm {
  // mandatory
  var $url;
  var $values; // a hash var=>value - default is empty array
  var $method; // default is POST

  function PlcForm ($full_url, $values, $method="POST") {
    // so we can use the various l_* functions:
    // we parse the url to extract var-values pairs, 
    // and add them to the 'values' argument if any

    // extract var=value settings from url if any
    $split=split_url($full_url);
    $this->url=$split['url'];
    
    $url_values=$split['values'];
    if ( ! $values ) $values = array();
    if ( $url_values ) $values=array_merge($values,$url_values);
    $this->values=$values;

    $this->method=$method;
  }

  function start () { print $this->start_html(); }
  function start_html () {
    $html="<form method=$this->method action='$this->url' enctype='multipart/form-data'>";
    if ($this->values) 
      foreach ($this->values as $key=>$value) 
	$html .= $this->hidden_html($key,$value);
    return $html;
  }

  function end() { print $this->end_html(); }
  function end_html() { return "</form>"; }

  static function hidden_html ($key,$value) {
    return "<input type=hidden name='$key' value='$value'/>";  
  }
  static function checkbox_html ($name,$value,$selected=false) {
    if ($selected) $xtra=" selected=selected";
    return "<input type=checkbox name='$name' value='$value'$xtra/>";
  }
  static function submit_html ($name,$display) {
    return "<input type=submit name='$name' value='$display'/>";
  }
  static function file_html ($name,$size) {
    return "<input type=file name='$name' size=$size/>";
  }
  static function label_html ($name,$display) {
    return "<label for=$name>$display </label>";
  }
  static function radio_html  ($id, $name, $value, $checked) {
    $html="<input type='radio' id='$id' name='$name' value='$value'";
    if ($checked) $html .= " checked='checked'";
    $html .="/>";
    return $html;
  }
  static function text_html ($name,$value,$options=NULL) {
    $default_options = array('width'=>20);
    if ( ! $options) $options=array();
    $options = array_merge($default_options,$options);
    $html="<input type=text name='$name' value='$value'";
    $html .= " size=" . $options['width'];
    $cbs=array('onFocus','onSelect');
    foreach ($cbs as $cb) {
      if ($options[$cb])
	$html .= " $cb='" . $options[$cb] . "'";
    }
    $html .= "/>";
    return $html;
  }
  static function textarea_html ($name,$value,$cols,$rows) {
    return "<textarea name='$name' cols=$cols rows=$rows>$value</textarea>";
  }
 
  // selectors is an array of hashes with the following keys
  // (*) display 
  // (*) value : the value that the 'name' variable will be assigned
  // (*) optional 'selected': the entry selected initially
  // (*) optional 'disabled': the entry is displayed but not selectable
  // optional label is inserted as the first option, with no value attached
  // autosubmit: onchange=submit()
  static function select_html ($name,$selectors,$label=NULL,$autosubmit=false) {
    $html="";
    $html.="<select name='$name'";
    if ($autosubmit) $html .= " onChange='submit();'";
    $html .= ">";
    if ($label) {
      $encoded=htmlentities($label,ENT_QUOTES);
      $html.="<option selected=selected value=''>$encoded</option>";
    }
    foreach ($selectors as $selector) {
      $display=htmlentities($selector['display'],ENT_QUOTES);
      $value=$selector['value'];
      $html .= "<option value='$value'";
      if ($selector['selected']) $html .= " selected=selected";
      if ($selector['disabled']) $html .= " disabled=disabled";
      $html .= ">$display</option>\n";
    }
    $html .= "</select>";
    return $html;
  }

  // helper function to handle role-oriented selectors
  // because GetRoles does not correctly support filters, it's really painful to do this
  static public function role_selectors($api,$role_ids=NULL,$current=NULL) {
    function role_selector ($role) { return array('display'=>$role['name'],"value"=>$role['role_id']); }
    function role_id ($role) { return $role['role_id']; }

    $all_roles=$api->GetRoles();
    if ( ! $role_ids)
      $role_ids=array_map("role_id",$all_roles);

    $selectors=array();
    // preserve input order
    foreach ($role_ids as $role_id) {
      foreach ($all_roles as $all_role) {
	if ($all_role['role_id'] == $role_id) {
	  $selector=role_selector($all_role);
	  if ($role_id == $current) 
	    $selector['selected']=true;
	  $selectors []= $selector;
	}
      }
    }
    return $selectors;
  }

  static public function role_selectors_excluding ($api,$exclude_role_ids=NULL,$current=NULL) {
    if ( ! $exclude_role_ids ) $exclude_role_ids = array();
    $all_roles=$api->GetRoles();
    $role_ids = array();
    foreach ($all_roles as $role) {
      if ( ! in_array ($role['role_id'],$exclude_role_ids)) {
	$role_ids [] = $role['role_id'];
      }
    }
    return PlcForm::role_selectors($api,$role_ids,$current);    
  }
}

// a form with a single button
class PlcFormButton extends PlcForm {
  
  var $button_id;
  var $button_text;

  function PlcFormButton ($full_url, $button_id, $button_text, $method="POST") {
    $this->PlcForm($full_url,array(),$method);
    $this->button_id=$button_id;
    $this->button_text=$button_text;
  }

  function html () {
    return 
      $this->start_html() . 
      $this->submit_html($this->button_id,$this->button_text).
      $this->end_html();
  }
}

?>
