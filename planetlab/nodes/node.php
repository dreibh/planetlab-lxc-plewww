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
require_once 'plc_peers.php';
require_once 'plc_minitabs.php';
require_once 'plc_tables.php';
require_once 'plc_details.php';
require_once 'plc_forms.php';

// -------------------- 
// recognized URL arguments
$node_id=intval($_GET['id']);
if ( ! $node_id ) { plc_error('Malformed URL - id not set'); return; }

////////////////////
// Get all columns as we focus on only one entry
$nodes= $api->GetNodes( array($node_id));

if (empty($nodes)) {
  drupal_set_message ("Node " . $node_id . " not found");
  return;
 }

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

// get peers
$peer_id = $node['peer_id'];
$peers=new Peers ($api);

// gets site info
$sites= $api->GetSites( array( $site_id ) );
$site=$sites[0];
$site_name= $site['name'];
$site_node_ids= $site['node_ids'];

// hash node_id=>hostname for this site's nodes
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

// get interface info
if( !empty( $interface_ids ) )
  $interfaces= $api->GetInterfaces( $interface_ids );

// gets nodegroup info
if( !empty( $nodegroup_ids ) )
  $nodegroups= $api->GetNodeGroups( $nodegroup_ids, array("groupname","tag_type_id","value"));

// xxx Thierry : remaining stuff
// (*) events: should display the latest events relating to that node.
// disabling call to GetEvents, that gets the session deleted in the DB
// (*) conf_files: is fetched but not displayed
if( !empty( $conf_file_ids ) )
  $conf_files= $api->GetConfFiles( $conf_file_ids );
// (*) idem for PCUs
// gets pcu and port info key to both is $pcu_id
if( !empty( $pcu_ids ) )
  $PCUs= $api->GetPCUs( $pcu_ids );

//////////////////// display node info

drupal_set_title("Details for node " . $hostname);
$local_peer= ! $peer_id;

  
// extra privileges to admins, and (pi||tech) on this site
$privileges = plc_is_admin () || ( plc_in_site($site_id) && ( plc_is_pi() || plc_is_tech()));
  
$tabs=array();
// available actions
if ( $local_peer  && $privileges ) {
    
  $tabs['Update'] = array ('url'=>"/db/nodes/node_actions.php",
			   'method'=>'POST',
			   'values'=>array('action'=>'prompt-update','node_id'=>$node_id),
			   'bubble'=>"Update details of $hostname");
  $tabs['Delete'] = array ('url'=>"/db/nodes/node_actions.php",
			   'method'=>'POST',
			   'values'=>array('action'=>'delete','node_id'=>$node_id),
			   'bubble'=>"Delete node $hostname",
			   'confirm'=>'Are you sure to delete ' . $hostname. ' ?');
  // xxx subject to roles
  $tabs["Add Interface"]=array('url'=>l_interface_add($node_id),
			       'bubble'=>"Declare new network interface on $hostname");
  $tabs["Events"]=array_merge(tabs_events(),
			      array('url'=>l_event("Node","node",$node_id),
				    'bubble'=>"Events for node $hostname"));
  $tabs["Comon"]=array_merge(tabs_comon(),
			     array('url'=>l_comon("node_id",$node_id),
				   'bubble'=>"Comon page about node $hostname"));
 }

$tabs["All nodes"]=l_nodes();

plc_tabs($tabs);

// show gray background on foreign objects : start a <div> with proper class
$peers->block_start ($peer_id);
  
plc_details_start ();
if ( ! $local_peer) {
  plc_details_line("Peer",$peers->peer_link($peer_id));
  plc_details_space_line();
 }

