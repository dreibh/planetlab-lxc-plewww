<?php

// $Id$
// Thierry on 2007-02-20
// There's no reason why we should see this page with a foreign slice, at least
// so long as the UI is used in a natural way, given the UI's logic as of now
// however it's always possible that someone forges her own url like
// http://one-lab.org/db/slices/slice_nodes?id=176
// So just to be consistent, we protect ourselves against such a usage

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api, $adm;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slices');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

//print_r( $_person );

// if no id ... redirect to slice index
if( !$_GET['id'] && !$_POST['id'] ) {
  plc_redirect( l_slices());
 }


// get slice id from GET or POST
if( $_GET['id'] )
  $slice_id= intval( $_GET['id'] );
elseif ( $_POST['id'] )
  $slice_id= intval( $_POST['id'] );
else
  echo "no slice_id<br />\n";


// if add node submitted add the nodes to slice
if( $_POST['add'] ) {
  $add_nodes= $_POST['add_nodes'];
  
  foreach( $add_nodes as $nodes) {
    $new_nodes[]= intval( $nodes );
  }

  // update it!
  $api->AddSliceToNodes( $slice_id, $new_nodes );

  $errors= $api->error();

  if( empty( $errors ) )
    $added= "<font color=blue>Nodes Added.</font><br /><br />";
  else
    $added= "<font color=red>Error: '$errors'</font><br /><br />";
}

// if rem node submitted remove the nodes from slice
if( $_POST['remove'] ) {
  $rem_nodes= $_POST['rem_nodes'];

  foreach( $rem_nodes as $nodes) {
    $new_nodes[]= intval( $nodes );
  }
  
  // Delete them!
  $api->DeleteSliceFromNodes( $slice_id, $new_nodes );

  $errors= $api->error();

  if( empty( $errors ) )
    $removed= "<font color=blue>Nodes Removed.</font><br />";
  else
    $removed= "<font color=red>Error: '$errors'</font><br /><br />";

}


// get slice info
$slice_info= $adm->GetSlices( array( $slice_id ), array( "name", "node_ids", "peer_id" ) );
$slice_readonly = $slice_info[0]['peer_id'];
drupal_set_title("Slice " . $slice_info[0]['name'] . " - Nodes");

// get node info
if( !empty( $slice_info[0]['node_ids'] ) )
  $node_info= $adm->GetNodes( $slice_info[0]['node_ids'], array( "hostname", "node_id", "site_id" , "peer_id") );
  
// get site names and ids
$site_info= $adm->GetSites( NULL, array( "site_id", "name", "peer_id" ) );
sort_sites( $site_info );

// if site_id is in post use it, if not use the user's primary
if( $_POST['site_id'] )
  $site_id= $_POST['site_id'];
else
  $site_id= $_person['site_ids'][0];


// get site nodes for $site_id
if( $site_id == 'all_site' ) {
  $full_node_info= $adm->GetNodes( array("node_type","regular"),
				   array( "hostname", "node_id" , "peer_id", "boot_state","last_updated") );

  $snode_info= array();
  foreach( $full_node_info as $full_node ) {
    if( !in_array( $full_node['node_id'], $slice_info[0]['node_ids'] ) )
      $snode_info[]= $full_node;
  }
}
else {
  $sid= intval( $site_id );
  $site_node_info= $adm->GetSites( array( $sid ), array( "node_ids" ) );
  $site_nodes= $site_node_info[0]['node_ids'];
	
  // gets all node_ids from site that arent already associated with the slice
  foreach( $site_nodes as $snode) {
    if( !in_array( $snode, $slice_info[0]['node_ids'] ) )
      $snodes[]= $snode;
  }
	
  // Get node info from new list
  if( !empty( $snodes ) )
    $snode_info= $adm->GetNodes( $snodes, array( "hostname", "node_id" , "peer_id", "boot_state","last_updated" ) );
  
}

// start form   
if ( $slice_readonly) 
  echo "<div class='plc-foreign'>";
else
  echo "<form action='slice_nodes.php?id=$slice_id' method=post>\n";

