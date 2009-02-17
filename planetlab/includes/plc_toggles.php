<?php

  // $Id$

require_once 'prototype.php';

drupal_set_html_head('
<script type="text/javascript" src="/planetlab/js/plc_toggles.js"></script>
<link href="/planetlab/css/plc_toggles.css" rel="stylesheet" type="text/css" />
');

// This is for creating an area that users can hide and show
// It is logically made of 3 parts:
// (*) area is what gets hidden and shown
// (*) switch is the area that can be clicked for toggling
// (*) image contains a visual indication of the current status
// 
// constructor needs 
// (*) id:	an 'id', used for naming the three parts
// (*) switch:	the html text for the switch
// (*) options:	a hash that can define
//	- switch-tagname : to be used instead of <span> for wrapping the switch
//	- switch-bubble : might not work if switch-tagname is redefined
//	- init-hidden : start hidden rather than visible
// 
// methods are as follows
// (*) switch_html ():	return the html code for the switch
// (*) image_html ():	returns the html code for the image
// (*) area_start ():	because we have too many places where php 'prints' code instead 
// (*) area_end():	  of returning it, we do not expect the code for the area to be passed
//			  so these methods can be used to delimit the area in question

class PlcToggle {
  // mandatory
  var $id;

  function PlcToggle ($id,$switch,$options=NULL) {
    $this->id = $id;
    $this->switch=$switch;
    if ( ! $options ) $options = array();
    $this->options = $options;
  }

  function id_name ($zonename) { return "toggle-$zonename-$this->id"; }

  // create two images that get shown/hidden - could not find a better way to do it
  function image_html () {
    $html="";
    if ( ! $this->options['start-hidden'])	{ $x1=""; $x2=" style='display:none'"; }
    else					{ $x2=""; $x1=" style='display:none'"; }
    $image_id=$this->id_name('image-visible');
    $html .= "<img id=$image_id class='plc-toggle-visible' src='/planetlab/icons/toggle-visible.png'$x1>";
    $image_id=$this->id_name('image-hidden');
    $html .= "<img id=$image_id class='plc-toggle-hidden' src='/planetlab/icons/toggle-hidden.png'$x2>";
    return $html;
  }

  // don't define switch as it's a php keyword 
  function switch_html () {
    $switch_id=$this->id_name('switch');
    $tagname='span';
    if (array_key_exists ('switch-tagname',$this->options)) $tagname=$this->options['switch-tagname'];
    if (array_key_exists ('switch-bubble',$this->options)) $bubble=$this->options['switch-bubble'];
    
    $html="<$tagname";
    $html .= " id=$switch_id";
    $html .= " class=plc-toggle-switch";
    if ($bubble) $html .= " title='$bubble'";
    $html .= " onclick=\"plc_toggle('$this->id')\"";
    $html .= ">";
    $html .= $this->image_html();
    $html .= $this->switch;
    $html .= "</$tagname>";
    return $html;
  }

  function area_start () { print $this->area_start_html(); }
  function area_start_html () {
    $area_id=$this->id_name('area');
    $html="";
    $html .= "<div";
    $html .= " class=plc-toggle-area";
    $html .= " id=$area_id";
    if ($this->options['start-hidden']) $html .= " style='display:none'";
    $html .= ">";
    return $html;
  }

  function area_end () { print $this->area_end_html(); }
  function area_end_html () {
    return "</div>";
  }

}

?>    
