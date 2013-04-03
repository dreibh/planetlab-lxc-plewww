<?php

require_once 'plc_functions.php';
require_once 'form.php';

drupal_set_html_head('
<link href="/plekit/details/details.css" rel="stylesheet" type="text/css" />
');


// the basic idea is to define an area for displaying details like
// fieldname=>value
// and we add in-line editing capabilities

// $editable : if not set, no edition will be allowed in the table 
//   this is typically set to false when user does not have write access
// then each individual th_td provides its form_varname if and only iff edition is desired

// start & end :create and close a 2-columns table
// th_td -> display label & value, with optional inline editing capability
// th_tds -> not editable, display a (vertical) list of values in the <td> area
// th_th : special cases, display 2 <th>
// xxx todo : accept optional arguments as an options hash, rather than using the set_ methods which are ugly

class PlekitDetails {
  
  var $editable;
  var $form;
  // various options for the editing area
  // set manually 
  var $width;
  var $height;
  var $input_type;

  function PlekitDetails ($editable) {
    $this->editable=$editable;
    $this->form=NULL;
    $this->width="";
    $this->height="2";
    $this->input_type="text";
  }

  function form() { return $this->form; }

  // start the details area, with an optional caption
  function start ($caption="") { print $this->start_html("$caption");}
  function start_html ($caption="") {
    $html="<table class='plc_details'>";
    if ($caption) $html .= "<thead><caption>$caption</caption></thead>";
    $html .= "<tbody>";
    return $html;
  }

  function end() { print $this->end_html(); }
  function end_html () {
    return "</tbody></table>\n";
  }

  // starts an inner form if the details are editable
  // accpets same args as PlekitForm
  function form_start ($url,$values,$options=NULL) { print $this->form_start_html($url,$values,$options); return $this->form; }
  function form_start_html ($url,$values,$options=NULL) {
    $this->form = new PlekitForm ($url,$values,$options);
    return $this->form->start_html();
  }

  function form_end () { print $this->form_end_html(); }
  function form_end_html () {
    if ( ! $this->form) return "";
    $html = $this->form->end_html();
    $form=NULL;
    return $html;
  }

  //////////////////// several forms for submit button
  // xxx need a way to ask for confirmation

  // must be embedded in a th_td or a tr
  function submit_html ($name,$display) {
    if ( ! $this->form) return "";
    if ( ! $this->editable) return "";
    return $this->form->submit_html($name,$display);
  }
  function tr_submit_html ($name,$display) {
    if ( ! $this->form) return "";
    if ( ! $this->editable) return "";
    return $this->tr_html($this->form->submit_html($name,$display),"right");
  }
  function tr_submit ($name,$display) {	print $this->tr_submit_html ($name,$display); }


  ////////////////////////////////////////
  function set_width ($width) {
    $old=$this->width;
    $this->width=$width;
    return $old;
  }
  function set_height ($height) {
    $old=$this->height;
    $this->height=$height;
    return $old;
  }

  // give a form_varname if the field can be edited 
  function th_td ($title,$value,$form_varname="",$options=NULL) {
    print $this->th_td_html ($title,$value,$form_varname,$options);
  }
  function th_td_html ($title,$value,$form_varname="",$options=NULL) {
    if (!$options) $options = array();
    if ( ! ($this->editable && $form_varname) ) {
      // xxx hack: if input_type is select, look for the 'value' option to display current value
      if ($options['input_type'] == "select") 
	$value=$options['value'];
      return "<tr><th>$title</th><td>$value</td></tr>";
    } else {
      // use options if provided, otherwise the latest set_ function 
      if (array_key_exists('input_type',$options)) $input_type=$options['input_type'];
      else $input_type=$this->input_type;
      if (array_key_exists('width',$options)) $width=$options['width'];
      else $width=$this->width;
      if (array_key_exists('height',$options)) $height=$options['height'];
      else $height=$this->height;

      $html="";
      $html .= "<tr><th><label for='$form_varname'>$title</label></th>";
      $html .= "<td>";
      // xxx hack: if input_type is select : user provides the input field verbatim
      if ( $input_type == "select" ) {
	$html .= $value;
      } else if ($input_type == "textarea") {
	$html .= "<textarea name='$form_varname'";
	if ($width) $html .= " cols=$width";
	if ($height) $html .= " rows=$height";
	$html .= ">$value</textarea>";
      } else {
	// set id too 
	$html .= "<input type='$input_type' name='$form_varname' id='$form_varname' value='$value'";
	if ($width) $html .= " size='$width'";
	// handle event callbacks
	$html .= PlekitForm::attributes($options);
	$html .= "/>";
      }
      $html .= "</td></tr>";
      return $html;
    }
  }

  // same but the values are multiple and displayed in an embedded vertical table (not editable)
  function th_tds($title,$list) { print $this->th_tds_html($title,$list); }
  function th_tds_html($title,$list) {
    return $this->th_td_html($title,plc_vertical_table($list,"foo"));
  }

  // only for special cases, not editable 
  function th_th ($th1,$th2) {	print $this->th_th_html ($th1, $th2);}
  function th_th_html ($th1, $th2) {
    return "<tr><th>$th1</th><th>$th2</th></tr>";
  }

  // 1 item, colspan=2
  function tr($title,$align=NULL) { print $this->tr_html($title,$align);}
  function tr_html($title,$align=NULL) {
    $result="<tr><td colspan='2'";
    if ($align) $result .= " style='text-align:$align'";
    $result .=">$title</td></tr>";
    return $result;
  }
  
  // a dummy line for getting some air
  function space () { print $this->space_html(); }
  function space_html () { return "<tr><td colspan='2'>&nbsp;</td></tr>\n"; }

}

?>
