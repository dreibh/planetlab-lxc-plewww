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

// keep css separate for now
drupal_set_html_head('
<link href="/planetlab/css/my_slice.css" rel="stylesheet" type="text/css" />
');

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
$local_peer = ! $peer_id;

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


//////////////////////////////////////// building blocks for the renew area
// Constants
global $DAY;		$DAY = 24*60*60;
global $WEEK;		$WEEK = 7 * $DAY; 
global $MAX_WEEKS;	$MAX_WEEKS= 8;		// weeks from today
global $GRACE_DAYS;	$GRACE_DAYS=10;		// days for renewal promoted on top
global $NOW;		$NOW=mktime();


// make the renew area on top and open if the expiration time is less than 10 days from now
function renew_needed ($slice) {
  global $DAY, $NOW, $GRACE_DAYS;
  $current_exp=$slice['expires'];

  $time_left = $current_exp - $NOW;
  $visible = $time_left/$DAY <= $GRACE_DAYS;
  return $visible;
}

function renew_area ($slice,$site,$visible) {
  global $DAY, $WEEK, $MAX_WEEKS, $GRACE_DAYS, $NOW;
 
  $current_exp=$slice['expires'];
  $max_exp= $NOW + ($MAX_WEEKS * $WEEK); // seconds since epoch

  // xxx some extra code needed to enable this area only if the slice description is OK:
  // description and url must be non void
  $toggle=
    new PlekitToggle('renew',"Renew this slice",
		     array("bubble"=>
			   "Enter this zone if you wish to renew your slice",
			   'visible'=>$visible));
  $toggle->start();

  // xxx message could take roles into account
  if ($site['max_slices']<=0) {
     $message= <<< EOF
<p class='my-slice-renewal'>Slice creation and renewal have been temporarily disabled for your
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
 		      3 => "Three more weeks", 
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
      print <<< EOF
<div class='plc-warning renewal'>
Slice cannot be renewed any further into the future, try again closer to expiration date.
</div>
EOF;
     } else {
      print <<< EOF
<div class='my-slice-renewal'>
<p>You must provide a short description as well as a link to a project website before renewing it.
Do <span class='bold'>not</span> provide bogus information; if a complaint is lodged against your slice 
and PlanetLab Operations is unable to determine what the normal behavior of your slice is, 
your slice may be deleted to resolve the complaint.</p>
<p><span class='bold'>NOTE:</span> 
Slices cannot be renewed beyond another $max_renewal_weeks week(s) ($max_renewal_date).
</p>
</div>
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

////////// 

$am_in_slice = in_array(plc_my_person_id(),$person_ids);

if ($am_in_slice) {
  drupal_set_title("My slice " . $name);
 } else {
  drupal_set_title("Slice " . $name);
}

$privileges = ( $local_peer && (plc_is_admin()  || plc_is_pi() || $am_in_slice));
$tags_privileges = $privileges || plc_is_admin();

$tabs=array();
$tabs [] = tab_nodes_slice($slice_id);
$tabs [] = tab_site($site_id);

// are these the right privileges for deletion ?
if ($privileges) {
  $tabs ['Delete']= array('url'=>l_actions(),
			  'method'=>'post',
			  'values'=>array('action'=>'delete-slice','slice_id'=>$slice_id),
			  'bubble'=>"Delete slice $name",
			  'confirm'=>"Are you sure to delete slice $name");

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

//////////////////////////////////////// renewal area 
// (1) close to expiration : show on top and open

if ($local_peer ) {
  $renew_visible = renew_needed ($slice);
  if ($renew_visible) renew_area ($slice,$site,true);
 }


//////////////////// details
// default for opening the details section or not ?
if ($local_peer) {
  $default_show_details = true;
 } else {
  $default_show_details = ! $renew_visible;
 }
  
$toggle = 
  new PlekitToggle ('my-slice-details',"Details",
		    array('bubble'=>
			  'Display and modify details for that slice',
			  'visible'=>get_arg('show_details',$default_show_details)));
$toggle->start();

$details=new PlekitDetails($privileges);
$details->form_start(l_actions(),array('action'=>'update-slice',
				       'slice_id'=>$slice_id,
				       'name'=>$name));

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
$details->tr_submit("submit","Update Slice");
$details->th_td('Expires',$expires);
$details->th_td('Instantiation',$slice['instantiation']);
$details->th_td('Site',l_site_obj($site));
// xxx show the PIs here
//$details->th_td('PIs',...);
$details->end();

$details->form_end();
$toggle->end();

//////////////////// persons
$person_columns = array('email','person_id','first_name','last_name','roles');
$persons=$api->GetPersons(array('person_id'=>$slice['person_ids']));
// just propose to add everyone else, 
// as regular persons can see only a fraction of the db anyway
$potential_persons=
  $api->GetPersons(array('~person_id'=>$slice['person_ids'],'peer_id'=>NULL,'enabled'=>true),
		   $person_columns);
$count=count($persons);

$toggle=
  new PlekitToggle ('my-slice-persons',"$count Users",
		    array('bubble'=>
			  'Manage accounts attached to this slice',
			  'visible'=>get_arg('show_persons',false)));
$toggle->start();

////////// people currently in
// visible:
// hide if both current+add are included
// so user can chose which section is of interest
// show otherwise
$toggle_persons = new PlekitToggle ('my-slice-persons-current',
				    "$count people currently in $name",
				    array('visible'=>get_arg('show_persons_current',!$privileges)));
$toggle_persons->start();

$headers=array();
$headers['email']='string';
$headers['first']='string';
$headers['last']='string';
$headers['R']='string';
if ($privileges) $headers[plc_delete_icon()]="none";
$table=new PlekitTable('persons',$headers,'0',
		       array('notes_area'=>false));
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

  // remove persons
  $table->tfoot_start();

  $table->row_start();
  $table->cell($form->submit_html ("remove-persons-from-slice","Remove selected"),
	       array('hfill'=>true,'align'=>'right'));
  $table->row_end();
 }
$table->end();
$toggle_persons->end();

////////// people to add
if ($privileges) {
  $count=count($potential_persons);
  $toggle_persons = new PlekitToggle ('my-slice-persons-add',
				      "$count people may be added to $name",
				      array('visible'=>get_arg('show_persons_add',false)));
  $toggle_persons->start();
  if ( ! $potential_persons ) {
    // xxx improve style
    echo "<p class='not-relevant'>No person to add</p>";
  } else {
    $headers=array();
    $headers['email']='string';
    $headers['first']='string';
    $headers['last']='string';
    $headers['R']='string';
    $headers['+']="none";
    $options = array('notes_area'=>false,
		     'search_width'=>15,
		     'pagesize'=>8);
    // show search for admins only as other people won't get that many names to add
    if ( ! plc_is_admin() ) $options['search_area']=false;
    
    $table=new PlekitTable('add_persons',$headers,'0',$options);
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
		 array('hfill'=>true,'align'=>'right'));
    $table->row_end();
    $table->end();
    $form->end();
  }
  $toggle_persons->end();
}
$toggle->end();

//////////////////// nodes
// minimal list as a start
$node_columns = array('hostname','node_id','arch','peer_id','slice_ids_whitelist');
$nodes=$api->GetNodes(array('node_id'=>$slice['node_ids']),$node_columns);
$potential_nodes=$api->GetNodes(array('~node_id'=>$slice['node_ids']),$node_columns);
$count=count($nodes);

$toggle=new PlekitToggle ('my-slice-nodes',"$count Nodes",
			  array('bubble'=>
				'Manage nodes attached to this slice',
				'visible'=>get_arg('show_nodes',false)));
$toggle->start();

////////// nodes currently in
$count=count($nodes);
$toggle_nodes=new PlekitToggle('my-slice-nodes-current',
			       "$count nodes currently in $name",
			       array('visible'=>get_arg('show_nodes_current',!$privileges)));
$toggle_nodes->start();

$headers=array();
$headers['peer']='string';
$headers['hostname']='string';
$headers['arch']='string';
if ($privileges) $headers[plc_delete_icon()]="none";

$table_options = array('notes_area'=>false,
                       'search_width'=>15,
                       'pagesize'=>20);
$table=new PlekitTable('nodes',$headers,'0',$table_options);

$form=new PlekitForm(l_actions(),array('slice_id'=>$slice['slice_id']));
$form->start();
$table->start();
if ($nodes) foreach ($nodes as $node) {
  $table->row_start();
  $peers->cell($table,$node['peer_id']);
  $table->cell(l_node_obj($node));
  $table->cell($node['arch']);
  if ($privileges) $table->cell ($form->checkbox_html('node_ids[]',$node['node_id']));
  $table->row_end();
}
// actions area
if ($privileges) {

  // remove nodes
  $table->tfoot_start();

  $table->row_start();
  $table->cell($form->submit_html ("remove-nodes-from-slice","Remove selected"),
	       array('hfill'=>true,'align'=>'right'));
  $table->row_end();
 }
$table->end();
$toggle_nodes->end();

////////// nodes to add
if ($privileges) {
  $new_potential_nodes = array();
  if ($potential_nodes) foreach ($potential_nodes as $node) {
      $emptywl=empty($node['slice_ids_whitelist']);
      $inwl = (!emptywl) and in_array($slice['slice_id'],$node['slice_ids_whitelist']);
      if ($emptywl or $inwl)
	$new_potential_nodes[]=$node;
  }
  $potential_nodes=$new_potential_nodes;

  $count=count($potential_nodes);
  $toggle_nodes=new PlekitToggle('my-slice-nodes-add',
				 "$count more nodes available",
				 array('visible'=>get_arg('show_persons_add',false)));
  $toggle_nodes->start();

  if ( ! $potential_nodes ) {
    // xxx improve style
    echo "<p class='not-relevant'>No node to add</p>";
  } else {
    $headers=array();
    $headers['peer']='string';
    $headers['hostname']='string';
    $headers['arch']='string';
    $headers['+']="none";
    
    $table=new PlekitTable('add_nodes',$headers,'1', $table_options);
    $form=new PlekitForm(l_actions(),
			 array('slice_id'=>$slice['slice_id']));
    $form->start();
    $table->start();
    if ($potential_nodes) foreach ($potential_nodes as $node) {
	$table->row_start();
	$peers->cell($table,$node['peer_id']);
	$table->cell(l_node_obj($node));
	$table->cell($node['arch']);
	$table->cell ($form->checkbox_html('node_ids[]',$node['node_id']));
	$table->row_end();
      }
    // add nodes
    $table->tfoot_start();
    $table->row_start();
    $table->cell($form->submit_html ("add-nodes-in-slice","Add selected"),
		 array('hfill'=>true,'align'=>'right'));
    $table->row_end();
    $table->end();
    $form->end();
  }
  $toggle_nodes->end();
}
$toggle->end();

//////////////////////////////////////////////////////////// Tags
//if ( $local_peer ) {
  $tags=$api->GetSliceTags (array('slice_id'=>$slice_id));
  function get_tagname ($tag) { return $tag['tagname'];}
  $tagnames = array_map ("get_tagname",$tags);
  
  $toggle = new PlekitToggle ('slice-tags',count_english_warning($tags,'tag'),
			      array('bubble'=>'Inspect and set tags on tat slice',
				    'visible'=>get_arg('show_tags',false)));
  $toggle->start();
  
  $headers=array(
    "Name"=>"string",
    "Value"=>"string",
    "Node"=>"string",
    "NodeGroup"=>"string");
  if ($tags_privileges) $headers[plc_delete_icon()]="none";
  
  $table_options=array("notes_area"=>false,"pagesize_area"=>false,"search_width"=>10);
  $table=new PlekitTable("slice_tags",$headers,'0',$table_options);
  $form=new PlekitForm(l_actions(),
                       array('slice_id'=>$slice['slice_id']));
  $form->start();
  $table->start();
  if ($tags) {
    foreach ($tags as $tag) {
      $node_name = "ALL";
      if ($tag['node_id']) {
        $nodes = $api->GetNodes(array('node_id'=>$tag['node_id']));
        if($nodes) {
          $node = $nodes[0];
          $node_name = $node['hostname'];
        }
      }
      $nodegroup_name="n/a";
      if ($tag['nodegroup_id']) { 
        $nodegroup=$api->GetNodeGroups(array('nodegroup_id'=>$tag['nodegroup_id']));
        if ($nodegroup) {
          $nodegroup = $nodegroup[0];
          $nodegroup_name = $nodegroup['groupname'];
        }
      }
      $table->row_start();
      $table->cell(l_tag_obj($tag));
      $table->cell($tag['value']);
      $table->cell($node_name);
      $table->cell($nodegroup_name);
      if ($tags_privileges) $table->cell ($form->checkbox_html('slice_tag_ids[]',$tag['slice_tag_id']));
      $table->row_end();
    }
  }
  if ($tags_privileges) {
    $table->tfoot_start();
    $table->row_start();
    $table->cell($form->submit_html ("delete-slice-tags","Remove selected"),
                 array('hfill'=>true,'align'=>'right'));
    $table->row_end();
    
    $table->row_start();
    function tag_selector ($tag) {
      return array("display"=>$tag['tagname'],"value"=>$tag['tag_type_id']);
    }
    $all_tags= $api->GetTagTypes( array ("category"=>"slice*","-SORT"=>"+tagname"), array("tagname","tag_type_id"));
    $selector_tag=array_map("tag_selector",$all_tags);
    
    function node_selector($node) { 
      return array("display"=>$node["hostname"],"value"=>$node['node_id']);
    }
    $all_nodes = $api->GetNodes( array ("node_id" => $slice['node_ids']), array("hostname","node_id"));
    $selector_node=array_map("node_selector",$all_nodes);
    
    function nodegroup_selector($ng) {
      return array("display"=>$ng["groupname"],"value"=>$ng['nodegroup_id']);
    }
    $all_nodegroups = $api->GetNodeGroups( array("groupname"=>"*"), array("groupname","nodegroup_id"));
    $selector_nodegroup=array_map("nodegroup_selector",$all_nodegroups);
    
    $table->cell($form->select_html("tag_type_id",$selector_tag,array('label'=>"Choose Tag")));
    $table->cell($form->text_html("value","",array('width'=>8)));
    $table->cell($form->select_html("node_id",$selector_node,array('label'=>"All Nodes")));
    $table->cell($form->select_html("nodegroup_id",$selector_nodegroup,array('label'=>"No Nodegroup")));
    $table->cell($form->submit_html("add-slice-tag","Set Tag"),array('columns'=>2,'align'=>'left'));
    $table->row_end();
  }
    
  $table->end();
  $form->end();
  $toggle->end();
//}


//////////////////////// renew slice
if ($local_peer ) {
  if ( ! $renew_visible) renew_area ($slice,$site,false);
 }

$peers->block_end($peer_id);

// Print footer
include 'plc_footer.php';

?>
