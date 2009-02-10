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
$tabs['Tag Types']=array('url'=>l_tags(),'bubble'=>"Create a new tag type");
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
$headers['Name']="string";
$headers['Description']="string";
$headers['Category']="string";
$headers['Min role']="string";
$headers["Id"]="int";
if (plc_is_admin()) $headers[plc_delete_icon()]="none";

$form=new PlcForm(l_actions(),NULL);
$form->start();

$table = new PlcTable("tags",$headers,0);
$table->start();

$roles_hash=plc_role_global_hash($api);

$description_width=40;

foreach( $tag_types as $tag_type ) {
  $role_name=$roles_hash[$tag_type['min_role_id']];

  $table->row_start();
  $tag_type_id=$tag_type['tag_type_id'];
  $table->cell(href(l_tag($tag_type_id),$tag_type['tagname']));
  $table->cell(wordwrap($tag_type['description'],$description_width,"<br/>"));
  $table->cell($tag_type['category']);
  $table->cell($role_name);
  $table->cell($tag_type_id);
  if (plc_is_admin()) 
    $table->cell ($form->checkbox_html('tag_type_ids[]',$tag_type_id));
  $table->row_end();
}

if (plc_is_admin()) {
  $table->tfoot_start();

  $table->row_start();
  $table->cell($form->submit_html ("delete-tag-types","Remove tags"),
	       $table->columns(),"right");
  $table->row_end();

  // an inline area to add a tag type
  $table->row_start();
  
  // build the role selector
  $relevant_roles = $api->GetRoles( array("~role_id"=>$role_ids));
  function selector_argument ($role) { return array('display'=>$role['name'],"value"=>$role['role_id']); }
  $selectors=array_map("selector_argument",$relevant_roles);
  $role_input=$form->select_html("min_role_id",$selectors,"Role");

  $table->cell($form->text_html('tagname',''));
  $table->cell($form->textarea_html('description','',$description_width,2));
  $table->cell($form->text_html('category',''));
  $table->cell($role_input);
  $table->cell($form->submit_html("add-tag-type","Add Type"),2);
  $table->row_end();
 }

$table->end();
$form->end();

// Print footer
include 'plc_footer.php';

?>
