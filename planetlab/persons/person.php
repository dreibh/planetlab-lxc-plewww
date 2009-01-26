<?php

// $Id: index.php 11645 2009-01-21 23:09:49Z thierry $

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
require_once 'plc_minitabs.php';
require_once 'plc_tables.php';


// Common functions
require_once 'plc_functions.php';
require_once 'plc_minitabs.php';
require_once 'plc_tables.php';
require_once 'plc_details.php';

// -------------------- 
// recognized URL arguments
$person_id=intval($_GET['id']);
if ( ! $person_id ) { plc_error('Malformed URL - id not set'); return; }

////////////////////
// Get all columns as we focus on only one entry
$persons= $api->GetPersons( array($person_id));

if (empty($persons)) {
  drupal_set_message ("Person " . $person_id . " not found");
 } else {
  $person=$persons[0];
  
  // vars from api
  $first_name= $person['first_name'];
  $last_name= $person['last_name'];
  $title= $person['title'];
  $url= $person['url'];
  $phone= $person['phone'];
  $email= $person['email'];
  $enabled= $person['enabled'];
  $peer_id=$person['peer_id'];
  
  // arrays from api
  $role_ids= $person['role_ids'];
  $roles= $person['roles'];
  $site_ids= $person['site_ids'];
  $slice_ids= $person['slice_ids'];
  $key_ids= $person['key_ids'];
  
  // gets more data from API calls
  $sites= $api->GetSites( $site_ids, array( "site_id", "name", "login_base" ) );
  $slices= $api->GetSlices( $slice_ids, array( "slice_id", "name" ) );
  $keys= $api->GetKeys( $key_ids );
  
  drupal_set_title("Details for account " . $first_name . " " . $last_name);

  $plc_hash=plc_peer_get_hash($api);

  $local_peer = plc_peer_block_start ($peer_hash,$peer_id);
  $is_my_account = plc_my_person_id() == $person_id;
  $privileges = plc_is_admin () || ( plc_in_site($site_id) && plc_is_pi());

  $tabs=array();

  if ($local_peer && $privileges) {
    if (plc_is_admin())
	$tabs['Events'] = array('url'=>l_event("Person","person",$person_id),
				'bubble'=>'Related events',
				'image'=>'/planetlab/icons/event.png',
				'height'=>18);
    if ($enabled) 
      $tabs['Disable'] = array ('method'=>'POST',
				'url'=>'/db/persons/person_actions.php',
				'values'=> array ('person_id'=>$person_id,
						  'action'=>'disable'),
				'bubble'=>"Disable $first_name");
    else 
      $tabs['Enable'] = array ('method'=>'POST',
				'url'=>'/db/persons/person_actions.php',
				'values'=> array ('person_id'=>$person_id,
						  'action'=>'enable'),
				'bubble'=>"Enable $first_name");
      $tabs['Delete'] = array ('method'=>'POST',
				'url'=>'/db/persons/person_actions.php',
				'values'=> array ('person_id'=>$person_id,
						  'action'=>'delete'),
			       'bubble'=>"Delete $first_name",
			       'confirm'=>"Are you sure to delete $first_name $last_name");
  }

  if ($privileges || $is_my_account) 
    $tabs['Update'] = array('url'=>'/db/persons/update.php',
			    'values'=>array('id'=>$person_id),
			    'bubble'=>"Update $first_name");

  $tabs['Persons'] = array ('url'=>l_persons());

  plc_tabs($tabs);
    
  plc_details_start();
  plc_details_line("First Name",$first_name);
  plc_details_line("Last Name",$last_name);
  plc_details_line("Title",$title);
  plc_details_line("Email",href("mailto:$email",$email));
  plc_details_line("Phone",$phone);
  plc_details_line("URL",$url);
  plc_details_end();

	  
  echo "<hr />\n";
		
  // keys
  $can_manage_keys = ( $local_peer && ( plc_is_admin() || $is_my_account) );
  plc_table_title('Keys');
  if ( empty( $key_ids ) ) {
    echo "<span class='plc-warning'> This user has no known key</span>";
  } else {
    echo "<p><table border=0 width=450>\n";
    echo "<thead><tr><th>Type</th><th>Key</th>";
    if ( $can_manage_keys )
      echo "<th>Remove</th>";
    echo "</tr></thead><tbody>\n";
    
    foreach( $keys as $key ) {
      $key_type= $key['key_type'];
      $key_id= $key['key_id'];
      $key_text= wordwrap( $key['key'], 70, "<br />\n", 1 );
      echo "<tr><td>$key_type</td><td>$key_text";
      
      if ( $can_manage_keys ) 
	echo "</td><td><input type=checkbox name='rem_key[]' value='$key_id'>";
      
      echo "</td></tr>\n";
    }
    
    echo "</tbody></table>\n";
    if ($can_manage_keys)
      echo "<p><input type=submit name='Remove_keys' value='Remove keys'><br />\n";
  }
		
  if( $can_manage_keys ){
    echo "<br /> Upload new key: <input type='file' name='key' size=30>\n
        <input type='submit' name='Upload' value='Upload'>\n
        <br /><hr />\n";
  }

  // sites
  plc_table_title('Sites');
  
  // sites
  if (empty( $sites ) ) {
    plc_warning('This user is not affiliated with a site !!');
    } else {
    $columns=array();
    $columns['Name']="string";
    $columns['Login_base']="string";
    $columns['Remove']="string";
    $table_options = array('notes_area'=>false,'search_area'=>false);
    plc_table_start ("person_slices",$columns,1,$table_options);
    foreach( $sites as $site ) {
      $site_name= $site['name'];
      $site_id= $site['site_id'];
      $login_base=$site['login_base'];
      plc_table_row_start();
      plc_table_cell (l_site_t($site_id,$site_name));
      plc_table_cell ($login_base);
      plc_table_cell ("<input id=" . $login_base . " type=checkbox name='rem_site[]' value=" . $site_id . ">");
      plc_table_row_end ();
    }
    plc_table_end($table_options);
  }

  echo "<input type=submit name='Remove_Sites' value='Remove Sites'>\n";
  
	
  // diplay site select list to add another site for user
  if ($local_peer && plc_is_admin()) {
    // get site info
    $all_sites= $api->GetSites( NULL, array( "site_id", "name" ) );
    
    if( $sites )
      $person_site= arr_diff( $all_sites, $sites );
    else
      $person_site= $all_sites;
    
    //    sort_sites( $person_site );
    
    echo "<p>Select a site to add this user to: ";
    echo "<select name='site_add' onChange='submit()'>\n<option value=''>Choose a site to add:</option>\n";
    
    foreach( $person_site as $site ) {
      echo "<option value=". $site['site_id'] .">". $site['name'] ."</option>\n";
      
    }
    
    echo "</select>";
    
  }
  echo "<hr />\n";
  
  // roles
  echo "<h3>Roles</h3>\n";
  echo "<p><table>\n";
  echo "<thead><tr><th>Role</th>";
  if( plc_is_admin())
    echo "<th>Remove</th>";
  echo "</tr></thead><tbody>\n";
  
  // construct role array
  for( $n=0; $n<count($roles); $n++ ) {
    $proles[]= array( 'role_id'=>$role_ids[$n], 'name'=>$roles[$n] );
  }
    
  $button_shown=0;
  if ( !empty ($roles) ) {
    foreach( $proles as $role ) {
      $role_name= $role['name'];
      $role_id= $role['role_id'];
      
      echo "<tr><td>$role_name";
	      

      if( plc_is_admin()) {
	echo "</td><td><input type=checkbox name='rem_role[]' value='$role_id'>";
	if ( ! $button_shown ) {
	  $rowspan=count($roles);
	  echo "</td><td rowspan=$rowspan valign=center><input type=submit name='Remove_Roles' value='Remove Roles'></td></tr>\n";
	  $button_shown=1;
	}
      }
      
      echo "</td></tr>\n";
    }
  } else {
    echo "<span class='plc-warning'> This user has no known role !!</span>";
  }
  echo "</tbody></table>\n";
	  
  // if admin show roles to add
  if( plc_is_admin()) {
    $all_roles= $api->GetRoles();
    $addable_roles= arr_diff( $all_roles, $proles );
    ##when the proles array is empty strangely the method arr_diff($all_roles, $proles )
    ##return an empty array and the scrolling roles list is not displayed in this case
    ##assign to addablerole all the roles
    if (count($proles)==0)
      $addable_roles=$all_roles;
    
    if( !empty( $addable_roles ) ) {
      echo "<p>Add role: <select name='add_role' onChange='submit()'>\n<option value=''>Choose a Role to add:</option>\n";
      
      foreach( $addable_roles as $arole ) {
	echo "<option value=". $arole['role_id'] .">". $arole['name'] ."</option>\n";
	
      }
      
      echo "</select>\n";
      
    }
  }
  
  echo "<hr />\n";
  
  // slices
  plc_table_title('Slices');

  if( empty( $slices ) ) {
    drupal_set_message ("User has no slice");
  } else {
    $columns=array('Slice name'=>'string');
    $table_options=array();
    plc_table_start("person_slices",$columns,1,$table_options);

    foreach( $slices as $slice ) {
      $slice_name= $slice['name'];
      $slice_id= $slice['slice_id'];
      plc_table_row_start();
      plc_table_cell(l_slice_t($slice_id,$slice_name));
      plc_table_row_end();
    }
    plc_table_end($table_options);
  }

  plc_peer_block_end();
  
 }

// Print footer
include 'plc_footer.php';


?>
