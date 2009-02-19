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
require_once 'linetabs.php';
require_once 'details.php';
require_once 'datepicker.php';
  
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
plekit_linetabs ($tabs);

// fill out dates from now if not specified
$from_picker = new PlekitDatepicker ('from_date','From (inclusive)',array('inline'=>true));
$from_picker->today();
$until_picker = new PlekitDatepicker ('until_date','Until (inclusive)',array('inline'=>true));
$until_picker->today();

$form=new PlekitForm(l_events(),array(),'GET');
$form->start();

$details = new PlekitDetails (true);
$details->start();

$details->tr ($form->submit_html('submit','Show Events'),'center');
$details->space();

$details->th_td ( $form->radio_html ('type','Event',array('id'=>'events','checked'=>true)) . "Events",
		 $form->text_html('event','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'events.checked=true')));
$details->th_td ( $form->radio_html ('type','Site',array('id'=>'sites')) . "Sites",
		 $form->text_html('site','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'sites.checked=true')));
$details->th_td ( $form->radio_html ('type','Person',array('id'=>'persons')) . "Persons",
		 $form->text_html('person','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'persons.checked=true')));
$details->th_td ( $form->radio_html ('type','Node',array('id'=>'nodes')) . "Nodes",
		 $form->text_html('node','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'nodes.checked=true')));
$details->th_td ( $form->radio_html ('type','Slice',array('id'=>'slices')) . "Slices",
		 $form->text_html('slice','',array('width'=>30,'onSelect'=>'submit()', 'onFocus'=>'slices.checked=true')));

$details->space();
$details->tr ($form->submit_html('submit','Show Events'),'center');

$details->space();
$details->th_th(html_div($from_picker->html()) , html_div($until_picker->html()));

$details->end();
$form->end();

//plekit_linetabs ($tabs,"bottom");

  // Print footer
include 'plc_footer.php';

?>

