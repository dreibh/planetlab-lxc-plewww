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

//////////////////////////////////////// interfaces
$known_actions []= "delete-interfaces";	
//	expects:	interface_ids
$known_actions []="add-interface";
//	expects:	node_id & interface details
$known_actions []="update-interface";
//	expects:	interface_id & interface details

//////////////////////////////////////// sites
$known_actions []= "delete-site";	
//	expects:	site_id
$known_actions []= "expire-all-slices-in-site";
//	expects:	slice_ids
$known_actions []= "update-site";
//	expects:	site_id & name abbreviated_name url latitude longitude [login_base max_slices]

//////////////////////////////////////// tag types
$known_actions []= "update-tag-type";
//	expects:	tag_type_id & name & description & category & min_role_id  
$known_actions []= "add-tag-type";
//	expects:	tag_type_id & tagname & description & category & min_role_id  
$known_actions []= "delete-tag-types";
//	expects:	tag_type_ids

//////////////////////////////////////// tags
$known_actions []= "set-tag-on-node";
//	expects:	node_id tagname value
$known_actions []= "set-tag-on-interface";
//	expects:	interface_id tagname value
$known_actions []= "delete-node-tags";
//	expects:	node_id & node_tag_ids
$known_actions []= "delete-interface-tags";
//	expects:	interface_id & interface_tag_ids

////////////////////////////////////////////////////////////
$interface_details= array ('method','type', 'ip', 'gateway', 'network', 
			   'broadcast', 'netmask', 'dns1', 'dns2', 
			   'hostname', 'mac', 'bwlimit' );

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

//uncomment for debugging incoming data
//$action='debug';

$person_id = $_POST['person_id'];	// usually needed

