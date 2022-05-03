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
require_once 'table.php';
require_once 'details.php';
require_once 'toggle.php';

// -------------------- 
// recognized URL arguments
$nodegroup_id=intval(get_array($_GET, 'id'));
if ( ! $nodegroup_id ) { plc_error('Malformed URL - id not set'); return; }

////////////////////
// Get all columns as we focus on only one entry
$nodegroups= $api->GetNodeGroups( array($nodegroup_id));

if (empty($nodegroups)) {
  drupal_set_message ("NodeGroup " . $nodegroup_id . " not found");
  return;
 }

$nodegroup=$nodegroups[0];
$node_ids=$nodegroup['node_ids'];
$tagname=$nodegroup['tagname'];

# fetch corresponding nodes
$node_columns = array("hostname","node_id");

$nodes = $api->GetNodes( $node_ids, $node_columns);

$tabs = array();

$tabs []= tab_tags();
$tabs []= tab_nodegroups();
$tabs []= tab_nodes_local();

drupal_set_title("Details for node group " . $nodegroup['groupname']);
plekit_linetabs($tabs);

$toggle=new PlekitToggle('details','Details');
$toggle->start();
$details=new PlekitDetails(plc_is_admin());
$details->start();
$details->form_start(l_actions(),array("action"=>"update-nodegroup", "nodegroup_id"=>$nodegroup_id));
$details->th_td ("Node group name",$nodegroup['groupname'],'groupname');
// can't change the target tag
$details->th_td ("Based on tag",href(l_tag($nodegroup['tag_type_id']),$tagname));
$details->th_td("Matching value",$nodegroup['value'],'value');
$details->th_td("# nodes",count($nodegroup['node_ids']));
$details->tr_submit("submit","Update Nodegroup");
$details->form_end();
$details->end();

$toggle->end();

// xxx : add & delete buttons would make sense here too
$toggle=new PlekitToggle('nodes',"Nodes");
$toggle->start();

$headers["Hostname"]="string";

$table = new PlekitTable("nodegroup_nodes",$headers,0,array('search_width'=>15));
$table->start();
if ($nodes) foreach ($nodes as $node) {
  $table->row_start ();
  $table->cell ( href (l_node ($node['node_id']),$node['hostname']));
  $table->row_end ();
}

$table->end ();
$toggle->end();

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
