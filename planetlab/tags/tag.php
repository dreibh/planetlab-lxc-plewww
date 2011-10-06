<?php

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
require_once 'linetabs.php';
require_once 'details.php';
require_once 'table.php';
require_once 'form.php';
require_once 'toggle.php';

// -------------------- 
// recognized URL arguments
$tag_type_id=intval($_GET['id']);
if ( ! $tag_type_id ) { 
  plc_error('Malformed URL - id not set'); 
  return;
 }

// --- decoration
$title="Tag Type";
$tabs=array();
$tabs []= tab_tags();

// -------------------- 
$tag_types= $api->GetTagTypes( array( $tag_type_id ) );
$tag_type=$tag_types[0];
  
$tagname=$tag_type['tagname'];
$role_ids= $tag_type['role_ids'];
$roles= $tag_type['roles'];
$description= $tag_type['description'];
$category=$tag_type['category'];

// where is it used 
$filter=array('tag_type_id'=>$tag_type_id);
$node_tags=$api->GetNodeTags($filter);
$interface_tags=$api->GetInterfaceTags($filter);
$person_tags=$api->GetPersonTags($filter);
$site_tags=$api->GetSiteTags($filter);
// split slice tags into 3 families, whether this applies to the whole slice, or a nodegroup, or a node
// using filters for this purpose does not work out very well, maybe a bug in the filter stuff
// anyway this is more efficient, and we compute the related node(groups) in the same pass
$slice_tags=$api->GetSliceTags(array_merge($filter));
$count_slice=0;
$count_nodegroup=0;
$nodegroup_ids=array();
$count_node=0;
$node_ids=array();
foreach ($slice_tags as $slice_tag) {
  if ($slice_tag['node_id']) {
    $node_ids []= $slice_tag['node_id'];
    $count_node += 1;
  } else if ($slice_tag['nodegroup_id']) {
    $nodegroup_ids []= $slice_tag['nodegroup_id'];
    $count_nodegroup += 1;
  } else {
    $count_slice += 1;
  }
}

$nodes=$api->GetNodes($node_ids,array('hostname','node_id'));
$node_hash=array();
foreach ($nodes as $node) $node_hash[$node['node_id']]=$node;
$nodegroups=$api->GetNodeGroups($nodegroup_ids,array('groupname','nodegroup_id'));
$nodegroup_hash=array();
foreach ($nodegroups as $nodegroup) $nodegroup_hash[$nodegroup['nodegroup_id']]=$nodegroup;


drupal_set_title("Details for tag type $tagname");
plekit_linetabs($tabs);

//////////////////// details
$toggle = new PlekitToggle ('details','Details');
$toggle->start();
$can_update=plc_is_admin();
$details=new PlekitDetails ($can_update);

$details->form_start(l_actions(),array("action"=>"update-tag-type",
				       "tag_type_id"=>$tag_type_id));
$details->start();
$details->th_td("Name",$tagname,"tagname");
$details->th_td("Category",$category,"category",array('width'=>30));
$details->th_td("Description",$description,"description",array('width'=>40));

if ($can_update) 
  $details->tr_submit('update-tag-type',"Update tag type");

$details->space();
$details->th_td("Used in nodes",count($node_tags));
$details->th_td("Used in interfaces",count($interface_tags));
$details->th_td("Used in slices/node",$count_node);
$details->th_td("Used in slices/nodegroup",$count_nodegroup);
$details->th_td("Used in slices",$count_slice);

$details->end();
$details->form_end();
$toggle->end();

//////////////////// roles
$form=new PlekitForm(l_actions(), array("tag_type_id"=>$tag_type_id));
$form->start();

$toggle=new PlekitToggle ('roles',count_english($roles,"role"),array('visible'=>get_arg('show_roles')));
$toggle->start();

if (! $roles) plc_warning ("This tag type has no role !");

$can_manage_roles= plc_is_admin();

$headers=array("Role"=>"string");
if ($can_manage_roles) $headers [plc_delete_icon()]="none";

$table_options=array('search_area'=>false,'pagesize_area'=>false,'notes_area'=>false);
$table=new PlekitTable("tag_roles",$headers,0,$table_options);  
$table->start();
  
// construct array of role objs
$role_objs=array();
for ($n=0; $n<count($roles); $n++) {
  $role_objs[]= array('role_id'=>$role_ids[$n], 'name'=>$roles[$n]);
}

if ($role_objs) foreach ($role_objs as $role_obj) {
    $table->row_start();
    $table->cell($role_obj['name']);
    if ($can_manage_roles) $table->cell ($form->checkbox_html('role_ids[]',$role_obj['role_id']));
    $table->row_end();
  }

