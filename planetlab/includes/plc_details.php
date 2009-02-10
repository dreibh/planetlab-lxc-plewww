<?php

// $Id$

require_once 'plc_functions.php';
require_once 'plc_forms.php';

drupal_set_html_head('
<link href="/planetlab/css/plc_details.css" rel="stylesheet" type="text/css" />
');


// the basic idea is to define an area for displaying details like
// fieldname=>value
// and we add in-line editing capabilities

class PlcDetails {
  
  var $editable;
  var $form;
  // set manually 
  var $field_width;
  var $field_height;
  var $input_type="text";

  function PlcDetails ($editable) {
    $this->editable=$editable;
    $this->form=NULL;
    $this->field_width="";
    $this->field_height="2";
  }

  function form() { return $this->form; }

  // start the details area, with an optional caption
  function start ($caption="") { print $this->start_html("$caption");}
  function start_html ($caption="") {
    $html="<table class=plc_details><thead>";
    if ($caption) $html .= "<caption>$caption</caption>";
    $html .= "</thead><tbody>";
    return $html;
  }

  function end() { print $this->end_html(); }
  function end_html () {
    return "</tbody></table>\n";
  }

  // starts an inner form if the details are editable
  // accpets same args as PlcForm
  function form_start ($url,$values,$method="POST") { print $this->form_start_html($url,$values,$method); return $this->form; }
  function form_start_html ($url,$values,$method="POST") {
    $this->form = new PlcForm ($url,$values,$method);
    return $this->form->start_html();
  }

  function form_end () { print $this->form_end_html(); }
  function form_end_html () {
    if ( ! $this->form) return "";
    $html = $this->form->end_html();
    $form=NULL;
    return $html;
  }

  // must be embedded in a line or a single
  // xxx need a way to ask for confirmation
  function submit_html ($name,$display) {
    if ( ! $this->form) return "";
    if ( ! $this->editable) return "";
    return $this->form->submit_html($name,$display);
  }

  // give a form_varname if the field can be edited 
  function line ($title,$value,$form_varname="") {
    print $this->line_html ($title,$value,$form_varname);
  }
  function line_html ($title,$value,$form_varname="") {
    if ( ! ($this->editable && $form_varname) ) {
      return "<tr><th>$title</th><td>$value</td></tr>";
    } else {
      $html="";
      $html .= "<tr><th><label for=$form_varname>$title</label></th>";
      $html .= "<td>";
      // hack: if input_type is select : user provides the input field verbatim
      if ( $this->input_type == "select" ) {
	$html .= $value;
      } else if ($this->input_type == "textarea") {
	$html .= "<textarea name='$form_varname'";
	if ($this->field_width) $html .= " cols=$this->field_width";
	if ($this->field_height) $html .= " rows=$this->field_height";
	$html .= ">$value</textarea>";
      } else {
	$html .= "<input type='$this->input_type' name='$form_varname' value='$value'";
	if ($this->field_width) $html .= " size=$this->field_width";
	$html .= "/>";
      }
      $html .= "</td></tr>";
      return $html;
    }
  }

  // same but the values are multiple and displayed in an embedded vertical table (not editable)
  function lines($title,$list) { print $this->lines_html($title,$list); }
  function lines_html($title,$list) {
    return $this->line_html($title,plc_vertical_table($list,"foo"));
  }

  function line_th ($th1,$th2) {	print $this->line_th_html ($th1, $th2);}
  function line_th_html ($th1, $th2) {
    return "<tr><th>$th1</th><th>$th2</th></tr>";
  }

  // 1 item, colspan=2
  function single($title,$align=NULL) { print $this->single_html($title,$align);}
  function single_html($title,$align=NULL) {
    $result="<tr><td colspan=2";
    if ($align) $result .= " style='text-align:$align'";
    $result .=">$title</td></tr>";
    return $result;
  }
  
  // a dummy line for getting some air
  function space () { print $this->space_html(); }
  function space_html () { return "<tr><td colspan=2>&nbsp;</td></tr>\n"; }

  function set_field_width ($field_width) {
    $old=$this->field_width;
    $this->field_width=$field_width;
    return $old;
  }
  function set_field_height ($field_height) {
    $old=$this->field_height;
    $this->field_height=$field_height;
    return $old;
  }

  function set_input_type ($input_type) {
    $old=$this->input_type;
    $this->input_type=$input_type;
    return $old;
  }

}

?>
