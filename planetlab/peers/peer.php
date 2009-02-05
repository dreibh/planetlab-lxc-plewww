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

$details=new PlcDetails(false);
$details->start();
$details->line("Peer name",$peer['peername']);
$details->line("Short name",$peer['shortname']);
$details->line("Hierarchical name",$peer['hrn_root']);
$details->line("API URL",$peer['peer_url']);

$nb=sizeof($peer['site_ids']);
$details->line("Number of sites",href(l_sites_peer($peer_id),"$nb sites"));
$nb=sizeof($peer['node_ids']);
$details->line("Number of nodes",href(l_nodes_peer($peer_id),"$nb nodes"));
$nb=sizeof($peer['person_ids']);
$details->line("Number of users",href(l_persons_peer($peer_id),"$nb users"));
$nb=sizeof($peer['slice_ids']);
$details->line("Number of slices",href(l_slices_peer($peer_id),"$nb slices"));
$details->end();

// Print footer
include 'plc_footer.php';

?>
