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
require_once 'form.php';
require_once 'toggle.php';
require_once 'plc_objects.php';

// -------------------- 
// recognized URL arguments
$node_id=intval($_GET['id']);
if ( ! $node_id ) { plc_error('Malformed URL - id not set'); return; }

////////////////////
// Get all columns as we focus on only one entry
$nodes= $api->GetNodes( array($node_id));

if (empty($nodes)) {
  drupal_set_message ("Node " . $node_id . " not found");
  return;
 }

$node=$nodes[0];
// node info
$hostname= $node['hostname'];
$boot_state= $node['boot_state'];
$site_id= $node['site_id'];
$model= $node['model'];
$version= $node['version'];
$node_type = $node['node_type'];

// arrays of ids of node info
$slice_ids= $node['slice_ids'];
$conf_file_ids= $node['conf_file_ids'];
$interface_ids= $node['interface_ids'];
$nodegroup_ids= $node['nodegroup_ids'];
$pcu_ids= $node['pcu_ids'];

// get peers
$peer_id = $node['peer_id'];
$peers=new Peers ($api);

// gets site info
$sites= $api->GetSites( array( $site_id ) );
$site=$sites[0];
$site_name= $site['name'];
$site_node_ids= $site['node_ids'];

// hash node_id=>hostname for this site's nodes
$site_node_hash=array();
if( !empty( $site_node_ids ) ) {
  // get site node info basics
  $site_nodes= $api->GetNodes( $site_node_ids );
    
  foreach( $site_nodes as $site_node ) {
    $site_node_hash[$site_node['node_id']]= $site_node['hostname'];
  }
 }
  
// gets slice info for each slice
if( !empty( $slice_ids ) )
  $slices= $api->GetSlices( $slice_ids, array( "slice_id", "name" , "peer_id" ) );

// get interface info
if( !empty( $interface_ids ) )
  $interfaces= $api->GetInterfaces( $interface_ids );

// gets nodegroup info
if( !empty( $nodegroup_ids ) )
  $nodegroups= $api->GetNodeGroups( $nodegroup_ids, array("groupname","tag_type_id","value"));

// xxx Thierry : remaining stuff
// (*) events: should display the latest events relating to that node.
// disabling call to GetEvents, that gets the session deleted in the DB
// (*) conf_files: is fetched but not displayed
if( !empty( $conf_file_ids ) )
  $conf_files= $api->GetConfFiles( $conf_file_ids );
// (*) idem for PCUs
// gets pcu and port info key to both is $pcu_id
// turning this off: GetPCUs is not allowed to users, and we don't show PCUs yet anyway
//if( !empty( $pcu_ids ) )
//  $PCUs= $api->GetPCUs( $pcu_ids );

//////////////////// display node info

drupal_set_title("Details for node " . $hostname);
$local_peer= ! $peer_id;

  
// extra privileges to admins, and (pi||tech) on this site
$privileges = (plc_is_admin () && $local_peer) || ( plc_in_site($site_id) && ( plc_is_pi() || plc_is_tech()));
  
$tabs=array();
// available actions
$tabs [] = tab_nodes_site($site_id);
$tabs [] = tab_site($site_id);
//$tabs [] = tab_nodes();

if ( $local_peer  && $privileges ) {
    
  $tabs["Add Interface"]=array('url'=>l_interface_add($node_id),
			       'bubble'=>"Define new network interface on $hostname");
  $tabs['Delete'] = array ('url'=>l_actions(),
			   'method'=>'POST',
			   'values'=>array('action'=>'delete-node','node_id'=>$node_id),
			   'bubble'=>"Delete node $hostname",
			   'confirm'=>'Are you sure to delete ' . $hostname);
  $tabs["Events"]=array_merge(tablook_event(),
			      array('url'=>l_event("Node","node",$node_id),
				    'bubble'=>"Events for node $hostname"));
  $tabs["Comon"]=array_merge(tablook_comon(),
			     array('url'=>l_comon("node_id",$node_id),
				   'bubble'=>"Comon page about node $hostname"));
 }

plekit_linetabs($tabs);

// show gray background on foreign objects : start a <div> with proper class
$peers->block_start ($peer_id);
  
$toggle = new PlekitToggle ('node',"Details",
			    array('bubble'=>'Display and modify details for that node',
				  'visible'=>get_arg('show_details',true)));
$toggle->start();

$details=new PlekitDetails($privileges);
$details->start();
if ( ! $local_peer) {
  $details->th_td("Peer",$peers->peer_link($peer_id));
  $details->space();
 }

$details->form_start(l_actions(),array("action"=>"update-node", "node_id"=>$node_id));
// xxx can hostname really be changed like this without breaking the rest, bootcd .. ?
//$details->th_td("Hostname",$hostname,"hostname"); 
$details->th_td("Hostname",$hostname); 
$details->th_td("Model",$model,"model");
$details->tr_submit("submit","Update Node");
$details->form_end();
if ($privileges) $details->space();

