<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

//print header
require_once 'plc_drupal.php';

// Common functions
require_once 'plc_functions.php';

$known_actions=array();
////////////////////////////////////////////////////////////
// interface :
// (*) use POST 
// (*) set 'action' to one of the following
$known_actions []= "add-person-to-site";
//	expects:	person_id & site_id
$known_actions []= "remove-person-from-sites";
//	expects:	person_id & site_ids
$known_actions []= "remove-roles-from-person";
//	expects:	person_id & role_ids
$known_actions []= "add-role-to-person";
//	expects:	role_person_id & id
$known_actions []= "enable-person";
//	expects:	person_id
$known_actions []= "disable-person";
//	expects:	person_id
$known_actions []= "become-person";
//	expects:	person_id
$known_actions []= "delete-person";
//	expects:	person_id
$known_actions []= "delete-keys";
//	expects:	key_ids & person_id (for redirecting to the person's page)
$known_actions []= "upload-key";
//	expects:	person_id & $_FILES['key']
$known_actions []= "update-tag-type";
//	expects:	tag_type_id & name & description & category & min_role_id  
$known_actions []= "add-tag-type";
//	expects:	tag_type_id & name & description & category & min_role_id  
$known_actions []= "delete-site";	
//	expects:	site_id
$known_actions []= "expire-all-slices-in-site";
//	expects:	slice_ids
$known_actions []= "set-tag-on-node";
//	expects:	node_id tagname value

//////////////////////////////
// sometimes we don't set 'action', but use the submit button name instead
// so if 'action' not set, see if $_POST has one of the actions as a key
if ($_POST['action']) 
  $action=$_POST['action'];
else 
  foreach ($known_actions as $known_action) 
    if ($_POST[$known_action]) {
      $action=$known_action;
      break;
    }

//debug
//$action='debug';

$person_id = $_POST['person_id'];	// usually needed

if ( ! $action ) {
  drupal_set_message ("actions.php: action not set");
  plc_debug('POST',$_POST);
  return;
 }

