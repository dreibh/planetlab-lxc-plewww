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
require_once 'plc_peers.php';
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
$site_columns=array( "site_id", "name", "login_base" );
$sites= $api->GetSites( $site_ids, $site_columns);
$slices= $api->GetSlices( $slice_ids, array( "slice_id", "name" ) );
$keys= $api->GetKeys( $key_ids );

drupal_set_title("Details for account " . $first_name . " " . $last_name);
$local_peer = ! $peer_id;

$peers = new Peers ($api);

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
			      'url'=>l_actions(),
			      'values'=> array ('person_id'=>$person_id,
						'action'=>'disable-person'),
			      'bubble'=>"Disable $first_name $last_name",
			      'confirm'=>"Are you sure you want to disable $first_name $last_name");
  else 
    $tabs['Enable'] = array ('method'=>'POST',
			     'url'=>l_actions(),
			     'values'=> array ('person_id'=>$person_id,
					       'action'=>'enable-person'),
			     'bubble'=>"Enable $first_name $last_name",
			     'confirm'=>"Are you sure you want to enable $first_name $last_name");

// become
if (plc_is_admin() && ! $is_my_account) 
  $tabs['Become'] = array('method'=>'POST',
			  'url'=>l_actions(),
			  'values'=>array('action'=>'become-person',
					  'person_id'=>$person_id),
			  'bubble'=>"Become $first_name $last_name",
			  'confirm'=>"Are you sure you want to su $first_name $last_name");
    
// delete
if ($local_peer && $privileges) 
  $tabs['Delete'] = array ('method'=>'POST',
			   'url'=>l_actions(),
			   'values'=> array ('person_id'=>$person_id,
					     'action'=>'delete-person'),
			   'bubble'=>"Delete $first_name $last_name",
			   'confirm'=>"Are you sure to delete $first_name $last_name");
// events for that person
if ( $privileges) 
  $tabs['Events'] = array('url'=>l_events(),
			  'values'=>array('type'=>'Person','person'=>$person_id),
			  'bubble'=>"Events about $first_name $last_name",
			  'image'=>'/planetlab/icons/event.png','height'=>18);

// Back button
$tabs['All Users'] = array ('url'=>l_persons(),
			    'bubble'=>'Back to the Users page');

plc_tabs($tabs);
    
$peers->block_start ($peer_id);

if ($local_peer && $privileges && ! $enabled ) 
  drupal_set_message ("$first_name $last_name is not enabled yet, you can enable her/him with the 'Enable' button below");

$enabled_text="Enabled";
if ( ! $enabled ) $enabled_text = plc_warning_text("Disabled");

plc_details_start();
plc_details_line("Enabled",$enabled_text);
plc_details_line("First Name",$first_name);
plc_details_line("Last Name",$last_name);
plc_details_line("Email",href("mailto:$email",$email));
plc_details_line("URL",$url);
plc_details_line("Phone",$phone);
plc_details_line("Title",$title);
plc_details_line("Bio",wordwrap($bio,50,"<br/>"));
plc_details_line("Peer",$peers->peer_link($peer_id));
plc_details_end();

//////////////////// slices
plc_section('Slices');

if( ! $slices) {
  plc_warning ("User has no slice");
 } else {
  $headers=array('Slice name'=>'string');
  $reasonable_page=5;
  $table_options = array('notes_area'=>false,"search_width"=>10,'pagesize'=>$reasonable_page);
  if (count ($slices) <= $reasonable_page) {
    $table_options['search_area']=false;
    $table_options['pagesize_area']=false;
  }
  plc_table_start("person_slices",$headers,1,$table_options);

  foreach( $slices as $slice ) {
    $slice_name= $slice['name'];
    $slice_id= $slice['slice_id'];
    plc_table_row_start();
    plc_table_cell(l_slice_t($slice_id,$slice_name));
    plc_table_row_end();
  }
  plc_table_end("person_slices");
 }

// we don't set 'action', but use the submit button name instead
plc_form_start(l_actions(), array("person_id"=>$person_id,));

//////////////////// keys
plc_section ("Keys");
		
$can_manage_keys = ( $local_peer && ( plc_is_admin() || $is_my_account) );
if ( empty( $key_ids ) ) {
  plc_warning("This user has no known key");
 } 

// headers
$headers=array("Type"=>"string",
	       "Key"=>"string");
if ($can_manage_keys) $headers['Remove']="none";
// table overall options
$table_options=array('search_area'=>false,'pagesize_area'=>false,'notes_area'=>false);
plc_table_start("person_keys",$headers,"1",$table_options);
    
