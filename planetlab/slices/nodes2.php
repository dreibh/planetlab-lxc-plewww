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
require_once 'raphael.php';
require_once 'columns.php';

// keep css separate for now
drupal_set_html_head('
<link href="/planetlab/css/my_slice.css" rel="stylesheet" type="text/css" />
<script src="/planetlab/slices/leases.js" type="text/javascript" charset="utf-8"></script>
');

// -------------------- admins potentially need to get full list of users
ini_set('memory_limit','32M');

$profiling=false;
if ($_GET['profiling']) $profiling=true;

if ($profiling)  plc_debug_prof_start();

// -------------------- 
// recognized URL arguments
$slice_id=intval($_GET['id']);
if ( ! $slice_id ) { plc_error('Malformed URL - id not set'); return; }

////////////////////
// have to name columns b/c we need the non-native 'omf_control' column
$slice_columns=array('slice_id','name','peer_id','site_id','person_ids','node_ids','expires',
		     'url','description','instantiation','omf_control');
$slices= $api->GetSlices( array($slice_id), $slice_columns);

if (empty($slices)) {
  drupal_set_message ("Slice " . $slice_id . " not found");
  return;
 }

$slice=$slices[0];

if ($profiling) plc_debug_prof('2: slice',count($slices));
// pull all node info to vars
$name= $slice['name'];
$expires = date( "d/m/Y", $slice['expires'] );
$site_id= $slice['site_id'];

$person_ids=$slice['person_ids'];

// get peers
$peer_id= $slice['peer_id'];
$peers=new Peers ($api);
$local_peer = ! $peer_id;

if ($profiling) plc_debug_prof('3: peers',count($peers));

// gets site info
$sites= $api->GetSites( array( $site_id ) );
$site=$sites[0];
$site_name= $site['name'];
$max_slices = $site['max_slices'];

if ($profiling) plc_debug_prof('4: sites',count($sites));
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
<p>You <span class='bold'>must</span> provide a short description, 
as well as a link to a project website, before renewing it.

<br/> Please make sure to provide reasonable details on <span class='bold'>
the kind of traffic</span>, and <span class='bold'>copyrights</span> if relevant. 
Do <span class='bold'>not</span> provide bogus information; if a complaint is lodged against 
your slice  and your PlanetLab Operations Center is unable to determine what the normal behavior 
of your slice is, your slice may be deleted to resolve the complaint.</p>

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
$tabs [] = tab_site($site);

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
$details->th_td("OMF-friendly", ($slice['omf_control'] ? 'Yes' : 'No') . " [to change: see 'omf_control' in the tags section below]");
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

if ($profiling) plc_debug_prof('4: persons',count($persons));
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
$node_fixed_columns = array('hostname','node_id','peer_id','slice_ids_whitelist', 'site_id',
			    'run_level','boot_state','last_contact','node_type');
// create a VisibleTags object : basically the list of tag columns to show
//$visibletags = new VisibleTags ($api, 'node');
//$visiblecolumns = $visibletags->column_names();

// optimizing calls to GetNodes
//$all_nodes=$api->GetNodes(NULL,$node_columns);
//$slice_nodes=$api->GetNodes(array('node_id'=>$slice['node_ids']),$node_columns);
//$potential_nodes=$api->GetNodes(array('~node_id'=>$slice['node_ids']),$node_columns);


//NEW CODE FOR ENABLING COLUMN CONFIGURATION

//prepare fix and configurable columns

$fix_columns = array();
$fix_columns[]=array('tagname'=>'hostname', 'header'=>'hostname', 'type'=>'string', 'title'=>'The name of the node');
$fix_columns[]=array('tagname'=>'peer_id', 'header'=>'AU', 'type'=>'string', 'title'=>'Authority');
$fix_columns[]=array('tagname'=>'run_level', 'header'=>'ST', 'type'=>'string', 'title'=>'Status');
$fix_columns[]=array('tagname'=>'node_type', 'header'=>'RES', 'type'=>'string', 'title'=>'Reservable');

// columns that correspond to the visible tags for nodes (*node/ui*)
$visibletags = new VisibleTags ($api, 'node');
$visibletags->columns();
$tag_columns = $visibletags->headers();

// extra columns that are not tags (for the moment not sorted correctly)

$extra_columns = array();
$extra_columns[]=array('tagname'=>'sitename', 'header'=>'SN', 'type'=>'string', 'title'=>'Site name', 'fetched'=>true);
$extra_columns[]=array('tagname'=>'domain', 'header'=>'DN', 'type'=>'string', 'title'=>'Toplevel domain name', 'fetched'=>true);
$extra_columns[]=array('tagname'=>'ipaddress', 'header'=>'IP', 'type'=>'string', 'title'=>'IP Address', 'fetched'=>true);

//get user's column configuration

$first_time_configuration = 'false';
$default_configuration = "hostname:f|ST:f|AU:f|RES:f";
$column_configuration = "";
$slice_column_configuration = "";

$show_configuration = "reservable:yes";
$slice_show_configuration = "";
$show_reservable_message = "";

$PersonTags=$api->GetPersonTags (array('person_id'=>$plc->person['person_id']));
//print_r($PersonTags);
foreach ($PersonTags as $ptag) {
	if ($ptag['tagname'] == 'columnconf')
	{
                $column_configuration = $ptag['value'];
		$conf_tag_id = $ptag['person_tag_id'];
	}
	if ($ptag['tagname'] == 'showconf')
	{
                $show_configuration = $ptag['value'];
		$show_tag_id = $ptag['person_tag_id'];
	}
}

//print("<br>person column configuration = ".$column_configuration);

$sliceconf_exists = false;
if ($column_configuration == "")
{
	$column_configuration = $slice_id.";default";
	$sliceconf_exists = true;
}
else {
	$slice_conf = explode(";",$column_configuration);
	for ($i=0; $i<count($slice_conf); $i++ ) {
        	if ($slice_conf[$i] == $slice_id)
        	{
                	$i++;
        		$slice_column_configuration = $slice_conf[$i];
			$sliceconf_exists = true;
                	break;
        	}
		else
		{
                	$i++;
        		$slice_column_configuration = $slice_conf[$i];
		}
	}        
}

if ($sliceconf_exists == false)
	$column_configuration = $column_configuration.";".$slice_id.";default";

//print("<br>slice configuration = ".$slice_column_configuration);

//instantiate the column configuration class, which prepares the headers array

if ($slice_column_configuration == "")
	$full_configuration = $default_configuration;
else
	$full_configuration = $default_configuration."|".$slice_column_configuration;

$ConfigureColumns =new PlekitColumns($full_configuration, $fix_columns, $tag_columns, $extra_columns);

$visiblecolumns = $ConfigureColumns->node_tags();

$node_columns=array_merge($node_fixed_columns,$visiblecolumns);
//print_r($node_columns);
$all_nodes=$api->GetNodes(NULL,$node_columns);

//print("<br>person show configuration = ".$show_configuration);

$show_conf = explode(";",$show_configuration);
for ($i=0; $i<count($show_conf); $i++ ) {
                $i++;
                $slice_show_configuration = $show_conf[$i];
}        
$reservable_value = explode(":", $slice_show_configuration);
	if ($reservable_value[0]=="reservable" && $reservable_value[1] == "no")
		$show_reservable_message = "display:none";

//print("<br>slice show configuration = ".$slice_show_configuration);

$slice_nodes=array();
$potential_nodes=array();
$reservable_nodes=array();
foreach ($all_nodes as $node) {
  if (in_array($node['node_id'],$slice['node_ids'])) {
    $slice_nodes[]=$node;
    if ($node['node_type']=='reservable') $reservable_nodes[]=$node;
  } else {
    $potential_nodes[]=$node;
  }
}
if ($profiling) plc_debug_prof('5: nodes',count($slice_nodes));
////////////////////
// outline the number of reservable nodes
$nodes_message=count_english($slice_nodes,"node");
if (count($reservable_nodes)) $nodes_message .= " (" . count($reservable_nodes) . " reservable)";
$toggle=new PlekitToggle ('my-slice-nodes',$nodes_message,
			  array('bubble'=>
				'Manage nodes attached to this slice',
				'visible'=>get_arg('show_nodes',false)));
$toggle->start();

////////// show a notice to people having attached a reservable node
if (count($reservable_nodes) && $privileges) {
  $mark=reservable_mark();
  print <<<EOF
<div id='note_reservable_div' style="align:center; border : solid 2px red; padding:4px; width:800px; $show_reservable_message">
<table align=center><tr><td valign=top>
You have attached one or more <span class='bold'>reservable nodes</span> to your slice. 
Reservable nodes show up with the '$mark' mark. 
Your slivers will be available <span class='bold'>only during timeslots
where you have obtained leases</span>. 
You can manage your leases in the tab below.
<br>
Please note that as of August 2010 this feature is experimental. 
Feedback is appreciated at <a href="mailto:devel@planet-lab.org">devel@planet-lab.org</a>
</td><td valign=top><span onClick=closeShowReservable()><img class='reset' src="/planetlab/icons/clear.png" alt="hide message"></span>
</td></tr></table>
</div>
EOF;
}  

//////////////////// reservable nodes area
$count=count($reservable_nodes);
if ($count && $privileges) {
  // having reservable nodes in white lists looks a bit off scope for now...
  $toggle_nodes=new PlekitToggle('my-slice-nodes-reserve',
				 "Leases - " . count($reservable_nodes) . " reservable node(s)",
				 array('visible'=>get_arg('show_nodes_resa',false)));
  $toggle_nodes->start();
  $grain=$api->GetLeaseGranularity();
  if ($profiling) plc_debug_prof('6 granul',$grain);
  // where to start from, expressed as an offset in hours from now
  $resa_offset=$_GET['resa_offset'];
  if ( ! $resa_offset ) $resa_offset=0;
  $rough_start=time()+$resa_offset*3600;
  // xxx should be configurable
  $resa_slots=$_GET['resa_slots'];
  if ( ! $resa_slots ) $resa_slots = 36;
  // for now, show the next 72 hours, or 72 grains, which ever is smaller
  $duration=$resa_slots*$grain;
  $steps=$duration/$grain;
  $start=intval($rough_start/$grain)*$grain;
  $end=$rough_start+$duration;
  $lease_columns=array('lease_id','name','t_from','t_until','hostname','name');
  $leases=$api->GetLeases(array(']t_until'=>$rough_start,'[t_from'=>$end,'-SORT'=>'t_from'),$lease_columns);
  if ($profiling) plc_debug_prof('7 leases',count($leases));
  // hash nodes -> leases
  $host_hash=array();
  foreach ($leases as $lease) {
    $hostname=$lease['hostname'];
    if ( ! $host_hash[$hostname] ) {
	$host_hash[$hostname]=array();
    }
    // resync within the table
    $lease['nfrom']=($lease['t_from']-$start)/$grain;
    $lease['nuntil']=($lease['t_until']-$start)/$grain;
    $host_hash[$hostname] []= $lease;
  }
  # leases_data is the name used by leases.js to locate this table
  echo "<table id='leases_data'>";
  # pass (slice_id,slicename) as the [0,0] coordinate as thead>tr>td
  echo "<thead><tr><td>" . $slice['slice_id'] . '&' . $slice['name'] . "</td>";
  # the timeslot headers read (timestamp,label)
  $day_names=array('Su','M','Tu','W','Th','F','Sa');
  for ($i=0; $i<$steps; $i++) {
    $timestamp=($start+$i*$grain);
    $day=$day_names[intval(strftime("%w",$timestamp))];
    $label=$day . strftime(" %H:%M",$timestamp);
    // expose in each header cell the full timestamp, and how to display it - use & as a separator*/
    echo "<th>" . implode("&",array($timestamp,$label)) . "</th>";
  }
  echo "</tr></thead><tbody>";
  // todo - sort on hostnames
  function sort_hostname ($a,$b) { return ($a['hostname']<$b['hostname'])?-1:1;}
  usort($reservable_nodes,sort_hostname);
  foreach ($reservable_nodes as $node) {
    echo "<tr><th scope='row'>". $node['hostname'] . "</th>";
    $hostname=$node['hostname'];
    $leases=$host_hash[$hostname];
    $counter=0;
    while ($counter<$steps) {
      if ($leases && ($leases[0]['nfrom']<=$counter)) {
	$lease=array_shift($leases);
	/* nicer display, merge two consecutive leases for the same slice 
	   avoid doing that for now, as it might makes things confusing */
	/* while ($leases && ($leases[0]['name']==$lease['name']) && ($leases[0]['nfrom']==$lease['nuntil'])) {
	  $lease['nuntil']=$leases[0]['nuntil'];
	  array_shift($leases);
	  }*/
	$duration=$lease['nuntil']-$counter;
	echo "<td colspan='$duration'>" . $lease['lease_id'] . '&' . $lease['name'] . "</td>";
	$counter=$lease['nuntil']; 
      } else {
	echo "<td></td>";
	$counter+=1;
      }
    }
    echo "</tr>";
  }
  echo "</tbody></table>\n";

  // the general layout for the scheduler
  echo <<< EOF
<div id='leases_area'></div>

<div id='leases_buttons'>
  <button id='leases_clear' type='submit'>Clear</button>
  <button id='leases_submit' type='submit'>Submit</button>
</div>
EOF;

  $toggle_nodes->end();
 }


//////////////////// node configuration panel

$toggle_nodes=new PlekitToggle('my-slice-nodes-configuration',
                               "Node table layout",
                               array('visible'=>'1'));
$toggle_nodes->start();

//usort ($table_headers, create_function('$col1,$col2','return strcmp($col1["header"],$col2["header"]);'));
//print("<p>TABLE HEADERS<p>");
//print_r($table_headers);

print("<div id='debug'></div>");
print("<input type='hidden' id='slice_id' value='".$slice['slice_id']."' />");
print("<input type='hidden' id='person_id' value='".$plc->person['person_id']."' />");
print("<input type='hidden' id='conf_tag_id' value='".$conf_tag_id."' />");
print("<input type='hidden' id='show_tag_id' value='".$show_tag_id."' />");
print("<input type='hidden' id='column_configuration' value='".$slice_column_configuration."' />");
print("<br><input type='hidden' size=80 id='full_column_configuration' value='".$column_configuration."' />");
print("<input type='hidden' id='previousConf' value='".$slice_column_configuration."'></input>");
print("<input type='hidden' id='defaultConf' value='".$default_configuration."'></input>");

$ConfigureColumns->configuration_panel_html(true);

$ConfigureColumns->javascript_init();

$toggle_nodes->end();


$all_sites=$api->GetSites(NULL, array('site_id','login_base'));
$site_hash=array();
foreach ($all_sites as $site) $site_hash[$site['site_id']]=$site['login_base'];

$interface_columns=array('ip','node_id','interface_id');
$interface_filter=array('is_primary'=>TRUE);
$interfaces=$api->GetInterfaces($interface_filter,$interface_columns);

$interface_hash=array();
foreach ($interfaces as $interface) $interface_hash[$interface['node_id']]=$interface;





//////////////////// nodes currently in
$toggle_nodes=new PlekitToggle('my-slice-nodes-current',
			       count_english($slice_nodes,"node") . " currently in $name",
			       array('visible'=>get_arg('show_nodes_current',!$privileges)));
$toggle_nodes->start();

$headers=array();
$notes=array();
//$notes=array_merge($notes,$visibletags->notes());
$notes [] = "For information about the different columns please see the <b>node table layout</b> tab above or <b>mouse over</b> the column headers";

/*
$headers['peer']='string';
$headers['hostname']='string';
$short="-S-"; $long=Node::status_footnote(); $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
$short=reservable_mark(); $long=reservable_legend(); $type='string';
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
// the extra tags, configured for the UI
$headers=array_merge($headers,$visibletags->headers());

if ($privileges) $headers[plc_delete_icon()]="none";
*/

$edit_header = array();
if ($privileges) $edit_header[plc_delete_icon()]="none";
$headers = array_merge($ConfigureColumns->get_headers(),$edit_header);

//print("<p>HEADERS<p>");
//print_r($headers);

$table_options = array('notes'=>$notes,
                       'search_width'=>15,
                       'pagesize'=>20,
			'configurable'=>true);

$table=new PlekitTable('nodes',$headers,NULL,$table_options);

$form=new PlekitForm(l_actions(),array('slice_id'=>$slice['slice_id']));
$form->start();
$table->start();
if ($slice_nodes) foreach ($slice_nodes as $node) {
  $table->row_start();

$table->cell($node['node_id'], array('display'=>'none'));

  $table->cell(l_node_obj($node));
  $peers->cell($table,$node['peer_id']);
  $run_level=$node['run_level'];
  list($label,$class) = Node::status_label_class_($node);
  $table->cell ($label,array('class'=>$class));
  $table->cell( ($node['node_type']=='reservable')?reservable_mark():"" );

  $hostname=$node['hostname'];
  $ip=$interface_hash[$node['node_id']]['ip'];
  $interface_id=$interface_hash[$node['node_id']]['interface_id'];

//extra columns
$node['domain'] = topdomain($hostname);
$node['sitename'] = l_site_t($node['site_id'],$site_hash[$node['site_id']]);
$node['ipaddress'] = l_interface_t($interface_id,$ip);


 //foreach ($visiblecolumns as $tagname) $table->cell($node[$tagname]);
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

//////////////////// nodes to add
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
				 count_english($potential_nodes,"more node") . " available",
				 array('visible'=>get_arg('show_nodes_add',false)));
  $toggle_nodes->start();

  if ( $potential_nodes ) {
    $headers=array();
    $notes=array();


/*
    $headers['peer']='string';
    $headers['hostname']='string';
    $short="-S-"; $long=Node::status_footnote(); $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
	$short=reservable_mark(); $long=reservable_legend(); $type='string';
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
    // the extra tags, configured for the UI
    $headers=array_merge($headers,$visibletags->headers());
    $headers['+']="none";
*/

    $add_header = array();
    $add_header['+']="none";
    $headers = array_merge($ConfigureColumns->get_headers(),$add_header);

    //$notes=array_merge($notes,$visibletags->notes());
$notes [] = "For information about the different columns please see the <b>node table layout</b> tab above or <b>mouse over</b> the column headers";
    
    $table=new PlekitTable('add_nodes',$headers,NULL, $table_options);
    $form=new PlekitForm(l_actions(),
			 array('slice_id'=>$slice['slice_id']));
    $form->start();
    $table->start();
    if ($potential_nodes) foreach ($potential_nodes as $node) {
	$table->row_start();

$table->cell($node['node_id'], array('display'=>'none'));

	$table->cell(l_node_obj($node));
	$peers->cell($table,$node['peer_id']);
	list($label,$class) = Node::status_label_class_($node);
	$table->cell ($label,array('class'=>$class));
	$table->cell( ($node['node_type']=='reservable')?reservable_mark():"" );

	//extra columns
	  $hostname=$node['hostname'];
	  $ip=$interface_hash[$node['node_id']]['ip'];
	  $interface_id=$interface_hash[$node['node_id']]['interface_id'];
	$node['domain'] = topdomain($hostname);
	$node['sitename'] = l_site_t($node['site_id'],$site_hash[$node['site_id']]);
	$node['ipaddress'] = l_interface_t($interface_id,$ip);

	//foreach ($visiblecolumns as $tagname) $table->cell($node[$tagname]);
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

// very wide values get abbreviated
$tag_value_threshold=24;
//////////////////////////////////////////////////////////// Tags
//if ( $local_peer ) {
  $tags=$api->GetSliceTags (array('slice_id'=>$slice_id));
  if ($profiling) plc_debug_prof('8 slice tags',count($tags));
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
        $tag_nodes = $api->GetNodes(array('node_id'=>$tag['node_id']));
	if ($profiling) plc_debug_prof('9 node for slice tag',count($tag_nodes));
        if($tag_nodes) {
          $node = $tag_nodes[0];
          $node_name = $node['hostname'];
        }
      }
      $nodegroup_name="n/a";
      if ($tag['nodegroup_id']) { 
        $nodegroups=$api->GetNodeGroups(array('nodegroup_id'=>$tag['nodegroup_id']));
	if ($profiling) plc_debug_prof('10 nodegroup for slice tag',$nodegroup);
        if ($nodegroup) {
          $nodegroup = $nodegroups[0];
          $nodegroup_name = $nodegroup['groupname'];
        }
      }
      $table->row_start();
      $table->cell(l_tag_obj($tag));
      // very wide values get abbreviated
      $table->cell(truncate_and_popup($tag['value'],$tag_value_threshold));
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
    if ($profiling) plc_debug_prof('11 tagtypes',count($all_tags));
    $selector_tag=array_map("tag_selector",$all_tags);
    
    function node_selector($node) { 
      return array("display"=>$node["hostname"],"value"=>$node['node_id']);
    }
    $selector_node=array_map("node_selector",$slice_nodes);
    
    function nodegroup_selector($ng) {
      return array("display"=>$ng["groupname"],"value"=>$ng['nodegroup_id']);
    }
    $all_nodegroups = $api->GetNodeGroups( array("groupname"=>"*"), array("groupname","nodegroup_id"));
    if ($profiling) plc_debug_prof('13 nodegroups',count($all_nodegroups));
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

if ($profiling) plc_debug_prof_end();

// Print footer
include 'plc_footer.php';

?>