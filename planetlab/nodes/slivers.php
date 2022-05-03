<?php

// Require login
require_once 'plc_login.php';

// Get session and API handles
require_once 'plc_session.php';
global $plc, $api;

// Print header
require_once 'plc_drupal.php';
drupal_set_title('Sliver Tags');
include 'plc_header.php';

// Common functions
require_once 'plc_functions.php';
require_once 'table.php';
require_once 'toggle.php';
require_once 'form.php';


// if slice and node ids are passed display slivers and tags
if( get_array($_GET, 'slice_id') && get_array($_GET, 'node_id') ) {
    $slice_id = $_GET['slice_id'];
    $node_id = $_GET['node_id'];


    // slice info
    $slice = $api->GetSlices( array( intval( $slice_id ) ), array( "name" ) );
    $slice = $slice[0];

    // node info
    $node = $api->GetNodes( array( intval( $node_id ) ), array( "hostname" ) );
    $node = $node[0];

    drupal_set_title("Sliver tags on node " . $node['hostname'] . " and slice " . $slice['name']);

    $peer_id= $slice['peer_id'];
    $local_peer = ! $peer_id;
    $privileges = ( $local_peer && (plc_is_admin()  || plc_is_pi() || $am_in_slice));

    // get the slivers for this node
    $tags = $api->GetSliceTags( array( "node_id"=>intval( $node_id ), "slice_id"=>intval( $slice_id ) ),
                                  array( "slice_tag_id", "tagname", "value", "min_role_id", "description" ) );

    $toggle = new PlekitToggle ('sliver-tags',count_english_warning($tags,'tag'),
                                array('bubble'=>'Inspect and set tags on the sliver',
                                      'visible'=>true));
    $toggle->start();

    $headers=array(
        "Name"=>"string",
        "Value"=>"string",
        "Description"=>"string");
    if ($privileges) $headers[plc_delete_icon()]="none";

    $table_options = array("notes_area"=>false,"pagesize_area"=>false,"search_width"=>10);
    $table = new PlekitTable("sliver_tags",$headers,'0',$table_options);
    $form = new PlekitForm(l_actions(),array('slice_id'=>$slice_id, 'node_id'=>$node_id, 'sliver_action'=>true));
    $form->start();
    $table->start();

    if ($tags) foreach ($tags as $tag) {
        $table->row_start();
        $table->cell($tag['tagname']);
        $table->cell($tag['value']);
        $table->cell($tag['description']);
        $table->row_end();
    }

    if ($privileges) {
        $table->tfoot_start();
        $table->row_start();
        $table->cell($form->submit_html ("delete-slice-tags","Remove selected"),
                     array('hfill'=>true,'align'=>'right'));
        $table->row_end();

        $table->row_start();
        function tag_selector ($tag) {
            return array("display"=>$tag['tagname'],"value"=>$tag['tag_type_id']);
        }
        $all_tags= $api->GetTagTypes( array ("category"=>"slice*"), array("tagname","tag_type_id"));
        $selector_tag=array_map("tag_selector",$all_tags);

        $table->cell($form->select_html("tag_type_id",$selector_tag,array('label'=>"Choose Tag")));
        $table->cell($form->text_html("value","",array('width'=>8)));
        $table->cell($form->submit_html("add-slice-tag","Set Tag"),array('columns'=>2,'align'=>'left'));
        $table->row_end();
    }

    $form->end();
    $table->end();
    $toggle->end();
}

// Print footer
include 'plc_footer.php';

?>
