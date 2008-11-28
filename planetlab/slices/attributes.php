<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Attributes');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

//print_r( $_person );

// if no id, display list of attributes types
if( !$_GET['id'] && !$_GET['add'] && !$_GET['add_type'] && !$_GET['edit_type'] ) {
  // get types
  $attrib_types= $api->GetSliceTagTypes( NULL, array( "attribute_type_id", "name", "description", "min_role_id" ) );
  
  // get role names for the min role_ids
  foreach( $attrib_types as $attrib_type ) {
    $roles= $api->GetRoles();
    foreach( $roles as $role ) {
      if( $attrib_type['min_role_id'] == $role['role_id'] )
        $role_name= $role['name'];
    }
    
    $attrib_type_info[]= array( "attribute_type_id" => $attrib_type['attribute_type_id'], "name" => $attrib_type['name'], "description" => $attrib_type['description'], "min_role" => $role_name );
  }

  // list them
  echo "<h2>Slice Attribute Types</h2>\n";
  
  echo "<table cellpadding=2><thead><tr><th>Name</th><th>Min Role</th><th>Description</th>";
  // if admin we need to more cells
  if(  in_array( "10", $_person['role_ids'] ) )
    echo "<th></th><th></th>";
  echo "</thead><tbody>";

  foreach( $attrib_type_info as $type ) {
    echo "<tr><td>". $type['name'] ."</td><td>". $type['min_role'] ."</td><td>". $type['description'] ."</td>";
    // if admin display edit/delet links
    if(  in_array( "10", $_person['role_ids'] ) ) {
      echo "<td><a href='attributes.php?edit_type=". $type['attribute_type_id'] ."'>Edit</a></td>";
      echo "<td>";
      echo plc_delete_link_button ('attrib_action.php?del_type='. $type['attribute_type_id'],
				   $type['name']);
      echo "</td>";
    }
    echo "</tr>\n";
  
  }
  
  echo "</tbody></table>\n";
  
  if( in_array( "10", $_person['role_ids'] ) )
    echo "<p><a href='attributes.php?add_type=1'>Add an Attribute Type</a>";
  
}
elseif( $_GET['add_type'] || $_GET['edit_type'] ) {
  // if its edit get the attribute info
  if( $_GET['edit_type'] ) {
    $type_id= intval( $_GET['edit_type'] );
    $type_info= $api->GetSliceTagTypes( array( $type_id ) );
    
    $name= $type_info[0]['name'];
    $min_role_id= $type_info[0]['min_role_id'];
    $description= $type_info[0]['description'];
    
  }
  
  // display form for attribute types
  echo "<form action='attrib_action.php' method='post'>\n";
  echo "<h2>Add Attribute Type</h2>\n";
  echo "<p><strong>Name: </strong> <input type=text name='name' size=20 value='$name'>\n";
  echo "<p><strong>Min Role: </strong><select name='min_role_id'>\n";
  echo "<option value='10'"; if( $min_role_id == 10 ) echo " selected"; echo ">Admin</option>\n";
  echo "<option value='20'"; if( $min_role_id == 20 ) echo " selected"; echo ">PI</option>\n";
  echo "<option value='30'"; if( $min_role_id == 30 ) echo " selected"; echo ">User</option>\n";
  echo "<option value='40'"; if( $min_role_id == 40 ) echo " selected"; echo ">Tech</option>\n";
  echo "</select>\n";
  echo "<p><strong>Description: </strong><br>\n";
  echo "<textarea name='description' cols=40 rows=5>$description</textarea>\n";
  echo "<p><input type=submit ";
  if( $_GET['edit_type'] ) {
    echo "name='edit_type' value='Edit Attribute Type'>\n";
    echo "<input type=hidden name='attribute_type_id' value='$type_id'>\n";
  }
  else
    echo "name='add_type' value='Add Attribute Type'>\n";

  echo "</form>\n";

}
elseif( $_GET['add'] ) {
  // get slice id from GET
  $slice_id= intval( $_GET['add'] );
  
  // get all attribute types 
  $attrib_types= $api->GetSliceTagTypes( NULL, array( "attribute_type_id", "name" ) );
  
  foreach( $attrib_types as $attrib_type ) {
    $all_attribs[$attrib_type['attribute_type_id']]= $attrib_type['name'];
  }
  
  // get slice's attribute types
  $slice_info= $api->GetSlices( array( $slice_id ), array( "slice_tag_ids" ) );
  $attrib_info= $api->GetSliceTags( $slice_info[0]['slice_tag_ids'], array( "attribute_type_id", "name" ) );
  
  foreach( $attrib_info as $info ) {
    $slice_attrib_types[$info['attribute_type_id']]= $info['name'];
  }


    $attribute_types= $all_attribs;
  
  // start form
  echo "<form action='attrib_action.php' method='post'>\n";
  echo "<h2>Edit ". $slice_info[0]['name'] ." attribute: ". $attrib_type[0]['name'] ."</h2>\n";
  
  echo "<select name='attribute_type_id'><option value=''>Choose a type to add</option>\n";
  
  foreach( $attribute_types as $key => $val ) {
    echo "<option value='". $key ."'>". $val ."</option>\n";
  
  }
  echo "</select>\n";
  
  echo "<p><strong>Value: </strong><input type=text name='value'>\n";
  
  echo "<p><input type=submit name='add_attribute' value='Add Attribute'>\n";
  echo "<input type=hidden name='slice_id' value='$slice_id'>\n";
  echo "</form>\n";
  
}
else {
  $attribute_id= intval( $_GET['id'] );
  
  // get attribute info
  $slice_attib= $api->GetSliceTags( array( $attribute_id ), array( "slice_id", "slice_tag_id", "attribute_type_id", "value", "description", "min_role_id" ) );
  
  // get type info 
  $attrib_type= $api->GetSliceTagTypes( array( $slice_attib[0]['attribute_type_id'] ), array( "attribute_type_id", "name", "description" ) );
  
  // slice info
  $slice_info= $api->GetSlices( array( $slice_attib[0]['slice_id'] ), array( "name" ) );
  
  // start form and put values in to be edited.
  echo "<form action='attrib_action.php' method='post'>\n";
  echo "<h2>Edit ". $slice_info[0]['name'] ." attribute: ". $attrib_type[0]['name'] ."</h2>\n";
  
  echo $slice_attib[0]['description'] ."<br />\n";
  echo "<strong>Value:</strong> <input type=text name=value value='". $slice_attib[0]['value'] ."'><br /><br />\n";
  
  echo "<input type=submit value='Edit Attribute' name='edit_attribute'>\n";
  echo "<input type=hidden name='slice_id' value='". $slice_attib[0]['slice_id'] ."'>\n";
  echo "<input type=hidden name='attribute_id' value='". $attribute_id ."'>\n";
  echo "</form>\n";
  
}

echo "<p><a href='index.php?id=$slice_id'>Back to Slices</a>\n";

// Print footer
include 'plc_footer.php';

?>