$details->th_td("Type",$node_type);
$details->th_td("Version",$version);
// let's use plc_objects
$Node = new Node($node);
$details->th_td("Date created",$Node->dateCreated());
$details->th_td("Last contact",$Node->lastContact());
$details->th_td("Last update",$Node->lastUpdated());

// boot area
$details->space ();
if ( ! ($local_peer && $privileges)) {
  // just display it
  $boot_value=$boot_state;
 } else {
  $boot_value="";
  $boot_form = new PlekitForm (l_actions(), array("node_id"=>$node_id,
					       "action"=>"node-boot-state"));
  $boot_value .= $boot_form->start_html();
  $states = array( 'boot'=>'Boot', 'safeboot'=>'SafeBoot', 'failboot'=>'FailBoot', 
		   'disabled' => 'Disabled', 'install'=>'Install', 'reinstall'=>'Reinstall');
  $selectors=array();
  foreach ($states as $dbname=>$displayname) { 
    $selector=array("display"=>$displayname, "value"=>$dbname);
    if ($dbname == $boot_state) $selector['selected']=true;
    $selectors []= $selector;
  }
  $boot_value .= $boot_form->select_html("boot_state",$selectors,array('autosubmit'=>true));
  $boot_value .= $boot_form->end_html();
 }
$details->th_td ("Boot state",$boot_value);

// same here for the download area
if ( $local_peer  && $privileges) {

  $download_value="";
  $download_form = new PlekitForm (l_actions_download(),array("node_id"=>$node_id));
  $download_value .= $download_form->start_html();
  $selectors = array( 
		     array("display"=>"-- All in one images --","disabled"=>true),
		     array("value"=>"download-node-iso","display"=>"Download ISO image for $hostname"),
		     array("value"=>"download-node-usb","display"=>"Download USB image for $hostname"),
		     array("display"=>"-- Floppy + generic image --","disabled"=>true),
		     array("value"=>"download-node-floppy","display"=>"Download Floppy file for $hostname"),
		     array("value"=>"download-generic-iso","display"=>"Download generic ISO image (requires floppy)"),
		     array("value"=>"download-generic-usb","display"=>"Download generic USB image (requires floppy)"));
  $download_value .= $download_form->select_html("action",$selectors,
						 array('label'=>"Download mode",'autosubmit'=>true));
  $download_value .= $download_form->end_html();
  $details->th_td ("Download",$download_value);
 }

// site info and all site nodes
$details->space ();
$details->th_td("Site",l_site_t($site_id,$site_name));
		   
// build list of node links
$nodes_area=array();
foreach ($site_node_hash as $hash_node_id => $hash_hostname) {
  $nodes_area []= l_node_t($hash_node_id,$hash_hostname);
}
$details->th_tds ("All site nodes",$nodes_area);

$details->end ();
$toggle->end();

$form=new PlekitForm (l_actions(), array('node_id'=>$node_id));
$form->start();

//////////////////////////////////////////////////////////// slivers
{
  $toggle=new PlekitToggle ('slices',count_english_warning($slices,'sliver'),
			    array('bubble'=>'Review slices running on that node',
				  'visible'=>get_arg('show_slices',false)));
  $toggle->start();
  if ( ! $slices  ) {
    plc_warning ("This node is not associated to any slice");
  } else {
    $headers=array();
    $headers['Peer']="string";
    $headers['Name']="string";
    $headers['Sliver']="string";
    $reasonable_page=10;
    $table_options = array('notes_area'=>false,"search_width"=>10,'pagesize'=>$reasonable_page);
    if (count ($slices) <= $reasonable_page) {
      $table_options['search_area']=false;
      $table_options['pagesize_area']=false;
    }
    $table=new PlekitTable("node_slices",$headers,1,$table_options);
    $table->start();

    foreach ($slices as $slice) {
      $table->row_start();
      $table->cell ($peers->shortname($peer_id));
      $table->cell (l_slice_t ($slice['slice_id'],$slice['name']));
      $table->cell (l_sliver_t ($node_id,$slice['slice_id'],'view'));
      $table->row_end();
    }
    $table->end();
  }
  $toggle->end();
}

