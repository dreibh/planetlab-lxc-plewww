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
require_once 'linetabs.php';
require_once 'details.php';
require_once 'table.php';
require_once 'form.php';
require_once 'toggle.php';

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
$tabs []= tab_tags();

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
// split slice tags into 3 families, whether this applies to the whole slice, or a nodegroup, or a node
// using filters for this purpose does not work out very well, maybe a bug in the filter stuff
// anyway this is more efficient, and we compute the related node(groups) in the same pass
$slice_tags=$api->GetSliceTags(array_merge($filter));
$count_slice=0;
$count_nodegroup=0;
$nodegroup_ids=array();
$count_node=0;
$node_ids=array();
foreach ($slice_tags as $slice_tag) {
  if ($slice_tag['node_id']) {
    $node_ids []= $slice_tag['node_id'];
    $count_node += 1;
  } else if ($slice_tag['nodegroup_id']) {
    $nodegroup_ids []= $slice_tag['nodegroup_id'];
    $count_nodegroup += 1;
  } else {
    $count_slice += 1;
  }
}

$nodes=$api->GetNodes($node_ids,array('hostname','node_id'));
$node_hash=array();
foreach ($nodes as $node) $node_hash[$node['node_id']]=$node;
$nodegroups=$api->GetNodeGroups($nodegroup_ids,array('groupname','nodegroup_id'));
$nodegroup_hash=array();
foreach ($nodegroups as $nodegroup) $nodegroup_hash[$nodegroup['nodegroup_id']]=$nodegroup;


drupal_set_title("Details for tag type $tagname");
plekit_linetabs($tabs);

// ----------
$toggle = new PlekitToggle ('details','Details');
$toggle->start();
$can_update=plc_is_admin();
$details=new PlekitDetails ($can_update);

$details->form_start(l_actions(),array("action"=>"update-tag-type",
				       "tag_type_id"=>$tag_type_id));
$details->start();
$details->th_td("Name",$tagname,"tagname");
$details->th_td("Category",$category,"category",array('width'=>30));
$details->th_td("Description",$description,"description",array('width'=>40));

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
$details->th_td("Used in slices/node",$count_node);
$details->th_td("Used in slices/nodegroup",$count_nodegroup);
$details->th_td("Used in slices",$count_slice);

$details->end();
$details->form_end();
$toggle->end();

// common options for tables below
$table_options=array('notes_area'=>false, 'pagesize_area'=>false, 'search_width'=>10);

// xxx could outline values corresponding to a nodegroup
if (count ($node_tags)) {
  $toggle=new PlekitToggle('tag_nodes',"Nodes");
  $toggle->start();
  $table=new PlekitTable ("tag_nodes",array("Hostname"=>"string","value"=>"string"),0,$table_options);
  $table->start();
  foreach ($node_tags as $node_tag) {
    $table->row_start();
    $table->cell(href(l_node($node_tag['node_id']),$node_tag['hostname']));
    $table->cell($node_tag['value']);
    $table->row_end();
  }
  $table->end();
  $toggle->end();
 }

if (count ($interface_tags)) {
  $toggle=new PlekitToggle('tag_interfaces',"Interfaces");
  $toggle->start();
  $table=new PlekitTable ("tag_interfaces",array("IP"=>"IPAddress","value"=>"string"),0,$table_options);
  $table->start();
  foreach ($interface_tags as $interface_tag) {
    $table->row_start();
    $table->cell(href(l_interface($interface_tag['interface_id']),$interface_tag['ip']));
    $table->cell($interface_tag['value']);
    $table->row_end();
  }
  $table->end();
  $toggle->end();
 }

if (count ($slice_tags)) {
  $toggle=new PlekitToggle('tag_slices',"Slice tags");
  $toggle->start();
  $headers=array();
  $headers["slice"]='string';
  $headers["value"]='string';
  $headers["node"]='string';
  $headers["nodegroup"]='string';
  $table=new PlekitTable ("tag_slices",$headers,0,$table_options);
  $table->start();
  foreach ($slice_tags as $slice_tag) {
    $table->row_start();
    $table->cell(href(l_slice($slice_tag['slice_id']),$slice_tag['name']));
    $table->cell($slice_tag['value']);

    $node_text="all";
    if ($slice_tag['node_id']) {
      $node_id=$slice_tag['node_id'];
      $node=$node_hash[$node_id];
      $node_text=l_node_obj($node);
    }
    $table->cell($node_text);

    $nodegroup_text="all";
    if ($slice_tag['nodegroup_id']) {
      $nodegroup_id=$slice_tag['nodegroup_id'];
      $nodegroup=$nodegroup_hash[$nodegroup_id];
      $nodegroup_text=l_nodegroup_obj($nodegroup);
    }
    $table->cell($nodegroup_text);

    $table->row_end();
  }
  $table->end();
  $toggle->end();
 }

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
