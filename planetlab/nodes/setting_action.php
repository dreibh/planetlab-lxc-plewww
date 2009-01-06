<?php

// $Id$

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

//plc_debug('GET',$_GET);
//plc_debug('POST',$_POST);

// tag type updates
if( $_POST['edit_type'] ) {
  $setting_type_id= intval( $_POST['interface_tag_type_id'] );
  $setting_type = array ('category' => $_POST['category'],
			 'name' => $_POST['name'],
			 'min_role_id' => intval( $_POST['min_role_id'] ),
			 'description' => $_POST['description']);
  
  // Update it!
  $api->UpdateTagType( $setting_type_id, $setting_type );
  $api_error=$api->error();
  if (!empty($api_error)) {
    print "<div class='plc-error'>" . $api_error . "</div>";
  }
  
  header( "location: settings.php" );
  exit();
}

// tag type adds
if( $_POST['add_type'] ) {
  $setting_type = array ('category' => $_POST['category'],
			 'name' => $_POST['name'],
			 'min_role_id' => intval( $_POST['min_role_id'] ),
			 'description' => $_POST['description']);
  // add it!!
  $api->AddTagType( $setting_type );

  header( "location: settings.php" );
  exit();
}
  

// tag deletion
if( $_GET['rem_id'] ) {
  // get the id of the tag to remove from GET
  $setting_id= intval( $_GET['rem_id'] );

  // get interface_id 
  $setting= $api->GetInterfaceTags( array( $setting_id ), array( "interface_id" ) );
  $interface_id= $setting[0]['interface_id'];
  
  // delete the tag
  $api->DeleteInterfaceTag( $setting_id );

  header( "location: interfaces.php?id=$interface_id" );
  exit();
}

// tag adds
if( $_POST['add_setting'] ) {
  // get the interface_id, tag_type_id, and value from POST
  $interface_id= intval( $_POST['interface_id'] );
  $interface_tag_type_id= intval( $_POST['interface_tag_type_id'] );
  $value= $_POST['value'];

  // add it!
  $api->AddInterfaceTag( $interface_id, $interface_tag_type_id, $value );

  header( "location: interfaces.php?id=$interface_id" );
  exit();
}

// tag updates
if( $_POST['edit_setting'] ) {
  // get the id of the setting to update and the value from POST
  $setting_id= intval( $_POST['setting_id'] );
  $value= $_POST['value'];
  $interface_id= $_POST['interface_id'];

  // update it!
  $api->UpdateInterfaceTag($setting_id, $value );

  header( "location: interfaces.php?id=$interface_id" );
  exit();
}

// Settings -------------------------------------------------

// TAG TYPES ---------------------------------------------------
  
// delete tag types
if( $_GET['del_type'] ) {
  // get vars
  $type_id= intval( $_GET['del_type'] );

  // delete it!
  $api->DeleteTagType( $type_id );
  
  header( "location: settings.php" );
  exit();
}
  
/*
// Print footer
include 'plc_footer.php';
*/

?>
