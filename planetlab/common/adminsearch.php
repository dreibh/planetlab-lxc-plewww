<?php

  // $Id$

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
// set default
drupal_set_title('DB Search');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'plc_objects.php';
require_once 'plc_peers.php';
require_once 'table.php';
require_once 'form.php';
require_once 'toggle.php';

ini_set("memory_limit","256M");

if ( ! plc_is_admin()) {
  plc_warning ("DB Search is available to admins only");
  return;
 }

$pattern="";
if (get_array($_GET,  'pattern')) { $pattern=$_GET['pattern']; }
if (get_array($_POST, 'pattern')) { $pattern=$_POST['pattern']; }

$tokens=explode(" ",$pattern);
function token_filter ($t) { $t = trim($t); if (empty($t)) return false; return true; }
$tokens=array_filter($tokens, "token_filter");


////////////////////
// from a single search form, extract all tokens entered
// and then show all entities that match one of that tokens among
// persons - sites - slices - nodes
////////////////////
function display_form ($pattern) {
  if ($pattern) {
    $title="Searching for $pattern";
    $visible=false;
  } else {
    $title="Search for what";
    $visible=true;
  }
  $toggle=new PlekitToggle("admin-search",$title,array('visible'=>$visible));
  $toggle->start();
  print <<< EOF
<p id='admin-search-message'>
This form searches for <span class="bold">any entry</span> in the database
(among <span class="bold">persons</span>, <span class="bold">slices</span>,
<span class="bold">sites</span> and <span class="bold">nodes</span>)
matching a name fragment, or token. <br/>
You can specify a space-separated list of tokens, all entries matching
<span class="bold">any token</span> would then get listed.
</p>
EOF;
  print "<div id='admin-search-form'>";
  $form=new PlekitForm ('/db/common/adminsearch.php',array());
  $form->start();
  print $form->label_html('pattern',"Enter space-separated tokens");
  print $form->text_html('pattern',$pattern);
  print $form->submit_html('submit','Submit');
  $form->end();
  print "</div>\n";
  $toggle->end();
}

// just look for *token*
function token_pattern ($token) {
  return "*" . $token . "*";
}

// $type is e.g. 'Persons' -- $field is e.g. 'email'
function generic_search ($type,$field,$tokens) {
  global $api;
  $results=array();
  $methodname='Get'.$type;
  /*
    This was broken after 598e1e840b55262fd40c6d1700148e4f0b508065 change in plcapi.
    We no longer generate a list of methods but let the api (php) object pass them through.

  if ( ! method_exists($api,$methodname)) {
    plc_error("generic_search failed with methodname=$methodname");
    return $results;
  }
  */
  foreach ($tokens as $token) {
    $filter=array($field=>token_pattern($token));
    $new_results = $api->$methodname($filter);
    if (is_array($new_results)) {
        $results = array_merge ($results, $new_results);
    }
  }
  return $results;
}

// $objects is e.g. a collection of persons
// then, e.g. on slice,  $key='site_id'  & $plural=false
// or,   e.g. on person, $key='site_ids'  & $plural=true
function generic_gather_related ($objects, $key, $plural) {
  if ( empty ($objects))
    return array();
  // else, look for either 'site_id' or 'site_ids' in the first object
  $sample=$objects[0];
  if ( array_key_exists($key,$sample)) {
    $result=array();
    foreach ($objects as $object) {
      if ($plural) {
	$result = array_merge ($result, $object[$key]);
      } else {
	$result []= $object[$key];
      }
    }
    return $result;
  } else {
    plc_debug("gather_related failed with $key",$sample);
    return array();
  }
}

//////////
// create link from an id, using the various global hashes
function plc_person_link ($person_id) {global $persons_hash; return l_person_obj($persons_hash[$person_id]);}
function plc_slice_link ($slice_id) {global $slices_hash; return l_slice_obj($slices_hash[$slice_id]);}
function plc_site_link ($site_id) {global $sites_hash; return l_site_obj($sites_hash[$site_id]);}
function plc_node_link ($node_id) {global $nodes_hash; return l_node_obj($nodes_hash[$node_id]);}

