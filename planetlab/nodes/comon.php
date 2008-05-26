<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Common functions
require_once 'plc_functions.php';

// arguments, e.g. :
// http://summer.cs.princeton.edu/
// "138.96.250.12"
function plc_ip_to_int ($ip) {
  $bytes=array_map("intval",split("[.]",$ip));
  $res=0;
  foreach (range(0,3) as $i) {
    $res=256*$res+$bytes[$i];
  }
  return $res;
  }

function plc_comon_address ($ip) {
  return "address==".plc_ip_to_int($ip);
}

// builds the url to comon for the set of ips
function plc_comon_url_from_ips($comon_server_url, $ips) {
  
  $select="select='" . join("||",array_map("plc_comon_address",$ips)) . "'";

  $url=$comon_server_url . 
    "/status/tabulator.cgi?table=table_nodeviewshort&" . $select;
  return $url;

}

// we expect to GET either
// node_id
// site_id
// slice_id
// peer_id
// from that we get a set of nodes and compute a comon URL to gather them all

$fields=array("hostname","node_id","peer_id", "interface_ids");

if ($_GET['node_id']) {
  $node_id=intval($_GET['node_id']);
  $nodes=$api->GetNodes(array("node_id"=>array($node_id)),$fields);
 } else if ($_GET['site_id']) {
  $site_id=intval($_GET['site_id']);
  $nodes=$api->GetNodes(array("site_id"=>array($site_id)),$fields);
 } else if ($_GET['slice_id']) {
  $slice_id=intval($_GET['slice_id']);
  $return=$api->GetSlices(array("slice_id"=>array($slice_id)),array("node_ids"));
  $node_ids=$return[0]['node_ids'];
  $nodes=$api->GetNodes(array("node_id"=>$node_ids),$fields);
 } else if (isset($_GET['peer_id'])) {
  $peer_id=intval($_GET['peer_id']);
  if ( ($peer_id == 0) || ($peer_id == "") )
    $peer_id=NULL;
  $nodes=$api->GetNodes(array("peer_id"=>$peer_id),$fields);
 } else {
  echo "<div class='plc-warning'> Unexpected args in comon.php </div>\n";
  exit();
 }

// first pass 
// * gather interface_ids for local nodes
// * gather hostnames for foreign nodes

$interface_ids=array();
$hostnames = array();

foreach ($nodes as $node) {
  if (empty($node['peer_id'])) {
    foreach ($node['interface_ids'] as $id=>$nnid) {
      $interface_ids [] = $nnid;
    }
  } else {
    $hostnames[] = $node['hostname'];
  }
}
  
// Gather local ips from primary interfaces
// fetch primary interfaces
$local_ips=array();
$nns = $api->GetInterfaces(array("is_primary"=>TRUE,"interface_id"=>$interface_ids),
			     array("ip"));
foreach ($nns as $nn) {
  $local_ips[] = $nn['ip'];
}

plc_debug('locals',$local_ips);

// for foreign hosts we're left with dns resolving them
$remote_ips=array();
foreach ($hostnames as $hostname) {
  $resolved=gethostbyname($hostname);
  // no way to notify this
  if ($resolved == $hostname) {
  } else {
    $remote_ips[] = $resolved;
  }
}

plc_debug('remote ips',$remote_ips);


// add both lists
$all_ips=$local_ips+$remote_ips;
// compute comon URL
$url = plc_comon_url_from_ips("http://comon.cs.princeton.edu",$all_ips);

plc_debug('url',$url);

// redirect to comon
header("Location: " . $url);

?>
