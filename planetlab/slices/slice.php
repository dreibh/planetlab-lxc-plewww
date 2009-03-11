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
require_once 'linetabs.php';
require_once 'table.php';
require_once 'details.php';
require_once 'toggle.php';
require_once 'form.php';

// -------------------- admins potentially need to get full list of users
ini_set('memory_limit','32M');

// -------------------- 
// recognized URL arguments
$slice_id=intval($_GET['id']);
if ( ! $slice_id ) { plc_error('Malformed URL - id not set'); return; }

////////////////////
// Get all columns as we focus on only one entry
$slices= $api->GetSlices( array($slice_id));

if (empty($slices)) {
  drupal_set_message ("Slice " . $slice_id . " not found");
  return;
 }

$slice=$slices[0];

// pull all node info to vars
$name= $slice['name'];
$expires = date( "d/m/Y", $slice['expires'] );
$site_id= $slice['site_id'];

//$node_ids=$slice['node_ids'];
$person_ids=$slice['person_ids'];
//$slice_tag_ids= $slice['slice_tag_ids'];

// get peers
$peer_id= $slice['peer_id'];
$peers=new Peers ($api);

// gets site info
$sites= $api->GetSites( array( $site_id ) );
$site=$sites[0];
$site_name= $site['name'];
$max_slices = $site['max_slices'];
// xxx PIs
//$pis=$api->GetPersons(...)

// get all persons info
if (!empty($person_ids))
  $persons=$api->GetPersons($person_ids,array('email','enabled'));

////////// 
drupal_set_title("Details for slice " . $name);
$local_peer= ! $peer_id;

$am_in_slice = in_array(plc_my_person_id(),$person_ids);

$privileges = (plc_is_admin()  || $am_in_slice);

$tabs=array();
$tabs [] = tab_nodes_slice($slice_id);
$tabs [] = tab_site($site_id);

// are these the right privileges for deletion ?
if ($privileges) {
  $tabs ['Delete']= array('url'=>l_actions(),
			  'method'=>'post',
			  'values'=>array('action'=>'delete-slice','slice_id'=>$slice_id),
			  'bubble'=>"Delete slice $name",
			  'confirm'=>'Are you sure to delete $name');

  $tabs["Events"]=array_merge(tablook_event(),
			      array('url'=>l_event("Slice","slice",$slice_id),
				    'bubble'=>"Events for slice $name"));
  $tabs["Comon"]=array_merge(tablook_comon(),
			     array('url'=>l_comon("slice_id",$slice_id),
				   'bubble'=>"Comon page about slice $name"));
}

plekit_linetabs($tabs);

////////////////////////////////////////
$peers->block_start($peer_id);

$toggle = new PlekitToggle ('slice',"Details",
			    array('trigger-bubble'=>'Display and modify details for that slice'));
$toggle->start();

$details=new PlekitDetails($privileges);
$details->form_start(l_actions(),array('action'=>'update-slice','slice_id'=>$slice_id));

$details->start();
if (! $local_peer) {
  $details->th_td("Peer",$peers->peer_link($peer_id));
  $details->space();
 }


$details->th_td('Name',$slice['name']);
$details->th_td('Description',$slice['description'],'description',
		array('input_type'=>'textarea',
		      'width'=>50,'height'=>5));
$details->th_td('URL',$slice['url'],'url',array('width'=>50));
$details->th_td('Expires',$expires);
$details->th_td('Instantiation',$slice['instantiation']);
$details->th_td('Site',l_site_obj($site));
// xxx
//$details->th_td('PIs',...);
$details->end();

$details->form_end();
$toggle->end();

//////////////////////////////////////// renewal area

