<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Common functions
require_once 'plc_functions.php';
require_once 'details.php';
require_once 'table.php';
require_once 'toggle.php';
  
//plc_debug('POST',$_POST);

// if not a PI or admin then redirect to slice index
$has_privileges = plc_is_admin() || plc_is_pi();
if ( ! $has_privileges ) {
  drupal_set_error("Insufficient privilege to add a slice");
  header( "index.php" );
  return 0;
}

// find out which site the slice should be added to
// without site_id set in GET, we use the first site that this user is in
if (get_array($_GET['site_id'])) {
  $site_id=intval($_GET['site_id']);
} else if (get_array($_POST, 'site_id')) {
  $site_id=intval($_POST['site_id']);
} else {
  $site_id=plc_my_site_id();
}

//////////////////// action
if ( $_POST['add-slice'] ) {
  // get post vars
  $url= $_POST['url'];
  $instantiation= $_POST['instantiation'];
  $name= $_POST['name'];
  $description= $_POST['description'];
  $person_ids = $_POST['person_ids'];

  $check=true;

  $sites=$api->GetSites(array('site_id'=>$site_id));
  if ( ! $sites) {
    drupal_set_error("Cannot find site_id $site_id");
    $check=false;
  }
  $site=$sites[0];
  $base=$site['login_base'] . '_';

  // validate input
  if( $name == $base ) {
    drupal_set_error("You must enter a name for your slice");
    $check=false;
  } else if (strpos($name,$base) != 0) {
    drupal_set_error("Slice name $name should begin with $base");
    $check=false;
  } else {
    // make sure slice name doesnt exist
    $slices = $api->GetSlices( array( $name ), array( "slice_id" ) );
    if ( count($slices) != 0) {
      drupal_set_error("Slice name $name already in use, please choose another");
      $check=false;
    }
  }
  
  if ( ($url == "http://") || ( $url=="" ) ) {
    drupal_set_error("You must enter a URL for your slice's info");
    $check=false;
  }
      
  if( $description == "" ) {
    drupal_set_error("Your must enter a description for you slice.");
    $check=false;
  }
  
  // if no errors then add
  if ( $check ) {
    $fields= array( "url" => $url, 
		    "instantiation" => $instantiation, 
		    "name" => $name, 
		    "description" => $description );
    // add it!
    $slice_id= $api->AddSlice( $fields );

    if ($slice_id > 0) {
      drupal_set_message ("Slice $slice_id created");
      if (isset($_POST['omf-control'])) {
	if ($api->SetSliceOmfControl($slice_id,'yes') != 'yes') {
	  drupal_set_error("Could not set the 'omf_control' tag on newly created slice...");
	} else {
	  drupal_set_message("Successfully set the 'omf_control' tag on slice");
	}
	if ($api->SetSliceVref($slice_id,'omf') != 'omf') {
	  drupal_set_error("Could not set the 'vref' tag on newly created slice...");
	} else {
	  drupal_set_message("Successfully set the 'vref' tag on slice");
	}
      }

      if ($person_ids) {
        // Add people
	$success=true;
	$counter=0;
	foreach ($person_ids as $person_id) {
	  $person_id=intval($person_id);
	  if ($api->AddPersonToSlice($person_id,$slice_id) != 1) {
	    drupal_set_error("Could not add person $person_id in slice :" . $api->error());
	    $success=false;
	  } else {
	    $counter++;
	  }
	}
	if ($success) 
	  drupal_set_message ("Added $counter person(s)");
	else
	  drupal_set_error ("Could not add all selected persons, only $counter were added");
      }
      plc_redirect(l_slice($slice_id) );
    } else {
      drupal_set_error("Could not create slice $name " . $api->error() );
      $check=false;
    }
  }
 }

//////////////////// still here : either it's a blank form or something was wrong

// Print header
require_once 'plc_drupal.php';
include 'plc_header.php';

$sites=$api->GetSites(array($site_id));
$site=$sites[0];
$sitename=$site['name'];
if ( ! $_POST['name']) 
  $base= $site['login_base'] ."_";

// propose to add all 'reachable' persons 
$site_person_ids=$site['person_ids'];
$persons_filter=array("person_id"=>$site_person_ids,
                      "enabled"=>true);
$persons=$api->GetPersons($persons_filter,array('email','enabled','first_name','last_name','person_id'));

drupal_set_title('Create slice in site "' . $sitename . '"');

// defaults
$url = $_POST['url'];
if( !$url ) $url= "http://";

// check for errors and set error styles
if( $error['name'] )
  $name_error= " class='plc-warning'";
  
if( $error['url'] )
  $url_error= " class='plc-warning'";
  
if( $error['description'] )
  $desc_error= " class='plc-warning'";


// is there a need to consider several sites ?
$multiple_sites=false;
$site_columns=array('name','login_base','site_id');
if (plc_is_admin ()) {
  $multiple_sites=true;
  $filter=array('-SORT'=>'name');
 } else if (count (plc_my_site_ids()) > 1) {
  $multiple_sites=true;
  $filter=array('-SORT'=>'name','site_id'=>plc_my_site_ids());
 }

