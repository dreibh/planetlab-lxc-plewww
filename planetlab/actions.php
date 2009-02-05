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
//////////////////////////////////////// persons
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
$known_actions []= "update-person";
//	expects:	person_id & first_name last_name title email phone url bio + [password1 password2]

//////////////////////////////////////// nodes
$known_actions []= "node-boot-state";	
//	expects:	node_id boot_state
$known_actions []= "delete-node";	
//	expects:	node_id
$known_actions []= "update-node";	
//	expects:	node_id, hostname, model
//////////////////////////////////////// sites
$known_actions []= "delete-site";	
//	expects:	site_id
$known_actions []= "expire-all-slices-in-site";
//	expects:	slice_ids

//////////////////////////////////////// tags
$known_actions []= "update-tag-type";
//	expects:	tag_type_id & name & description & category & min_role_id  
$known_actions []= "add-tag-type";
//	expects:	tag_type_id & name & description & category & min_role_id  
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
   plc_redirect (l_person($person_id));
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
   plc_redirect (l_person($person_id));
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
   plc_redirect (l_person($person_id));
 }
     
 case 'add-role-to-person' : {
   $role_id=$_POST['role_id'];
   $api->AddRoleToPerson( intval( $role_id ), intval( $person_id ) );
   plc_redirect (l_person($person_id));
 }

 case 'enable-person' : {
   $fields = array( "enabled"=>true );
   $api->UpdatePerson( intval( $person_id ), $fields );
   plc_redirect (l_person($person_id));
 }

 case 'disable-person' : {
   $fields = array( "enabled"=>false );
   $api->UpdatePerson( intval( $person_id ), $fields );
   plc_redirect (l_person($person_id));
 }

 case 'become-person' : {
   $plc->BecomePerson (intval($person_id));
   plc_redirect (l_persons());
 }

 case 'delete-person' : {
  $api->DeletePerson( intval( $person_id ) );
   plc_redirect (l_persons());
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
   plc_redirect(l_person($person_id));
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
   plc_redirect(l_person($person_id));
 }

 case 'update-person': {
   $person_id=$_POST['person_id'];
   // attempt to update this person
   $first_name= $_POST['first_name'];
   $last_name= $_POST['last_name'];
   $title= $_POST['title'];
   $email= $_POST['email'];
   $phone= $_POST['phone'];
   $url= $_POST['url'];
   $bio= str_replace("\r", "", $_POST['bio']);
   $password1= $_POST['password1'];
   $password2= $_POST['password2'];

   if( $password1 != $password2 ) {
     drupal_set_error ("The passwords do not match");
     plc_redirect(l_person($person_id));
  }

   $update_vals= array();
   $update_vals['first_name']= $first_name;
   $update_vals['last_name']= $last_name;
   $update_vals['title']= $title;
   $update_vals['email']= $email;
   $update_vals['phone']= $phone;
   $update_vals['url']= $url;
   $update_vals['bio']= $bio;
		
   if( $password1 != "" )
     $update_vals['password']= $password1;
    
    $rc= $api->UpdatePerson( intval( $person_id ), $update_vals);
    
    if ( $rc == 1 ) {
      drupal_set_message("$first_name $last_name updated");
    } else {
      drupal_set_error ("Could not update person $person_id" . $api->error());
    }
    plc_redirect(l_person($person_id));
    break;
  }

//////////////////////////////////////////////////////////// nodes
 case 'node-boot-state': {
   $node_id=intval($_POST['node_id']);
   $boot_state=$_POST['boot_state'];
   $result=$api->UpdateNode( $node_id, array( "boot_state" => $boot_state ) );
   if ($result==1) {
     drupal_set_message("boot state updated");
     plc_redirect (l_node($node_id));
   } else {
     drupal_set_error("Could not set boot_state '$boot_state'");
   }
   break;
 }

 case 'delete-node': {
   $node_id=intval($_POST['node_id']);
   $result=$api->DeleteNode( intval( $node_id ) );
   if ($api==1) {
     drupal_set_message("Node $node_id deleted");
     plc_redirect (l_nodes());
   } else {
     drupal_set_error ("Could not delete node $node_id");
   }
   break;
 }

 case 'update-node': {
   $hostname= $_POST['hostname'];
   $model= $_POST['model'];

   $fields= array( "hostname"=>$hostname, "model"=>$model );
   $api->UpdateNode( intval( $node_id ), $fields );
   $error= $api->error();

   if( empty( $error ) ) {
     drupal_set_message("Update node $hostname");
     plc_redirect(l_node($node_id));
   } else {
     drupal_set_error($error);
   }
   break;
 }

//////////////////////////////////////////////////////////// sites
 case 'delete-site': {
   $site_id = intval($_POST['site_id']);
   if ($api->DeleteSite($site_id) ==1) 
     drupal_set_message ("Site $site_id deleted");
   else
     drupal_set_error("Failed to delete site $site_id");
   plc_redirect (l_sites());
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
   plc_redirect (l_site($site_id));
 }

//////////////////////////////////////////////////////////// tags

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
   
   plc_redirect(l_tag($tag_type_id));
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
  
   plc_redirect( l_tag($id));
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
   
   plc_redirect (l_node($node_id));
 }

////////////////////////////////////////

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
