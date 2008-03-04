<?php

// $Id: setting_action.php 1159 2008-01-24 18:51:36Z thierry $

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

// attribute type updates
if( $_POST['edit_type'] ) {
  $setting_type_id= intval( $_POST['nodenetwork_setting_type_id'] );
  $setting_type = array ('category' => $_POST['category'],
			 'name' => $_POST['name'],
			 'min_role_id' => intval( $_POST['min_role_id'] ),
			 'description' => $_POST['description']);
  
  // Update it!
  $api->UpdateNodeNetworkSettingType( $setting_type_id, $setting_type );
  $api_error=$api->error();
  if (!empty($api_error)) {
    print "<div class='plc-error'>" . $api_error . "</div>";
  }
  
  header( "location: settings.php" );
  exit();
}

// attribute type adds
if( $_POST['add_type'] ) {
  $setting_type = array ('category' => $_POST['category'],
			 'name' => $_POST['name'],
			 'min_role_id' => intval( $_POST['min_role_id'] ),
			 'description' => $_POST['description']);
  // add it!!
  $api->AddNodeNetworkSettingType( $setting_type );

  header( "location: settings.php" );
  exit();
}
  

// attribute deletion
if( $_GET['rem_id'] ) {
  // get the id of the attrib to remove from GET
  $setting_id= intval( $_GET['rem_id'] );

  // get nodenetwork_id 
  $setting= $api->GetNodeNetworkSettings( array( $setting_id ), array( "nodenetwork_id" ) );
  $nodenetwork_id= $setting[0]['nodenetwork_id'];
  
  // delete the attribute
  $api->DeleteNodeNetworkSetting( $setting_id );

  header( "location: node_networks.php?id=$nodenetwork_id" );
  exit();
}

// attribute adds
if( $_POST['add_setting'] ) {
  // get the nodenetwork_id, attribute_type_id, and value from POST
  $nodenetwork_id= intval( $_POST['nodenetwork_id'] );
  $nodenetwork_setting_type_id= intval( $_POST['nodenetwork_setting_type_id'] );
  $value= $_POST['value'];

  // add it!
  $api->AddNodeNetworkSetting( $nodenetwork_id, $nodenetwork_setting_type_id, $value );

  header( "location: node_networks.php?id=$nodenetwork_id" );
  exit();
}

// attribute updates
if( $_POST['edit_setting'] ) {
  // get the id of the setting to update and the value from POST
  $setting_id= intval( $_POST['setting_id'] );
  $value= $_POST['value'];
  $nodenetwork_id= $_POST['nodenetwork_id'];

  // update it!
  $api->UpdateNodeNetworkSetting($setting_id, $value );

  header( "location: node_networks.php?id=$nodenetwork_id" );
  exit();
}

// down here is some codqe from attrib_action.php that was not converted yet
// Settings -------------------------------------------------

// ATTRIBUTE TYPES ---------------------------------------------------
  
// delete attribute types
if( $_GET['del_type'] ) {
  // get vars
  $type_id= intval( $_GET['del_type'] );

  // delete it!
  $api->DeleteNodeNetworkSettingType( $type_id );
  
  header( "location: settings.php" );
  exit();
}

  
  
/*
// Print footer
include 'plc_footer.php';
*/

?>
