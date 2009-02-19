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
require_once 'details.php';
require_once 'linetabs.php';

$tabs=array();
$tabs['Back to peers list']=l_peers();
plekit_linetabs ($tabs);

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

$details=new PlekitDetails(false);
$details->start();
$details->th_td("Peer name",$peer['peername']);
$details->th_td("Short name",$peer['shortname']);
$details->th_td("Hierarchical name",$peer['hrn_root']);
$details->th_td("API URL",$peer['peer_url']);

$nb=sizeof($peer['site_ids']);
$details->th_td("Number of sites",href(l_sites_peer($peer_id),"$nb sites"));
$nb=sizeof($peer['node_ids']);
$details->th_td("Number of nodes",href(l_nodes_peer($peer_id),"$nb nodes"));
$nb=sizeof($peer['person_ids']);
$details->th_td("Number of users",href(l_persons_peer($peer_id),"$nb users"));
$nb=sizeof($peer['slice_ids']);
$details->th_td("Number of slices",href(l_slices_peer($peer_id),"$nb slices"));
$details->end();

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
