<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

//print header
require_once 'plc_drupal.php';
//set default
drupal_set_title('Persons');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_sorts.php';

// find person roles
$_person= $plc->person;
$_roles= $_person['role_ids'];


// get person id
if( $_POST['person_id'] )
  $person_id= $_POST['person_id'];


// if site_id submitted add the person to that site_id
if( $_POST['site_add'] ) {
  $site_id= $_POST['site_add'];

  $api->AddPersonToSite( intval( $person_id ), intval( $site_id ) );
  header( "location: index.php?id=$person_id" );
  exit();

}

if ( $_POST['Remove_Sites']){
  if( $_POST['rem_site'] ) {
    foreach( $_POST['rem_site'] as $site_id ) {
      $api->DeletePersonFromSite( intval( $person_id ), intval( $site_id ) );
    }
    header( "location: index.php?id=$person_id" );
    exit();
  }else{
    echo "<h3><span class='plc-warning'>Please select one or more Sites to  remove.<br /></h3> </span>\n";
    echo "<br /><hr /><p><a href='/db/persons/index.php?id=$person_id'>Back to person page</a></div>";
  }
}

// remove role
if ( $_POST['Remove_Roles']){
  if($_POST['rem_role']) {
    $rem_ids= $_POST['rem_role'];
    foreach( $rem_ids as $role_id ) {
      $api->DeleteRoleFromPerson( intval( $role_id ), intval( $person_id ) );
    }
    header( "location: index.php?id=$person_id" );
    exit();
  }else{
    echo "<h3><span class='plc-warning'>Please select one or more Roles to  remove.<br /></h3> </span>\n";
    echo "<br /><hr /><p><a href='/db/persons/index.php?id=$person_id'>Back to person page</a></div>";
  }
}

// add roles
if( $_POST['add_role'] ) {
  $role_id= $_POST['add_role'];

  $api->AddRoleToPerson( intval( $role_id ), intval( $person_id ) );

  header( "location: index.php?id=$person_id" );
  exit();
}

// enable person
if( $_GET['enab_id'] ) {
  $per_id= $_GET['enab_id'];

  $fields= array( "enabled"=>true );

  $api->UpdatePerson( intval( $per_id ), $fields );

  header( "location: index.php?id=$per_id" );
  exit();

}

// disable person
if( $_GET['dis_id'] ) {
  $per_id= $_GET['dis_id'];

  $fields= array( "enabled"=>false );

  $api->UpdatePerson( intval( $per_id ), $fields );

  header( "location: index.php?id=$person_id" );
  exit();
  
}

// if action exists figure out what to do
if( $_POST['action'] ) {

  // depending on action, run function
  switch( $_POST['action'] ) {
    case "delete":
      header( "location: person_actions.php?del_id=$person_id" );
      exit();
      break;
    case "disable":
      header( "location: person_actions.php?dis_id=$person_id" );
      exit();
      break;
    case "enable":
      header( "location: person_actions.php?enab_id=$person_id" );
      exit();
      break;
    case "su":
      plc_debug('plc',$plc);
      $plc->BecomePerson (intval($person_id));
      header ( "location: index.php" );
      break;
  }

}

// delete person
if( $_GET['per_id'] ) {
  $person_id= $_GET['per_id'];

  $api->DeletePerson( intval( $person_id ) );

  header( "location: index.php" );
  exit();
 }

//delete a key
if ( $_POST['Remove_keys'] ){
  if( $_POST['rem_key'] ) {
    $key_ids= $_POST['rem_key'];
    
    foreach( $key_ids AS $key_id ) {
      $api->DeleteKey( intval( $key_id ) );
    }
    header( "location: index.php?id=$person_id" );
    exit();
  }else{
    echo "<h3><span class='plc-warning'>Please select one or more keys to remove.<br /></h3> </span>\n";
    echo "<br /><hr /><p><a href='/db/persons/index.php?id=$person_id'>Back to person page</a></div>";
  }
 }

// upload a key if the user submitted one
if ( $_POST['Upload']){
  if( isset( $_FILES['key'] ) ) {
    $key_file= $_FILES['key']['tmp_name'];
    if( $key_file ){
      $fp = fopen( $key_file, "r" );
      $key = "";
      if( $fp ) {
	// opened the key file, read the one line of contents
	// The POST operation always creates a file even if the filename
	// the user specified was garbage.  If there was some problem
	// with the source file, we'll get a zero length read here.
	$key = fread($fp, filesize($key_file));
	fclose($fp);
	
	$key_id= $api->AddPersonKey( intval( $person_id ), array( "key_type"=> 'ssh', "key"=> $key ) );
	
	if (!$key_id){
	  $error=  $api->error();
	  echo "<h3><span class='plc-warning'> Please verify your SSH  file content.<br /></h3> </span>\n";
	  print '<br /><div class="messages error">' . $error . '</div>';
	  echo "<br /><hr /><p><a href='/db/persons/index.php?id=$person_id'>Back to person page</a></div>";
	}
	else{
	  header( "location: index.php?id=$person_id" );
	  exit();
	}
      }else {
	$error= "Unable to open key file.";
	print '<div class="messages error">' . $error . '</div>';
      }
    }else{
      echo "<h3><span class='plc-warning'>Please select a valid SSH key file to upload.<br /></h3> </span>\n";
      echo "<br /><hr /><p><a href='/db/persons/index.php?id=$person_id'>Back to person page</a></div>";
    }
  }
 }

// delete person confimation
if( $_GET['del_id'] ) {
  $person_id= $_GET['del_id'];

  // get person info from API
  $person_info= $api->GetPersons( array( intval( $person_id ) ), array( "first_name", "last_name", "email", "roles" ) );

  // start form
  echo "<form action='person_actions.php?per_id=$person_id' method=post>\n";

  // show delete confirmation
  echo "<h2>Delete ". $person_info[0]['first_name'] ." ". $person_info[0]['last_name'] ."</h2>\n";
  echo "<p>Are you sure you want to delete this user?\n";

  echo "<table><tbody>\n";
  echo "<tr><th>Email: </th><td> ". $person_info[0]['email'] ."</td></tr>\n";
  echo "<tr><th>Roles: </th><td> ";

  foreach( $person_info[0]['roles'] as $role ) {
    echo "$role<br />\n";
  }

  echo "</td></tr>\n";

  echo "</tbody></table>\n";
  echo "<p><input type=submit value='Delete User' name='delete'>\n";
  echo "</form>\n";


}

// Print footer
include 'plc_footer.php';


?>
