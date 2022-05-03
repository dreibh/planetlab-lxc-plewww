<?php

require_once 'prototype.php';
require_once 'jstorage.php';
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
//	- visible : if set to false, start hidden rather than visible
//	- info-text : the text for help on the tab
//	- info-visible : whether info needs to be visible at startup
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

  function __construct ($id,$trigger,$options=NULL) {
    $this->id = $id;
    $this->trigger=$trigger;
    if ( ! $options ) $options = array();
    // 'visible' may be set or not; if set to NULL it's considered as undefined
    // so using NULL as the default means 'select from local storage i.e. last status'
    if (array_key_exists ('visible',$options) && $options['visible']==NULL)
      unset ($options['visible']);
    // start-hidden is internal and is always set
    if (array_key_exists ('visible',$options)) {
      $options['start-hidden'] = ! $options['visible'];
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
    $html .= $this->info_html();
    return $html;
  }

  function end ()		{ print $this->end_html(); }
  function end_html () {
    $html = "";
    $html .= $this->area_end_html();
    $html .= $this->container_end();
    // if 'visible' is not set, set or or off from local storage
    if ( ! array_key_exists('visible',$this->options) )
      $html .= $this->visible_from_store_html();
    return $html;
  }

  function visible_from_store_html() {
    $id=$this->id;
    $html = "";
    $html .= "<script type='text/javascript'>";
    // javascript code can't take -
    //    $idj=str_replace('-','_',$id);
    //    $html .= "function init_$idj () { pletoggle_from_store('$id');}";
    //    $html .= "Event.observe(window,'load',init_$idj);";
    $html .= "pletoggle_from_store('$id');";
    $html .= "</script>";
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
    $html .= " onclick=\"pletoggle_toggle('$this->id')\"";
    $html .= ">";
    $html .= $this->image_html();
    $html .= $this->trigger;
    $html .= "</$tagname>";
    if (array_key_exists ('info-text',$this->options)) {
      $id=$this->id;
      $html .= "<span class='toggle-info-button' onClick='pletoggle_toggle_info(\"$id\");'><img height=20 src='/planetlab/icons/info.png' alt='close info'/></span>";
    }
    return $html;
  }

  function info()		{ print $this->info_html();}
  function info_html () {
    if (! array_key_exists ('info-text',$this->options)) return "";

    // compute if info should be visible at startup
    // xxx in fact the default should be fetched in the browser storage xxx
    $info_visible=TRUE;
    // if info-visible is set, use this value
    if (array_key_exists ('info-visible',$this->options))
      $info_visible=$this->options['info-visible'];

    $id=$this->id;
    $div_id=$this->id_name('info');
    $html="";
    $html .= "<div class='toggle-info'";
    $html .= " id='$div_id'";
    if (!$info_visible) $html .= " style='display:none'";
    $html .= ">";
    // tmp
    $html .= "<table class='center'><tr><td class='top'>";
    $html .= $this->options['info-text'];
    $html .= "</td><td class='top'><span onClick='pletoggle_toggle_info(\"$id\");'><img height=20 class='reset' src='/planetlab/icons/close.png' alt='toggle info' /></span>";
    $html .= "</td></tr></table></div>";
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
