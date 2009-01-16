<?php

// $Id$

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_minitabs.php';
require_once 'plc_tables.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

// -------------------- 
$pattern=$_GET['pattern'];
$peerscope=$_GET['peerscope'];

drupal_set_title('Nodes');

$minitabs=array("Old page"=>"/db/nodes/index.php",
	       "About"=>"/db/about.php",
	       "Logout"=>"/planetlab/logout.php",
	       "And others"=>"http://www.google.com",
	       "For demo"=>"/undefined");
plc_show_options($minitabs);

// -------------------- 
$peer_filter=array();

// fetch nodes - set pattern in the url for server-side filtering
$node_columns=array('hostname','site_id','node_id','boot_state','interface_ids','peer_id');
if ($pattern) {
  $node_filter['hostname']=$pattern;
 } else {
  $node_filter=array('hostname'=>"*");
 }

// server-side selection on peerscope
list ( $peer_filter, $peer_label) = plc_peer_info($api,$_GET['peerscope']);
$node_filter=array_merge($node_filter,$peer_filter);

// go
$nodes=$api->GetNodes($node_filter,$node_columns);

// build site_ids - interface_ids
$site_ids=array();
$interface_ids=array();
foreach ($nodes as $node) {
  $site_ids []= $node['site_id'];
  $interface_ids = array_merge ($interface_ids,$node['interface_ids']);
}

// fetch related interfaces
$interface_columns=array('ip','node_id','interface_id');
$interface_filter=array('is_primary'=>TRUE,'interface_id'=>$interface_ids);
$interfaces=$api->GetInterfaces($interface_filter,$interface_columns);

$interface_hash=array();
foreach ($interfaces as $interface) {
    $interface_hash[$interface['node_id']]=$interface;
}

// fetch related sites
$site_columns=array('site_id','login_base');
$site_filter=array('site_id'=>$site_ids);
$sites=$api->GetSites($site_filter,$site_columns);

$site_hash=array();
foreach ($sites as $site) {
    $site_hash[$site['site_id']]=$site;
}

// fetch peers
$peer_columns=array('peer_id','shortname');
$peer_filter=array();
$peers = $api->GetPeers($peer_filter,$peer_columns);

$peer_hash=array();
foreach ($peers as $peer) {
    $peer_hash[$peer['peer_id']]=$peer;
}



$columns = array ("Peer"=>"string",
		  "Region"=>"string",
		  "Site"=>"string",
		  "State"=>"string",
		  "Hostname"=>"string",
		  "IP"=>"IPAddress",
		  "Load"=>"int",
		  "Avg Load"=>"float");

# initial sort on hostnames
plc_table_start("nodes",$columns,4);

// write rows
$fake1=1; $fake2=3.14; $fake_i=0;
foreach ($nodes as $node) {
    $hostname=$node['hostname'];
    $node_id=$node['node_id'];
    $site_id=$node['site_id'];
    $site=$site_hash[$site_id];
    $login_base = $site['login_base'];
    $node_id=$node['node_id'];
    $ip=$interface_hash[$node['node_id']]['ip'];
    $interface_id=$interface_hash[$node['node_id']]['interface_id'];
    if ( ! $node['peer_id'] ) {
      $shortname="local";
    } else {
      $shortname=$peer_hash[$node['peer_id']]['shortname'];
    }
    printf ('<tr id="%s">',$hostname);
    printf ('<td class="plc_table"> %s </td>',$shortname);
    printf ('<td class="plc_table"> %s </td>',topdomain($hostname));
    printf ('<td class="plc_table"> <a href="/db/sites/index.php?id=%s">%s</a></td>',$site_id,$login_base);
    printf ('<td class="plc_table"> %s </td>',$node['boot_state']);
    printf ('<td class="plc_table"> <a href="/db/nodes/index.php?id=%s">%s</a></td>',$node_id,$hostname);
    printf ('<td class="plc_table"> <a href="/db/nodes/interfaces.php?id=%s">%s</a></td>', $interface_id,$ip);
    printf ('<td class="plc_table"> %s </td>', $fake1);
    printf ('<td class="plc_table"> %s </td>', $fake2);
    printf ( '</tr>');
				 
    if ($fake_i % 5 == 0) $fake1 += 3; 
    if ($fake_i % 3 == 0) $fake2 +=5; else $fake2 -= $fake_i;
    $fake_i += 1;
}

plc_table_end();

plc_table_notes();
?>

