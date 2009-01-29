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
require_once 'plc_forms.php';

// -------------------- 
// recognized URL arguments
$pattern=$_GET['pattern'];

// --- decoration
$title="Tag Types";
$tabs=array();
$tabs['New Tag Type']=array('url'=>l_tag_add(),'bubble'=>"Create a new tag type");
$tabs['All Nodes']=array('url'=>l_nodes(),'bubble'=>"Nodes from all peers");
$tabs['Local Nodes']=array('url'=>l_nodes(),'values'=>array('peerscope'=>'local'),'bubble'=>"All local nodes");
//$tabs['Interfaces']=l_interfaces();
$tabs['All Slices']=array('url'=>l_slices(),'bubble'=>"Slices from all peers");

// -------------------- 
drupal_set_title($title);
plc_tabs($tabs);

$tag_type_columns = array( "tag_type_id", "tagname", "category", "description", "min_role_id" );

$tag_type_filter=NULL;
if ($pattern) 
  $tag_type_filter['category']=$pattern;

// get types
$tag_types= $api->GetTagTypes($tag_type_filter, $tag_type_columns);
  
$headers=array();
// delete button
if (plc_is_admin()) $headers[' ']="none";
$headers["Id"]="int";
$headers['Name']="string";
$headers['Description']="string";
$headers['Min role']="string";
$headers['Category']="string";

plc_table_start("tags",$headers,1);

$roles_hash=plc_role_global_hash($api);

foreach( $tag_types as $tag_type ) {
  $role_name=$roles_hash[$tag_type['min_role_id']];

  plc_table_row_start();
  $id=$tag_type['tag_type_id'];
  if (plc_is_admin()) 
    plc_table_cell(plc_delete_link_button ('tag_action.php?del_type='. $id,
					   $tag_type['tagname']));
  plc_table_cell($id);
  plc_table_cell(href(l_tag_update($id),$tag_type['tagname']));
  plc_table_cell(wordwrap($tag_type['description'],40,"<br/>"));
  plc_table_cell($role_name);
  plc_table_cell($tag_type['category']);
  plc_table_row_end();
}
$footers=array();
if (plc_is_admin()) 
  $footers[]=plc_table_td_text(plc_form_simple_button(l_tag_add(),"Add a Tag Type","GET"),6,"right");

plc_table_end("tags",array('footers'=>$footers));

// Print footer
include 'plc_footer.php';

?>