global $table_options;
$table_options = array('notes_area'=>false);

global $peers;
$peers = new Peers ($api);

function display_persons ($persons,$visible) {
  if ( ! $persons) return;

  $toggle=new PlekitToggle('persons-area',"Persons",array('visible'=>$visible));
  $toggle->start();

  $headers=array('id'=>'int',
		 'P'=>'string',
		 'email'=>'string',
		 'sites'=>'string',
		 'slices'=>'string',
		 'roles'=>'string');
  global $table_options;
  global $peers;
  $table=new PlekitTable('persons',$headers,1,$table_options);
  $table->start();
  foreach ($persons as $person) {
    $table->row_start();
    $table->cell($person['person_id']);
    $peers->cell($table,$person['peer_id']);
    $table->cell(l_person_obj($person));
    $table->cell(plc_vertical_table(array_map("plc_site_link",$person['site_ids'])));
    $table->cell(plc_vertical_table(array_map("plc_slice_link",$person['slice_ids'])));
    $table->cell(plc_vertical_table($person['roles']));
    $table->row_end();
  }
  $table->end();
  $toggle->end();
}

function display_slices ($slices,$visible) {
  if ( ! $slices) return;

  $toggle=new PlekitToggle('slices-area',"Slices",array('visible'=>$visible));
  $toggle->start();

  $headers=array('id'=>'int',
		 'P'=>'string',
		 'name'=>'string',
		 'site'=>'string',
		 'persons'=>'string',
		 'N'=>'string');
  global $table_options;
  global $peers;
  $table=new PlekitTable('slices',$headers,1,$table_options);
  $table->start();
  foreach ($slices as $slice) {
    $table->row_start();
    $table->cell($slice['slice_id']);
    $peers->cell($table,$slice['peer_id']);
    $table->cell(l_slice_obj($slice));
    global $sites_hash;
    $site=$sites_hash[$slice['site_id']];
    $table->cell(l_site_obj($site));
    $table->cell(plc_vertical_table(array_map("plc_person_link",$slice['person_ids'])));
    // this makes really long tables, use the slice link to see details
    //$table->cell(plc_vertical_table(array_map("plc_node_link",$slice['node_ids'])));
    $table->cell(count($slice['node_ids']));
    $table->row_end();
  }
  $table->end();
  $toggle->end();
}

function display_sites ($sites,$visible) {
  if ( ! $sites) return;

  $toggle=new PlekitToggle('sites-area',"Sites",array('visible'=>$visible));
  $toggle->start();

  $headers=array('id'=>'int',
		 'P'=>'string',
		 'name'=>'string',
		 'url'=>'string',
		 'persons'=>'string',
		 'slices'=>'string',
		 'nodes'=>'string');
  global $table_options;
  global $peers;
  $table=new PlekitTable('sites',$headers,1,$table_options);
  $table->start();
  foreach ($sites as $site) {
    $table->row_start();
    $table->cell($site['site_id']);
    $peers->cell($table,$site['peer_id']);
    $table->cell(l_site_obj($site));
    $table->cell(href($site['url'],$site['url']));
    $table->cell(plc_vertical_table(array_map("plc_person_link",$site['person_ids'])));
    $table->cell(plc_vertical_table(array_map("plc_slice_link",$site['slice_ids'])));
    $table->cell(plc_vertical_table(array_map("plc_node_link",$site['node_ids'])));
    $table->row_end();
  }
  $table->end();
  $toggle->end();
}

function display_nodes ($nodes,$visible) {
  if ( ! $nodes) return;

  $toggle=new PlekitToggle('nodes-area',"Nodes",array('visible'=>$visible));
  $toggle->start();

  $headers=array('id'=>'int',
		 'P'=>'string',
		 'hostname'=>'string',
		 'site'=>'string',
		 'slices'=>'string');
  global $table_options;
  global $peers;
  $table=new PlekitTable('nodes',$headers,1,$table_options);
  $table->start();
  foreach ($nodes as $node) {
    $table->row_start();
    $table->cell($node['node_id']);
    $peers->cell($table,$node['peer_id']);
    $table->cell(l_node_obj($node));
    global $sites_hash;
    $site=$sites_hash[$node['site_id']];
    $table->cell(l_site_obj($site));
    // same as above, too many entries, just list how many there are
    //$table->cell(plc_vertical_table(array_map("plc_slice_link",$node['slice_ids'])));
    $table->cell(count($node['slice_ids']));
    $table->row_end();
  }
  $table->end();
  $toggle->end();
}


