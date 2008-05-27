<?php

// $Id: index.php 1175 2008-02-07 16:20:15Z thierry $

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Nodes');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

////////////////////
// The set of columns to fetch
// and the filter applied for fetching sites
$columns = array( "node_id", "hostname", "boot_state", "peer_id" ) ;
$filter = array();
if ( in_array( '10', $_roles ) || in_array('20', $_roles) || in_array('40',$_roles)) {
  // admins, PIs and techs can see interface details
  $columns [] = "interface_ids";
 }

//////////////////
// perform post-processing on objects as returned by GetNodes
// performs sanity check and summarize the result in a single column
// performs in-place replacement, so passes a reference
function layout_node ($node) {

  // we need the 'interface_ids' field to do this
  // so regular users wont run this
  if ( ! array_key_exists ('interface_ids', $node))
    return $node;
    
  $messages=array();
  
  // do all this stuff on local nodes only
  if ( ! $node['peer_id'] ) {
    // check that the node has keys
    if (count($node['interface_ids']) == 0)
      $messages [] = "No interface";
    
  }
  // but always cleanup $node columns
  unset ($node['interface_ids']);
  $node['status'] = plc_make_table('plc-warning',$messages);
  $node['comon'] = plc_comon_button("node_id",$node['node_id']);
  return $node;
}

// if nodepattern is set then set id to that node's id.
// we use GET rather than POST so paginate can display the right contents on subsequent pages
// can be useful for writing bookmarkable URL's as well
if( $_GET['nodepattern'] || $_GET['peerscope']) {
  $nodepattern= $_GET['nodepattern'];
  if (empty($nodepattern)) { 
    $nodepattern="*";
  }
  $filter = array_merge (array( "hostname"=>$nodepattern ), $filter);
  switch ($_GET['peerscope']) {
  case '':
    $peer_label="all peers";
    break;
  case 'local':
    $filter=array_merge(array("peer_id"=>NULL),$filter);
    $peer_label="local peer";
    break;
  case 'foreign':
    $filter=array_merge(array("~peer_id"=>NULL),$filter);
    $peer_label="foreign peers";
    break;
  default:
    $peer_id=intval($_GET['peerscope']);
    $filter=array_merge(array("peer_id"=>$peer_id),$filter);
    $peer=$api->GetPeers(array("peer_id"=>$peer_id));
    $peer_label='peer "' . $peer[0]['peername'] . '"';
    break;
  }
  // need to use a hash filter for patterns to be properly handled
  $nodes= $api->GetNodes($filter, $columns);
  $nodes_count = count ($nodes);
  if ( $nodes_count == 1) {
    header( "location: index.php?id=". $nodes[0]['node_id'] );
    exit();
  } else if ( $nodes_count == 0) {
    echo "<span class='plc-warning'> No node matching $nodepattern </span>";
  } else {
    drupal_set_title ("Nodes matching $nodepattern on". $peer_label);
    $nodes = array_map(layout_node,$nodes);
    sort_nodes ($nodes);
    echo paginate( $nodes, "node_id", "Nodes", 25, "hostname");
  }
}
// if a site_id is given, display the site nodes only
else if( $_GET['site_id'] ) {

  $site_id= $_GET['site_id'];

  // Get site info
  $site_info= $api->GetSites( array( intval( $site_id ) ), array( "name", "node_ids" ) );
  drupal_set_title("Nodes on site " . $site_info[0]['name']);

  // Get site nodes
  $nodes= $api->GetNodes( array_merge(array('node_id'=>$site_info[0]['node_ids']),$filter), $columns);

  if ( empty ($nodes) ) {
    echo "No node to display";
  } else {

    $nodes = array_map(layout_node,$nodes);
    sort_nodes( $nodes );	

    echo paginate( $nodes, "node_id", "Nodes", 25, "hostname");
  }

}
// if a slice_id is given, display only the nodes related to this slice
else if( $_GET['slice_id'] ) {

  $slice_id= $_GET['slice_id'];

  // Get slice infos
  $slice_info= $api->GetSlices( array( intval( $slice_id ) ), array( "name", "node_ids" ) );
  drupal_set_title($slice_info[0]['name']."run on");

  // Get slice nodes
  $nodes= $api->GetNodes( array_merge(array('node_id'=>$slice_info[0]['node_ids']),$filter), $columns);

  if ( empty ($nodes) ) {
    echo "No node to display";
  } else {

    $nodes = array_map(layout_node,$nodes);
    sort_nodes( $nodes );	

    echo paginate( $nodes, "node_id", "Nodes", 25, "hostname");
    echo "<br /><p><a href='/db/slices/index.php?id=".$slice_id.">Back to slice page</a></div>";
  }
  
 }
