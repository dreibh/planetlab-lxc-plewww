<?php

// $Id: index.php 11577 2009-01-16 06:29:51Z thierry $

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

// tmp 
//require_once 'plc_sorts.php';
// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

// -------------------- 
// recognized URL arguments
$node_id=intval($_GET['id']);
if ( ! $node_id ) { plc_error('Malformed URL - id not set'); return; }

////////////////////
// Get all columns as we focus on only one entry
$nodes= $api->GetNodes( array($node_id));

if (empty($nodes)) {
  drupal_set_message ("Node " . $node_id . " not found");
 } else {
  $node=$nodes[0];
    // node info
  $hostname= $node['hostname'];
  $boot_state= $node['boot_state'];
  $site_id= $node['site_id'];
  $model= $node['model'];
  $version= $node['version'];
  $node_type = $node['node_type'];

  // arrays of ids of node info
  $slice_ids= $node['slice_ids'];
  $conf_file_ids= $node['conf_file_ids'];
  $interface_ids= $node['interface_ids'];
  $nodegroup_ids= $node['nodegroup_ids'];
  $pcu_ids= $node['pcu_ids'];

  // get peer
  $peer_id= $node['peer_id'];

  // gets site info
  $sites= $api->GetSites( array( $site_id ) );
  $site=$sites[0];
  $site_name= $site['name'];
  $site_node_ids= $site['node_ids'];

  $site_node_hash=array();
  if( !empty( $site_node_ids ) ) {
    // get site node info basics
    $site_nodes= $api->GetNodes( $site_node_ids );
    
    foreach( $site_nodes as $site_node ) {
      $site_node_hash[$site_node['node_id']]= $site_node['hostname'];
    }
  }
  
  // gets slice info for each slice
  if( !empty( $slice_ids ) )
    $slices= $api->GetSlices( $slice_ids, array( "slice_id", "name" , "peer_id" ) );

  // gets conf file info
  if( !empty( $conf_file_ids ) )
    $conf_files= $api->GetConfFiles( $conf_file_ids );

  // get interface info
  if( !empty( $interface_ids ) )
    $interfaces= $api->GetInterfaces( $interface_ids );

  // gets nodegroup info
  if( !empty( $nodegroup_ids ) )
    $nodegroups= $api->GetNodeGroups( $nodegroup_ids, array("groupname","tag_type_id","value"));

  // xxx Thierry : disabling call to GetEvents, that gets the session deleted in the DB
  // needs being reworked

  // gets pcu and port info key to both is $pcu_id
  if( !empty( $pcu_ids ) )
    $PCUs= $api->GetPCUs( $pcu_ids );


  // display node info
  plc_peer_block_start ($peer_hash,$peer_id);
  
  drupal_set_title("Details for node " . $hostname);
  
  // extra privileges to admins, and (pi||tech) on this site
  $extra_privileges = plc_is_admin () || ( plc_in_site($site_id) && ( plc_is_pi() || plc_is_tech()));
  
  $tabs=array();
  // available actions
  if ( ! $peer_id  && $extra_privileges ) {
    
    $tabs['Update'] = array ('url'=>"/db/nodes/node_actions.php",
			     'method'=>'POST',
			     'values'=>array('action'=>'prompt-update','node_id'=>$node_id));
    $tabs['Delete'] = array ('url'=>"/db/nodes/node_actions.php",
			     'method'=>'POST',
			     'values'=>array('action'=>'delete','node_id'=>$node_id),
			     'confirm'=>'Are you sure to delete ' . $hostname. ' ?');
    // xxx subject to roles
    $tabs["Add Interface"]=l_interface_add_u($node_id);
    $tabs["Comon"]=l_comon("node_id",$node_id);
    $tabs["Events"]=l_event("Node","node",$node_id);

    $tabs["All nodes"]=l_nodes();

    plc_tabs($tabs);

  }    
  
  echo "<hr />";
  echo "<table><tbody>\n";
  
  echo "<tr><th>Hostname: </th><td> $hostname </td></tr>\n";
  echo "<tr><th>Type: </th><td> $node_type</td></tr>\n";
  echo "<tr><th>Model: </th><td> $model</td></tr>\n";
  echo "<tr><th>Version: </th><td> $version</td></tr>\n";
    
  echo "<tr><th>Boot State: </th><td>";
  if ($peer_id) {
    echo $boot_state;
  } else {
    echo "<form name='bootstate' action='/db/nodes/node_actions.php' method=post>\n";
    echo "<input type=hidden name='node_id' value='$node_id'>\n";
    echo "<input type=hidden name='action' value='boot-state'>\n";
    echo "<select name='boot_state' onChange=\"submit();\">\n";

    $states= array( 'boot'=>'Boot', 'dbg'=>'Debug', 'inst'=>'Install', 'rins'=>'Reinstall', 'rcnf'=>'Reconfigure', 'new'=>'New' );

    foreach( $states as $key => $val ) {
      echo "<option value='$key'";
      
      if( $key == $boot_state )
	echo " selected";
      
      echo ">$val</option>\n";
      
    }
  
    echo "</select></input></form>";
  }
  echo "</td></tr>\n";

  if ( ! $peer_id  && $extra_privileges) {

    echo "<tr><th>Download </th><td>";
    echo "<form name='download' action='/db/nodes/node_actions.php' method='post'>\n";
    echo "<input type=hidden name='node_id' value='$node_id'></input>\n";
    echo "<select name='action' onChange='submit();'>\n";
    echo "<option value='' selected='selected'> Download Mode </option>\n";
    echo "<option value='' disabled='disabled'> -- All in one images -- </option>"; 
    echo "<option value='download-node-iso' $new_api_only> Download ISO image for $hostname</option>\n";
    echo "<option value='download-node-usb' $new_api_only> Download USB image for $hostname</option>\n";
    echo "<option value='' disabled='disabled'> -- Floppy + generic image -- </option>"; 
    echo "<option value='download-node-floppy'> Download Floppy file for $hostname</option>\n";
    echo "<option value='download-generic-iso' $new_api_only> Download generic ISO image (requires floppy) </option>\n";
    echo "<option value='download-generic-usb' $new_api_only> Download generic USB image (requires floppy) </option>\n";
    echo "</select></form>";
    echo "</td></tr>\n";

  }

  // site info and all site nodes
  echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
  echo "<tr><th>Site: </th><td> <a href='/db/sites/index.php?id=$site_id'>$site_name</a></td></tr>\n";
  echo "<tr><th>All site nodes: </th><td>";
  if (empty($site_node_hash)) {
    echo "<span class='plc-warning'>Site has no node</span>";
  } else {
    foreach( $site_node_hash as $key => $val ) {
      echo "<a href=index.php?id=$key>$val</a><br />";
    }
  }
  echo "</td></tr>\n";

  echo "</tbody></table><br />\n";
    
  //////////////////////////////////////////////////////////// interfaces
  if ( ! $peer_id ) {

    // display interfaces
    if( ! $interfaces ) {
      echo "<p><span class='plc-warning'>No interface</span>.  Please add an interface to make this a usable PLC node</p>.\n";
    } else {
      $columns=array();
      if ( $extra_privileges ) {
	// a single symbol, marking 'p' for primary and a delete button for non-primary
	$columns[' ']='string';
      }
	 
      $columns["IP"]="IPAddress";
      $columns["Method"]="string";
      $columns["Type"]="string";
      $columns["MAC"]="string";
      $columns["bw limit"]="FileSize";

      print "<hr/>\n";
      plc_table_title('Interfaces');
      plc_table_start("interfaces",$columns,2,false);
	
      foreach ( $interfaces as $interface ) {
	$interface_id= $interface['interface_id'];
	$interface_ip= $interface['ip'];
	$interface_broad= $interface['broadcast'];
	$interface_primary= $interface['is_primary'];
	$interface_network= $interface['network'];
	$interface_dns1= $interface['dns1'];
	$interface_dns2= $interface['dns2'];
	$interface_hostname= $interface['hostname'];
	$interface_netmaks= $interface['netmask'];
	$interface_gatewary= $interface['gateway'];
	$interface_mac= $interface['mac'];
	$interface_bwlimit= $interface['bwlimit'];
	$interface_type= $interface['type'];
	$interface_method= $interface['method'];

	plc_table_row_start($interface['ip']);
	if ( $extra_privileges ) {
	  if (!$interface_primary) {
	    // xxx 
	    plc_table_cell (plc_delete_link_button ('interfaces.php?id=' . $interface_id . '&delete=1&submitted=1', 
						    '\\nInterface ' . $interface_ip));
	  } else {
	    plc_table_cell('p');
	  }
	}
	plc_table_cell(l_interface2($interface_id,$interface_ip));
	plc_table_cell($interface_method);
	plc_table_cell($interface_type);
	plc_table_cell($interface_mac);
	plc_table_cell($interface_bwlimit);
	plc_table_row_end();
      }
      plc_table_end();
    }
      
  }

  //////////////////////////////////////////////////////////// slices
  // display slices
  $peer_hash = plc_peer_get_hash ($api);

  print "<hr/>\n";
  plc_table_title ("Slices");
  if ( ! $slices  ) {
    echo "<p><span class='plc-warning'>This node is not associated to any slice.</span></p>\n";
  } else {
    $columns=array();
    $columns['Peer']="string";
    $columns['Name']="string";
    $columns['Slivers']="string";
    plc_table_start ("slivers",$columns,1);

    foreach ($slices as $slice) {
      plc_table_row_start($slice['name']);
      plc_table_cell (plc_peer_shortname($peer_hash,$slice['peer_id']));
      plc_table_cell (l_slice2 ($slice['slice_id'],$slice['name']));
      plc_table_cell (l_sliver3 ($node_id,$slice['slice_id'],'view'));
      plc_table_row_end();
    }
    plc_table_end();
  }

  //////////////////////////////////////////////////////////// nodegroups
  // display node group info
  if ( ! $nodegroups ) {
    echo "<p><span class='plc-warning'>This node is not in any nodegroup.</span></p>\n";
  } else {
    $columns=array();
    $columns['Name']="string";
    $columns['Tag']="string";
    $columns['Value']="string";
      
    print "<hr/>\n";
    plc_table_title("Nodegroups");
    plc_table_start("nodegroups",$columns,0,false);

    foreach( $nodegroups as $nodegroup ) {
      plc_table_row_start();
      plc_table_cell(l_nodegroup2($nodegroup_id,$nodegroup['groupname']));
      $tag_types=$api->GetTagTypes(array($nodegroup['tag_type_id']));
      plc_table_cell($tag_types[0]['tagname']);
      plc_table_cell($nodegroup['value']);
      plc_table_row_end();
    }
    plc_table_end();
  }    

  ////////////////////////////////////////////////////////////
  plc_peer_block_end();
}

// Print footer
include 'plc_footer.php';

?>
