<?php

// $Id$

require_once 'plekit-utils.php';

// the rationale behind having function names with _html is that
// the first functions that we had were actually printing the stuff instead of returning it
// so basically the foo (...) function should just do ``print (foo_html(...))''

class PlekitForm {
  // mandatory
  var $url;
  var $values; // a hash var=>value - default is empty array
  var $method; // default is POST, can be changed with options
  var $onSubmit; // can be set with options
  var $onReset; // can be set with options

  function PlekitForm ($full_url, $values, $options=NULL) {
    // so we can use the various l_* functions:
    // we parse the url to extract var-values pairs, 
    // and add them to the 'values' argument if any

    // extract var=value settings from url if any
    $split=plekit_split_url($full_url);
    $this->url=$split['url'];
    
    $url_values=$split['values'];
    if ( ! $values ) $values = array();
    if ( $url_values ) $values=array_merge($values,$url_values);
    $this->values=$values;

    // make strict xhtml happy
    $this->method="post";	if (isset($options['method']) && (!empty($options['method']))) $this->method=strtolower($options['method']);
    $this->onSubmit=NULL;	if (isset($options['onSubmit']) && (!empty($options['onSubmit']))) $this->onSubmit=$options['onSubmit'];
    $this->onReset=NULL;	if (isset($options['onReset']) && (!empty($options['onReset']))) $this->onReset=$options['onReset'];
  }

  function start () { print $this->start_html(); }
  function start_html () {
    $html="<form method='$this->method' action='$this->url' enctype='multipart/form-data'";
    if ($this->onSubmit) $html .= " onSubmit='$this->onSubmit'";
    if ($this->onReset) $html .= " onReset='$this->onReset'";
    $html .= ">";
    if ($this->values) 
      foreach ($this->values as $key=>$value) 
	$html .= $this->hidden_html($key,$value);
    return $html;
  }

  function end() { print $this->end_html(); }
  function end_html() { return "</form>"; }

  static function attributes ($options) {
    $html="";
    $names=array('id','size','selected', 'checked',
		 'onfocus','onselect', 'onchange', 
		 'onkeyup', 'onmouseup', 'onclick', 'onsubmit');
    if ($options['selected']) $options['selected']='selected';
    if ($options['checked']) $options['checked']='checked';
    if ($options) foreach ($options as $key=>$value) {
	if (in_array(strtolower($key),$names)) 
	  $html .= " $key='$value'";
      }
    return $html;
  }

  // options
  // (*) width to set the text size
  // (*) callbacks, e.g. onFocus=>'your javascript code'
  static function input_html ($type,$name,$value,$options=NULL) {
    if ( ! $options) $options=array();
    $html="<input";
    $html .= " type='$type' name='$name' value='$value'";
    $html .= PlekitForm::attributes ($options);
    $html .= "/>";
    return $html;
  }

  static function text_html ($name,$value, $options=NULL) {	return PlekitForm::input_html('text', $name, $value, $options); }
  static function hidden_html ($name,$value, $options=NULL) {	return PlekitForm::input_html('hidden', $name, $value, $options); }
  static function checkbox_html ($name,$value,$options=NULL) {	return PlekitForm::input_html('checkbox', $name, $value, $options); }
  static function submit_html ($name,$value,$options=NULL) {	return PlekitForm::input_html('submit', $name, $value, $options); }
  static function button_html ($name,$value,$options=NULL) {	return PlekitForm::input_html('button', $name, $value, $options); }
  static function radio_html ($name,$value,$options=NULL) {	return PlekitForm::input_html('radio', $name, $value, $options); }
  static function file_html ($name,$value,$options=NULL) {	return PlekitForm::input_html('file', $name, $value, $options); }

  static function label_html ($name,$display) {
    return "<label for=$name>$display</label>";
  }
  static function textarea_html ($name,$value,$cols,$rows) {
    return "<textarea name='$name' cols='$cols' rows='$rows'>$value</textarea>";
  }
 
  // selectors is an array of hashes with the following keys
  // (*) display 
  // (*) value : the value that the 'name' variable will be assigned
  // (*) optional 'selected': the entry selected initially
  // (*) optional 'disabled': the entry is displayed but not selectable
  // options
  // (*) id
  // (*) label : displayed as the first option, with no value attached
  // (*) autosubmit : equivalent to onChange=>'submit()'
  // (*) standard callbacks

  static function select_html ($name,$selectors,$options=NULL) {
    if ( ! $options) $options=array();
    if ( $options ['autosubmit'] ) $options['onChange']='submit()';
    $html="";
    $html.="<select name='$name'";
    if ($options['id']) $html .= " id='" . $options['id'] . "'";
    $cbs=array('onFocus','onSelect','onChange');
    foreach ($cbs as $cb) {
      if ($options[$cb])
	$html .= " $cb='" . $options[$cb] . "'";
    }
    $html .= ">";
    if ($options['label']) {
      $encoded=htmlentities($options['label'],ENT_QUOTES);
      $html.="<option selected=selected value=''>$encoded</option>";
    }
    if ($selectors) {
      foreach ($selectors as $selector) {
        $display=htmlentities($selector['display'],ENT_QUOTES);
        $value=$selector['value'];
        $html .= "<option value='$value'";
        if ($selector['selected']) $html .= " selected=selected";
        if ($selector['disabled']) $html .= " disabled=disabled";
        $html .= ">$display</option>\n";
      }
    }
    $html .= "</select>";
    return $html;
  }

  // helper function to handle role-oriented selectors
  // because GetRoles does not correctly support filters, it's really painful to do this
  static public function role_selectors($roles,$current_id=NULL) {
    function role_selector ($role) { return array('display'=>$role['name'],"value"=>$role['role_id']); }
    $selectors=array();
    // preserve input order
    if ( ! $roles) {
      drupal_set_message('WARNING: empty roles in role_selectors');
    } else {
      foreach ($roles as $role) {
	$selector=role_selector($role);
	if ($role['role_id'] == $current_id) 
	    $selector['selected']=true;
	$selectors []= $selector;
      }
    }
    return $selectors;
  }

}

// a form with a single button
class PlekitFormButton extends PlekitForm {
  
  var $button_id;
  var $button_text;

  function PlekitFormButton ($full_url, $button_id, $button_text, $method="POST") {
    $this->PlekitForm($full_url,array(),$method);
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