if ($multiple_sites) {
  print "<div id='create-slice-in-site'>";
  $other_sites=$api->GetSites($filter,$site_columns);
  $selectors=array();
  foreach ($other_sites as $other_site) {
    $selector=array('display'=>$other_site['name'],
		    'value'=>$other_site['site_id']);
    if ($other_site['site_id']==$site_id) $selector['selected']='selected';
    $selectors []= $selector;
  }

  $site_form = new  PleKitForm (l_slice_add(),array(),array('method'=>'get'));
  $site_form->start();
  print $site_form->label_html('site_id','Or choose some other site');
  print $site_form->select_html('site_id',$selectors,array('autosubmit'=>true,
							   'id'=>'create-slice-choose-site'));
  $site_form->end();
  print "</div>";
 }
		  
print <<< EOF
<div class='create-slice-instantiations'>
<p><span class='bold'>Important:</span> Please provide a short description, as well as a 
link to a project website, before creating your slice.</p>
<p>
PlanetLab's security model requires that anyone who is concerned about a slice's activity be able to immediately learn about that slice. The details that you provide are your public explanation about why the slice behaves as it does. Be sure to describe the <span class='bold'>kind of traffic</span> that your slice generates, and how it handles material that is under <span class='bold'>copyright</span>, if relevant.
</p><p>
The PlanetLab Operations Centres regularly respond to concerns raised by third parties about site behaviour. Most incidents are resolved rapidly based upon the publicly posted slice details. However, when these details are not sufficiently clear or accurate, and we cannot immediately reach the slice owner, we must delete the slice.
</p>
<p><span class='bold'>NOTE</span>: All PlanetLab users are <span class='bold'>strongly</span>
 encouraged to join the PlanetLab 
<a href='https://lists.planet-lab.org/mailman/listinfo/users'>Users</a> 
mailing list. Most questions about running software on PlanetLab can be answered by 
posting to this list. 
<br/>Site administrators often use this list to post announcements about service outages. 
New software releases and available services are announced here as well.
</p>
</div>
EOF;

$toggle = new PlekitToggle ('create-slice-details','Slice Details',
			    array ('visible'=>get_arg('show_slice')));
$details = new PlekitDetails(TRUE);

$form_variables = array('site_id'=>plc_my_site_id());
$form = $details -> form_start("/db/slices/slice_add.php",$form_variables);
print $form->hidden_html("site_id",$site_id);

$toggle->start();
$details->start();

$running=count($site['slice_ids']);
$max=$site['max_slices'];
$allocated = " $running running / $max max";
if ($running >= $max) $allocated = plc_warning_html($allocated);
$details->th_td("Allocated slices",$allocated);
$details->th_td("Name",$name ? $name : $base, "name");
$details->th_td("URL",$url,"url");
$details->th_td("Description",$description,"description",
		array('input_type'=>'textarea',
		      'width'=>50,'height'=>5));
$selectors=array(array('display'=>"PLC",'value'=>'plc-instantiated'),
		 array('display'=>"Delegated",'value'=>'delegated'),
		 array('display'=>"Controller",'value'=>'nm-controller'),
		 array('display'=>"None",'value'=>'not-instantiated'));

$instantiation_select = $form->select_html ("instantiation", $selectors);
$details->th_td("Instantiation",$instantiation_select,"instantiation",
		array('input_type'=>'select', 'value'=>$instantiation));

// display the current settings if any (like, we've screwed up the first time)
if (isset($_POST['omf-control'])) {
  $omf_options=array('checked'=>'checked');
} else {
  $omf_options=array();
}
// 2018 feb 15 - turning off omf slices creation 
// $details->th_td("OMF friendly",
//		$form->checkbox_html('omf-control','yes',$omf_options));

$instantiation_text = <<< EOF
<div class='create-slice-instantiations'>
<p>There are four possible "instantiation" states for a slice.</p>
<ul>
<li> <span class='bold'>PLC</span> creates a slice with default settings. </li>
<li><span class='bold'>Delegated</span> creates a ticket to use on each node. </li>
<li><span class='bold'>Controller</span> creates a slice on all nodes to manipulate Delegated slices. </li>
<li><span class='bold'>None</span> allows you to reserve a slice name; you may instantiate the slice later.</li>
</ul>
<p>PLC instantiated slices can be defined as <span class='bold'>OMF friendly</span>, 
in which case slivers come with the OMF <span class='bold'>Resource Controller</span> pre-installed and pre-configured. 
Such slivers can then be easily managed through a centralized tool, the OMF Experiment Controller.
Using these <a href="http://omf.mytestbed.net">OMF tools</a>, a user can describe, instrument, 
and automatically execute their experiments across many slivers.
Please refer to <a href="http://mytestbed.net/wiki/omf/OMF_User_Guide">the OMF User Guide</a> 
to learn more on how to use this feature.
</p>
</div>
EOF;

$details->tr($instantiation_text);

$details->end();
$toggle->end();

if ($persons) {
  $title = count($persons) . " people can be added in slice";
  $toggle=new PlekitToggle ('create-slice-persons',$title,
			  array('visible'=>get_arg('show_persons')));
  $toggle->start();
  
  $headers = array();
  $headers['email']='string';
  $headers['first']='string';
  $headers['last']='string';
  $headers['+']='none';
  $table = new PlekitTable ('persons_in_slice',$headers,0);
  $table->start();
  foreach ($persons as $person) {
    $table->row_start();
    $table->cell($person['email']);
    $table->cell($person['first_name']);
    $table->cell($person['last_name']);
    $table->cell ($form->checkbox_html('person_ids[]',$person['person_id']));
    $table->row_end();
  }
  $table->end();
  $toggle->end();
 }

$add_button = $form->submit_html ("add-slice","Create Slice");
print ("<div id='slice_add_button'> $add_button </div>");

$form->end();

// Print footer
include 'plc_footer.php';

?>