function renew_area ($slice,$site) {
 
    // Constants
  $DAY = 24*60*60;
  $WEEK = 7 * $DAY; 
  $MAX_WEEKS= 8;			// weeks from today
  $now=mktime();
  $current_exp=$slice['expires'];
  $max_exp= $now + ($MAX_WEEKS * $WEEK); // seconds since epoch

  // make this area visible only is the expiration time is less than 10 days from now
  $time_left = $current_exp - $now;
  $visible = $time_left/$DAY <= 10;
  
  // xxx some extra code needed to enable this area only if the slice description is OK:
  // description and url must be non void
  $toggle=new PlekitToggle('renew',"Renew this slice",
 			   array("trigger-bubble"=>"Enter this zone if you wish to renew your slice",
 				 'start-visible'=>$visible));
  $toggle->start();

  // xxx message could take roles into account
  if ($site['max_slices']<=0) {
     $message= <<< EOF
<p>Slice creation and renewal have been temporarily disabled for your
<site. This may have occurred because your site's nodes have been down
or unreachable for several weeks, and multiple attempts to contact
your site's PI(s) and Technical Contact(s) have all failed. If so,
contact your site's PI(s) and Technical Contact(s) and ask them to
bring up your site's nodes. Please visit your <a
href='/db/sites/index.php?id=$site_id'>site details</a> page to find
out more about your site's nodes, and how to contact your site's PI(s)
and Technical Contact(s).</p>
EOF;
     echo $message;
 
  } else {
    // xxx this is a rough cut and paste from the former UI
    // showing a datepicker view could be considered as well with some extra work
    // calculate possible extension lengths
    $selectors = array();
    foreach ( array ( 1 => "One more week", 
 		      2 => "Two more weeks", 
 		      3 => "Two more weeks", 
 		      4 => "One more month" ) as $weeks => $text ) {
      $candidate_exp = $current_exp + $weeks*$WEEK;
      if ( $candidate_exp < $max_exp) {
	$selectors []= array('display'=>"$text (" . gmstrftime("%A %b-%d-%y %T %Z", $candidate_exp) . ")",
			     'value'=>$candidate_exp);
	$max_renewal_weeks=$weeks;
	$max_renewal_date= gmstrftime("%A %b-%d-%y %T %Z", $candidate_exp);
      }
    }

    if ( empty( $selectors ) ) {
      plc_warning("Slice cannot be renewed any further into the future, try again closer to expiration date.");
     } else {
      print <<< EOF
<p>You must provide a short description as well as a link to a project website before renewing it.
Do <span class='bold'>not</span> provide bogus information; if a complaint is lodged against your slice 
and PlanetLab Operations is unable to determine what the normal behavior of your slice is, 
your slice may be deleted to resolve the complaint.</p>
<p><span class='bold'>NOTE:</span> 
Slices cannot be renewed beyond another $max_renewal_weeks week(s) ($max_renewal_date)
.</p>
EOF;

      $form = new PlekitForm (l_actions(),
			      array('action'=>'renew-slice',
				    'slice_id'=>$slice['slice_id']));
      $form->start();
      print $form->label_html('expires','Duration');
      print $form->select_html('expires',$selectors,array('label'=>'Pick one'));
      print $form->submit_html('renew-button','Renew');
      $form->end();
    }
  }
 
  $toggle->end();
}

renew_area ($slice,$site);

//////////////////// users
$persons=$api->GetPersons(array('person_id'=>$slice['person_ids']));
// just propose to add evryone else, regular users can see only a fraction of the db anyway
$potential_persons=$api->GetPersons(array('~person_id'=>$slice['person_ids'],'peer_id'=>NULL),
				    array('email','person_id','first_name','last_name','roles'));
$toggle=new PlekitToggle ('persons',"Users",array('trigger-bubble'=>'Manage users attached to this slice','start-visible'=>false));
$toggle->start();

////////// people currently in
$headers=array();
$headers['email']='string';
$headers['first']='string';
$headers['last']='string';
$headers['R']='string';
if ($privileges) $headers[plc_delete_icon()]="none";
$table=new PlekitTable('persons',$headers,'1',array('caption'=>'Current users',
						    'search_area'=>false,
						    'notes_area'=>false,
						    'pagesize_area'=>false));
$form=new PlekitForm(l_actions(),array('slice_id'=>$slice['slice_id']));
$form->start();
$table->start();
if ($persons) foreach ($persons as $person) {
  $table->row_start();
  $table->cell(l_person_obj($person));
  $table->cell($person['first_name']);
  $table->cell($person['last_name']);
  $table->cell(plc_vertical_table ($person['roles']));
  if ($privileges) $table->cell ($form->checkbox_html('person_ids[]',$person['person_id']));
  $table->row_end();
}
// actions area
if ($privileges) {

  // remove users
  $table->tfoot_start();

  $table->row_start();
  $table->cell($form->submit_html ("remove-persons-from-slice","Remove selected"),
	       $table->columns(),"right");
  $table->row_end();
 }
$table->end();

////////// people to add
if ($privileges) {
  $headers=array();
  $headers['email']='string';
  $headers['first']='string';
  $headers['last']='string';
  $headers['R']='string';
  $headers['Add']="none";
  $table=new PlekitTable('add_persons',$headers,'1',array('caption'=>'Users to add',
							  'search_area'=>false,
							  'notes_area'=>false));
  //							  'pagesize'=>5));
  $form=new PlekitForm(l_actions(),array('slice_id'=>$slice['slice_id']));
  $form->start();
  $table->start();
  if ($potential_persons) foreach ($potential_persons as $person) {
      $table->row_start();
      $table->cell(l_person_obj($person));
      $table->cell($person['first_name']);
      $table->cell($person['last_name']);
      $table->cell(plc_vertical_table ($person['roles']));
      $table->cell ($form->checkbox_html('person_ids[]',$person['person_id']));
      $table->row_end();
    }
  // add users
  $table->tfoot_start();
  
  $table->row_start();
  $table->cell($form->submit_html ("add-persons-in-slice","Add selected"),
	       $table->columns(),"right");
  $table->row_end();
 }
$table->end();

$toggle->end();

//////////////////// nodes

//////////////////// tags

$peers->block_end($peer_id);

// Print footer
include 'plc_footer.php';

return;

?>