switch ($action) {

 case 'add-person-to-site': {
   $site_id = $_POST['site_id'];
   $api->AddPersonToSite( intval( $person_id ), intval( $site_id ) );
   header( "location: " . l_person($person_id));
   exit();
 }

 case 'remove-person-from-sites': {
   $site_ids = $_POST['site_ids'];
   if ( ! $site_ids) {
     drupal_set_message("action=$action - No site selected");
     return;
   }
   foreach ( $site_ids as $site_id ) {
     $api->DeletePersonFromSite( intval( $person_id ), intval( $site_id ) );
   }
   header( "location: " . l_person($person_id));
   exit();
 }

 case 'remove-roles-from-person' : {
   $role_ids=$_POST['role_ids'];
   if ( ! $role_ids) {
     drupal_set_message("action=$action - No role selected");
     return;
   }
   foreach( $role_ids as $role_id)  {
     $api->DeleteRoleFromPerson( intval( $role_id ), intval( $person_id ) );
   }
   header( "location: " . l_person($person_id));
   exit();
 }
     
 case 'add-role-to-person' : {
   $role_id=$_POST['role_id'];
   $api->AddRoleToPerson( intval( $role_id ), intval( $person_id ) );
   header( "location: " . l_person($person_id));
   exit();
 }

 case 'enable-person' : {
   $fields = array( "enabled"=>true );
   $api->UpdatePerson( intval( $person_id ), $fields );
   header( "location: " . l_person($person_id));
   exit();
 }

 case 'disable-person' : {
   $fields = array( "enabled"=>false );
   $api->UpdatePerson( intval( $person_id ), $fields );
   header( "location: " . l_person($person_id));
   exit();
 }

 case 'become-person' : {
   $plc->BecomePerson (intval($person_id));
   header ("location: " . l_persons());
   exit();
 }

 case 'delete-person' : {
  $api->DeletePerson( intval( $person_id ) );
  header( "location: " . l_persons() );
  exit();
 }

 case 'delete-keys' : {
   $key_ids=$_POST['key_ids'];
   if ( ! $key_ids) {
     drupal_set_message("action=$action - No key selected");
     return;
   }
   foreach( $key_ids as $key_id ) {
     $api->DeleteKey( intval( $key_id ) );
   }
   header( "location: " . l_person($person_id));
   exit();
 }

 case 'upload-key' : {
   if ( ! isset( $_FILES['key'] ) ) {
     drupal_set_message ("action=$action, no key file set");
     return;
   }
   
   $key_file= $_FILES['key']['tmp_name'];
   if ( ! $key_file ) {
     plc_error("Please select a valid SSH key file to upload");
     return;
   } 
   $fp = fopen( $key_file, "r" );
   $key = "";
   if( ! $fp ) {
     plc_error("Unable to open key file $key_file");
     return;
   }
   // opened the key file, read the one line of contents
   // The POST operation always creates a file even if the filename
   // the user specified was garbage.  If there was some problem
   // with the source file, we'll get a zero length read here.
   $key = fread($fp, filesize($key_file));
   fclose($fp);
   
   $key_id= $api->AddPersonKey( intval( $person_id ), array( "key_type"=> 'ssh', "key"=> $key ) );
   
   if ( ! $key_id ) {
     $error=  $api->error();
     plc_error("$error");
     plc_error("Please verify your SSH  file content");
     return;
   }
   header( "location: " . l_person($person_id));
   exit();
 }

 case 'update-tag-type': {
  // get post vars 
   $tag_type_id= intval( $_POST['tag_type_id'] );
   $name = $_POST['name'];
   $min_role_id= intval( $_POST['min_role_id'] );
   $description= $_POST['description'];  
   $category= $_POST['category'];  
  
   // make tag_type_fields dict
   $tag_type_fields= array( "min_role_id" => $min_role_id, 
			    "tagname" => $name, 
			    "description" => $description,
			    "category" => $category,
			    );

   // Update it!
   $api->UpdateTagType( $tag_type_id, $tag_type_fields );
   
   header( "location: " . l_tag($tag_type_id));
   exit();
 }

 case 'add-tag-type': {
  // get post vars 
   $name = $_POST['name'];
   $min_role_id= intval( $_POST['min_role_id'] );
   $description= $_POST['description'];  
   $category= $_POST['category'];  
  
   // make tag_type_fields dict
   $tag_type_fields= array( "min_role_id" => $min_role_id, 
			    "tagname" => $name, 
			    "description" => $description,
			    "category" => $category,
			    );

  // Add it!
   $id=$api->AddTagType( $tag_type_fields );
   drupal_set_message ("tag type $id created");
  
   header( "location: " . l_tag($id));
   exit();
 }

 case 'delete-site': {
   $site_id = intval($_POST['site_id']);
   if ($api->DeleteSite($site_id) ==1) 
     drupal_set_message ("Site $site_id deleted");
   else
     drupal_set_error("Failed to delete site $site_id");
   header ("location: " . l_sites());
   exit();
 }

 case 'expire-all-slices-in-site': {
   // xxx todo
   drupal_set_message("action $action not implemented in actions.php -- need tweaks and test");
   return;

   //// old code from sites/expire.php
   $sites = $api->GetSites( array( intval( $site_id )));
   $site=$sites[0];
   // xxx why not 'now?'
   $expiration= strtotime( $_POST['expires'] );
   // loop through all slices for site
   foreach ($site['slice_ids'] as $slice_id) {
     $api->UpdateSlice( $slice_id, array( "expires" => $expiration ) );
   }
   // update site to not allow slice creation or renewal
   $api->UpdateSite( $site_id, array( "max_slices" => 0 )) ;
   header ("location: " . l_site($site_id));
   exit(0);
 }

 case 'set-tag-on-node': {

   $node_id = intval($_POST['node_id']);
   $tag_type_id = intval($_POST['tag_type_id']);
   $value = $_POST['value'];

   $tag_types=$api->GetTagTypes(array($tag_type_id));
   if (count ($tag_types) != 1) {
     drupal_set_error ("Could not locate tag_type_id $tag_type_id </br> Tag not set.");
   } else {
     $tags = $api->GetNodeTags (array('node_id'=>$node_id, 'tag_type_id'=> $tag_type_id));
     if ( count ($tags) == 1) {
       $tag=$tags[0];
       $tag_id=$tag['node_tag_id'];
       $result=$api->UpdateNodeTag($tag_id,$value);
       if ($result == 1) 
	 drupal_set_message ("Updated tag, new value = $value");
       else
	 drupal_set_error ("Could not update tag");
     } else {
       $tag_id = $api->AddNodeTag($node_id,$tag_type_id,$value);
       if ($tag_id) 
	 drupal_set_message ("Created tag, new value = $value");
       else
	 drupal_set_error ("Could not create tag");
     }
   }
   
   header ("location: " . l_node($node_id));
   exit();
 }


 case 'debug': {
   plc_debug('GET',$_GET);
   plc_debug('POST',$_POST);
   plc_debug('FILES',$_FILES);
   return;
 }

 default: {
   plc_error ("Unknown action $action in actions.php");
   return;
 }

 }

?>