if ($keys) foreach ($keys as $key) {
  $key_id=$key['key_id'];
  plc_table_row_start();
  plc_table_cell ($key['key_type']);
  plc_table_cell(wordwrap( $key['key'], 60, "<br />\n", 1 ));
  if ($can_manage_keys) 
    plc_table_cell (plc_form_checkbox_text('key_ids[]',$key_id));
  plc_table_row_end();
}
// the footer area is used for displaying key-management buttons
$footers=array();
// add the 'remove keys' button and key upload areas as the table footer
if ($can_manage_keys) {
  $remove_keys_area=plc_form_submit_text ("delete-keys","Remove keys");
  $upload_key_left_area= plc_form_label_text("key","Upload new key") . plc_form_file_text("key",60);
  $upload_key_right_area=plc_form_submit_text("upload-key","Upload key");
  // no need to remove if there's no key
  if ($keys) 
    $footers[]="<td colspan=3 style='text-align:right'> $remove_keys_area </td>";
  // upload a new key
  $footers []="<td colspan=2 style='text-align:right'> $upload_key_left_area </td>".
    "<td> $upload_key_right_area </td>";
}

plc_table_end("person_keys",array("footers"=>$footers));

//////////////////// sites
plc_section('Sites');
  
// sites
if (empty( $sites ) ) {
  plc_warning('This user is not affiliated with a site !!');
 } 
$can_manage_sites = $local_peer && plc_is_admin() || $is_my_account;
$headers=array();
$headers['Login_base']="string";
$headers['Name']="string";
if ($can_manage_sites) 
  $headers['Remove']="string";
$table_options = array('notes_area'=>false,'search_area'=>false, 'pagesize_area'=>false);
plc_table_start ("person_sites",$headers,0,$table_options);
foreach( $sites as $site ) {
  $site_name= $site['name'];
  $site_id= $site['site_id'];
  $login_base=$site['login_base'];
  plc_table_row_start();
  plc_table_cell ($login_base);
  plc_table_cell (l_site_t($site_id,$site_name));
  if ($can_manage_sites)
    plc_table_cell (plc_form_checkbox_text('site_ids[]',$site_id));
  plc_table_row_end ();
}
// footers : the remove and add buttons
$footers=array();
if ($can_manage_sites) {
  // remove selected sites
  $remove_sites_area = plc_form_submit_text("remove-person-from-sites","Remove Sites");

  // add a site : the button
  $add_site_right_area=plc_form_submit_text("add-person-to-site","Add in site");
  // get list of local sites that the person is not in
  function get_site_id ($site) { return $site['site_id'];}
  $person_site_ids=array_map("get_site_id",$sites);
  $relevant_sites= $api->GetSites( array("peer_id"=>NULL,"~site_id"=>$person_site_ids), $site_columns);

  // xxx cannot use onchange=submit() - would need to somehow pass action name 
  $selector=array();
  foreach ($relevant_sites as $site) 
    $selector[]= array('display'=>$site['name'],"value"=>$site['site_id']);
  $add_site_left_area=plc_form_select_text("site_id",$selector,"Choose a site to add");
  $add_site_area = $add_site_left_area . $add_site_right_area;
  if ($sites) 
    $footers[]=plc_table_td_text ($remove_sites_area,3,"right");
  // add a new site
  $footers []= plc_table_td_text ($add_site_right_area,3,"right");
 }
plc_table_end("person_sites",array("footers"=>$footers));

//////////////////// roles
plc_section("Roles");
if (! $roles) plc_warning ("This user has no role !");

$can_manage_roles= ($local_peer && plc_is_admin());
$table_options=array("search_area"=>false,"notes_area"=>false);

$headers=array("Role"=>"none");
if ($can_manage_roles) $headers ["Remove"]="none";

$table_options=array('search_area'=>false,'pagesize_area'=>false,'notes_area'=>false);
plc_table_start("person_roles",$headers,0,$table_options);  
  
// construct array of role objs
$role_objs=array();
for ($n=0; $n<count($roles); $n++) {
  $role_objs[]= array('role_id'=>$role_ids[$n], 'name'=>$roles[$n]);
 }

if ($role_objs) foreach ($role_objs as $role_obj) {
  plc_table_row_start();
  plc_table_cell($role_obj['name']);
  if ($can_manage_roles) plc_table_cell (plc_form_checkbox_text('role_ids[]',$role_obj['role_id']));
  plc_table_row_end();
 }

// footers : the remove and add buttons
$footers=array();
if ($can_manage_roles) {
  // remove selected roles
  $remove_roles_area = plc_form_submit_text("remove-roles-from-person","Remove Roles");

  // add a role : the button
  $add_role_right_area=plc_form_submit_text("add-role-to-person","Add role");
  // get list of local roles that the person has not yet
  // xxx this does not work because GetRoles does not support filters
  $relevant_roles = $api->GetRoles( array("~role_id"=>$role_ids));

  $selector=array();
  foreach ($relevant_roles as $role) 
    $selector[]= array('display'=>$role['name'],"value"=>$role['role_id']);
  $add_role_left_area=plc_form_select_text("role_id",$selector,"Choose a role to add");
  $add_role_area = $add_role_left_area . $add_role_right_area;
  if ($roles) 
    $footers[]="<td colspan=3 style='text-align:right'> $remove_roles_area </td>";
  // add a new role
  $footers[]="<td colspan=3 style='text-align:right'> $add_role_area </td>";
 }
plc_table_end("person_roles",array("footers"=>$footers));

//////////////////////////////
plc_form_end();
$peers->block_end($peer_id);
  
// Print footer
include 'plc_footer.php';


?>