if ( ! $action ) {
  drupal_set_message ("actions.php: action not set or not in known_actions");
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
   $success=true;
   $counter=0;
   foreach( $key_ids as $key_id ) {
     if ($api->DeleteKey( intval( $key_id )) != 1) 
       $success=false;
     else
       $counter++;
   }
   if ($success) 
     drupal_set_message ("Deleted $counter key(s)");
   else
     drupal_set_error ("Could not delete all selected keys, only $counter were removed");
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
   
   $key_id = $api->AddPersonKey( intval( $person_id ), array( "key_type"=> 'ssh', "key"=> $key ) );
   
   if ( $key_id == 1) 
     drupal_set_message ("New key added");
   else
     drupal_set_error("Could not add key, please verify your SSH file content\n" . $api->error());
   
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

   $fields= array();
   $fields['first_name']= $first_name;
   $fields['last_name']= $last_name;
   $fields['title']= $title;
   $fields['email']= $email;
   $fields['phone']= $phone;
   $fields['url']= $url;
   $fields['bio']= $bio;
		
   if ( $password1 != "" )
     $fields['password']= $password1;
    
    if ( $api->UpdatePerson( intval( $person_id ), $fields) == 1 )
      drupal_set_message("$first_name $last_name updated");
    else 
      drupal_set_error ("Could not update person $person_id" . $api->error());

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

//////////////////////////////////////////////////////////// interfaces
 case 'delete-interfaces' : {
   $interface_ids=$_POST['interface_ids'];
   if ( ! $interface_ids) {
     drupal_set_message("action=$action - No interface selected");
     return;
   }
   $success=true;
   $counter=0;
   foreach( $interface_ids as $interface_id ) {
     if ($api->DeleteInterface( intval( $interface_id )) != 1) 
       $success=false;
     else
       $counter++;
   }
   if ($success) 
     drupal_set_message ("Deleted $counter interface(s)");
   else
     drupal_set_error ("Could not delete all selected interfaces, only $counter were removed");
   plc_redirect(l_node($_POST['node_id']));
 }

 case 'add-interface': {
   $node_id=$_POST['node_id'];
   foreach ($interface_details as $field) {
     $interface[$field]= $_POST[$field];
     if( in_array( $field, array( 'bwlimit', 'node_id' ) ) ) {
       $interface[$field]= intval( $interface[$field] );
     }
   }
   $result=$api->AddInterface( intval( $node_id ), $interface );
   if ($result >0 ) 
     drupal_set_message ("Interface $result added into node $node_id");
   else
     drupal_set_error ("Could not create interface");
   plc_redirect (l_node($node_id));
 }
   
 case 'update-interface': {
   $interface_id=$_POST['interface_id'];
   foreach ($interface_details as $field) {
     $interface[$field]= $_POST[$field];
     if( in_array( $field, array( 'bwlimit', 'node_id' ) ) ) {
       $interface[$field]= intval( $interface[$field] );
     }
   }
   $result=$api->UpdateInterface( intval( $interface_id ), $interface );
   if ($result == 1 ) 
     drupal_set_message ("Interface $interface_id updated");
   else
     drupal_set_error ("Could not update interface");
   plc_redirect (l_interface($interface_id));
 }
   
//////////////////////////////////////////////////////////// sites
 case 'delete-site': {
   $site_id = intval($_POST['site_id']);
   if ($api->DeleteSite($site_id) ==1) 
     drupal_set_message ("Site $site_id deleted");
   else
     drupal_set_error("Failed to delete site $site_id");
   plc_redirect (l_sites());
   break;
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
   break;
 }

 case 'update-site': {
   $site_id=intval($_POST['site_id']);
   $name= $_POST['name'];
   $abbreviated_name= $_POST['abbreviated_name'];
   $url= $_POST['url'];
   $latitude= floatval($_POST['latitude']);
   $longitude= floatval($_POST['longitude']);
   //$max_slivers= $_POST['max_slivers'];
   
   $fields= array( "name" => $name, 
		   "abbreviated_name" => $abbreviated_name, 
		   "url" => $url, 
		   "latitude" => floatval( $latitude ), 
		   "longitude" => floatval( $longitude ));

   if ($_POST['login_base']) 
     $fields['login_base'] = $_POST['login_base'];
   if ($_POST['max_slices']) 
     $fields['max_slices'] = intval($_POST['max_slices']);
   
   $retcod=$api->UpdateSite( intval( $site_id ), $fields );
   if ($retcod == 1) 
     drupal_set_message("Site $name updated");
   else 
     drupal_set_error ("Could not update site $site_id");
     
   plc_redirect(l_site($site_id));
   break;
 }

//////////////////////////////////////////////////////////// tag types

 case 'update-tag-type': {
  // get post vars 
   $tag_type_id= intval( $_POST['tag_type_id'] );
   $tagname = $_POST['tagname'];
   $min_role_id= intval( $_POST['min_role_id'] );
   $description= $_POST['description'];  
   $category= $_POST['category'];  
  
   // make tag_type_fields dict
   $tag_type_fields= array( "min_role_id" => $min_role_id, 
			    "tagname" => $tagname, 
			    "description" => $description,
			    "category" => $category,
			    );

   if ($api->UpdateTagType( $tag_type_id, $tag_type_fields ) == 1) 
     drupal_set_message ("Tag type $tagname updated");
   else 
     drupal_set_error ("Could not update tag type $tag_type_id\n".$api->error());
   plc_redirect(l_tag($tag_type_id));
 }

 case 'add-tag-type': {
  // get post vars 
   $tagname = $_POST['tagname'];
   $min_role_id= intval( $_POST['min_role_id'] );
   $description= $_POST['description'];  
   $category= $_POST['category'];  
  
   // make tag_type_fields dict
   $tag_type_fields= array( "min_role_id" => $min_role_id, 
			    "tagname" => $tagname, 
			    "description" => $description,
			    "category" => $category,
			    );

  // Add it!
   $tag_type_id=$api->AddTagType( $tag_type_fields );
   if ($tag_type_id > 0) 
     drupal_set_message ("tag type $tag_type_id created");
   else
     drupal_set_error ("Could not create tag type $tagname");
   plc_redirect( l_tags());
 }

 case 'delete-tag-types': {
   $tag_type_ids = $_POST['tag_type_ids'];
   if ( ! $tag_type_ids) {
     drupal_set_message("action=$action - No tag selected");
     return;
   }
   $success=true;
   $counter=0;
   foreach ($tag_type_ids as $tag_type_id) 
     if ($api->DeleteTagType(intval($tag_type_id)) != 1) 
       $success=false;
     else
       $counter++;
   if ($success) 
     drupal_set_message ("Deleted $counter tag(s)");
   else
     drupal_set_error ("Could not delete all selected tags, only $counter were removed");
   plc_redirect (l_tags());
   break;
 }

//////////////////////////////////////// tags   
 case 'set-tag-on-node': 
 case 'set-tag-on-interface': {
   
   $node_mode = false;
   if ($action == 'set-tag-on-node') $node_mode=true;

   if ($node_mode)
     $node_id = intval($_POST['node_id']);
   else 
     $interface_id=intval($_POST['interface_id']);
   $tag_type_id = intval($_POST['tag_type_id']);
   $value = $_POST['value'];

   $tag_types=$api->GetTagTypes(array($tag_type_id));
   if (count ($tag_types) != 1) {
     drupal_set_error ("Could not locate tag_type_id $tag_type_id </br> Tag not set.");
   } else {
     if ($node_mode) 
       $tags = $api->GetNodeTags (array('node_id'=>$node_id, 'tag_type_id'=> $tag_type_id));
     else
       $tags = $api->GetInterfaceTags (array('interface_id'=>$interface_id, 'tag_type_id'=> $tag_type_id));
     if ( count ($tags) == 1) {
       $tag=$tags[0];
       if ($node_mode) {
	 $tag_id=$tag['node_tag_id'];
	 $result=$api->UpdateNodeTag($tag_id,$value);
       } else {
	 $tag_id=$tag['interface_tag_id'];
	 $result=$api->UpdateInterfaceTag($tag_id,$value);
       }
       if ($result == 1) 
	 drupal_set_message ("Updated tag, new value = $value");
       else
	 drupal_set_error ("Could not update tag");
     } else {
       if ($node_mode)
	 $tag_id = $api->AddNodeTag($node_id,$tag_type_id,$value);
       else
	 $tag_id = $api->AddInterfaceTag($interface_id,$tag_type_id,$value);
       if ($tag_id) 
	 drupal_set_message ("Created tag, new value = $value");
       else
	 drupal_set_error ("Could not create tag");
     }
   }
   
   if ($node_mode)
     plc_redirect (l_node($node_id));
   else
     plc_redirect (l_interface($interface_id));
 }

 case 'delete-node-tags' : 
 case 'delete-interface-tags' : {

   $node_mode = false;
   if ($action == 'delete-node-tags') $node_mode=true;

   if ($node_mode)
     $tag_ids=$_POST['node_tag_ids'];
   else
     $tag_ids=$_POST['interface_tag_ids'];

   if ( ! $tag_ids) {
     drupal_set_message("action=$action - No tag selected");
     return;
   }
   $success=true;
   $counter=0;
   foreach( $tag_ids as $tag_id ) {
     if ($node_mode)
       $retcod = $api->DeleteNodeTag( intval( $tag_id ));
     else
       $retcod = $api->DeleteInterfaceTag( intval( $tag_id ));
     if ($retcod != 1) 
       $success=false;
     else
       $counter++;
   }
   if ($success) 
     drupal_set_message ("Deleted $counter tag(s)");
   else
     drupal_set_error ("Could not delete all selected tags, only $counter were removed");
   if ($node_mode)
     plc_redirect(l_node($_POST['node_id']));
   else
     plc_redirect(l_interface($_POST['interface_id']));
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
