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
require_once 'plc_details.php';
require_once 'plc_tables.php';
require_once 'plc_forms.php';

// -------------------- 
// recognized URL arguments
$tag_type_id=intval($_GET['id']);
if ( ! $tag_type_id ) { 
  plc_error('Malformed URL - id not set'); 
  return;
 }

// --- decoration
$title="Tag Type";
$tabs=array();
$tabs['All Types']=array('url'=>l_tags(),'bubble'=>"All Tag Types");

// -------------------- 
$tag_types= $api->GetTagTypes( array( $tag_type_id ) );
$tag_type=$tag_types[0];
  
$tagname=$tag_type['tagname'];
$min_role_id= $tag_type['min_role_id'];
$description= $tag_type['description'];
$category=$tag_type['category'];

// where is it used 
$filter=array('tag_type_id'=>$tag_type_id);
$node_tags=$api->GetNodeTags($filter);
$interface_tags=$api->GetInterfaceTags($filter);
$slice_tags=$api->GetSliceTags(array_merge($filter,array("node_id"=>array())));
$sliver_tags=$api->GetSliceTags(array_merge($filter,array("~node_id"=>array())));

drupal_set_title("Details for tag type $tagname");
plc_tabs($tabs);

// ----------
$can_update=plc_is_admin();
$details=new PlcDetails ($can_update);

$details->form_start(l_actions(),array("action"=>"update-tag-type",
				       "tag_type_id"=>$tag_type_id));
$details->start();
$details->th_td("Name",$tagname,"tagname");
$details->th_td("Category",$category,"category");
$details->th_td("Description",$description,"description");

if ($can_update) {
// select the option corresponding with min_role_id
  $selectors = $details->form()->role_selectors($api,"",$min_role_id);
  $select_field = $details->form()->select_html("min_role_id",$selectors);
  // xxx would need to turn role_id into role name
  $details->th_td("Min role",$select_field,"min_role_id",array('input_type'=>'select','value'=>$min_role_id));
 } else {
  $details->th_td("Min role",$min_role_id);
 }
if ($can_update) 
  $details->tr_submit('update-tag-type',"Update tag type");

$details->space();
$details->th_td("Used in nodes",count($node_tags));
$details->th_td("Used in interfaces",count($interface_tags));
$details->th_td("Used in slices",count($slice_tags));
$details->th_td("Used in slivers",count($sliver_tags));

$details->end();
$details->form_end();

// common options for tables below
$table_options=array('notes_area'=>false, 'pagesize_area'=>false, 'search_width'=>10);

// xxx could outline values corresponding to a nodegroup
if (count ($node_tags)) {
  plc_section("Nodes");
  $table=new PlcTable ("tag_nodes",array("Hostname"=>"string","value"=>"string"),0,$table_options);
  $table->start();
  foreach ($node_tags as $node_tag) {
    $table->row_start();
    $table->cell(href(l_node($node_tag['node_id']),$node_tag['hostname']));
    $table->cell($node_tag['value']);
    $table->row_end();
  }
  $table->end();
 }

if (count ($interface_tags)) {
  plc_section("Interfaces");
  $table=new PlcTable ("tag_interfaces",array("IP"=>"IPAddress","value"=>"string"),0,$table_options);
  $table->start();
  foreach ($interface_tags as $interface_tag) {
    $table->row_start();
    $table->cell(href(l_interface($interface_tag['interface_id']),$interface_tag['ip']));
    $table->cell($interface_tag['value']);
    $table->row_end();
  }
  $table->end();
 }

// grouping both kinds of slice tags 
// xxx don't show hostnames yet
$slice_tags = array_merge ($slice_tags,$sliver_tags);
if (count ($slice_tags)) {
  plc_section("Slice and sliver tags");
  $table=new PlcTable ("tag_slices",array("Slice"=>"string","value"=>"string","Node id"=>"int"),0,$table_options);
  $table->start();
  foreach ($slice_tags as $slice_tag) {
    $table->row_start();
    $table->cell(href(l_slice($slice_tag['slice_id']),$slice_tag['name']));
    $table->cell($slice_tag['value']);
    $node_text="all";
    // sliver tag
    if ($slice_tag['node_id']) 
      $node_text=l_node($slice_tag['node_id'],$slice_tag['node_id']);
    $table->cell($node_text);
    $table->row_end();
  }
  $table->end();
 }

// Print footer
include 'plc_footer.php';

?>