plc_details_line("Hostname",$hostname);
plc_details_line("Type",$node_type);
plc_details_line("Model",$model);
plc_details_line("Version",$version);
// no tool to implement this multiple-choice setting yet
// xxx would need at least to use the proper class, like plc_details_class() or something
plc_details_space_line ();
echo "<tr><th>Boot State: </th><td>";
if ( ! $local_peer) {
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

// same here for the download area
if ( $local_peer  && $privileges) {

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
plc_details_space_line ();
plc_details_line("Site",l_site_t($site_id,$site_name));
		   
// build list of node links
$nodes_area=array();
foreach ($site_node_hash as $hash_node_id => $hash_hostname) {
  $nodes_area []= l_node_t($hash_node_id,$hash_hostname);
}
plc_details_line_list ("All site nodes",$nodes_area);

plc_details_end ();

plc_form_start(l_actions(), array('node_id'=>$node_id));

//////////////////////////////////////////////////////////// Tags
// get tags
if ( $local_peer ) {
  
  $tags=$api->GetNodeTags (array('node_id'=>$node_id));
  function get_tagname ($tag) { return $tag['tagname'];}
  $tagnames = array_map ("get_tagname",$tags);
  $nodegroups_hash=plc_nodegroup_global_hash($api,$tagnames);
  
  plc_section("Tags");
  $headers=array("Name"=>"string",
		 "Value"=>"string",
		 "Nodegroup"=>"string");
  
  $table_options=array("notes_area"=>false,"pagesize_area"=>false,"search_width"=>10);
  plc_table_start("node_tags",$headers,0,$table_options);
  if ($tags) foreach ($tags as $tag) {
      // does this match a nodegroup ?
      $nodegroup_name="n/a";
      $nodegroup_key=$tag['tagname'] . "=" . $tag['value'];
      $nodegroup=$nodegroups_hash[$nodegroup_key];
      if ($nodegroup) $nodegroup_name=l_nodegroup_t($nodegroup['nodegroup_id'],$nodegroup['groupname']);
      plc_table_row_start();
      plc_table_cell($tag['tagname']);
      plc_table_cell($tag['value']);
      plc_table_cell($nodegroup_name);
      plc_table_row_end();
    }
  
  $footers=array();
  if ($privileges) {
    // remove selected sites
    // get list of tag names in the node/* category    
    $all_tags= $api->GetTagTypes( array ("category"=>"node*"), array("tagname","tag_type_id"));

    // xxx cannot use onchange=submit() - would need to somehow pass action name 
    function tag_selector ($tag) { return array("display"=>$tag['tagname'],"value"=>$tag['tag_type_id']); }
    $selector=array_map("tag_selector",$all_tags);
    $add_tag_name=plc_form_select_text("tag_type_id",$selector,"Choose");
    $add_tag_value=plc_form_text_text("value","",8);
    $add_tag_submit=plc_form_submit_text("set-tag-on-node","Set Tag");

    $add_tag_footer=plc_table_td_text($add_tag_name).plc_table_td_text($add_tag_value).plc_table_td_text($add_tag_submit);
    $footers[]= $add_tag_footer;
  }
  
  plc_table_end("node_tags",array('footers'=>$footers));
 }

//////////////////////////////////////////////////////////// slices
// display slices

plc_section ("Slices");
if ( ! $slices  ) {
  plc_warning ("This node is not associated to any slice");
 } else {
  $headers=array();
  $headers['Peer']="string";
  $headers['Name']="string";
  $headers['Slivers']="string";
  $reasonable_page=10;
  $table_options = array('notes_area'=>false,"search_width"=>10,'pagesize'=>$reasonable_page);
  if (count ($slices) <= $reasonable_page) {
    $table_options['search_area']=false;
    $table_options['pagesize_area']=false;
  }
  plc_table_start ("node_slices",$headers,1,$table_options);

  foreach ($slices as $slice) {
    plc_table_row_start();
    plc_table_cell ($peers->shortname($peer_id));
    plc_table_cell (l_slice_t ($slice['slice_id'],$slice['name']));
    plc_table_cell (l_sliver_t ($node_id,$slice['slice_id'],'view'));
    plc_table_row_end();
  }
  plc_table_end("node_slices");
 }

//////////////////////////////////////////////////////////// interfaces
if ( $local_peer ) {

  plc_section ("Interfaces");
  // display interfaces
  if( ! $interfaces ) {
    echo '<p>';
    plc_warning_text("This node has no interface");
    echo "Please add an interface to make this a usable PLC node.</p>\n";
  } else {
    $headers=array();
    if ( $privileges ) {
      // a single symbol, marking 'p' for primary and a delete button for non-primary
      $headers[' ']='string';
    }
	 
    $headers["IP"]="IPAddress";
    $headers["Method"]="string";
    $headers["Type"]="string";
    $headers["MAC"]="string";
    $headers["bw limit"]="FileSize";

    $table_options=array('search_area'=>false,"pagesize_area"=>false,'notes_area'=>false);
    plc_table_start("node_interfaces",$headers,2,$table_options);
	
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

      plc_table_row_start();
      if ( $privileges ) {
	if (!$interface_primary) {
	  // xxx 
	  plc_table_cell (plc_delete_link_button ('interfaces.php?id=' . $interface_id . '&delete=1&submitted=1', 
						  '\\nInterface ' . $interface_ip));
	} else {
	  plc_table_cell('p');
	}
      }
      plc_table_cell(l_interface_t($interface_id,$interface_ip));
      plc_table_cell($interface_method);
      plc_table_cell($interface_type);
      plc_table_cell($interface_mac);
      plc_table_cell($interface_bwlimit);
      plc_table_row_end();
    }
    if ($privileges) {
      $button=plc_form_simple_button(l_interface_add($node_id),"Add interface","GET");
      $footers=array(plc_table_td_text($button,6,"right"));
    }
    plc_table_end("node_interfaces",array("footers"=>$footers));
  }
 }

plc_form_end();

////////////////////////////////////////////////////////////
$peers->block_end($peer_id);


// Print footer
include 'plc_footer.php';

?>
