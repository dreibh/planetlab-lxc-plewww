<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
require_once 'plc_api.php';
global $plc, $api, $adm;

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
$known_actions []= "attach-pcu";
//	expects:	node_id, pcu_id, port (pcu_id <0 means detach)
$known_actions []= "reboot-node-with-pcu";
//	expects:	node_id

//////////////////////////////////////// interfaces
$known_actions []= "delete-interfaces";	
//	expects:	interface_ids
$known_actions []="add-interface";
//	expects:	node_id & interface details
$known_actions []="new-interface";
//	expects:	node_id 
$known_actions []="update-interface";
//	expects:	interface_id & interface details

//////////////////////////////////////// sites
$known_actions []= "delete-site";	
//	expects:	site_id
$known_actions []= "expire-all-slices-in-site";
//	expects:	slice_ids
$known_actions []= "update-site";
//	expects:	site_id & name abbreviated_name url latitude longitude [login_base max_slices]

//////////////////////////////////////// slices
$known_actions []= "delete-slice";
//      expects:        slice_id
$known_actions []= "update-slice";	
//	expects:	slice_id, name, description, url
$known_actions []= "renew-slice";
//	expects:	slice_id & expires
$known_actions []= 'remove-persons-from-slice';
//	expects:	slice_id & person_ids
$known_actions []= 'add-persons-in-slice';
//	expects:	slice_id & person_ids
$known_actions []= 'remove-nodes-from-slice';
//	expects:	slice_id & node_ids
$known_actions []= 'add-nodes-in-slice';
//	expects:	slice_id & node_ids
$known_actions []= 'update-initscripts';
//	expects:	slice_id & name & previous-initscript & previous-initscript-code 
//			& initscript & initscript-code
$known_actions []= 'delete-slice-tags';
//      expects:        slice_tag_id
$known_actions []= 'add-slice-tag';
//      expects:        slice_id & tag_type_id & node_id & nodegroup_id

//////////////////////////////////////// tag types
$known_actions []= "update-tag-type";
//	expects:	tag_type_id & name & description & category & min_role_id  
$known_actions []= "add-tag-type";
//	expects:	tag_type_id & tagname & description & category & min_role_id  
$known_actions []= "delete-tag-types";
//	expects:	tag_type_ids
$known_actions []= "remove-roles-from-tag-type";
//	expects:	tag_type_id & role_ids
$known_actions []= "add-role-to-tag-type";
//	expects:	tag_type_id_id & id

//////////////////////////////////////// tags
$known_actions []= "set-tag-on-node";
//	expects:	node_id tagname value
$known_actions []= "set-tag-on-interface";
//	expects:	interface_id tagname value
$known_actions []= "delete-node-tags";
//	expects:	node_id & node_tag_ids
$known_actions []= "delete-interface-tags";
//	expects:	interface_id & interface_tag_ids

//////////////////////////////////////// nodegroups
$known_actions []= "update-nodegroup";
//	expects nodegroup_id groupname value
$known_actions []= "add-nodegroup";
//	expects groupname, tag_type_id, value
$known_actions []= 'delete-nodegroups';
//	expects nodegroup_ids

