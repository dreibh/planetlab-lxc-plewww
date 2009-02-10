<?php
// $Id: index.php 11949 2009-02-09 17:57:16Z thierry $

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

//// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_minitabs.php';
require_once 'plc_details.php';
require_once 'plc_datepicker.php';
  
//set default title
drupal_set_title('Events choser');

// this needs to be fine-tuned anyway
if ( ! plc_is_admin()) {
  drupal_set_error("You need admin role to see this page.");
  return;
 }

//////////////////////////////////////////////////////////// form

$tabs=array();
$tabs['Clear']=l_events();
$tabs['Sites']=l_sites();
$tabs['Users']=l_persons();
$tabs['Nodes']=l_nodes();
$tabs['Slices']=l_slices();
plc_tabs ($tabs);

// fill out dates from now if not specified
$from_picker = new PlcDatepicker ('from_date','From (inclusive)',array('inline'=>true));
$from_picker->today();
$until_picker = new PlcDatepicker ('until_date','Until (inclusive)',array('inline'=>true));
$until_picker->today();

$form=new PlcForm(l_events(),array(),'GET');
$form->start();

$details = new PlcDetails (true);
$details->start();

$details->single ($form->submit_html('submit','Show Events'),'center');
$details->space();

$details->line ( $form->radio_html ('events','type','Event',true) . "Events",
		 $form->text_html('event','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'events.checked=true')));
$details->line ( $form->radio_html ('sites','type','Site',false) . "Sites",
		 $form->text_html('site','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'sites.checked=true')));
$details->line ( $form->radio_html ('persons','type','Person',false) . "Persons",
		 $form->text_html('person','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'persons.checked=true')));
$details->line ( $form->radio_html ('nodes','type','Node',false) . "Nodes",
		 $form->text_html('node','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'nodes.checked=true')));
$details->line ( $form->radio_html ('slices','type','Slice',false) . "Slices",
		 $form->text_html('slice','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'slices.checked=true')));

$details->space();
$details->single ($form->submit_html('submit','Show Events'),'center');

$details->space();
$details->line_th(html_div($from_picker->html()) , html_div($until_picker->html()));

$details->end();
$form->end();

  // Print footer
include 'plc_footer.php';

?>

