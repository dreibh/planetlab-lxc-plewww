<?php

require_once 'prototype.php';
require_once 'nifty.php';

drupal_set_html_head('
<script type="text/javascript" src="/plekit/toggle/toggle.js"></script>
<link href="/plekit/toggle/toggle.css" rel="stylesheet" type="text/css" />
');

// This is for creating an area that users can hide and show
// It is logically made of 3 parts:
// (*) area is what gets hidden and shown
// (*) trigger is the area that can be clicked for toggling
// (*) image contains a visual indication of the current status
// 
// constructor needs 
// (*) id:	an 'id', used for naming the three parts
// (*) trigger:	the html text for the trigger
// (*) options:	a hash that can define
//	- trigger-tagname : to be used instead of <span> for wrapping the trigger
//	- bubble : might not work if trigger-tagname is redefined
//	- init-hidden : start hidden rather than visible
// 
// methods are as follows
// (*) trigger_html ():	return the html code for the trigger
// (*) image_html ():	returns the html code for the image
// (*) area_start ():	because we have too many places where php 'prints' code: instead 
// (*) area_end():	  of returning it, we do not expect the code for the area to be passed
//			  so these methods can be used to delimit the area in question

class PlekitToggle {
  // mandatory
  var $id;
  var $nifty;
  var $info_div = "";

  function PlekitToggle ($id,$trigger,$options=NULL) {
    $this->id = $id;
    $this->trigger=$trigger;
    if ( ! $options ) $options = array();
    if (array_key_exists ('visible',$options)) {
      $options['start-hidden'] = ! $options['visible'];
      unset ($options['visible']);
    }

    if (array_key_exists ('info_div',$options)) {
	$this->info_div = $options['info_div'];
    }

    if (!isset ($options['start-hidden'])) $options['start-hidden']=false;
    $this->options = $options;
  }

  // the simple, usual way to use it :
  // a container that contains the switch and the area in sequence
  function start ()		{ print $this->start_html(); }
  function start_html () {
    $html = "";
    $html .= $this->container_start();
    $html .= $this->trigger_html();
    $html .= $this->area_start_html();
    return $html;
  }

  function end ()		{ print $this->end_html(); }
  function end_html () {
    $html = "";
    $html .= $this->area_end_html();
    $html .= $this->container_end();
    return $html;
  }


  // create two images that get shown/hidden - could not find a better way to do it
  function image_html () {
    $html="";
    if ( ! $this->options['start-hidden'])	{ $x1=""; $x2=" style='display:none'"; }
    else					{ $x2=""; $x1=" style='display:none'"; }
    $image_id=$this->id_name('image-visible');
    $html .= "<img id='$image_id' class='plc-toggle-visible' src='/plekit/icons/toggle-visible.png'$x1";
    $html .= " alt='Hide this section' />";
    $image_id=$this->id_name('image-hidden');
    $html .= "<img id='$image_id' class='plc-toggle-hidden' src='/plekit/icons/toggle-hidden.png'$x2";
    $html .= " alt='Show this section' />";
    return $html;
  }

  function trigger ()		{ print $this->trigger_html(); }
  function trigger_html () {
    $trigger_id=$this->id_name('trigger');
    if (array_key_exists ('trigger-tagname',$this->options)) $tagname=$this->options['trigger-tagname'];
    if (empty($tagname)) $tagname="span";
    $bubble="";
    if (array_key_exists ('bubble',$this->options)) $bubble=$this->options['bubble'];
    
    $html="<$tagname";
    $html .= " id='$trigger_id'";
    $html .= " class='plc-toggle-trigger'";
    if ($bubble) $html .= " title='$bubble'";
    $html .= " onclick=\"plc_toggle('$this->id')\"";
    $html .= ">";
    $html .= $this->image_html();
    $html .= $this->trigger;
    $html .= "</$tagname>";
    if ($this->info_div != "")
      $html .= "&nbsp;(<a href=javascript:plc_show_toggle_info('$this->info_div','$this->id')>?</a>)";
    return $html;
  }

  function area_start () { print $this->area_start_html(); }
  function area_start_html () {
    $area_id=$this->id_name('area');
    $html="";
    $html .= "<div";
    $html .= " class='plc-toggle-area'";
    $html .= " id='$area_id'";
    if ($this->options['start-hidden']) $html .= " style='display:none'";
    $html .= ">";
    return $html;
  }

  function area_end () { print $this->area_end_html(); }
  function area_end_html () {
    return "</div>";
  }

  /* if desired, you can embed the whole (trigger+area) in another div for visual effects */
  function container_start ()		{ print $this->container_start_html(); }
  function container_start_html ()	{ 
    $id=$this->id_name('container');
    $this->nifty=new PlekitNifty ($id,'plc-toggle-container','medium');
    return $this->nifty->start_html();
  }

  function container_end ()		{ print $this->container_end_html(); }
  function container_end_html ()	{ return $this->nifty->end_html(); }

  // build id names
  function id_name ($zonename) { return "toggle-$zonename-$this->id"; }

}

?>    
