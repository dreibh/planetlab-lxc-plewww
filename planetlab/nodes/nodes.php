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
require_once 'plc_objects.php';
require_once 'plc_peers.php';
require_once 'plc_visibletags2.php';
require_once 'linetabs.php';
require_once 'table2.php';
require_once 'nifty.php';
require_once 'toggle.php';
require_once 'columns.php';

// keep css separate for now
drupal_set_html_head('
<link href="/planetlab/css/my_slice.css" rel="stylesheet" type="text/css" />
');


ini_set("memory_limit","64M");

// -------------------- 
// recognized URL arguments
$peerscope=$_GET['peerscope'];
$pattern=$_GET['pattern'];
$site_id=intval($_GET['site_id']);
$slice_id=intval($_GET['slice_id']);
$person_id=intval($_GET['person_id']);

// --- decoration
$title="Nodes";
$tabs=array();
$tabs []= tab_nodes();
if (count (plc_my_site_ids()) == 1) {
    $tabs []= tab_nodes_mysite();
} else {
    $tabs []= tab_nodes_all_mysite();
}
$tabs []= tab_nodes_local();

// -------------------- 
$node_filter=array();

//////////////////
// performs sanity check and summarize the result in a single column
function node_status ($node) {

  $messages=array();
  if ($node['node_type'] != 'regular' && $node['node_type'] != 'reservable' ) 
    $messages []= $node['node_type'];

  // checks on local nodes only
  if ( ( ! $node['peer_id']) ) {
    // has it got interfaces 
    if (count($node['interface_ids']) == 0) 
      $messages []= "No interface";
  }
  return plc_vertical_table($messages,'plc-warning');
}


$first_time_configuration = 'false';

if (plc_is_admin()) 
	$default_configuration = "ID:f|hostname:f|ST:f|AU:f";
else
	$default_configuration = "hostname:f|ST:f|AU:f";

//$extra_default = "LCN|DN|R|L|OS|MS|SN";
$column_configuration = "";
$slice_column_configuration = "";

$PersonTags=$api->GetPersonTags (array('person_id'=>$plc->person['person_id']));
//print_r($PersonTags);
foreach ($PersonTags as $ptag) {
        if ($ptag['tagname'] == 'columnconf')
        {
                $column_configuration = $ptag['value'];
                $conf_tag_id = $ptag['person_tag_id'];
        }
}

//print("column configuration = ".$column_configuration);

$nodesconf_exists = false;
if ($column_configuration == "")
{
        $column_configuration = "nodes;default";
        $nodesconf_exists = true;
}
else {
        $slice_conf = explode(";",$column_configuration);
        for ($i=0; $i<count($slice_conf); $i++ ) {
                if ($slice_conf[$i] == "nodes")
                {
                        $i++;
                        $slice_column_configuration = $slice_conf[$i];
                        $nodesconf_exists = true;
                        break;
                }
                else
                {
                        $i++;
                        $slice_column_configuration = $slice_conf[$i];
                }
        }
}

if ($nodesconf_exists == false)
        $column_configuration = $column_configuration.";nodes;default";
//panos: need to define an "empty" configuration here (for the moment A column
//will be added by default the first time


if ($slice_column_configuration == "" || $slice_column_configuration == "default")
        $full_configuration = $default_configuration;
	
else
        $full_configuration = $default_configuration."|".$slice_column_configuration;

//print("full configuration = ".$full_configuration);

// fetch nodes 
$node_fixed_columns=array('hostname','node_type','site_id','node_id','boot_state','last_contact','interface_ids','peer_id', 'slice_ids');

$fix_columns = array();
if (plc_is_admin()) 
$fix_columns[]=array('tagname'=>'node_id', 'header'=>'ID', 'type'=>'string', 'title'=>'The ID the node');
$fix_columns[]=array('tagname'=>'hostname', 'header'=>'hostname', 'type'=>'string', 'title'=>'The name of the node');
$fix_columns[]=array('tagname'=>'peer_id', 'header'=>'AU', 'type'=>'string', 'title'=>'Authority');
$fix_columns[]=array('tagname'=>'run_level', 'header'=>'ST', 'type'=>'string', 'title'=>'Status');
//$fix_columns[]=array('tagname'=>'node_type', 'header'=>'RES', 'type'=>'string', 'title'=>'Reservable');


$visibletags = new VisibleTags ($api, 'node');
$visibletags->columns();
$tag_columns = $visibletags->headers();

$extra_columns = array();
$extra_columns[]=array('tagname'=>'sitename', 'header'=>'SN', 'type'=>'string', 'title'=>'Site name', 'fetched'=>true);
$extra_columns[]=array('tagname'=>'domain', 'header'=>'DN', 'type'=>'string', 'title'=>'Toplevel domain name', 'fetched'=>true);
$extra_columns[]=array('tagname'=>'ipaddress', 'header'=>'IP', 'type'=>'string', 'title'=>'IP Address', 'fetched'=>true);
$extra_columns[]=array('tagname'=>'fcdistro', 'header'=>'OS', 'type'=>'string', 'title'=>'Operating system', 'fetched'=>false);

$ConfigureColumns =new PlekitColumns($full_configuration, $fix_columns, $tag_columns, $extra_columns);

$visiblecolumns = $ConfigureColumns->node_tags();

$node_columns=array_merge($node_fixed_columns,$visiblecolumns);

//$visibletags = new VisibleTags ($api, 'node');
//$visiblecolumns = $visibletags->column_names();
//print("<p>OLD");
//print_r($visiblecolumns);
//$node_columns=array_merge($node_fixed_columns,$visiblecolumns);


// server-side filtering - set pattern in $_GET for filtering on hostname
if ($pattern) {
  $node_filter['hostname']=$pattern;
  $title .= " matching " . $pattern;
 } else {
  $node_filter['hostname']="*";
 }

// server-side selection on peerscope
$peerscope=new PeerScope($api,$_GET['peerscope']);
$node_filter=array_merge($node_filter,$peerscope->filter());
$title .= ' - ' . $peerscope->label();

if ($site_id) {
  $sites=$api->GetSites(array($site_id));
  $site=$sites[0];
  $name=$site['name'];
  $login_base=$site['login_base'];
  $title .= t_site($site);
  $tabs []= tab_site($site);
  $node_filter['site_id']=array($site_id);
}

if ($slice_id) {
  $slices=$api->GetSlices(array($slice_id),array('node_ids','name'));
  $slice=$slices[0];
  $title .= t_slice($slice);
  $tabs []= tab_slice($slice);
  $node_filter['node_id'] = $slice['node_ids'];
 }

// person_id is set : this is mostly oriented towards people managing several sites
if ($person_id) {
  // avoid doing a useless call to GetPersons if the person_id is already known though $plc,
  // as this is mostly done for the 'all my sites nodes' link
  if ($person_id == plc_my_person_id()) { 
    $person=plc_my_person();
    $site_ids = plc_my_site_ids();
  } else {
    // fetch the person's site_ids
    $persons = $api->GetPersons(array('person_id'=>$person_id),array('person_id','email','site_ids'));
    $person=$persons[0];
    $site_ids=$person['site_ids'];
  }
  $title .= t_person($person);
  $node_filter['site_id']=$site_ids;
 }

// go
$nodes=$api->GetNodes($node_filter,$node_columns);

// build site_ids - interface_ids
$site_ids=array();
$interface_ids=array();
if ($nodes) foreach ($nodes as $node) {
  $site_ids []= $node['site_id'];
  $interface_ids = array_merge ($interface_ids,$node['interface_ids']);
}

// fetch related interfaces
$interface_columns=array('ip','node_id','interface_id');
$interface_filter=array('is_primary'=>TRUE,'interface_id'=>$interface_ids);
$interfaces=$api->GetInterfaces($interface_filter,$interface_columns);

$interface_hash=array();
foreach ($interfaces as $interface) $interface_hash[$interface['node_id']]=$interface;

// fetch related sites
$site_columns=array('site_id','login_base');
$site_filter=array('site_id'=>$site_ids);
$sites=$api->GetSites($site_filter,$site_columns);

$site_hash=array();
foreach ($sites as $site) $site_hash[$site['site_id']]=$site;

// --------------------
drupal_set_title($title);

plekit_linetabs($tabs);

if ( ! $nodes ) {
  drupal_set_message ('No node found');
  return;
 }
  
$nifty=new PlekitNifty ('','objects-list','big');
$nifty->start();
$headers = array (); $offset=0;
$notes=array();
$notes [] = "For information about the different columns please see the <b>node table layout</b> tab above or <b>mouse over</b> the column headers";


/*
// fixed columns
if (plc_is_admin()) { 
  $short="I"; $long="node_id"; $type='int'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
  $offset=1; 
 }
$short="P"; $long="Peer"; $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
$short="D"; $long="toplevel domain name"; $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
$headers["Site"]="string";
$headers["Hostname"]="string";
$short="IP"; $long="IP Address"; $type='sortIPAddress'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
$short="ST"; $long=Node::status_footnote(); $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
$short="SL"; $long="Number of slivers"; $type='int'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";

$headers=array_merge($headers,$visibletags->headers());
$notes=array_merge($notes,$visibletags->notes());
$short="?"; $long="extra status info"; $type='string'; 
	$headers[$short]=array('type'=>$type,'title'=>$long); $notes []= "$short = $long";
*/

$info_header = array();
$short="?"; $long="extra status info"; $type='string'; 
$info_header[$short]=array('type'=>$type,'title'=>$long, 'label'=>'?', 'header'=>'?', 'visible'=>true); 
//$notes []= "$short = $long";
//$info_header["?"] = "none";
$headers = array_merge($ConfigureColumns->get_headers(),$info_header);

//print("<p>HEADERS");
//print_r($headers);

$toggle_nodes=new PlekitToggle('nodes-column-configuration',
                               "Node table layout",
                               array('visible'=>'1'));
$toggle_nodes->start();
print("<div id='debug'></div>");
print("<input type='hidden' id='slice_id' value='nodes' />");
print("<input type='hidden' id='person_id' value='".$plc->person['person_id']."' />");
print("<input type='hidden' id='conf_tag_id' value='".$conf_tag_id."' />");
print("<input type='hidden' id='column_configuration' value='".$slice_column_configuration."' />");
print("<br><input type='hidden' size=80 id='full_column_configuration' value='".$column_configuration."' />");
//print("<input type='hidden' id='previousConf' value='".$slice_column_configuration."'></input>");
print("<input type='hidden' id='defaultConf' value='".$default_configuration."'></input>");
$ConfigureColumns->configuration_panel_html(true);
$ConfigureColumns->javascript_init();
$toggle_nodes->end();

$table_options = array('notes'=>$notes,
                       'search_width'=>15,
                       'pagesize'=>20,
                        'configurable'=>true);

# initial sort on hostnames
$table=new PlekitTable ("nodes",$headers,3+$offset, $table_options);
$table->start();

$peers = new Peers ($api);
// write rows
foreach ($nodes as $node) {
  //$node_obj = new Node ($node);
  $hostname=$node['hostname'];
  $node_id=$node['node_id'];
  $site_id=$node['site_id'];
  $site=$site_hash[$site_id];
  $login_base = $site['login_base'];
  $ip=$interface_hash[$node['node_id']]['ip'];
  $interface_id=$interface_hash[$node['node_id']]['interface_id'];
  $peer_id=$node['peer_id'];
  
  $table->row_start();
  $table->cell($node['node_id'], array('display'=>'none'));
  if (plc_is_admin()) $table->cell(l_node_t($node_id,$node_id));
  $table->cell (l_node_t($node_id,$hostname));
  $peers->cell ($table,$peer_id);
  //$table->cell (topdomain($hostname));
  $node['domain'] = topdomain($hostname);
  //$table->cell (l_site_t($site_id,$login_base));
  $node['sitename'] = l_site_t($site_id,$login_base);
  //$table->cell (l_interface_t($interface_id,$ip),array('only-if'=> !$peer_id));
  $node['ipaddress'] = l_interface_t($interface_id,$ip);
  list($label,$class) = Node::status_label_class_($node);
  $table->cell ($label,array('class'=>$class));
  //$table->cell (count($node['slice_ids']));
  //foreach ($visiblecolumns as $tagname) $table->cell($node[$tagname]);
  $ConfigureColumns->cells($table, $node);
  $table->cell (node_status($node));
  $table->row_end();
  
}

$table->end();
$nifty->end();

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>