// section for adding nodes : for local slices only
if ( ! $slice_readonly ) {
  echo "<hr />";
  echo "<h5> Select a site to add nodes from.</h5>\n";
  echo "<table><tr><td>";
  if ($site_id != 'all_site') {
    echo plc_comon_button("site_id",$site_id,"_blank");
    echo "</td><td>";
  }
  echo "<select name='site_id' onChange='submit()'>\n";
  echo "<option value='all_site'";
  if( $site_id == 'all_site' )
    echo " selected";
  echo ">--All Sites--</option>\n";

  foreach( $site_info as $site ) {
    echo "<option value=". $site['site_id'];
    if( $site['site_id'] == $site_id )
      echo " selected";
    if ($site["peer_id"]) 
      echo " class='plc-foreign'";
    echo ">". $site['name'] ."</option>\n";
    
  }
  
  echo "</select></td></tr></table>\n";

  // show all availible nodes at $site_id
  //echo "<pre>"; print_r( $snode_info ); echo "</pre>";
  if( $snode_info ) {
    echo $added;
    echo "<table cellpadding=2><tbody >\n<tr>";
    echo "<th></th> <th> check </th><th>Hostname</th><th> Boot State </th><th> Last Update</th>
        </tr>";
    foreach( $snode_info as $snodes ) {
      $class="";
      if ($snodes['peer_id']) {
	$class="class='plc-foreign'";
      } 
      echo "<tr " . $class . "><td>";
      echo plc_comon_button("node_id",$snodes['node_id'],"_blank");
      echo "</td><td>";
      echo "<input type=checkbox name='add_nodes[]' value=". $snodes['node_id'] .">";
      echo "</td><td align='center'>";
      echo $snodes['hostname'];
      echo "</td><td align='center'>";
      echo $snodes['boot_state'];
      echo "</td><td align='center'>";
      echo date('Y-m-d',$snodes['last_updated']);
      echo "</td></tr>\n";
    }
  
    echo "</tbody></table>\n";
    echo "<p><input type=submit value='Add Nodes' name='add'>\n";
  } else {
    echo "<p>No site nodes or all are already added.\n";
  }
}

echo "<hr />\n";

// show all nodes currently associated
echo $removed;
echo "<h5>Nodes currently associated with slice</h5>\n";
if( $node_info ) {
  if ( ! $slice_readonly) {
    echo "<u>Check boxes of nodes to remove:</u>\n";
    echo "<table cellpadding=2><tbody><tr>\n";
    echo "<th></th> <th> check </th><th>Hostname</th><th> Boot State </th><th> Last Update</th>
        </tr>";
  } else {
    echo "<table cellpadding=2><tbody><tr>\n";
    echo "<th></th> <th> check </th><th>Hostname</th><th> Boot State </th><th> Last Update</th>
        </tr>";
  }

  foreach( $node_info as $node ) {
    $class="";
      if ($node['peer_id']) {
	$class="class='plc-foreign'";
      } 
    if ( ! $slice_readonly) {
      echo "<tr " . $class . "><td>";
      echo plc_comon_button("node_id",$node['node_id'],"_blank");
      echo "</td><td>";
      echo "<input type=checkbox name='rem_nodes[]' value=". $node['node_id'] .">";
      echo "</td><td>" ;
      echo $node['hostname'];
      echo "</td><td align='center'>";
      echo $snodes['boot_state'];
      echo "</td><td align='center'>";
      echo date('Y-m-d',$snodes['last_updated']);
      echo "</td></tr>\n";
    } else {
      echo "<tr " . $class . "><td>";
      echo plc_comon_button("node_id",$node['node_id'],"_blank");
      echo "</td><td>" ;
      echo $node['hostname'];
      echo "</td></tr>";
    }
  
  }
  
  echo "</tbody></table>\n";
  if ( ! $slice_readonly) 
    echo "<p><input type=submit value='Remove Nodes' name='remove'>\n";
  
} else {
  echo "<p>No nodes associated with slice.\n";
}

if ($slice_readonly)
  echo "</div>";
 else 
   echo "</form>";

echo "<p><a href='index.php?id=$slice_id'>Back to Slice</a>\n";


// Print footer
include 'plc_footer.php';

?>