// footers : the remove and add buttons
if ($can_manage_roles) {
  
  // remove
  $table->tfoot_start();
  if ($roles) {
    $table->row_start();
    $table->cell($form->submit_html("remove-roles-from-tag-type","Remove Roles"),
		 array('hfill'=>true,'align'=>'right'));
    $table->row_end();
  }

  // add
  // compute the roles that can be added
  if ($can_manage_roles) 
    // all roles
    $exclude_role_ids=array();
  else
    // all roles except admin and pi
    $exclude_role_ids=array(10,20);
  $possible_roles = roles_except($api->GetRoles(),$exclude_role_ids);
  $roles_to_add = roles_except ($possible_roles,$role_ids);
  if ( $roles_to_add ) {
    $selectors=$form->role_selectors($roles_to_add);
    $table->row_start();
    $add_role_left_area=$form->select_html("role_id",$selectors,array('label'=>"Choose role"));
    // add a role : the button
    $add_role_right_area=$form->submit_html("add-role-to-tag-type","Add role");
    $table->cell ($add_role_left_area . $add_role_right_area,
		  array('hfill'=>true,'align'=>'right'));
    $table->row_end();
  }
}
$table->end();
$toggle->end();
$form->end();

//////////////////// the 5 flavours of objects that the tag may be attached to

// common options for tables below
$table_options=array('notes_area'=>false, 'pagesize_area'=>false, 'search_width'=>10);

// xxx could outline values corresponding to a nodegroup
if (count ($node_tags)) {
  $toggle=new PlekitToggle('tag_nodes',"Nodes");
  $toggle->start();
  $table=new PlekitTable ("tag_nodes",array("Hostname"=>"string","value"=>"string"),0,$table_options);
  $table->start();
  foreach ($node_tags as $node_tag) {
    $table->row_start();
    $table->cell(href(l_node($node_tag['node_id']),$node_tag['hostname']));
    $table->cell($node_tag['value']);
    $table->row_end();
  }
  $table->end();
  $toggle->end();
 }

if (count ($interface_tags)) {
  $toggle=new PlekitToggle('tag_interfaces',"Interfaces");
  $toggle->start();
  $table=new PlekitTable ("tag_interfaces",array("IP"=>"IPAddress","value"=>"string"),0,$table_options);
  $table->start();
  foreach ($interface_tags as $interface_tag) {
    $table->row_start();
    $table->cell(href(l_interface($interface_tag['interface_id']),$interface_tag['ip']));
    $table->cell($interface_tag['value']);
    $table->row_end();
  }
  $table->end();
  $toggle->end();
 }

if (count ($site_tags)) {
  $toggle=new PlekitToggle('tag_sites',"Sites");
  $toggle->start();
  $table=new PlekitTable("tag_sites",array("L"=>"login_base","value"=>"string"),0,$table_options);
  $table->start();
  foreach ($site_tags as $site_tag) {
    $table->row_start();
    $table->cell(href(l_site($site_tag['site_id']),$site_tag['login_base']));
    $table->cell($site_tag['value']);
    $table->row_end();
  }
  $table->end();
  $toggle->end();
}

if (count ($person_tags)) {
  $toggle=new PlekitToggle('tag_persons',"Persons");
  $toggle->start();
  $table=new PlekitTable("tag_persons",array("E"=>"email","value"=>"string"),0,$table_options);
  $table->start();
  foreach ($person_tags as $person_tag) {
    $table->row_start();
    $table->cell(href(l_person($person_tag['person_id']),$person_tag['email']));
    $table->cell($person_tag['value']);
    $table->row_end();
  }
  $table->end();
  $toggle->end();
}

if (count ($slice_tags)) {
  $toggle=new PlekitToggle('tag_slices',"Slice tags");
  $toggle->start();
  $headers=array();
  $headers["slice"]='string';
  $headers["value"]='string';
  $headers["node"]='string';
  $headers["nodegroup"]='string';
  $table=new PlekitTable ("tag_slices",$headers,0,$table_options);
  $table->start();
  foreach ($slice_tags as $slice_tag) {
    $table->row_start();
    $table->cell(href(l_slice($slice_tag['slice_id']),$slice_tag['name']));
    $table->cell($slice_tag['value']);

    $node_text="all";
    if ($slice_tag['node_id']) {
      $node_id=$slice_tag['node_id'];
      $node=$node_hash[$node_id];
      $node_text=l_node_obj($node);
    }
    $table->cell($node_text);

    $nodegroup_text="all";
    if ($slice_tag['nodegroup_id']) {
      $nodegroup_id=$slice_tag['nodegroup_id'];
      $nodegroup=$nodegroup_hash[$nodegroup_id];
      $nodegroup_text=l_nodegroup_obj($nodegroup);
    }
    $table->cell($nodegroup_text);

    $table->row_end();
  }
  $table->end();
  $toggle->end();
 }

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
