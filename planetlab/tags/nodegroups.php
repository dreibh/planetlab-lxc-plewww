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
require_once 'linetabs.php';
require_once 'table.php';
require_once 'form.php';

// -------------------- 
// recognized URL arguments
$pattern=get_array($_GET, 'pattern');

// --- decoration
$title="Nodegroups";
$tabs=array();
$tabs []= tab_tags();

// -------------------- 
$node_filter=array();


// fetch objs
$nodegroup_columns=array("nodegroup_id","groupname","tagname","value","node_ids","tag_type_id");

// server-side filtering - set pattern in $_GET for filtering on hostname
if ($pattern) {
  $nodegroup_filter['groupname']=$pattern;
  $title .= " matching " . $pattern;
 } else {
  $nodegroup_filter['groupname']="*";
 }

// go
$nodegroups=$api->GetNodeGroups($nodegroup_filter,$nodegroup_columns);

// --------------------
drupal_set_title($title);

plekit_linetabs($tabs);

if ( ! $nodegroups ) {
  drupal_set_message ('No node group found');
 }
  
$headers=array();
$notes=array();

$headers['group name']='string';
$headers['tag name']='string';
$headers['tag value']='string';
$headers['# N']='int';
$notes []= '# N = number of nodes in the group';

$headers["Id"]="int";
if (plc_is_admin()) $headers[plc_delete_icon()]="none";

$form=new PlekitForm(l_actions(),NULL);
$form->start();
# initial sort on groupname
$table=new PlekitTable("nodegroups",$headers,0,array('notes'=>$notes));
$table->start();

foreach ($nodegroups as $nodegroup) {
  $table->row_start();
  $nodegroup_id=$nodegroup['nodegroup_id'];
  $table->cell (href(l_nodegroup($nodegroup_id),$nodegroup['groupname']));
  // yes, a nodegroup is not a tag, but knows enough for this to work
  $table->cell (l_tag_obj($nodegroup));
  $table->cell ($nodegroup['value']);
  $table->cell (count($nodegroup['node_ids']));
  $table->cell ($nodegroup_id);
  $table->cell ($form->checkbox_html('nodegroup_ids[]',$nodegroup_id));
  $table->row_end();
}

$table->tfoot_start();

$table->row_start();
$table->cell($form->submit_html ("delete-nodegroups","Remove groups"),
	     array('hfill'=>true,'align'=>'right'));
$table->row_end();

// an inline area to add a tag type
$table->row_start();

// build the tagname selector
$relevant_tags = $api->GetTagTypes( array("category"=>'*node*'));
function selector_argument ($tt) { return array('display'=>$tt['tagname'],"value"=>$tt['tag_type_id']); }
$selectors=array_map("selector_argument",$relevant_tags);
$tagname_input=$form->select_html("tag_type_id",$selectors,array('label'=>"Tag Name"));


$table->cell($form->text_html('groupname',''));
$table->cell($tagname_input);
$table->cell($form->text_html('value',''));
$table->cell($form->submit_html("add-nodegroup","Add"),3);
$table->row_end();

$table->end();
$form->end();

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
