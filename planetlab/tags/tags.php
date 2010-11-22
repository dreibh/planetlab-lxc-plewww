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
require_once 'table.php';
require_once 'form.php';

// -------------------- 
// recognized URL arguments
$pattern=$_GET['pattern'];

// --- decoration
$title="Tag Types";
$tabs=array();
$tabs []= tab_tags();
$tabs []= tab_nodes_local();
$tabs []= tab_slices();

// -------------------- 
drupal_set_title($title);
plekit_linetabs($tabs);

$tag_type_columns = array( "tag_type_id", "tagname", "category", "description", "roles");

$tag_type_filter=NULL;
if ($pattern) 
  $tag_type_filter['category']=$pattern;

// get types
$tag_types= $api->GetTagTypes($tag_type_filter, $tag_type_columns);
  
$headers=array();
$notes=array();
// delete button
$headers['Name']="string";
$headers['Description']="string";
$headers['Category']="string";
$headers['Roles']="string";

// xxx ref count would be helpful but seem too expensive to compute at this stage 
// the individual tag page show those ref counts per type

$headers["Id"]="int";
if (plc_is_admin()) $headers[plc_delete_icon()]="none";

$form=new PlekitForm(l_actions(),NULL);
$form->start();

$table = new PlekitTable("tags",$headers,0,array('notes'=>$notes));
$table->start();

$description_width=40;

foreach( $tag_types as $tag_type ) {

  $table->row_start();
  $tag_type_id=$tag_type['tag_type_id'];
  $table->cell(href(l_tag($tag_type_id),$tag_type['tagname']));
  $table->cell(wordwrap($tag_type['description'],$description_width,"<br/>"));
  $table->cell($tag_type['category']);
  $table->cell(plc_vertical_table ($tag_type['roles']));
  $table->cell($tag_type_id);
  if (plc_is_admin()) 
    $table->cell ($form->checkbox_html('tag_type_ids[]',$tag_type_id));
  $table->row_end();
}

if (plc_is_admin()) {
  $table->tfoot_start();

  $table->row_start();
  $table->cell($form->submit_html ("delete-tag-types","Remove tags"),
	       array('hfill'=>true,'align'=>'right'));
  $table->row_end();

  // an inline area to add a tag type
  $table->row_start();
  
  $table->cell($form->text_html('tagname',''));
  $table->cell($form->textarea_html('description','',$description_width,2));
  $table->cell($form->text_html('category',''));
  $table->cell("<span class='note_roles'>add roles later</span>");
  $table->cell($form->submit_html("add-tag-type","Add"),2);
  $table->row_end();
 }

$table->end();
$form->end();

//plekit_linetabs ($tabs,"bottom");

// Print footer
include 'plc_footer.php';

?>
