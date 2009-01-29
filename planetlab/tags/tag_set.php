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
require_once 'plc_minitabs.php';
require_once 'plc_tables.php';

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

// Print footer
include 'plc_footer.php';

?>
