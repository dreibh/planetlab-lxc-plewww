<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

if (! isset($_GET['type']) || $_GET['type']=='all') {
  $title="All tag types";
  $tag_type_filter=array("-SORT"=>"tagname"); 
 } else {
  $title="Tag Types for " . $_GET['type'] . "s";
  $pattern=$_GET['type'] . '*';
  $tag_type_filter=array("category"=>$pattern,"-SORT"=>"tagname");
 }


// Print header
require_once 'plc_drupal.php';
drupal_set_title($title);
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];

//print_r( $_person );

// if no id, display list of tag types
if( !$_GET['id'] && !$_GET['add'] && !$_GET['add_type'] && !$_GET['edit_type'] ) {
  // get types
  $tag_types= $api->GetTagTypes($tag_type_filter,
				array( "tag_type_id", "tagname", "category", "description", "min_role_id" ) );
  
  // get role names for the min role_ids
  foreach( $tag_types as $tag_type ) {
    $roles= $api->GetRoles();
    foreach( $roles as $role ) {
      if( $tag_type['min_role_id'] == $role['role_id'] )
        $role_name= $role['name'];
    }
    
    $tag_type_info[]= array( "tag_type_id" => $tag_type['tag_type_id'], 
			     "tagname" => $tag_type['tagname'], 
			     "description" => $tag_type['description'], 
			     "min_role" => $role_name,
			     "category" => $tag_type['category']);
  }

  // list them
  echo "<h2>Tag Types</h2>\n";
  
  echo "<table cellpadding=2><thead><tr><th>Name</th><th>Category></th><th>Min Role</th><th>Description</th>";
  // if admin we need to more cells
  if(  in_array( "10", $_person['role_ids'] ) )
    echo "<th></th><th></th>";
  echo "</thead><tbody>";

  foreach( $tag_type_info as $type ) {
    echo "<tr>";
    echo "<td>". $type['tagname'] ."</td>";
    echo "<td>". $type['category'] ."</td>";
    echo "<td>". $type['min_role'] ."</td>";
    echo "<td>". $type['description'] ."</td>";
    // if admin display edit/delet links
    if(  in_array( "10", $_person['role_ids'] ) ) {
      echo "<td><a href='tags.php?type=slice&edit_type=". $type['tag_type_id'] ."'>Edit</a></td>";
      echo "<td>";
      echo plc_delete_link_button ('tag_action.php?del_type='. $type['tag_type_id'],
				   $type['tagname']);
      echo "</td>";
    }
    echo "</tr>\n";
    
  }
  
  echo "</tbody></table>\n";
  
  if( in_array( "10", $_person['role_ids'] ) )
    echo "<p><a href='tags.php?type=slice&add_type=1'>Add a Tag Type</a>";
  
 }
elseif( $_GET['add_type'] || $_GET['edit_type'] ) {
  // if its edit get the tag info
  if( $_GET['edit_type'] ) {
    $type_id= intval( $_GET['edit_type'] );
    $type_info= $api->GetTagTypes( array( $type_id ) );
    
    $tagname= $type_info[0]['tagname'];
    $min_role_id= $type_info[0]['min_role_id'];
    $description= $type_info[0]['description'];
    $category=$type_info[0]['category'];
    
  }
  
  // display form for tag types
  echo "<form action='tag_action.php' method='post'>\n";
  echo "<h2>Add Tag Type</h2>\n";
  echo "<p><strong>Name: </strong> <input type=text name='name' size=20 value='$tagname'>\n";
  echo "<p><strong>Category: </strong><input type=text name='category' size=30 value='$category'>\n";
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
    echo "name='edit_type' value='Edit Tag Type'>\n";
    echo "<input type=hidden name='tag_type_id' value='$type_id'>\n";
  }
  else
    echo "name='add_type' value='Add Tag Type'>\n";

  echo "</form>\n";

}
elseif( $_GET['add'] ) {
  // get slice id from GET
  $slice_id= intval( $_GET['add'] );
  
  // get all tag types 
  $tag_types= $api->GetTagTypes( $tag_type_filter , array( "tag_type_id", "tagname" ) );
  
  foreach( $tag_types as $tag_type ) {
    $all_tags[$tag_type['tag_type_id']]= $tag_type['tagname'];
  }
  
  // get slice's tag types
  $slice_info= $api->GetSlices( array( $slice_id ), array( "slice_tag_ids" ) );
  $tag_info= $api->GetSliceTags( $slice_info[0]['slice_tag_ids'], array( "tag_type_id", "tagname" ) );
  
  foreach( $tag_info as $info ) {
    $slice_tag_types[$info['tag_type_id']]= $info['tagname'];
  }


    $tag_types= $all_tags;
  
  // start form
  echo "<form action='tag_action.php' method='post'>\n";
  echo "<h2>Edit ". $slice_info[0]['name'] ." tag: ". $tag_type[0]['tagname'] ."</h2>\n";
  
  echo "<select name='tag_type_id'><option value=''>Choose a type to add</option>\n";
  
  foreach( $tag_types as $key => $val ) {
    echo "<option value='". $key ."'>". $val ."</option>\n";
  
  }
  echo "</select>\n";
  
  echo "<p><strong>Value: </strong><input type=text name='value'>\n";
  
  echo "<p><input type=submit name='add_tag' value='Add Tag'>\n";
  echo "<input type=hidden name='slice_id' value='$slice_id'>\n";
  echo "</form>\n";
  
}
else {
  $tag_id= intval( $_GET['id'] );
  
  // get tag
  $slice_tag= $api->GetSliceTags( array( $tag_id ), array( "slice_id", "slice_tag_id", "tag_type_id", "value", "description", "min_role_id" ) );
  
  // get type info 
  $tag_type= $api->GetTagTypes( array( $slice_tag[0]['tag_type_id'] ), array( "tag_type_id", "tagname", "description" ) );
  
  // slice info
  $slice_info= $api->GetSlices( array( $slice_tag[0]['slice_id'] ), array( "name" ) );
  
  // start form and put values in to be edited.
  echo "<form action='tag_action.php' method='post'>\n";
  echo "<h2>Edit ". $slice_info[0]['name'] ." tag: ". $tag_type[0]['tagname'] ."</h2>\n";
  
  echo $slice_tag[0]['description'] ."<br />\n";
  echo "<strong>Value:</strong> <input type=text name=value value='". $slice_tag[0]['value'] ."'><br /><br />\n";
  
  echo "<input type=submit value='Edit Tag' name='edit_tag'>\n";
  echo "<input type=hidden name='slice_id' value='". $slice_tag[0]['slice_id'] ."'>\n";
  echo "<input type=hidden name='tag_id' value='". $tag_id ."'>\n";
  echo "</form>\n";
  
}

echo "<p><a href='index.php?id=$slice_id'>Back to Slices</a>\n";

// Print footer
include 'plc_footer.php';

?>
