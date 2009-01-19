<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;


// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


// if sent here from another page remove then redirect
if( $_GET['remove'] && $_GET['nodegroup_id'] ) {
  $ng_id= $_GET['nodegroup_id'];
  $node_id= $_GET['remove'];

  $api->DeleteNodeFromNodeGroup( intval( $node_id ), intval( $ng_id ) );

  header( "location: index.php?id=$node_id" );
  exit();
  
}


// Print header
require_once 'plc_drupal.php'; 
drupal_set_title('Node Groups');
include 'plc_header.php';


// if no id display list of nodegroups
if( !$_GET['id'] && !$_GET['nodegroup_id'] ) {
  $nodegroup_info= $api->GetNodeGroups( NULL, array( "nodegroup_id", "name", "description" ) );

  echo "<h2>Node Groups</h2>\n
	<table cellpadding=2><thead><tr><th>Name</th><th>Description</th>";

   // if admin we need to more cells
   if(  in_array( "10", $_person['role_ids'] ) )
     echo "<th></th><th></th>";
   echo "</thead><tbody>";

  foreach( $nodegroup_info as $type ) {
    echo "<tr><td><a href='/db/nodes/node_groups.php?nodegroup_id=". $type['nodegroup_id'] ."'>". $type['name'] ."</a></td><td>". $type['description'] ."</td>";
    // if admin display edit/delet links
    if(  in_array( "10", $_person['role_ids'] ) ) {
      echo "<td><a href='/db/nodes/node_groups.php?nodegroup_id=". $type['nodegroup_id'] ."'>Edit</a></td>";
      echo plc_delete_link_button('node_groups.php?del_type=' . $type['nodegroup_id'],
				  $type['name']);
      echo "</td>";
    }
    echo "</tr>\n";

  }

  echo "</tbody></table>\n";

}
// if id is set then show nodegroup info
elseif( $_GET['id'] ) {
  $nodegroup_id= $_GET['id'];

  $nodegroup_info= $api->GetNodeGroups( array( intval( $nodegroup_id ) ), 
					array( "name", "nodegroup_id", "node_ids" ) );
  $node_info= $api->GetNodes( $nodegroup_info[0]['node_ids'], array( "node_id", "hostname" ) );

  //display info 
  echo "<h2>Node Group ". $nodegroup_info[0]['name'] ."</h2>\n";

  if( empty( $nodegroup_info[0]['node_ids'] ) )
    echo "<p>No nodes in node group.";
  else {
    echo "<table cellpadding=2><thead><tr><th>Hostname</th>";

    // if admin need more cells
    if( in_array( 10, $_roles ) ) 
      echo "<th>Remove</th>";

    echo "</tr>\n";

    foreach( $node_info as $node ) {
      echo "<tr><td><a href='/db/nodes/index.php?id=". $node['node_id'] ."'>". $node['hostname'] ."</a></td>";

      if( in_array( 10, $_roles ) )
        echo "<td><a href='/db/nodes/node_groups.php?remove=". $node['node_id'] ."&nodegroup_id=". $nodegroup_id ."'>remove</a></td>";

      echo "</tr>";

    }

    echo "</tbody></table>\n";

  }

}
// if no id add else update
elseif( $_GET['add'] ) {
  // add node group and redirect to update nodes for it
  if( $_POST['add_sub'] ) {
    $name= $_POST['name'];
    $description= $_POST['description'];

    $fields= array( 'name'=>$name, 'description'=>$description );

    // add it
    $api->AddNodeGroup( $fields );

    // get nodegroup_id
    $group_info= $api->GetNodeGroups( array( $name ), array( "nodegroup_id" ) );

    // redirect
    header( "location: node_groups.php?id=". $group_info[0]['nodegroup_id'] );
    exit();

  }
  
  // add form
  echo "<form method=post action='node_groups.php?add=1'>";
  echo "<h2>Create Node Group</h2>\n";
  echo "<table><tbody>\n";
  echo "<tr><th>Name: </th><td><input type=text name='name' value=''></td></tr>\n<tr><th>Description: </th><td><input type=text name='description' size=50></td></tr>\n";
  echo "</tbody></table>\n";

  echo "<br /><input type=submit value='Add Node Group' name='add_sub'>\n</form\n";

  echo "<p><a href='index.php'>Back to Node Index</a>\n";
  
}
elseif( $_GET['nodegroup_id'] )
{
  // get node group id
  $node_group_id= $_GET['nodegroup_id'];
  
  // if add node submitted, add
  if( $_POST['add'] )
  {
    $add_nodes= $_POST['add_nodes'];

    // add nodes to node group
    foreach( $add_nodes as $add_node )
    {
      $api->AddNodeToNodeGroup( intval( $add_node ), intval( $node_group_id ) );

    }
    
  }

  // if remove node submitted, remove
  if( $_POST['remove'] )
  {
    $rem_nodes= $_POST['rem_nodes'];

    // remove nodes from node group
    foreach( $rem_nodes as $rem_node )
    {
      $api->DeleteNodeFromNodeGroup( intval( $rem_node ), intval( $node_group_id ) );

    }
    
  }

  // update name and description
  $name= $_POST['name'];
  $description= $_POST['description'];

  $fields= array();

  if( $name )
    $fields['name']= $name;

  if( $description )
    $fields['description']= $description;

  // api call
  if( !empty( $fields ) )
    $api->UpdateNodeGroup( intval( $node_group_id ), $fields );

  // get node_group info
  $group_info= $api->GetNodeGroups( array( intval( $node_group_id ) ),
				    array( "node_ids", "name", "conf_file_ids", "description" ) );

  $node_ids = $group_info[0]['node_ids'];
  $name = $group_info[0]['name'];
  $conf_file_ids = $group_info[0]['conf_file_ids'];
  $description = $group_info[0]['description'];

  // get node info
  if( !empty( $node_ids ) )
    $node_info= $api->GetNodes( $node_ids, 
				array( "hostname", "node_id" ) );

  // get site names and ids
  $site_info= $api->GetSites( NULL, array( "site_id", "name" ) );
  sort_sites( $site_info );

  // if site_id is in post use it, if not use the user's primary
  if( $_POST['site_id'] )
    $site_id= $_POST['site_id'];
  else
    $site_id= $_person['site_ids'][0];

  // get site nodes for $site_id
  $sid= intval( $site_id );
  $site_node_info= $api->GetSites( array( $sid ), array( "node_ids" ) );
  $site_nodes= $site_node_info[0]['node_ids'];


  // gets all node_ids from site that arent already associated with the node group
  foreach( $site_nodes as $snode) {
    if( !in_array( $snode, $node_ids ) )
      $snodes[]= $snode;

  }

  // Get node info from new list
  if( !empty( $snodes ) )
    $snode_info= $api->GetNodes( $snodes, array( "hostname", "node_id" ) );


  // start form
  echo "<form action='node_groups.php?nodegroup_id=$node_group_id' method=post name='fm'>\n";
  echo "<h2>Update Node Group id $name</h2>\n";

  echo "Select a site to add nodes from.<br />\n";
  echo "<select name='site_id' onChange='submit()'>\n";

  foreach( $site_info as $site ) {
    echo "<option value=". $site['site_id'];
    if( $site['site_id'] == $site_id )
      echo " selected";
    echo ">". $site['name'] ."</option>\n";
    
  }

  echo "</select>\n";

  echo "<hr />\n";

  // show all availible nodes at $site_id
  if( $snode_info ) {
    echo $added;
    echo "<table><tbody>\n";
    
    foreach( $snode_info as $snodes ) {
      echo "<tr><td><input type=checkbox name='add_nodes[]' value=". $snodes['node_id'] ."> </td><td> ". $snodes['hostname'] ." </td></tr>\n";
    
    }
    
    echo "</tbody></table>\n";
    echo "<p><input type=submit value='Add Nodes' name='add'>\n";

  }
  else
    echo "<p>All nodes on site already added.\n";

  echo "<hr />\n";

  // show all nodes currently associated
  echo $removed;
  echo "<h5>Nodes already associated with node group</h5>\n";
  if( $node_info ) {
    echo "<u>Check boxes of nodes to remove:</u>\n";
    echo "<table><tbody>\n";

    foreach( $node_info as $node ) {
      echo "<tr><td><input type=checkbox name='rem_nodes[]' value=". $node['node_id'] ."> </td><td> ". $node['hostname'] ." </td></tr>\n";
    
    }
    
    echo "</tbody></table>\n";
    echo "<p><input type=submit value='Remove Nodes' name='remove'>\n";
    
  }
  else
    echo "<p>No nodes associated with node group.\n";

  echo "<hr />\n";

  echo "<table><tbody>\n";
  echo "<tr><th>Name: </th><td><input type=text name='name' value='$name'></td></tr>\n";
  echo "<tr><th>Description: </th><td><input type=text name='description' value='$description' size=50></td></tr>\n";

  echo "</tbody></table>\n";
  echo "<br /><input type=submit value='Update Node Group' name='update'>\n";

  echo "</form>\n";

  echo "<br /><a href='/db/nodes/node_groups.php'>Back to Node Group Index</a>\n";


}


// Print footer
include 'plc_footer.php';

?>
