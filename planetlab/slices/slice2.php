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
require_once 'plc_objects.php';
require_once 'plc_visibletags2.php';
require_once 'linetabs.php';
require_once 'table2.php';
require_once 'details.php';
require_once 'toggle.php';
require_once 'form.php';
require_once 'columns.php';

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

$person_ids=$slice['person_ids'];

// get peers
$peer_id= $slice['peer_id'];
$peers=new Peers ($api);
$local_peer = ! $peer_id;

// gets site info
$sites= $api->GetSites( array( $site_id ) );
$site=$sites[0];
$site_name= $site['name'];
$max_slices = $site['max_slices'];

//////////////////////////////////////// building blocks for the renew area
// Constants
global $DAY;		$DAY = 24*60*60;
global $WEEK;		$WEEK = 7 * $DAY; 
global $MAX_WEEKS;	$MAX_WEEKS= 8;		// weeks from today
global $GRACE_DAYS;	$GRACE_DAYS=10;		// days for renewal promoted on top
global $NOW;		$NOW=mktime();

////////////////////////////////////////////////////////////
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
  $current_text = gmstrftime("%A %b-%d-%y %T %Z", $current_exp);
  $max_exp= $NOW + ($MAX_WEEKS * $WEEK); // seconds since epoch
  $max_text = gmstrftime("%A %b-%d-%y %T %Z", $max_exp);

  // xxx some extra code needed to enable this area only if the slice description is OK:
  // description and url must be non void
  $toggle=
    new PlekitToggle('renew',"Expires $current_text - Renew this slice",
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
<div class='my-slice-renewal'>
Slices annot be renewed more than $MAX_WEEKS weeks from now, i.e. not beyond $max_text. 
For this reason, the current slice cannot be renewed any further into the future, try again closer to expiration date.
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

////////////////////////////////////////////////////////////

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
// get persons in slice
if (!empty($person_ids))
  $persons=$api->GetPersons(array('person_id'=>$slice['person_ids']),$person_columns);
// just propose to add everyone else
// xxx this is maybe too much for admins as it slows stuff down 
// as regular persons can see only a fraction of the db anyway
$potential_persons=
  $api->GetPersons(array('~person_id'=>$slice['person_ids'],
			 'peer_id'=>NULL,
			 'enabled'=>true),
		   $person_columns);
$count=count($persons);

$toggle=
  new PlekitToggle ('my-slice-persons',"$count users",
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
$table=new PlekitTable2('persons',$headers,'0', NULL,
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
    
    $table=new PlekitTable2('add_persons',$headers,'0',NULL,$options);
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

//////////////////////////////////////////////////////////// Nodes
// the nodes details to display here
// (1) we search for the tag types for which 'category' matches 'node*/ui*'
// all these tags will then be tentatively displayed in this area
// (2) further information can also be optionally specified in the category:
//     (.) we split the category with '/' and search for assignments of the form var=value
//     (.) header can be set to supersede the column header (default is tagname)
//     (.) rank can be used for ordering the columns (default is tagname)
//     (.) type is passed to the javascript table, for sorting (default is 'string')

// minimal list as a start
//$node_fixed_columns = array('hostname','node_id','slice_ids_whitelist','boot_state','last_contact');
// create a VisibleTags object : basically the list of tag columns to show
//$visibletags = new VisibleTags ($api, 'node');
//$visiblecolumns = $visibletags->column_names();
//$node_columns=array_merge($node_fixed_columns,$visiblecolumns);



/* TEST PlekitColumns */

//prepare fix and configurable columns

$fix_columns = array();
$fix_columns[]=array('tagname'=>'hostname', 'header'=>'hostname', 'type'=>'string', 'title'=>'The name of the node');
$fix_columns[]=array('tagname'=>'peer_id', 'header'=>'AU', 'type'=>'string', 'title'=>'Authority');
$fix_columns[]=array('tagname'=>'run_level', 'header'=>'ST', 'type'=>'string', 'title'=>'Status');

$visibletags = new VisibleTags ($api, 'node');
$visibletags->columns();
$tag_columns = $visibletags->headers();

$extra_columns = array();
$extra_columns[]=array('tagname'=>'site_id', 'header'=>'SN', 'type'=>'string', 'title'=>'Site name', 'description'=>'Site name');

//$configurable_columns = array_merge($tag_columns, $extra_columns);
//usort ($configurable_columns, create_function('$col1,$col2','return strcmp($col1["header"],$col2["header"]);'));


$first_time_configuration = 'false';
$default_configuration = "hostname:f|ST:f|AU:f|Rw|AST";
$column_configuration = "";

$DescTags=$api->GetSliceTags (array('slice_id'=>$slice['slice_id']));
for ($i=0; $i<count($DescTags); $i++ ) {
        if ($DescTags [$i]['tagname']=='Configuration'){
		$column_configuration = $DescTags [$i]['value'];
                break;
        }
}

if ($column_configuration == "")
{
	$first_time_configuration = 'true';
	$column_configuration = $default_configuration;
}

//print("<p>GOT CONFIGURATION: ".$column_configuration);

//$test_configuration = "hostname:f|AU:f|ST:f|Rw|AST";
//print("<p>Parsing configuration ".$test_configuration);


$ConfigureColumns =new PlekitColumns($column_configuration, $fix_columns, $tag_columns, $extra_columns);

$node_requested_data = $ConfigureColumns->node_tags();
$nodes=$api->GetNodes(array('node_id'=>$slice['node_ids']),$node_requested_data);
$potential_nodes=$api->GetNodes(array('~node_id'=>$slice['node_ids']),$node_requested_data);

//$nodes = array();
//$potential_nodes = array();

//print("<p>RESULTS for ".print_r(array('~node_id'=>$slice['node_ids'])));
//print_r($nodes);

$count=count($nodes);

$toggle=new PlekitToggle ('my-slice-nodes',"$count nodes",
			  array('bubble'=>
				'Manage nodes attached to this slice',
				'visible'=>get_arg('show_nodes',false)));
$toggle->start();


$toggle_nodes=new PlekitToggle('my-slice-nodes-configuration',
			       "Node table column configuration",
			       array('visible'=>'1'));

$toggle_nodes->start();


//usort ($table_headers, create_function('$col1,$col2','return strcmp($col1["header"],$col2["header"]);'));

//print("<p>HEADERS TO SHOW<p>");
//print_r($headersToShow);

//print("<p>TABLE HEADERS<p>");
//print_r($table_headers);

print("<div id='debug'></div>");
print("<input type='hidden' id='slice_id' value='".$slice['slice_id']."' />");
print("<input type='hidden' size='80' id='column_configuration' value='".$column_configuration."' />");
print("<input type='hidden' id='previousConf' value='".$column_configuration."'></input>");
print("<input type='hidden' id='defaultConf' value='".$default_configuration."'></input>");
//print("<input type='button' id='testFunctions' onclick=\"highlightOption('AU')\" value='test'></input>");

$ConfigureColumns->javascript_vars();

$ConfigureColumns->configuration_panel_html(true);

print("<div align='center' id='loadingDiv'></div>");

$toggle_nodes->end();


////////// nodes currently in

$count=count($nodes);

$toggle_nodes=new PlekitToggle('my-slice-nodes-current',
			       "$count nodes currently in $name",
			       array('visible'=>get_arg('show_nodes_current',!$privileges)));

$toggle_nodes->start();

$edit_header = array();
if ($privileges) $edit_header[plc_delete_icon()]="none";

$table_options = array('search_width'=>15,
                       'pagesize'=>20);
//$table=new PlekitTable2('nodes',$headers,'1',$table_options);
//$table=new PlekitTable2('nodes_pairwise',array_merge($ConfigureColumns->plekit_columns_get_headers(),$edit_header),NULL,$headersToShow, $table_option);
//$headersToShow = $ConfigureColumns->plekit_columns_visible();

$table=new PlekitTable2('nodes',array_merge($ConfigureColumns->get_headers(),$edit_header),NULL,NULL, $table_option); 

$form=new PlekitForm(l_actions(),array('slice_id'=>$slice['slice_id']));
$form->start();
$table->start();
if ($nodes) foreach ($nodes as $node) {
  $table->row_start();

  $table->cell($node['node_id'], array('display'=>'none'));
  $table->cell(l_node_obj($node));

  $peers->cell($table,$node['peer_id']);
  $run_level=$node['run_level'];
  list($label,$class) = Node::status_label_class_($node);
  $table->cell ($label,array('name'=>'ST', 'class'=>$class, 'display'=>'table-cell'));

 
$ConfigureColumns->cells($table, $node);



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
				 array('visible'=>get_arg('show_nodes_add',false)));
  $toggle_nodes->start();

  if ( ! $potential_nodes ) {
    // xxx improve style
    echo "<p class='not-relevant'>No node to add</p>";
  } else {

    $edit_header = array();
    if ($privileges) $edit_header['+']="none";
    
    //$table=new PlekitTable2('add_nodes',$headers,'1', $table_options);
    $table=new PlekitTable2('add_nodes',array_merge($ConfigureColumns->get_headers(), $edit_header),NULL,$headersToShow, $table_options);
    $form=new PlekitForm(l_actions(),
			 array('slice_id'=>$slice['slice_id']));
    $form->start();
    $table->start();

    if ($potential_nodes) foreach ($potential_nodes as $node) {
	$table->row_start();

  $table->cell($node['node_id'], array('display'=>'none'));
  $table->cell(l_node_obj($node));

  $peers->cell($table,$node['peer_id']);
  $run_level=$node['run_level'];
  list($label,$class) = Node::status_label_class_($node);
  $table->cell ($label,array('name'=>'ST', 'class'=>$class, 'display'=>'table-cell'));

$ConfigureColumns->cells($table, $node);

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
  $table=new PlekitTable2("slice_tags",$headers,'0',NULL,$table_options);
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


<script type="text/javascript">

var sourceComon = '<a target="source_window" href="http://comon.cs.princeton.edu/">CoMoN</a>';
var sourceTophat = '<b><a target="source_window" href="http://www.top-hat.info/">TopHat</a></b>';
var sourceTophatAPI = '<b><a target="source_window" href="http://www.top-hat.info/API/">TopHat API</a></b>';
var sourceMySlice = '<b><a target="source_window" href="http://myslice.info/">MySlice</a></b>';
var sourceCymru = '<b><a target="source_window" href="http://www.team-cymru.org/">Team Cymru</a></b>';
var sourceMyPLC = '<b><a target="source_window" href="http://www.planet-lab.eu/PLCAPI/">MyPLC API</a></b>';
var sourceManiacs = '<b><a target="source_window" href="http://www.ece.gatech.edu/research/labs/MANIACS/as_taxonomy/">MANIACS</a></b>';
var sourceMonitor = '<b><a target="source_window" href="http://monitor.planet-lab.org/">Monitor</a></b>';
var selectReferenceNode ='Select reference node: <select id="reference_node" onChange="updateDefaultConf(this.value)"><option value=planetlab-europe-07.ipv6.lip6.fr>planetlab-europe-07.ipv6.lip6.fr</option></select>';
var addButton = '<input id="addButton" type="button" value="Add" onclick=addColumnAjax(document.getElementById("list1").value)></input>';
var deleteButton = '<input id="deleteButton" type="button" value="Delete" onclick=deleteColumn(window.document.getElementById("list1").value)></input>';

var titleAU = 'Authority';
var detailAU = '<i>The authority of the global PlanetLab federation that the site of the node belongs to.</i>';
var valuesAU = 'Values: <b>PLC</b> (PlanetLab Central), <b>PLE</b> (PlanetLab Europe)';
var sourceAU = '<b>Source:</b> '+sourceMyPLC;
var descAU = '<span class="myslice title">'+titleAU+'</span><p>'+detailAU+'<p>'+valuesAU+'<p>'+sourceAU;

var descHOSTNAME = "test";


var titleAS = 'Autonomous system ID';
var sourceAS = 'Source: '+sourceCymru+' (via '+sourceTophat+')';
var valuesAS = 'Unit: <b>Integer between 0 and 65535</b>';
var descAS = '<span class="myslice title">'+titleAS+'</span><p>'+valuesAS+'<p>' + sourceAS;

var titleAST = 'Autonomous system type';
var sourceAST = 'Source: '+sourceManiacs;
var valuesAST = 'Values: <b>t1</b> (tier-1), <b>t2</b> (tier-2), <b>edu</b> (university), <b>comp</b> (company), <b>nic</b> (network information center), <b>ix</b> (IXP), <b>n/a</b>';
var descAST = '<span class="myslice title">'+titleAST+'</span><p>'+valuesAST+'<p>'+sourceAST;

var titleASN = 'Autonomous system name';
var sourceASN = 'Source: '+sourceTophat;
var descASN = '<span class="myslice title">'+titleASN+'</span><p>'+sourceASN;

var selectPeriodBU = 'Select period: <select id="selectperiodBU" onChange=updatePeriod("BU")><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleBU = 'Bandwidth utilization ';
var sourceBU = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesBU ='Unit: <b>Kbps</b>';
var detailBU = '<i>The average Transmited bandwidh (Tx) over the selected period. The period is the most recent for which data is available, with CoMoN data being collected by MySlice daily.</i>'
var descBU = '<span class="myslice title">'+titleBU+'</span><p>'+detailBU+'<p>'+selectPeriodBU+'<p>'+valuesBU+'<p>'+sourceBU; 

var titleBW= 'Bandwidth limit';
var sourceBW = 'Source: '+sourceComon;
var valuesBW = 'Unit: <b>Kbps</b>';
var detailBW = '<i>The bandwidth limit is a cap on the total outbound bandwidth usage of a node. It is set by the site administrator (PI). For more details see <a href="http://www.planet-lab.org/doc/BandwidthLimits">Bandwidth Limits (planet-lab.org)</a></i>';
var descBW = '<span class="myslice title">'+titleBW+'</span><p>'+detailBW+'<p>'+valuesBW+'<p>'+sourceBW;

var titleCC = 'Number of CPU cores';
var sourceCC = 'Source: '+sourceComon;
var valuesCC = 'Current PlanetLab hardware requirements: 4 cores min. <br><i>(Older nodes may have fewer cores)</i>';
var descCC = '<span class="myslice title">'+titleCC+'</span><p>'+valuesCC+'<p>'+sourceCC;

var titleCN = 'Number of CPUs';
var sourceCN = 'Source: '+sourceComon;
var valuesCN = 'Current PlanetLab hardware requirements: <b>1 (if quad core) or 2 (if dual core)</b>';
var descCN = '<span class="myslice title">'+titleCN+'</span><p>'+valuesCN+'<p>'+sourceCN;

var titleCR = 'CPU clock rate';
var sourceCR = 'Source: '+sourceComon;
var valuesCR = 'Unit: <b>GHz</b><p>Current PlanetLab hardware requirements: <b>2.4 GHz</b>';
var descCR = '<span class="myslice title">'+titleCR+'</span><p>'+valuesCR+'<p>'+sourceCR;

var selectPeriodCF = 'Select period: <select id="selectperiodCF" onChange=updatePeriod("CF")><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleCF = 'Free CPU';
var sourceCF = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesCF = 'Unit: <b>%</b>';
var detailCF = '<i> The average CPU percentage that could be allocated by a test slice (burb) run periodically by CoMoN </i>';
var descCF = '<span class="myslice title">'+titleCF+'</span><p>'+detailCF+'<p>'+selectPeriodCF+'<p>'+valuesCF+'<p>'+sourceCF; 

var titleDS = 'Disk size';
var sourceDS = 'Source: '+sourceComon;
var valuesDS = 'Unit: <b>GB</b><p>Current PlanetLab hardware requirements: <b>500GB</b>';
var descDS = '<span class="myslice title">'+titleDS+'</span><p>'+valuesDS+'<p>'+sourceDS;

var titleDU = 'Current disk utilization';
var sourceDU = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesDU = 'Unit: <b>GB</b>';
var detailDU = '<i> The amount of disk space currently consumed (checked daily) </i>';
var descDU = '<span class="myslice title">'+titleDU+'</span><p>'+detailDU+'<p>'+valuesDU+'<p>'+sourceDU;

var titleHC = 'Hop count from a reference node';
var sourceHC = 'Source: '+sourceTophat;
var detailHC = '<i>TopHat conducts traceroutes every five minutes in a full mesh between all PlanetLab nodes. The hop count is the length of the traceroute from the node to the reference node, based upon the most recently reported traceroute</i>.';
var descHC = '<span class="myslice title">'+titleHC+'</span><p>'+detailHC+'<p>'+selectReferenceNode+'<p>'+sourceHC;

var titleIP = 'IP address';
var sourceIP = 'Source: '+sourceTophat;
var descIP = '<span class="myslice title">'+titleIP+'</span><p>'+sourceIP;

var selectPeriodL = 'Select period: <select id="selectperiodL" onChange=updatePeriod("L")><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleL= 'Load ';
var sourceL = 'Source: '+sourceComon;
var valuesL = 'Unit: <b>float</b>';
var detailL = '<i>The average 5-minute Unix load (as reported by the uptime command) over the selected period</i>';
var descL = '<span class="myslice title">'+titleL+'</span><p>'+detailL+'<p>'+selectPeriodL+'<p>'+valuesL+'<p>'+sourceL; 

var titleLON= 'Longitude';
var sourceLON = 'Source: '+sourceTophat;
var descLON = '<span class="myslice title">'+titleLON+'</span><p>'+sourceLON;

var titleLAT= 'Latitude';
var sourceLAT = 'Source: '+sourceTophat;
var descLAT = '<span class="myslice title">'+titleLAT+'</span><p>'+sourceLAT;

var titleLCN= 'Location (Country)';
var sourceLCN = 'Source: '+sourceTophat;
var detailLCN = '<i>Based on the latitude, longitude information</i>';
var descLCN = '<span class="myslice title">'+titleLCN+'</span><p>'+detailLCN+'<p>'+sourceLCN;

var titleLCT= 'Location (Continent)';
var sourceLCT = 'Source: '+sourceTophat;
var detailLCT = '<i>Based on the latitude, longitude information</i>';
var descLCT = '<span class="myslice title">'+titleLCT+'</span><p>'+detailLCT+'<p>'+sourceLCT;

var titleLCY= 'Location (City)';
var sourceLCY = 'Source: '+sourceTophat;
var detailLCY = '<i>Based on the latitude, longitude information</i>';
var descLCY = '<span class="myslice title">'+titleLCY+'</span><p>'+detailLCY+'<p>'+sourceLCY;

var titleLPR= 'Location precision radius';
var sourceLPR = 'Source: '+sourceTophat;
var valuesLPR = 'Unit: <b>float</b>';
var detailLPR = '<i>The radius of the circle corresponding to the error in precision of the geolocalization</i>';
var descLPR = '<span class="myslice title">'+titleLPR+'</span><p>'+detailLPR+'<p>'+valuesLPR+'<p>'+sourceLPR;

var titleLRN= 'Location (Region)';
var sourceLRN = 'Source: '+sourceTophat;
var detailLRN = '<i>Based on the latitude, longitude information</i>';
var descLRN = '<span class="myslice title">'+titleLRN+'</span><p>'+detailLRN+'<p>'+sourceLRN;

var titleMS= 'Memory size';
var sourceMS = 'Source: '+sourceComon;
var valuesMS = 'Unit: <b>GB</b><p>Current PlanetLab hardware requirements: <b>4GB</b>';
var descMS = '<span class="myslice title">'+titleMS+'</span><p>'+valuesMS+'<p>'+sourceMS;

var selectPeriodMU = 'Select period: <select id="selectperiodMU" onChange=updatePeriod("MU")><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleMU = 'Memory utilization';
var sourceMU = 'Source: '+sourceComon;
var valuesMU = '<p>Unit: <b>%</b>';
var detailMU = '<i>The average active memory utilization as reported by CoMoN</i>';
var descMU = '<span class="myslice title">'+titleMU+'</span><p>'+detailMU+'<p>'+selectPeriodMU+'<p>'+valuesMU+'<p>'+sourceMU; 

var titleNEC= 'Network information (ETOMIC)';
var sourceNEC = 'Source: '+sourceTophat;
var valuesNEC = 'Values: <b>yes/no</b>';
var detailNEC = '<i>The existence of a colocated ETOMIC box. When an ETOMIC box is present you have the possibility to conduct high-precision measurements through the '+sourceTophatAPI+'</i>';
var descNEC = '<span class="myslice title">'+titleNEC+'</span><p>'+detailNEC+'<p>'+valuesNEC+'<p>'+sourceNEC;

var titleNTH= 'Network information (TopHat)';
var sourceNTH = 'Source: '+sourceTophat;
var valuesNTH = 'Values: <b>yes/no</b>';
var detailNTH = '<i>The existence of a colocated TopHat agent. When a TopHat agent is present you have the possibility to conduct high-precision measurements through the '+sourceTophatAPI+'</i>';
var descNTH = '<span class="myslice title">'+titleNTH+'</span><p>'+detailNTH+'<p>'+valuesNTH+'<p>'+sourceNTH;

var titleNDS= 'Network information (DIMES)';
var sourceNDS = 'Source: '+sourceTophat;
var valuesNDS = 'Values: <b>yes/no</b>';
var detailNDS = '<i>The existence of a colocated DIMES agent. When a DIMES agent is present you have the possibility to conduct high-precision measurements through the '+sourceTophatAPI+'</i>';
var descNDS = '<span class="myslice title">'+titleNDS+'</span><p>'+detailNDS+'<p>'+valuesNDS+'<p>'+sourceNDS;

var titleNSF= 'Network information (spoof)';
var sourceNSF = 'Source: '+sourceTophat;
var valuesNSF = '<p>Values: <b>yes/no</b>';
var detailNSF = '<i> Whether the node can send packets packets using the IP spoof option</i>';
var descNSF = '<span class="myslice title">'+titleNSF+'</span><p>'+detailNSF+'<p>'+valuesNSF+'<p>'+sourceNSF;

var titleNSR= 'Network information (source route)';
var sourceNSR = 'Source: '+sourceTophat;
var valuesNSR = '<p>Values: <b>yes/no</b>';
var detailNSR = '<i> Whether the node can send packets packets using the IP source route option</i>';
var descNSR = '<span class="myslice title">'+titleNSR+'</span><p>'+detailNSR+'<p>'+valuesNSR+'<p>'+sourceNSR;

var titleNTP= 'Network information (timestamp)';
var sourceNTP = 'Source: '+sourceTophat;
var valuesNTP = '<p>Values: <b>yes/no</b>';
var detailNTP = '<i> Whether the node can send packets packets using the IP timestamp option</i>';
var descNTP = '<span class="myslice title">'+titleNTP+'</span><p>'+detailNTP+'<p>'+valuesNTP+'<p>'+sourceNTP;

var titleNRR= 'Network information (record route)';
var sourceNRR = 'Source: '+sourceTophat;
var valuesNRR = '<p>Values: <b>yes/no</b>';
var detailNRR = '<i> Whether the node can send packets packets using the IP record route option</i>';
var descNRR = '<span class="myslice title">'+titleNRR+'</span><p>'+detailNRR+'<p>'+valuesNRR+'<p>'+sourceNRR;

var titleOS = 'Operating system';
var sourceOS = 'Source: '+sourceMyPLC;
var valuesOS = 'Values: <b>Fedora, Cent/OS, other, n/a</b>';
var descOS = '<span class="myslice title">'+titleOS+'</span><p>'+valuesOS+'<p>'+sourceOS;

var selectPeriodR = 'Select period: <select id="selectperiodR" onChange=updatePeriod("R")><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleR = 'Reliability';
var sourceR = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var detailR = '<i>CoMoN queries nodes every 5 minutes, for 255 queries per day. The average reliability is the percentage of queries over the selected period for which CoMoN reports a value. The period is the most recent for which data is available, with CoMoN data being collected by MySlice daily</i>';
var valuesR = 'Unit: <b>%</b>';
var descR = '<span class="myslice title">'+titleR+'</span><p>'+detailR+'<p>'+selectPeriodR+'<p>'+valuesR+'<p>'+sourceR; 

var titleRES = 'Reservation capabilities';
var sourceRES = 'Source: '+sourceMyPLC;
var valuesRES = 'Values: <b>yes/no</b>';
var detailRES = '<i> Whether the node can be reserved for a certain duration</i>';
var descRES = '<span class="myslice title">'+titleRES+'</span><p>'+detailRES+'<p>'+valuesRES+'<p>'+sourceRES;

var selectPeriodS = 'Select period: <select id="selectperiodS" onChange=updatePeriod("S")><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleS = 'Active slices';
var sourceS = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesS = 'Unit: <b>%</b>';
var detailS = '<i>Average number of active slices over the selected period for which CoMoN reports a value. The period is the most recent for which data is available, with CoMoN data being collected by MySlice daily</i>';
var descS = '<span class="myslice title">'+titleS+'</span><p>'+detailS+'<p>'+selectPeriodS+'<p>'+valuesS+'<p>'+sourceS; 

var titleSN = 'Site name';
var sourceSN = 'Source: '+sourceMyPLC;
var descSN = '<span class="myslice title">'+titleSN+'</span><p>'+sourceSN;

var selectPeriodSSH = 'Select period: <select id="selectperiodSSH" onChange=updatePeriod("SSH")><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleSSH = 'Average SSH response delay';
var valuesSSH = 'Unit: <b>%</b>';
var detailSSH = '<i>The average response delay of the node to SSH logins over the selected period for which CoMoN reports a value. The period is the most recent for which data is available, with CoMoN data being collected by MySlice daily</i>';
var sourceSSH ='Source: '+sourceComon+' (via '+sourceMySlice+')';
var descSSH = '<span class="myslice title">'+titleSSH+'</span><p>'+detailSSH+'<p>'+selectPeriodSSH+'<p>'+valuesSSH+'<p>'+sourceSSH; 

var titleST = 'Status';
var sourceST = 'Source: '+sourceMonitor;
var valuesST = 'Values: <b>online</b> (up and running), <b>good</b> (up and running recently), <b>offline</b> (unreachable today), <b>down</b> (unreachable nodes for more than 1 day), <b>failboot</b> (safeboot, MyPLC API term for debug)';
var descST = '<span class="myslice title">'+titleST+'</span><p>'+valuesST+'<p>'+sourceST;

highlightOption("AU");
overrideTitles();


/*
document.defaultAction = false;
document.onkeyup = detectEvent;

function detectEvent(e) {
	var evt = e || window.event;
	//debugfilter(evt.type);
	//debugfilter('keyCode is ' + evt.keyCode);
	//debugfilter('charCode is ' + evt.charCode);
	//debugfilter(document.getElementById('testfocus').focused);
	return document.defaultAction;
}
*/


</script>
