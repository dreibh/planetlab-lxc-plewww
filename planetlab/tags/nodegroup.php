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
//require_once 'plc_forms.php';
//require_once 'plc_peers.php';

// -------------------- 
// recognized URL arguments
$nodegroup_id=intval($_GET['id']);
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

$tabs ["All nodegroups"] = array ('url'=>l_nodegroups(),
				  'bubble'=>'All nodegroups');
$tabs ["All tags"] = array ('url'=>l_tags(),
			    'bubble'=>'All tags');
$tabs ["Local nodes"] = array ('url'=>l_nodes_peer('local'),
			     'bubble'=>'All local nodes');

drupal_set_title("Details for node group " . $nodegroup['groupname']);
plc_tabs($tabs);

plc_details_start();
plc_details_line ("Node group name",$nodegroup['groupname']);
plc_details_line ("Based on tag",href(l_tag($nodegroup['tag_type_id']),$tagname));
plc_details_line("Matching value",$nodegroup['value']);
plc_details_line("# nodes",count($nodegroup['node_ids']));
plc_details_end();

// xxx : add & delete buttons would make sense here too
plc_section("Nodes");

$headers["Hostname"]="string";

$table = new PlcTable("nodegroup_nodes",$headers,0,array('search_width'=>15));
$table->start();
if ($nodes) foreach ($nodes as $node) {
  $table->row_start ();
  $table->cell ( href (l_node ($node['node_id']),$node['hostname']));
  $table->row_end ();
}

$table->end ();
// Print footer
include 'plc_footer.php';

?>
