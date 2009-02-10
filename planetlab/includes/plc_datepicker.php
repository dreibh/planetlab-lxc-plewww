<?php

  // $Id$

  // see the demo at http://www.frequency-decoder.com/demo/date-picker-v4/

drupal_set_html_head('
<script type="text/javascript" src="/planetlab/datepicker/datepicker.js"></script>
<link href="/planetlab/datepicker/datepicker.css" rel="stylesheet" type="text/css" />
');

// supported options
// (*) inline (default=true)
// (*) format (default 2010/Jan/01), php-equiv. Y/M/d, which in this paradigm translates into Y-sl-m-sl-d
// (*) value : the value to display initially - default ''

class PlcDatepicker {

  var $id;

  function PlcDatepicker ($id,$display,$options=NULL) {
    $datepicker_default_options = 
      array ('inline'=>true,
	     'format'=>'Y-sl-M-sl-d',
	     'value'=>'');
    if (!$options) $options=array();
    $this->id=$id;
    $this->display=$display;
    $this->options=array_merge($datepicker_default_options,$options);
    
  }

  function html () {
    $inline=$this->options['inline'];
    $format=$this->options['format'];
    $value=$this->options['value'];

    $html="";
    $html .= "<label for='$this->id'>$this->display</label>";
    $html .= "<input size=13 type='text'";
    $html .= " class='dateformat-$format";
    $html .= " opacity-60";
    if ($inline) $html .= " display-inline";
    $html .= "' id='$this->id' name='$this->id' value='$value' />";
    return $html;
  }

  function today () {
    // works for default format only for now
    $this->options['value']=date('Y/M/d');
  }

}
