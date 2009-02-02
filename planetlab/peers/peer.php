<?php

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
require_once 'plc_details.php';
require_once 'plc_minitabs.php';

$tabs=array();
$tabs['Back to peers list']=l_peers();
plc_tabs ($tabs);

// -------------------- 
// recognized URL arguments
if ( $_GET['peername'] ) {
  $peername= $_GET['peername'];
  $peers = $api->GetPeers( array( $peername ), array( "peer_id" ) );
  $peer_id=$peers[0]['peer_id'];

 } else {
  $peer_id=intval($_GET['id']);
 }

if ( ! $peer_id ) { plc_error('Malformed URL - id not set'); return; }

// make the api call to pull that peers DATA
$peers= $api->GetPeers( array( $peer_id ) );
$peer = $peers[0];
$peer_id=$peer['peer_id'];

drupal_set_title("Details for Peer " . $peername);

plc_details_start();
plc_details_line("Peer name",$peer['peername']);
plc_details_line("Short name",$peer['shortname']);
plc_details_line("Hierarchical name",$peer['hrn_root']);
plc_details_line("API URL",$peer['peer_url']);

$nb=sizeof($peer['site_ids']);
plc_details_line("Number of sites",href(l_sites_peer($peer_id),"$nb sites"));
$nb=sizeof($peer['node_ids']);
plc_details_line("Number of nodes",href(l_nodes_peer($peer_id),"$nb nodes"));
$nb=sizeof($peer['person_ids']);
plc_details_line("Number of users",href(l_persons_peer($peer_id),"$nb users"));
$nb=sizeof($peer['slice_ids']);
plc_details_line("Number of slices",href(l_slices_peer($peer_id),"$nb slices"));
plc_details_end();

// Print footer
include 'plc_footer.php';

?>
