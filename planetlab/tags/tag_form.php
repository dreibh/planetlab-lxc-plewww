<?php

// $Id$

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_minitabs.php';
require_once 'plc_tables.php';
require_once 'plc_details.php';
require_once 'plc_forms.php';

// to create a new (action=='add-tag-type') 
// or to update an existing (action='update-tag-type','tag_type_id'=<id>)

// -------------------- 
// recognized URL arguments
$pattern=$_GET['pattern'];

// --- decoration
$title="Tag Types";
$tabs=array();
$tabs['All Types']=array('url'=>l_tags(),'bubble'=>"All Tag Types");

// -------------------- 
drupal_set_title($title);
plc_tabs($tabs);

// if its edit get the tag info
$update_mode = ( $_GET['action'] == 'update-tag-type' ) ;

if ($update_mode) {
  $tag_type_id= intval( $_GET['id'] );
  $type_info= $api->GetTagTypes( array( $tag_type_id ) );
  
  $tagname=$type_info[0]['tagname'];
  $min_role_id= $type_info[0]['min_role_id'];
  $description= $type_info[0]['description'];
  $category=$type_info[0]['category'];
 }  

// display form for tag types
plc_section($label,false);

$form = new PlcForm (l_actions(),array());
$form->start();
// XXX make this updatable ?
$details = new PlcDetails(false);
$details->start();
$details->line("Name", $form->text_html("name",$tagname,20));
$details->line("Category", $form->text_html("category",$category,30));
$details->line("Description",$form->textarea_html("description",$description,40,5));
//tmp
// select the option corresponding with min_role_id
$selector = "<select name='min_role_id'>".
  "<option value='10'>Admin</option>".
  "<option value='20'>PI</option>".
  "<option value='30'>User</option>" .
  "<option value='40'>Tech</option>" . "</select>\n";
$details->line("Min Role",$selector);
if ($update_mode) {
  $submit=$form->hidden_html ('tag_type_id',$tag_type_id) . 
    $form->submit_html('update-tag-type',"Update tag type");
 } else {
  $submit=$form->submit_html('add-tag-type',"Add tag type");
 }
$details->single ($submit,"right");

$details->end();
$form->end();

// Print footer
include 'plc_footer.php';

?>
