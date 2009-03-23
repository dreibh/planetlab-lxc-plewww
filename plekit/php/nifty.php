<?php

  // $Id$

require_once 'prototype.php';

drupal_set_html_head('
<script type="text/javascript" src="/plekit/niftycorner/niftycube.js"></script>
<script type="text/javascript" src="/plekit/niftycorner/nifty_init.js"></script>
<script> Event.observe(window,"load", nifty_init); </script>
');

class PlekitNifty {
  var $id;
  var $class;
  var $size;

  function PlekitNifty ($id,$class,$size='medium') {
    $this->id = $id;
    $this->class=$class;
    $this->size=$size;
  }

  function start () { print $this->start_html(); }
  function start_html () {
    $html="";
    $html .= "<div";
    if ($this->id) $html .= " id='$this->id'";
    $html .= " class='";
    if ($this->class) $html .= $this->class . " ";
    $html .= "nifty-$this->size";
    // close the class quote
    $html .= "'>";
    return $html;
  }

  function end () { print $this->end_html();} 
  function end_html () {
    return "</div>";
  }

}

?>
