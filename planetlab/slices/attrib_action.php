<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

/*
// Print header
require_once 'plc_drupal.php';
drupal_set_title('Slices');
include 'plc_header.php';
*/

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


// ATTRIBUTES -------------------------------------------------

// attribute deletion
if( $_GET['rem_id'] ) {
  // get the id of the attrib to remove from GET
  $attribute_id= intval( $_GET['rem_id'] );

  // get slice_id 
  $attrib_info= $api->GetSliceAttributes( array( $attribute_id ), array( "slice_id" ) );
  $slice_id= $attrib_info[0]['slice_id'];
  
  // delete the attribute
  $api->DeleteSliceAttribute( $attribute_id );


  header( "location: index.php?id=$slice_id" );
  exit();
}


// attirbute updates
if( $_POST['edit_attribute'] ) {
  // get the id of the attrib to update and teh value from POST
  $attribute_id= intval( $_POST['attribute_id'] );
  $value= $_POST['value'];
  $slice_id= $_POST['slice_id'];

  // update it!
  $api->UpdateSliceAttribute( $attribute_id, $value );

  header( "location: index.php?id=$slice_id" );
  exit();
}


// attribute adds
if( $_POST['add_attribute'] ) {
  // get the slice_id, attribute_type_id, and value from POST
  $slice_id= intval( $_POST['slice_id'] );
  $attribute_type_id= intval( $_POST['attribute_type_id'] );
  $value= $_POST['value'];

  // add it!
  $api->AddSliceAttribute( $slice_id, $attribute_type_id, $value );

  header( "location: index.php?id=$slice_id" );
  exit();
}

// ATTRIBUTE TYPES ---------------------------------------------------
  
// attribute type adds
if( $_POST['add_type'] ) {
  // get post vars 
  $name= $_POST['name'];
  $min_role_id= intval( $_POST['min_role_id'] );
  $description= $_POST['description'];
  
  // make the attribute_type_fields dict
  $attribute_type_fields= array( "min_role_id" => $min_role_id, "name" => $name, "description" => $description );
  
  // add it!!
  $api->AddSliceAttributeType( $attribute_type_fields );

  header( "location: attributes.php" );
  exit();
}
  

// attribute type updates
if( $_POST['edit_type'] ) {
  // get post vars 
  $name= $_POST['name'];
  $min_role_id= intval( $_POST['min_role_id'] );
  $description= $_POST['description'];  
  $attribute_type_id= intval( $_POST['attribute_type_id'] );
  
  // make attribute_type_fields dict
  $attribute_type_fields= array( "min_role_id" => $min_role_id, "name" => $name, "description" => $description );

  // Update it!
  $api->UpdateSliceAttributeType( $attribute_type_id, $attribute_type_fields );
  
  header( "location: attributes.php" );
  exit();
}


// delete attribute types
if( $_GET['del_type'] ) {
  // get vars
  $type_id= intval( $_GET['del_type'] );

  // delete it!
  $api->DeleteSliceAttributeType( $type_id );
  
  header( "location: attributes.php" );
  exit();
}

  
  
/*
// Print footer
include 'plc_footer.php';
*/

?>
