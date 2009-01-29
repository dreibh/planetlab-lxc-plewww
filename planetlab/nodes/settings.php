<?php

// $Id$

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Interface Setting Types');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

//plc_debug("person", $_person );

$tag_columns=array( "tag_type_id", "category", "tagname", "description", "min_role_id" );

// prepare dict role_id => role_name
global $roles;
$roles= $api->GetRoles();
global $roles_id_to_name;
$roles_id_to_name=array();
foreach ($roles as $role) {
  $roles_id_to_name[$role['role_id']] = $role['name'];
}

// compute person's smallest role
global $person_role;
$person_role=50;
foreach ($_person['role_ids'] as $role_id) {
  if ($role_id < $person_role) {
    $person_role=$role_id;
  }
}
//plc_debug("person_role",$person_role);

// post-process results from GetTagTypes
// with planetlab 4.2, we've moved to php-5.2
// with the former 5.0 reelase, I could invoke array_map 
// with a function that took a reference and could do side-effects
// Now I have to return the copy...
// this new way of doing things might require more memory
// on the other hand we should move to a schema where 
// pagination is done in the API, so it's no big deal hopefully
function layout_setting_type ($setting_type) {
  // replace role_id with name
  global $roles_id_to_name;
  $setting_type['min_role']=$roles_id_to_name[$setting_type['min_role_id']];
  return $setting_type;
}