////////////////////////////////////////////////////////////
display_form($pattern);

if ($pattern) {

  ////////// search database
  // search persons on email
  $persons = array();
  $persons = array_merge ($persons, generic_search ('Persons','email',$tokens));

  // search slices on name
  $slices=array();
  $slices = array_merge ($slices, generic_search ('Slices','name',$tokens));

  // search sites on name and login_base
  $sites=array();
  $sites = array_merge ($sites, generic_search('Sites','name',$tokens));
  $sites = array_merge ($sites, generic_search('Sites','login_base',$tokens));

  // nodes on hostname
  $nodes=array();
  $nodes = array_merge ($nodes, generic_search('Nodes','hostname',$tokens));

  print "Search results for <span class='tokens'> $pattern </span>\n";

  // what kind of result have we gotten:
  // if none : display message and exit
  // if only one kind of objects : start toggle with visible=true
  // otherwise start all toggles with visible=false
  $areas=0;
  if ($persons) $areas += 1;
  if ($slices) $areas += 1;
  if ($sites) $areas += 1;
  if ($nodes) $areas += 1;

  if ( $areas == 0) {
    plc_warning ("No result found");
    return;
  } else if ($areas == 1) {
    $visible=true;
  } else {
    $visible=false;
  }


  ////////// collect all related objects
  $rel_person_ids = array();
  $rel_person_ids = array_merge($rel_person_ids, generic_gather_related ($sites,'person_ids',true));
  $rel_person_ids = array_merge($rel_person_ids, generic_gather_related ($slices,'person_ids',true));

  $rel_slice_ids = array();
  $rel_slice_ids = array_merge($rel_slice_ids, generic_gather_related ($persons,'slice_ids',true));
  $rel_slice_ids = array_merge($rel_slice_ids, generic_gather_related ($sites,'slice_ids',true));
  $rel_slice_ids = array_merge($rel_slice_ids, generic_gather_related ($nodes,'slice_ids',true));

  $rel_site_ids = array();
  $rel_site_ids = array_merge ( $rel_site_ids, generic_gather_related ($persons,'site_ids',true));
  $rel_site_ids = array_merge ( $rel_site_ids, generic_gather_related ($slices,'site_id',false));
  $rel_site_ids = array_merge ( $rel_site_ids, generic_gather_related ($nodes,'site_id',false));

  $rel_node_ids = array();
  $rel_node_ids = array_merge($rel_node_ids, generic_gather_related ($sites,'node_ids',true));
  $rel_node_ids = array_merge($rel_node_ids, generic_gather_related ($slices,'node_ids',true));


  ////////// fetch related and store in a global hash
  $rel_persons = $api->GetPersons ($rel_person_ids);
  global $persons_hash; $persons_hash=array();
  foreach ($rel_persons as $person) $persons_hash[$person['person_id']]=$person;

  $rel_slices = $api->GetSlices ($rel_slice_ids);
  global $slices_hash; $slices_hash=array();
  foreach ($rel_slices as $slice) $slices_hash[$slice['slice_id']]=$slice;

  $rel_sites = $api->GetSites ($rel_site_ids);
  global $sites_hash; $sites_hash=array();
  foreach ($rel_sites as $site) $sites_hash[$site['site_id']]=$site;

  $rel_nodes = $api->GetNodes ($rel_node_ids);
  global $nodes_hash; $nodes_hash=array();
  foreach ($rel_nodes as $node) $nodes_hash[$node['node_id']]=$node;

  ////////// show results
  display_persons ($persons,$visible);
  display_slices ($slices,$visible);
  display_sites($sites,$visible);
  display_nodes($nodes,$visible);

 }

// Print footer
include 'plc_footer.php';

?>