//////////////////////////////////////////////////////////// Tags
// tags section
if ( $local_peer ) {
  
  $tags=$api->GetNodeTags (array('node_id'=>$node_id));
  function get_tagname ($tag) { return $tag['tagname'];}
  // xxx looks like tech-only see an error here, 
  // might be that GetNodeTags is not accessible or something
  $tagnames = array_map ("get_tagname",$tags);
  $nodegroups_hash=plc_nodegroup_global_hash($api,$tagnames);
  
  $toggle = new PlekitToggle ('tags',count_english_warning($tags,'tag'),
			      array('bubble'=>'Inspect and set tags on that node',
				    'visible'=>get_arg('show_tags',false)));
  $toggle->start();

  $headers=array("Name"=>"string",
		 "Value"=>"string",
		 "Nodegroup"=>"string",
		 );
  if (plc_is_admin()) $headers[plc_delete_icon()]="none";
  
  $table_options=array("notes_area"=>false,"pagesize_area"=>false,"search_width"=>10);
  $table=new PlekitTable("node_tags",$headers,0,$table_options);
  $table->start();
  if ($tags) foreach ($tags as $tag) {
      // does this match a nodegroup ?
      $nodegroup_name="n/a";
      $nodegroup_key=$tag['tagname'] . "=" . $tag['value'];
      $nodegroup=$nodegroups_hash[$nodegroup_key];
      if ($nodegroup) $nodegroup_name=l_nodegroup_t($nodegroup['nodegroup_id'],$nodegroup['groupname']);
      $table->row_start();
      $table->cell(l_tag_obj($tag));
      $table->cell($tag['value']);
      $table->cell($nodegroup_name);
      // the remove checkbox
      if (plc_is_admin()) $table->cell ($form->checkbox_html('node_tag_ids[]',$tag['node_tag_id']));
      $table->row_end();
    }
  
  if ($privileges) {
    $table->tfoot_start();

    // remove tag 
    $table->row_start();
    $table->cell($form->submit_html("delete-node-tags","Remove Tags"),
		 // use the whole columns and right adjust
		 array('hfill'=>true,'align'=>'right'));
    $table->row_end();

    // set tag area
    $table->row_start();
    // get list of tag names in the node/* category    
    $all_tags= $api->GetTagTypes( array ("category"=>"node*"), array("tagname","tag_type_id"));
    // xxx cannot use onchange=submit() - would need to somehow pass action name 
    function tag_selector ($tag) { return array("display"=>$tag['tagname'],"value"=>$tag['tag_type_id']); }
    $selector=array_map("tag_selector",$all_tags);
    $table->cell($form->select_html("tag_type_id",$selector,array('label'=>"Choose")));
    $table->cell($form->text_html("value","",array('width'=>8)));
    $table->cell($form->submit_html("set-tag-on-node","Set Tag"),array('columns'=>2,'align'=>'left'));
    $table->row_end();
  }
  
  $table->end();
  $toggle->end();
}

//////////////////////////////////////////////////////////// interfaces
if ( $local_peer ) {
  $toggle=new PlekitToggle ('interfaces',count_english_warning($interfaces,'interfaces'),
			    array('bubble'=>'Inspect and tune interfaces on that node',
				  'visible'=>get_arg('show_interfaces',false)));
  $toggle->start();
  // display interfaces
  if( ! $interfaces ) {
    echo '<p>';
    plc_warning_html("This node has no interface");
    echo "Please add an interface to make this a usable PLC node.</p>\n";
  } else {
    $headers=array();

    $headers["IP"]="IPAddress";
    $headers["Method"]="string";
    $headers["Type"]="string";
    $headers["MAC"]="string";
    $headers["bw limit"]="FileSize";
    // a single symbol, marking 'p' for primary and a delete button for non-primary
    if ( $privileges ) $headers[plc_delete_icon()]='string';

    $table_options=array('search_area'=>false,"pagesize_area"=>false,'notes_area'=>false);
    $table=new PlekitTable("node_interfaces",$headers,2,$table_options);
    $table->start();
	
    foreach ( $interfaces as $interface ) {
      $interface_id= $interface['interface_id'];
      $interface_ip= $interface['ip'];
      $interface_broad= $interface['broadcast'];
      $interface_primary= $interface['is_primary'];
      $interface_network= $interface['network'];
      $interface_dns1= $interface['dns1'];
      $interface_dns2= $interface['dns2'];
      $interface_hostname= $interface['hostname'];
      $interface_netmaks= $interface['netmask'];
      $interface_gatewary= $interface['gateway'];
      $interface_mac= $interface['mac'];
      $interface_bwlimit= $interface['bwlimit'];
      $interface_type= $interface['type'];
      $interface_method= $interface['method'];

      $table->row_start();
      $table->cell(l_interface_t($interface_id,$interface_ip));
      $table->cell($interface_method);
      $table->cell($interface_type);
      $table->cell($interface_mac);
      $table->cell($interface_bwlimit);
      if ( $privileges ) {
	if ($interface_primary) {
	  $table->cell(plc_bubble("p","Cannot delete a primary interface"));
	} else {
	  $table->cell ($form->checkbox_html('interface_ids[]',$interface_id));
	}
      }
      $table->row_end();
    }
    if ($privileges) {
      $table->tfoot_start();
      $table->row_start();
      $add_button=new PlekitFormButton (l_interface_add($node_id),"add","Add Interface","GET");
      // we should have 6 cols, use 3 for the left (new) and the rest for the right (remove)
      $table->cell($add_button->html(),array('columns'=> 3,'align'=>'left'));
      $table->cell($form->submit_html("delete-interfaces","Remove Interfaces"), 
		   array('columns'=>$table->columns()-3,'align'=>'right'));
      $table->row_end();
    }
    $table->end();
  }
  $toggle->end();
 }

$form->end();

////////////////////////////////////////////////////////////
$peers->block_end($peer_id);

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
