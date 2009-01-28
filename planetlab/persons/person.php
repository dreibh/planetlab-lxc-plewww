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
require_once 'plc_details.php';
require_once 'plc_forms.php';

// -------------------- 
// recognized URL arguments
$person_id=intval($_GET['id']);
if ( ! $person_id ) { 
  plc_error('Malformed URL - id not set'); 
  return;
 }

////////////////////
// Get all columns as we focus on only one entry
$persons= $api->GetPersons( array($person_id));

if (empty($persons)) {
  drupal_set_message ("Person " . $person_id . " not found");
  return;
 }
$person=$persons[0];
  
// vars from api
$enabled= $person['enabled'];
$first_name= $person['first_name'];
$last_name= $person['last_name'];
$email= $person['email'];
$url= $person['url'];
$phone= $person['phone'];
$title= $person['title'];
$bio= $person['bio'];
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

// update
if ($privileges || $is_my_account) 
  $tabs['Update'] = array('url'=>'/db/persons/update.php',
			  'values'=>array('id'=>$person_id),
			  'bubble'=>"Update $first_name $last_name");
  
// enable / disable
if ($local_peer && $privileges) 
  if ($enabled) 
    $tabs['Disable'] = array ('method'=>'POST',
			      'url'=>l_person_actions(),
			      'values'=> array ('person_id'=>$person_id,
						'action'=>'disable-person'),
			      'bubble'=>"Disable $first_name $last_name",
			      'confirm'=>"Are you sure you want to disable $first_name $last_name");
  else 
    $tabs['Enable'] = array ('method'=>'POST',
			     'url'=>l_person_actions(),
			     'values'=> array ('person_id'=>$person_id,
					       'action'=>'enable-person'),
			     'bubble'=>"Enable $first_name $last_name",
			     'confirm'=>"Are you sure you want to enable $first_name $last_name");

// become
if (plc_is_admin() && ! $is_my_account) 
  $tabs['Become'] = array('url'=>l_person_actions(),
			  'values'=>array('action'=>'become-person',
					  'person_id'=>$person_id),
			  'bubble'=>"Become $first_name $last_name",
			  'confirm'=>"Are you sure you want to su $first_name $last_name");
    
// delete
if ($local_peer && $privileges) 
  $tabs['Delete'] = array ('method'=>'POST',
			   'url'=>l_person_actions(),
			   'values'=> array ('person_id'=>$person_id,
					     'action'=>'delete-person'),
			   'bubble'=>"Delete $first_name $last_name",
			   'confirm'=>"Are you sure to delete $first_name $last_name");
// events for that person
if ( $privileges) 
  $tabs['Events'] = array('url'=>l_events(),
			  'values'=>array('type'=>'Person','person'=>$person_id),
			  'bubble'=>"Events about $first_name $last_name",
			  'image'=>'/planetlab/icons/event.png',
			  'height'=>18);

// Back button
$tabs['All Users'] = array ('url'=>l_persons(),
			    'bubble'=>'Back to the Users page');

plc_tabs($tabs);
    
if ($local_peer && $privileges && ! $enabled ) 
  drupal_set_message ("$first_name $last_name is not enabled yet, you can enable her/him with the 'Enable' button below");

$enabled_text="Enabled";
if ( ! $enabled ) $enabled_text = plc_warning_div("Disabled");

plc_details_start();
plc_details_line("Enabled",$enabled_text);
plc_details_line("First Name",$first_name);
plc_details_line("Last Name",$last_name);
plc_details_line("Email",href("mailto:$email",$email));
plc_details_line("URL",$url);
plc_details_line("Phone",$phone);
plc_details_line("Title",$title);
plc_details_line("Bio",wordwrap($bio,50,"<br/>"));
plc_details_end();

//////////////////// slices
echo "<hr />\n";
plc_table_title('Slices');

if( empty( $slices ) ) {
  drupal_set_message ("User has no slice");
 } else {
  $columns=array('Slice name'=>'string');
  plc_table_start("person_slices",$columns,1,$table_options);

  foreach( $slices as $slice ) {
    $slice_name= $slice['name'];
    $slice_id= $slice['slice_id'];
    plc_table_row_start();
    plc_table_cell(l_slice_t($slice_id,$slice_name));
    plc_table_row_end();
  }
  plc_table_end("person_slices");
 }

////////// keys	  
echo "<hr />\n";
plc_table_title ("Keys");
		
$can_manage_keys = ( $local_peer && ( plc_is_admin() || $is_my_account) );
if ( empty( $key_ids ) ) {
  plc_warning("This user has no known key");
 } else {
  // we don't set 'action', but use the submit button name instead
  plc_form_start(l_person_actions(),
		 array("person_id"=>$person_id));

  // the headers
  $columns=array("Type"=>"string",
		 "Key"=>"string");
  if ($can_manage_keys) $columns['Remove']="none";
  // table overall options
  $table_options=array("search_area"=>false,"notes_area"=>false);
  // add the 'remove site' button and key upload areas as the table footer
  if ($can_manage_keys) {
    $remove_keys_area=plc_form_submit_text ("delete-keys","Remove keys");
    $upload_key_left_area= plc_form_label_text("Upload new key","key") . plc_form_file_text("key",60);
    $upload_key_right_area=plc_form_submit_text("upload-key","Upload key");
    $table_options['footer']="";
    $table_options['footer'].="<tr><td colspan=3 style='text-align:right'> $remove_keys_area </td></tr>";
    $table_options['footer'].="<tr><td colspan=2 style='text-align:right'> $upload_key_left_area </td>".
      "<td> $upload_key_right_area </td></tr>";
  }
  plc_table_start("person_keys",$columns,"1",$table_options);
    
  foreach( $keys as $key ) {
    $key_id=$key['key_id'];
    plc_table_row_start($key_id);
    plc_table_cell ($key['key_type']);
    plc_table_cell(wordwrap( $key['key'], 60, "<br />\n", 1 ));
    if ($can_manage_keys) 
      plc_table_cell (plc_form_checkbox_text('key_ids[]',$key_id));
    plc_table_row_end();
  }
  plc_table_end("person_keys");
  plc_form_end();
 }

// sites
echo "<hr />\n";
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
  plc_table_start ("person_sites",$columns,1,$table_options);
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
  plc_table_end("person_sites");
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
  
plc_peer_block_end();
  
// Print footer
include 'plc_footer.php';


?>