// if no node id, display list of nodes to choose
elseif( !$_GET['id'] ) {

  // GetNodes API call
  $nodes= $api->GetNodes( empty($filter) ? NULL : $filter, $columns );

  if ( empty ($nodes) ) {
    echo "No node to display";
  } else {
    
    $nodes = array_map(layout_node,$nodes);
    sort_nodes( $nodes );

    drupal_set_html_head('<script type="text/javascript" src="/planetlab/includes/js/bsn.Ajax.js"></script>
    <script type="text/javascript" src="/planetlab/includes/js/bsn.DOM.js"></script>
    <script type="text/javascript" src="/planetlab/includes/js/bsn.AutoSuggest.js"></script>');

    echo "<div>\n
        <form method=get action='index.php'>\n";
    echo "<table><tr>\n
<th><label for='testinput'>Enter hostname or pattern: : </label></th>\n
<td><input type='text' id='testinput' name='nodepattern' size=40 value='' /></td>\n
<td rowspan=2><input type=submit value='Select Node' /></td>\n
</tr> <tr>
<th><label for='peerscope'>Federation scope: </label></th>\n
<td><select id='peerscope' name='peerscope' onChange='submit()'>\n
";
    echo plc_peers_option_list($api);
    echo "</select></td>\n
         </tr></table></form></div>\n
          <br />\n";


echo paginate( $nodes, "node_id", "Nodes", 25, "hostname" );

    echo "<script type=\"text/javascript\">\n
var options = {\n
	script:\"/planetlab/nodes/test.php?\",\n
	varname:\"input\",\n
	minchars:1\n
};\n
var as = new AutoSuggest('testinput', options);\n
</script>\n";
  }
}

if ( $_GET['id'] ) {
  // get the node id from the URL
  $node_id= intval( $_GET['id'] );

  // make the api call to pull that nodes DATA
  $node_info= $api->GetNodes( array( $node_id ) );

  if (empty ($node_info)) {
    echo "No such node.";
  } else {

    // node info
    $hostname= $node_info[0]['hostname'];
    $boot_state= $node_info[0]['boot_state'];
    $site_id= $node_info[0]['site_id'];
    $model= $node_info[0]['model'];
    $version= $node_info[0]['version'];

    // arrays of ids of node info
    $slice_ids= $node_info[0]['slice_ids'];
    $conf_file_ids= $node_info[0]['conf_file_ids'];
    $interface_ids= $node_info[0]['interface_ids'];
    $nodegroup_ids= $node_info[0]['nodegroup_ids'];
    $pcu_ids= $node_info[0]['pcu_ids'];
    $ports= $node_info[0]['ports'];

    // get peer
    $peer_id= $node_info[0]['peer_id'];

    // gets site info
    $site_info= $api->GetSites( array( $site_id ) );
    $site_name= $site_info[0]['name'];
    $site_nodes= $site_info[0]['node_ids'];

    if( !empty( $site_nodes ) ) {
      // get site node info basics
      $site_node_list= $api->GetNodes( $site_nodes );

      foreach( $site_node_list as $s_node ) {
	$site_node[$s_node['node_id']]= $s_node['hostname'];
      }
    }

    // gets slice info for each slice
    if( !empty( $slice_ids ) )
      $slice_info= $api->GetSlices( $slice_ids, array( "slice_id", "name" , "peer_id" ) );

    // gets conf file info
    if( !empty( $conf_file_ids ) )
      $conf_files= $api->GetConfFiles( $conf_file_ids );

    // get node network info
    if( !empty( $interface_ids ) )
      $interfaces= $api->GetInterfaces( $interface_ids );

    // gets nodegroup info
    if( !empty( $nodegroup_ids ) )
      $node_groups= $api->GetNodeGroups( $nodegroup_ids );

    // xxx Thierry : disabling call to GetEvents, that gets the session deleted in the DB
    // gets events 
    //  $filter= array( "object_type"=>"Node", "object_id"=>$node_id );
    //  $fields= array( "event_id", "person_id", "fault_code", "call_name", "call", "message", "time" );
    //
    //  $event_info= $api->GetEvents( $filter, $fields );

    // gets pcu and port info key to both is $pcu_id
    if( !empty( $pcu_ids ) )
      $PCUs= $api->GetPCUs( $pcu_ids );


    // display node info
    if ( $peer_id) {
      echo "<div class=plc-foreign>";
    }
  
    drupal_set_title("Node " . $hostname . " details");

    $is_admin = in_array( 10, $_roles );
    $is_pi = in_array( 20, $_roles );
    $is_tech = in_array( 40, $_roles );
    $in_site = in_array( $site_id, $_person['site_ids'] );

    // available actions
    if ( ! $peer_id  && ( $is_admin  || ( ($is_pi||$is_tech) && $in_site ) ) )  {
      
      // the javascript callback we set on the form; this
      // (*) checks whether we clicked on 'delete'
      // (*) in this case performs a javascript 'confirm'
      // (*) then, notice that if we select delete, then cancel, we can select back 'Choose action' 
      //     so submit only when value is not empty
      $change='if (document.basic.action.value=="delete") if (! confirm("Are you sure you want to delete ' . $hostname . ' ? ") ) return false; if (document.basic.action.value!="") submit();';
    
      echo "<table><tr><td>";
      if ($is_admin) {
	echo plc_event_button("Node","node",$node_id);
	echo "</td><td>";
      }
      echo plc_comon_button("node_id",$node_id);
      echo "</td><td>";
      echo "<form name='basic' action='/db/nodes/node_actions.php' method='post'>\n";
      echo "<input type=hidden name='node_id' value='$node_id'></input>\n";
      echo "<select name='action' onChange='$change'>\n";
      echo "<option value='' selected='selected'> Choose Action </option>\n";
      echo "<option value='prompt-update'> Update $hostname </option>\n";
      echo "<option value='delete'> Delete $hostname </option>\n";
      echo "</select></form>\n";

      echo "</td></tr></table>";
    }    

    echo "<hr />";
    echo "<table><tbody>\n";

    echo "<tr><th>Hostname: </th><td> $hostname </td></tr>\n";
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

    if ( ! $peer_id  && ( $is_admin  || ( ($is_pi||$is_tech) && $in_site ) ) )  {
      // handle legacy API
      if ( ! method_exists ($api,"GetBootMedium")) {
	$new_api_only=" disabled='disabled' ";
      }

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
    if (empty($site_node)) {
      echo "<span class='plc-warning'>Site has no node</span>";
    } else {
      foreach( $site_node as $key => $val ) {
	echo "<a href=index.php?id=$key>$val</a><br />";
      }
    }
    echo "</td></tr>\n";

    echo "</tbody></table><br />\n";

    if ( ! $peer_id ) {

      echo "<hr />\n";

      // display node networks
      if( $interfaces ) {
	echo "<p><table class='list_set' border=0 cellpadding=2>\n";
	echo "<caption class='list_set'>Node Networks</caption>\n";
	echo "<thead><tr class='list_set'>";
	// placeholder for the delete buttons
	if ( $is_admin || ($is_pi && $in_site)) {
	  echo "<th class='list_set'></th>";
	}
	echo "<th class='list_set'>IP Address</th><th class='list_set'>Method</th><th class='list_set'>Type</th><th class='list_set'>MAC</th><th class='list_set'>Bandwidth Limit</th></tr></thead><tbody>\n";

	foreach( $interfaces as $interface ) {
	  $nn_id= $interface['interface_id'];
	  $nn_ip= $interface['ip'];
	  $nn_broad= $interface['broadcast'];
	  $nn_primary= $interface['is_primary'];
	  $nn_network= $interface['network'];
	  $nn_dns1= $interface['dns1'];
	  $nn_dns2= $interface['dns2'];
	  $nn_hostname= $interface['hostname'];
	  $nn_netmaks= $interface['netmask'];
	  $nn_gatewary= $interface['gateway'];
	  $nn_mac= $interface['mac'];
	  $nn_bwlimit= $interface['bwlimit'];
	  $nn_type= $interface['type'];
	  $nn_method= $interface['method'];

	  echo "<tr class='list_set'>";
	  if ( $is_admin || ($is_pi && $in_site)) {
	    echo "<td class='list_set'>";
	    if (!$nn_primary) {
	      echo plc_delete_link_button('interfaces.php?id=' . $nn_id . '&delete=1&submitted=1', '\\nNode Network ' . $nn_ip);
	    } else {
	      echo '<span title="This node network is primary"> P </span>';
	    }
	    echo "</td>";
	  }
	  echo "<td class='list_set'>";
	  if( $is_admin || $is_pi || $is_tech ) {
	    echo "<a href='interfaces.php?id=$nn_id'>$nn_ip</a>";
	  } else {
	    echo "</td><td class='list_set'>$nn_ip</td>";
	  }
	  echo "<td class='list_set'>$nn_method</td><td class='list_set'>$nn_type</td><td class='list_set'>$nn_mac</td><td class='list_set'>$nn_bwlimit</td></tr>\n";
	}

	echo "</tbody></table>\n";

      } else {
	echo "<p><span class='plc-warning'>No Node Network</span>.  Please add a node network to make this a usable PLC node</p>.\n";
      }

      echo "<br /><a href='interfaces.php?node_id=$node_id'>Add a node network</a>.\n";
      echo "<br /><hr />\n";
    }

    // display node group info
    if( !empty( $node_groups ) ) {

      echo "<p><table border=0 cellpadding=3>\n<caption>Node Groups</caption>\n<thead><tr><th>Name</th><th>Description</th>";
      if( in_array( 10, $_roles ) ) echo "<th></th><th></th>";
      echo "</tr></thead><tbody>\n";

      foreach( $node_groups as $node_group ) {
	echo "<tr><td><a href='/db/nodes/node_groups.php?id=". $node_group['nodegroup_id'] ."'>". $node_group['name'] ."</a></td><td>". $node_group['description'] ."</td>";

	if( in_array( '10', $_roles ) || ( in_array( 20, $_roles ) && in_array( $site_id, $_person['site_ids'] ) ) || ( in_array( 40, $_roles ) && in_array( $site_id, $_person['site_ids'] ) ) ) 
	  echo "<td><a href='node_groups.php?nodegroup_id=". $node_group['nodegroup_id'] ."'>Update</a></td><td><a href='node_groups.php?remove=$node_id&nodegrop_id=". $node_group['nodegroup_id'] ."' onclick=\"javascript:return confirm('Are you sure you want to remove ". $hostname ." from ". $node_group['name'] ."?')\">remove</a></td>";

	echo "</tr>\n";

      }
      echo "</tbody></table><br />\n";

    } else {
      echo "<p><span class='plc-warning'>This node is not in any nodegroup.</span></p>\n";
    }    

    // select list for adding to node group
    // get nodegroup info
    $full_ng_info= $api->GetNodeGroups( NULL );
    if( empty( $node_groups ) ) {
      $person_ng= $full_ng_info;
    } else {
      $person_ng= arr_diff( $full_ng_info, $node_groups );
    }

    sort_nodegroups( $person_ng );

    if( !empty( $person_ng ) ) {
      echo "<p>Select nodegroup to add this node to.<br />\n";
      echo "<select name='ng_add' onChange='submit()'>\n<option value=''>Choose node group...</option>\n";
    
      foreach( $person_ng as $ngs ) {
	echo "<option value=". $ngs['nodegroup_id'] .">". $ngs['name'] ."</option>\n";
      }
      echo "</select>\n";
    }


    // display slices
    echo "<br /><hr />\n";
    if( !empty( $slice_info ) ) {
      sort_slices( $slice_info );
      echo paginate( $slice_info, "slice_id", "Slices", 15, "name", "slivers", $node_id );
    } else {
      echo "<p><span class='plc-warning'>This node is not associated to any slice.</span></p>\n";
    }


    // display events - disabled, see GetEvents above
    if( !empty( $event_info ) ) {
      echo "<br /><hr />\n";
      echo "<p><table class='list_set' border=0 cellpadding=2>\n<caption class='list_set'>Node Events</caption>\n<thead><tr class='list_set'><th class='list_set'></th><th class='list_set'>Call Name</th><th class='list_set'>Call</th><th class='list_set'>Message</th><th class='list_set'>Time</th></tr></thead><tbody>\n";

      // display event on rows of table
      foreach( $event_info as $event ) {
	echo "<tr><td>". $event['call'] ."</td><td>". $event['message'] ."</td><td>". $event['time'] ."</td></tr>\n";
      }
      echo "</tbody></table>\n";
    }

    if ( $peer_id ) {
      echo "</div>";
    }
  }
  if( $peer_id )
    echo "<br /></div>";
  
  echo "<br /><hr /><p><a href='/db/nodes/index.php'>Back to nodes list</a></div>";
  
 }

// Print footer
include 'plc_footer.php';

?>