// if no id, display list of tag types
if( !$_GET['id'] && !$_GET['add'] && !$_GET['add_type'] && !$_GET['edit_type'] ) {
  // get types
  global $person_role;
  $filter = array (']min_role_id'=>$person_role,'category'=>'interface*');
  $setting_types= $api->GetTagTypes( $filter, $tag_columns );
  $setting_types = array_map(layout_setting_type,$setting_types);
  sort_interface_tags ($setting_types);
  
  // list them
  
  echo "<table cellpadding=2>";
  echo "<thead><tr>";
  // if admin we need one more cells for delete links
  if(  in_array( "10", $_person['role_ids'] ) )
    echo "<th></th>";
  $role_header="<table><tr><th>min</th></tr><tr><th>Role</th></tr></table>";
  echo "<th>Name</th>";
  echo "<th>Category</th>";
  echo "<th>" . $role_header . "</th>";
  echo "<th>Id</th>";
  echo "<th>Description</th>";
  echo "</tr></thead>";
  echo "<tbody>";

  foreach( $setting_types as $type ) {
    echo "<tr>";
    // if admin display delete links
    if(  in_array( "10", $_person['role_ids'] ) ) {
      echo "<td>";
      echo plc_delete_link_button('setting_action.php?del_type='. $type['tag_type_id'],
				  $type['tagname']);
      echo "</td>";
    }
    // if admin, the name is a link to edition
    if (in_array( "10", $_person['role_ids'])) {
      echo "<td><a href='settings.php?edit_type=". $type['tag_type_id'] . "'>" . $type['tagname'] . "</a></td>";
    } else {
      echo "<td>" . $type['tagname'] . "</td>";
    }
    echo "<td>" . $type['category'] . "</td>";
    echo "<td>" . $type['min_role'] . "</td><td>" . $type['min_role_id'] . "</td><td>" . $type['description'] . "</td>";
    echo "</tr>\n";
  }
  
  if( in_array( "10", $_person['role_ids'] ) )
    echo "<tr><td colspan=6><a href='settings.php?add_type=true'>Add a Setting Type</td></tr>";

  echo "</tbody></table>\n";
  
  
  // back link o nodes
  echo "<p><p><a href='/db/nodes/index.php'>Back to Nodes</a>\n";

}
elseif( $_GET['add_type'] || $_GET['edit_type'] ) {
  // if its edit get the tag info
  if( $_GET['edit_type'] ) {
    $type_id= intval( $_GET['edit_type'] );
    $type= $api->GetTagTypes( array( $type_id ) );
    
    $category=$type[0]['category'];
    $name= $type[0]['tagname'];
    $min_role_id= $type[0]['min_role_id'];
    $description= $type[0]['description'];
    
  }
  
  // display form for setting types
  echo "<form action='setting_action.php' method='post'>\n";
  if ($_GET['edit_type']) {
    drupal_set_title("Edit Setting Type");
  } else {
    drupal_set_title("Add Setting Type");
  }
  echo "<table cellpadding='5' cellspacing='5' border='0'>";
  echo "<tr><th>Category:</th><td><input type=text name='category' size=20 value='$category'></td></tr>\n";
  echo "<tr><th>Name:</th><td><input type=text name='name' size=20 value='$name'></td></tr>\n";
  echo "<tr><th>Min Role:</th><td><select name='min_role_id'>\n";
  global $roles;
  foreach ($roles as $role) {
    echo "<option value='" . $role['role_id'] . "'"; 
    if( $min_role_id == intval($role['role_id']) ) echo " selected"; 
    echo ">" . $role['name'] . "</option>\n";
  }
  echo "</select></td></tr>\n";
  echo "<tr><th>Description:</th><td>";
  echo "<textarea name='description' cols=50 rows=5>$description</textarea>\n";
  echo "</td></tr>\n";
  echo "<tr><td colspan=2 align=center>";
  if( $_GET['edit_type'] ) {
    echo "<input type=hidden name='tag_type_id' value='$type_id'>\n";
    echo "<input type=submit name='edit_type' value='Edit Setting Type'>\n";
  } else {
    echo "<input type=submit name='add_type' value='Add Interface Type'>\n";
  }
  echo "</td></tr>";
  echo "</table>";

  echo "</form>\n";

  echo "<p><a href='/db/nodes/settings.php'>Back to Setting Types</a>\n";
}
elseif( $_GET['add'] ) {

  // get interface id from GET
  $interface_id= intval( $_GET['add'] );
  
  // get all setting types 
  global $person_role;
  $filter = array (']min_role_id'=>$person_role,'category'=>'interface*');
  $setting_types= $api->GetTagTypes( $filter, array( "tag_type_id", "tagname" , "category") );
  sort_interface_tags($setting_types);
    
  // get interface's settings
  $interface = $api->GetInterfaces( array( $interface_id ), array( "interface_tag_ids","ip" ) );
  
  drupal_set_title("Add a setting to  ". $interface[0]['ip']);

  // start form
  echo "<form action='setting_action.php' method='post'>\n";
  echo "<input type=hidden name='interface_id' value='$interface_id'>\n";
  
  echo "<table cellpadding='2'> <caption> New Setting </caption>";

  echo "<tr><th>Select</th><td><select name='tag_type_id'><option value=''>Choose a type to add</option>\n";
  
  foreach( $setting_types as $setting_type ) {
    echo "<option value='". $setting_type['tag_type_id'] ."'>". $setting_type['category'] . ":" . $setting_type['tagname'] ."</option>\n";
  
  }
  echo "</select></td</tr>\n";
  
  echo "<tr><th>Value: </th><td><input type=text name='value'></td></tr>\n";
  
  echo "<tr><td colspan=2 align=center><input type=submit name='add_setting' value='Add Setting'></td></tr>\n";
  echo "</table>";
  echo "</form>\n";

}
else {
  $setting_id= intval( $_GET['id'] );
  
  // get setting info
  $setting= $api->GetInterfaceTags( array( $setting_id ));
  
  // interface info
  $interface= $api->GetInterfaces( array( $setting[0]['interface_id'] ), array( "ip" ) );
  
  drupal_set_title("Edit setting ". $setting[0]['tagname'] ." on ". $interface[0]['ip']);

  // start form and put values in to be edited.
  echo "<form action='setting_action.php' method='post'>\n";
  echo "<input type=hidden name='setting_id' value='". $setting[0]['interface_tag_id'] ."'>\n";
  echo "<input type=hidden name='interface_id' value='". $setting[0]['interface_id'] ."'>\n";
  
  echo "<table cellpadding='2'> <caption> Edit Setting </caption>";
  echo "<tr><th> Category </th> <td>" . $setting[0]['category'] . "</td></tr>";
  echo "<tr><th> Name </th> <td>" . $setting[0]['tagname'] . "</td></tr>";
  echo "<tr><th> Value </th> <td><input type=text name='value' value='" . $setting[0]['value'] . "'> </td></tr>";
  echo "<tr><td colspan=2> <input type=submit value='Edit Setting' name='edit_setting'></td></tr>";
  echo "</table>";
  echo "</form>\n";
  
}

// back link is case-dependant

// Print footer
include 'plc_footer.php';

?>