//////////////////////////////////////// leases
$known_actions []= "manage-leases";
//	expects as 'actions' a list of 'action' of the form
//      either [ 'add-leases', [nodenames], slicename, t_from, t_until ]
//      or     [ 'delete-leases', lease_id ]

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
     drupal_set_error("You have not selected role(s) to remove");
   } else {
     foreach( $role_ids as $role_id)  
       if ( $api->DeleteRoleFromPerson( intval( $role_id ), intval( $person_id ) ) != 1 ) 
	 drupal_set_error ("Could not remove role $role_id from person $person_id");
   }
   plc_redirect (l_person_roles($person_id));
 }
     
 case 'add-role-to-person' : {
   $role_id=$_POST['role_id'];
   if ( ! $role_id) {
     drupal_set_error ("You have not selected a role to add");
   } else if ($api->AddRoleToPerson( intval( $role_id ), intval( $person_id ) ) != 1) {
     drupal_set_error("Could not add role $role_id to person $person_id");
   }
   plc_redirect (l_person_roles($person_id));
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
   plc_redirect (l_person(intval($person_id)));
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
   
   if ( $key_id >= 1) 
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
   $node_id=intval($_POST['node_id']);
   $hostname= $_POST['hostname'];
   $model= $_POST['model'];

   $fields= array( "hostname"=>$hostname, "model"=>$model );
   $api->UpdateNode( $node_id, $fields );
   $error= $api->error();

   if( empty( $error ) ) {
     drupal_set_message("Update node $hostname");
     plc_redirect(l_node($node_id));
   } else {
     drupal_set_error($error);
   }
   break;
 }

 // this code will ensure that at most one PCU gets attached to the node
 case 'attach-pcu': {
   $node_id=intval($_POST['node_id']);
   $pcu_id=intval($_POST['pcu_id']);
   $port=intval($_POST['port']);
   // always start with deleting former PCUs
   $nodes = $api->GetNodes(array($node_id),array('pcu_ids'));
   $former_pcu_ids = $nodes[0]['pcu_ids'];
   if ($former_pcu_ids) foreach ($former_pcu_ids as $former_pcu_id) {
       if ($api->DeleteNodeFromPCU($node_id,$former_pcu_id) == 1) 
	 drupal_set_message ('Detached node ' . $node_id . ' from PCU ' . $pcu_id);
       else 
	 drupal_set_error ('Could not detach node ' . $node_id . ' from PCU ' . $pcu_id);
     }
   // re-attach only if provided pcu_id >=0
   if ($pcu_id >= 0) {
     if ($api->AddNodeToPCU($node_id,$pcu_id,$port) == 1)
       drupal_set_message ('Attached node ' . $node_id . ' to PCU ' . $pcu_id . ' on port ' . $port);
     else
       drupal_set_error ('Failed to attach node ' . $node_id . ' to PCU ' . $pcu_id . ' on port ' . $port);
   } else {
     drupal_set_message ('Detached node from all PCUs');
   }
   
   plc_redirect(l_node($node_id));
   break;
 }

 case 'reboot-node-with-pcu': {
   $node_id=intval($_POST['node_id']);
   $hostname= $_POST['hostname'];
   $test = $_POST['test'];
   settype($test, "boolean");

   $ret = $api->RebootNodeWithPCU( $node_id, $test );
   $error= $api->error();

   if( empty( $error ) ) {
     drupal_set_message("Reboot node $hostname: $ret");
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

 case 'new-interface': {
   plc_redirect(l_interface_add($_POST['node_id']));
 }

 case 'add-interface': {
   $node_id=intval($_POST['node_id']);
   foreach ($interface_details as $field) {
     $interface[$field]= $_POST[$field];
     // these must be integers
     if( in_array( $field, array( 'bwlimit', 'node_id' ) ) ) {
       if ( empty ($interface[$field]) ) 
	 unset ($interface[$field]);
       else 
	 $interface[$field]= intval( $interface[$field] );
     }
   }
   $interface_id =$api->AddInterface( $node_id , $interface );
   if ($interface_id <= 0 ) {
     drupal_set_error ("Could not create interface");
     drupal_set_error ($api->error());
   } else {
     $ip=$interface['ip'];
     drupal_set_message ("Interface $ip added into node $node_id");
     if ($_POST['is-virtual']) {
       $ifname=$_POST['ifname'];
       if ($api->AddInterfaceTag($interface_id,"ifname",$ifname) <= 0) 
	 drupal_set_error ("Could not set tag 'ifname'=$ifname");
       else 
	 drupal_set_message ("Set tag 'ifname'=$ifname");
       $alias=$_POST['alias'];
       // deafult to interface_id
       if ( ! $alias ) $alias=strval($interface_id);
       if ($api->AddInterfaceTag($interface_id,"alias",$alias) <= 0) 
	 drupal_set_error ("Could not set tag 'alias'=$alias");
       else 
	 drupal_set_message ("Set tag 'alias'=$alias");
     }
   }
   plc_redirect (l_node_interfaces($node_id));
 }
   
 case 'update-interface': {
   $interface_id=$_POST['interface_id'];
   foreach ($interface_details as $field) {
     $interface[$field]= $_POST[$field];
     if( in_array( $field, array( 'bwlimit', 'node_id' ) ) ) {
       if ( intval($interface[$field]) != 0 ) {
           $interface[$field]= intval( $interface[$field]);
       } elseif ($field=='bwlimit' ) {
           $interface[$field] = NULL;
       }
     }
   }
   $result=$api->UpdateInterface( intval( $interface_id ), $interface );
   if ($result == 1 ) 
     drupal_set_message ("Interface $interface_id updated");
   else
     drupal_set_error ("Could not update interface: " . $api->error());
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
   if (isset($_POST['max_slices']))
     $fields['max_slices'] = intval($_POST['max_slices']);
   if (isset($_POST['enabled'])) {
     $fields['enabled'] = (bool)$_POST['enabled'];
   }
   
   $retcod=$api->UpdateSite( intval( $site_id ), $fields );
   if ($retcod == 1) 
     drupal_set_message("Site $name updated");
   else 
     drupal_set_error ("Could not update site $site_id");
     
   plc_redirect(l_site($site_id));
   break;
 }

//////////////////////////////////////////////////////////// slices
 case 'delete-slice': {
   $slice_id = $_POST['slice_id'];
   if ($api->DeleteSlice( intval( $slice_id )) == 1 ) {
     drupal_set_message("Slice $slice_id deleted");
     plc_redirect(l_slices());
   } else {
     drupal_set_error("Could not delete slice $slice_id " . $api->error());
   }
   break;
 }
     
 case 'update-slice': {
   $slice_id = $_POST['slice_id'];
   $name = $_POST['name'];
   $description= $_POST['description'];
   $url= $_POST['url'];

   $fields= array( "description"=>$description, "url"=>$url );
   $api->UpdateSlice( intval( $slice_id ), $fields );
   $error= $api->error();

   if( empty( $error ) ) {
     drupal_set_message("Update slice $name");
     plc_redirect(l_slice($slice_id));
   } else {
     drupal_set_error($error);
   }
   break;
 }

 case 'renew-slice': {
   $slice_id = intval ($_POST['slice_id']); 	
   $expires = intval ($_POST['expires']);
   // 8 weeks from now
   // xxx
   $now=mktime();
   $WEEK=7*24*3600;
   $WEEKS=8;
   $MAX_FUTURE=$WEEKS*$WEEK;
   if ( ($expires-$now) > $MAX_FUTURE) {
     drupal_set_error("Cannot renew slice that far in the future, max is $WEEKS weeks from now");
     plc_redirect(l_slice($slice_id));
   }
   if ($api->UpdateSlice ($slice_id, array('expires'=>$expires)) == 1)
     drupal_set_message("Slice renewed");
   else
     drupal_set_error("Could not update slice $slice_id");
   plc_redirect(l_slice($slice_id));
   break;
 }

 case 'remove-persons-from-slice': {
   $slice_id = intval ($_POST['slice_id']); 	
   $person_ids = $_POST['person_ids'];
   
   $slice_name = "";
   $tmp_slices = $api->GetSlices($slice_id, array("name"));
   if (count($tmp_slices) > 0) {
       $tmp_slice = $tmp_slices[0];
       $slice_name = $tmp_slice["name"];
   }
   $notify_subject = "Removed from slice: " . $slice_name;
   $notify_body = sprintf("You have been removed from the slice %s.

Our support team will be glad to answer any question that you might have.
",$slice_name);
   $notify_person_ids = array();
   
   $success=true;
   $counter=0;
   foreach( $person_ids as $person_id ) {
     if ($api->DeletePersonFromSlice(intval($person_id),$slice_id) != 1) 
       $success=false;
     else {
         array_push($notify_person_ids, intval($person_id));
       $counter++;
     }
   }
   if ($success) {
     $adm->NotifyPersons($notify_person_ids,$notify_subject,$notify_body);
     drupal_set_message ("Deleted $counter person(s)");
   }
   else
     drupal_set_error ("Could not delete all selected persons, only $counter were removed");
   plc_redirect(l_slice($slice_id) . " &show_persons=true");
   break;
 }

 case 'add-persons-in-slice': {
   $slice_id = intval ($_POST['slice_id']); 	
   $person_ids = $_POST['person_ids'];

   $slice_name = "";
   $tmp_slices = $api->GetSlices($slice_id, array("name"));
   if (count($tmp_slices) > 0) {
     $tmp_slice = $tmp_slices[0];
     $slice_name = $tmp_slice["name"];
   }
   $notify_subject = "Added to slice: " . $slice_name;
   $notify_body = sprintf("You have been added to the slice %s as a user.

You can go to your slice page following the link below:
https://%s:%d/db/slices/index.php?id=%d

Our support team will be glad to answer any question that you might have.
",$slice_name,PLC_WWW_HOST,PLC_WWW_SSL_PORT,$slice_id);
   $notify_person_ids = array();
   
   $success=true;
   $counter=0;
   foreach ($person_ids as $person_id) {
     if ($api->AddPersonToSlice(intval($person_id),$slice_id) != 1) 
       $success=false;
     else {
       array_push($notify_person_ids, intval($person_id));
       $counter++;
     }
   }
   if ($success) {
     $adm->NotifyPersons($notify_person_ids,$notify_subject,$notify_body);
     drupal_set_message ("Added $counter person(s)");
   }
   else
     drupal_set_error ("Could not add all selected persons, only $counter were added");
   plc_redirect(l_slice($slice_id) . "&show_persons=true" );
   break;
 }

 case 'remove-nodes-from-slice': {
   $slice_id = intval ($_POST['slice_id']); 	
   $node_ids = array_map("intval",$_POST['node_ids']);
   $count=count($node_ids);
   
   if ($api->DeleteSliceFromNodes($slice_id,$node_ids) == 1) 
     drupal_set_message ("Removed $count node(s)");
   else
     drupal_set_error ("Could not remove selected nodes");
   plc_redirect(l_slice_nodes($slice_id));
   break;
 }

 case 'add-nodes-in-slice': {
   $slice_id = intval ($_POST['slice_id']); 	
   $node_ids = array_map("intval",$_POST['node_ids']);
   $count=count($node_ids);
   if ($api->AddSliceToNodes($slice_id,$node_ids) == 1) 
     drupal_set_message ("Added $count node(s)");
   else
     drupal_set_error ("Could not add all selected nodes");
   plc_redirect(l_slice_nodes($slice_id));
   break;
 }

 case 'update-initscripts': {
//	expects:	slice_id & name & previous-initscript & previous-initscript-code 
//			& initscript & initscript-code
   $slice_id = intval ($_POST['slice_id']); 	
   $previous_initscript=$_POST['previous-initscript'];
   $initscript=$_POST['initscript'];
   $previous_initscript_code=html_entity_decode($_POST['previous-initscript-code']);
   $initscript_code=$_POST['initscript-code'];

   $changes=FALSE;
   if (strcmp($initscript,$previous_initscript) != 0) {
     $newvalue=$api->SetSliceInitscript($slice_id,$initscript);
     $status = (strcmp($newvalue,$initscript)==0) ? "OK" : "failed";
     if (! $initscript)		drupal_set_message("Removed shared initscript '" . $previous_initscript . "' " . $status);
     else			drupal_set_message("Replaced shared initscript with '" . $initscript . "' " . $status);
     $changes=TRUE;
   }

   // somehow some \r chars make it here; just ignore them
   $previous_initscript_code=str_replace("\r","",$previous_initscript_code);
   //   plc_debug_txt('previous initscript_code after cr',$previous_initscript_code);

   $initscript_code=str_replace("\r","",$initscript_code);
   // make sure the script ends with a single \n 
   $initscript_code=trim($initscript_code);
   if (!empty($initscript_code) && $initscript_code[strlen($initscript_code)-1] != "\n")
     $initscript_code.="\n";
   // plc_debug_txt('initscript_code after cr & nl/eof',$initscript_code);

   if (strcmp($initscript_code,$previous_initscript_code) != 0) {
     $newvalue=$api->SetSliceInitscriptCode($slice_id,$initscript_code);
     // plc_debug_txt('newvalue',$newvalue);
     $status=(strcmp($newvalue,$initscript_code)==0) ? "OK" : "failed";
     if (! $initscript_code)	drupal_set_message("Removed initscript code " . $status);
     else			drupal_set_message("Installed new initscript code " . $status);
     $changes=TRUE;
   }
   if (!$changes) drupal_set_message("No changes required in initscript");
   plc_redirect(l_slice($slice_id) . "&show_details=0&show_initscripts=1" );
   break;
 }


 case 'delete-slice-tags': {
   $slice_id = intval($_POST['slice_id']);
   $slice_tag_ids = array_map("intval", $_POST['slice_tag_ids']);
   $count = 0;
   $success = true;
   foreach($slice_tag_ids as $slice_tag_id) {
     if ($api->DeleteSliceTag($slice_tag_id)) $count += 1;
     else {
       drupal_set_error("Could not delete slice tag: slice_tag_id = $slice_tag_id");
       $success = false;
     }
   }
   if ($success)
     drupal_set_message ("Deleted $count slice tag(s)");
   plc_redirect(l_slice($slice_id) . "&show_tags=1" );
   break;
 }
  
 case 'add-slice-tag': {
   $slice_id = intval($_POST['slice_id']);
   $tag_type_id = intval($_POST['tag_type_id']);
   $value = $_POST['value'];
   $node_id = intval($_POST['node_id']);
   $nodegroup_id = intval($_POST['nodegroup_id']);
  
   $result = null;
   if ($node_id) {
     $result = $api->AddSliceTag($slice_id, $tag_type_id, $value, $node_id);
   } elseif ($nodegroup_id) {
     $result = $api->AddSliceTag($slice_id, $tag_type_id, $value, null, $nodegroup_id);
   } else {
     $result = $api->AddSliceTag($slice_id, $tag_type_id, $value);
   }
   if ($result)
     drupal_set_message ("Added slice tag.");
   else 
       drupal_set_error("Could not add slice tag");
   if ($_POST['sliver_action'])
       plc_redirect(l_sliver($node_id,$slice_id));
   else
       plc_redirect(l_slice($slice_id) . "&show_tags=true" );
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
   break;
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
   break;
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

 case 'remove-roles-from-tag-type' : {
   $tag_type_id=$_POST['tag_type_id'];
   $role_ids=$_POST['role_ids'];
   if ( ! $role_ids) {
     drupal_set_error("You have not selected role(s) to remove");
   } else {
     foreach( $role_ids as $role_id)  
       if ( $api->DeleteRoleFromTagType( intval( $role_id ), intval( $tag_type_id ) ) != 1 ) 
	 drupal_set_error ("Could not remove role $role_id from tag type $tag_type_id");
   }
   plc_redirect (l_tag_roles($tag_type_id));
 }
     
 case 'add-role-to-tag-type' : {
   $tag_type_id=$_POST['tag_type_id'];
   $role_id=$_POST['role_id'];
   if ( ! $role_id) {
     drupal_set_error ("You have not selected a role to add");
   } else if ($api->AddRoleToTagType( intval( $role_id ), intval( $tag_type_id ) ) != 1) {
     drupal_set_error("Could not add role $role_id to tag $tag_type_id");
   }
   plc_redirect (l_tag_roles($tag_type_id));
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
     plc_redirect (l_node_tags($node_id));
   else
     plc_redirect (l_interface_tags($interface_id));
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
     plc_redirect(l_node_tags($_POST['node_id']));
   else
     plc_redirect(l_interface_tags($_POST['interface_id']));
 }

//////////////////////////////////////// nodegroups
 case 'update-nodegroup': {
   $nodegroup_id = $_POST['nodegroup_id'];
   $groupname = $_POST['groupname'];
   $value = $_POST['value'];

   $fields=array();
   $fields['groupname']=$groupname;
   $fields['value']=$value;
   if ( $api->UpdateNodeGroup($nodegroup_id,$fields) == 1) 
     drupal_set_message ('Nodegroup updated');
   else 
     drupal_set_error ("Could not update nodegroup $groupname");
   
   plc_redirect(l_nodegroup($nodegroup_id));

 }

 case 'add-nodegroup': {
   $groupname=$_POST['groupname'];
   if ( ! $groupname ) {
     drupal_set_error ('Empty groupname');
     plc_redirect (l_nodegroups());
   }
   $tag_type_id=intval($_POST['tag_type_id']);
   if ( ! $tag_type_id ) {
     drupal_set_error ('You must select a tag in the dropdown list');
     plc_redirect (l_nodegroups());
   }
   $value=$_POST['value'];
   if ( ! $value ) {
     drupal_set_message ("Empty value.. let's see ..");
   }
   if ( $api->AddNodeGroup ($groupname,$tag_type_id,$value) > 0) 
     drupal_set_message ("Nodegroup $groupname created");
   else
     drupal_set_error ("Could not create nodegroup $groupname");

   plc_redirect (l_nodegroups());
   break;
 }

 case 'delete-nodegroups': {
   $nodegroup_ids=$_POST['nodegroup_ids'];
   if ( ! $nodegroup_ids ) {
     drupal_set_message("action=delete-nodegroups - No group selected");
     plc_redirect(l_nodegroups());
   }
   $success=true;
   $counter=0;
   foreach ($nodegroup_ids as $nodegroup_id) 
     if ($api->DeleteNodeGroup(intval($nodegroup_id)) != 1) 
       $success=false;
     else
       $counter++;
   if ($success) 
     drupal_set_message ("Deleted $counter group(s)");
   else
     drupal_set_error ("Could not delete all selected groups, only $counter were removed");
   plc_redirect (l_nodegroups());
   break;
 }

//////////////////////////////////////// leases
 case 'manage-leases': {
   $actions=json_decode($_POST['actions']);
   $add_requested=0;
   $add_done=0;
   $del_requested=0;
   $del_done=0;
   $errors=array();
   foreach ($actions as $action) {
     if ($action[0] == 'add-leases') {
       $nodenames=$action[1];
       $add_requested += count($nodenames);
       $slicename=$action[2];
       $t_from=intval($action[3]);
       $t_until=intval($action[4]);
       $hash = $api->AddLeases($nodenames,$slicename,$t_from,$t_until);
       // update number of added leases
       $ids=$hash['new_ids'];
       $add_done += count($ids);
       // update global errors array
       foreach ($api_errors=$hash['errors'] as $error) $errors[]=$error;
     } else if ($action[0]=='delete-leases') {
       $lease_id=intval($action[1]);
       $del_requested += 1;
       if ($api->DeleteLeases(array($lease_id)) == 1) {
	 $del_done += 1;
       } else {
	 $errors []= "Could not delete lease " . $lease_id;
       }
     } else {
       $errors []= "in actions.php, manage-leases, wrong action ". $action[0];
     }
   }
   
   if (count($errors)==0) {
     echo("All leases updated (" . $add_done . " added and " . $del_done . " deleted)");
   } else {
     foreach ($errors as $error) echo($error. "\n");
     echo("Leases updated only partially (" . 
	  $add_done . "/" . $add_requested . " added and " . 
	  $del_done . "/" . $del_requested . " deleted)");
   }     

   break;
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
