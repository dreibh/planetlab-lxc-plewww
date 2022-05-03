<?php

  // $Id$

require_once 'prototype.php';

drupal_set_html_head('
<script type="text/javascript" src="/plekit/tablesort/tablesort.js"></script>
<script type="text/javascript" src="/plekit/tablesort/customsort.js"></script>
<script type="text/javascript" src="/plekit/tablesort/plc_customsort.js"></script>
<script type="text/javascript" src="/plekit/tablesort/paginate.js"></script>
<script type="text/javascript" src="/plekit/table/table.js"></script>
<link href="/plekit/table/table.css" rel="stylesheet" type="text/css" />
');

////////////////////////////////////////
// table_id: <table>'s id tag - WARNING : do not use '-' in table ids as it's used for generating javascript code
// headers: an associative array; the values can take several forms
//      simple/legacy form is "label"=>"type"
//      more advanced form is "label"=>options, it self a dict with the following known keys
//         (*) 'type': the type for sorting; this is passed to the javascript layer for custom sorting
//		default is to use 'text', custom sort functions can be specified with e.g. 'type'=>'sortAlphaNumericBottom'
//		a special case is for type to be 'date-format' like e.g. 'type'=>'date-dmy'
//		setting type to 'none' gives an non-sortable column
//	   (*) 'title': if set, this is used in the "Sort on ``<title>''" bubble
// sort_column: the column to sort on at load-time - set to negative number for no onload- sorting
// options : an associative array to override options
//  - bullets1 : set to true if you want decorative bullets in column 1 (need white background)
//  - stripes : use diferent colors for odd and even rows
//  - caption : a caption for the table -- never used I'm afraid
//  - search_area : boolean (default true)
//  - pagesize_area : boolean (default true)
//  - notes_area : boolean (default true)
//  - search_width : size in chars of the search text dialog
//  - pagesize: the initial pagination size
//  - pagesize_def: the page size when one clicks the pagesize reset button
//  - max_pages: the max number of pages to display in the paginator
//  - notes : an array of additional notes
//  - debug: enables debug callbacks (prints out on console.log)

class PlekitTable {
  // mandatory
  var $table_id;
  var $headers;
  var $sort_column;
  // options
  var $bullets1;      // boolean - default false - display decorative bullets in column 1
  var $stripes;	      // boolean - default true - use different colors for odd and even rows
  var $caption;	      // string - never used so far
  var $search_area;   // boolean (default true)
  var $pagesize_area; // boolean (default true)
  var $notes_area;    // boolean (default true)
  var $search_width;  // size in chars of the search text dialog
  var $pagesize;      // the initial pagination size
  var $pagesize_def;  // the page size when one clicks the pagesize reset button
  var $max_pages;     // the max number of pages to display in the paginator
  var $notes;         // an array of additional notes
  var $debug;	      // set to true for enabling various log messages on console.log

  // internal
  var $has_tfoot;

  function __construct ($table_id,$headers,$sort_column,$options=NULL) {
    $this->table_id = $table_id;
    $this->headers = $headers;
    $this->sort_column = $sort_column;

    $this->bullets1 = true;
    $this->stripes=true;
    $this->caption='';
    $this->search_area = true;
    $this->pagesize_area = true;
    $this->notes_area = true;
    $this->search_width = 40;
    $this->pagesize = 250;
    $this->pagesize_def = 9999;
    $this->max_pages = 10;
    $this->notes = array();
    $this->debug = false;

    $this->set_options ($options);

    // internal
    $this->has_tfoot=false;
  }

  function set_options ($options) {
    if ( ! $options)
      return;
    if (array_key_exists('bullets1',$options)) $this->bullets1=$options['bullets1'];
    if (array_key_exists('stripes',$options)) $this->stripes=$options['stripes'];
    if (array_key_exists('caption',$options)) $this->caption=$options['caption'];
    if (array_key_exists('search_area',$options)) $this->search_area=$options['search_area'];
    if (array_key_exists('pagesize_area',$options)) $this->pagesize_area=$options['pagesize_area'];
    if (array_key_exists('notes_area',$options)) $this->notes_area=$options['notes_area'];
    if (array_key_exists('search_width',$options)) $this->search_width=$options['search_width'];
    if (array_key_exists('pagesize',$options)) $this->pagesize=$options['pagesize'];
    if (array_key_exists('pagesize_def',$options)) $this->pagesize_def=$options['pagesize_def'];
    if (array_key_exists('max_pages',$options)) $this->max_pages=$options['max_pages'];
    if (array_key_exists('notes',$options)) $this->notes=array_merge($this->notes,$options['notes']);
    if (array_key_exists('debug',$options)) $this->debug=$options['debug'];
  }

  public function columns () {
    return count ($this->headers);
  }

  ////////////////////
  public function start () {
    $paginator=$this->table_id."_paginator";
    $classname="paginationcallback-".$paginator;
    $classname.=" max-pages-" . $this->max_pages;
    $classname.=" paginate-" . $this->pagesize;
    if ($this->bullets1) { $classname .= " bullets1"; }
    if ($this->stripes) { $classname .= " rowstyle-alt"; }
    if ($this->sort_column >= 0) { $classname .= " sortable-onload-$this->sort_column"; }

    // instantiate paginator callback
    print "<script type='text/javascript'> function $paginator (opts) { plekit_table_paginator (opts,'$this->table_id'); } </script>\n";

    // instantiate debug hooks if needed
    if ($this->debug) {
      $cb_init = $this->table_id."_init";
      print "<script type='text/javascript'> function $cb_init () { plc_message ('sorting table $this->table_id'); } </script>\n";
      $classname .= " sortinitiatedcallback-$cb_init";
      $cb_comp = $this->table_id."_comp";
      print "<script type='text/javascript'> function $cb_comp () { plc_message ('table $this->table_id sorted'); } </script>\n";
      $classname .= " sortcompletecallback-$cb_comp";
    }
    // start actual table
    print "<table id='$this->table_id' class='plekit_table colstyle-alt no-arrow $classname'><thead>\n";

    if ($this->pagesize_area)
      print $this->pagesize_area_html ();
    if ($this->search_area)
      print $this->search_area_html ();

    if ($this->caption)
      print "<caption> $this->caption </caption>";
    print "<tr>";
    foreach ($this->headers as $label => $colspec) {
      // which form is being used
      if (is_array($colspec)) {
	$type=$colspec['type'];
	$title=ucfirst($colspec['title']);
      } else {
	// simple/legacy form
	$type=$colspec;
	$title=NULL;
      }
      switch ($type) {
      case "none" :
	$class=""; break;
      case "string": case "int": case "float":
	$class="sortable"; break;
      case ( strpos($type,"date-") == 0):
	$class="sortable-" . $type; break;
      default:
	$class="sortable-sort" . $type; break;
      }
      $title_part=$title ? "title=\"$title\"" : "";
      print ("<th class=\"$class plekit_table\" $title_part>$label</th>\n");
    }

    print "</tr></thead><tbody>";
  }

  ////////////////////
  // for convenience, the options that apply to the bottom area can be passed here
  // typically notes will add up to the ones provided so far, and to the default ones
  // xxx default should be used only if applicable
  function end ($options=NULL) {
    $this->set_options($options);
    print $this->bottom_html();
    if ($this->notes_area)
      print $this->notes_area_html();
  }

  ////////////////////
  function pagesize_area_html () {
    $width=count($this->headers);
    $pagesize_text_id = $this->table_id . "_pagesize";
    $result= <<< EOF
<tr class='pagesize_area'><td class='pagesize_area' colspan='$width'>
<form class='pagesize' action='satisfy_xhtml_validator'><fieldset>
   <input class='pagesize_input' type='text' id="$pagesize_text_id" value='$this->pagesize'
      onkeyup='plekit_pagesize_set("$this->table_id","$pagesize_text_id", $this->pagesize);'
      size='3' maxlength='4' />
  <label class='pagesize_label'> items/page </label>
  <img class='reset' src="/planetlab/icons/clear.png" alt="reset visible size"
      onmousedown='plekit_pagesize_reset("$this->table_id","$pagesize_text_id",$this->pagesize_def);' />
</fieldset></form></td></tr>
EOF;
    return $result;
}

  ////////////////////
  function search_area_html () {
    $width=count($this->headers);
    $search_text_id = $this->table_id . "_search";
    $search_reset_id = $this->table_id . "_search_reset";
    $search_and_id = $this->table_id . "_search_and";
    $result = <<< EOF
<tr class='search_area'><td class='search_area' colspan='$width'>
<div class='search'><fieldset>
   <label class='search_label'> Search </label>
   <input class='search_input' type='text' id='$search_text_id'
      onkeyup='plekit_table_filter("$this->table_id","$search_text_id","$search_and_id");'
      size='$this->search_width' maxlength='256' />
   <label>and</label>
   <input id='$search_and_id' class='search_and'
      type='checkbox' checked='checked'
      onchange='plekit_table_filter("$this->table_id","$search_text_id","$search_and_id");' />
   <img class='reset' src="/planetlab/icons/clear.png" alt="reset search"
      onmousedown='plekit_table_filter_reset("$this->table_id","$search_text_id","$search_and_id");' />
</fieldset></div></td></tr>
EOF;
    return $result;
  }

  //////////////////// start a <tfoot> section
  function tfoot_start () { print $this->tfoot_start_html(); }
  function tfoot_start_html () {
    $this->has_tfoot=true;
    return "</tbody><tfoot>";
  }

  ////////////////////////////////////////
  function bottom_html () {
    $result="";
    if ($this->has_tfoot)
      $result .= "</tfoot>";
    else
      $result .= "</tbody>";
    $result .= "</table>\n";
    return $result;
  }

  ////////////////////////////////////////
  function notes_area_html () {
    $search_notes =
      array("Enter &amp; or | in the search area to switch between <span class='bold'>AND</span> and <span class='bold'>OR</span> search modes");
    $sort_notes =
      array ("Hold down the shift key to select multiple columns to sort");

    if ($this->notes)
      $notes=$this->notes;
    else
      $notes=array();
    $notes=array_merge($notes,$sort_notes);
    if ($this->search_area)
      $notes=array_merge($notes,$search_notes);
    if (! $notes)
      return "";
    $result = "";
    $result .= "<p class='table_note'> <span class='table_note_title'>Notes</span>\n";
    foreach ($notes as $note)
      $result .= "<br/>$note\n";
    $result .= "</p>";
    return $result;
  }

  ////////////////////////////////////////
  function row_start ($id=NULL,$class=NULL) {
    print "<tr";
    if ( $id) print (" id=\"$id\"");
    if ( $class) print (" class=\"$class\"");
    print ">\n";
  }

  function row_end () {
    print "</tr>\n";
  }

  ////////////////////
  // supported options:
  // (*) only-if : if set and false, then print 'n/a' instead of (presumably void) $text
  // (*) class
  // (*) columns
  // (*) hfill
  // (*) align
  public function cell ($text,$options=NULL) { print $this->cell_html ($text,$options); }
  public function cell_html ($text, $options=NULL) {
    if (isset ($options['only-if']) && ! $options['only-if'] )
      $text = "n/a";
    $html = "";
    $html .= "<td";
    // xxx this should not happen, but we see it sometimes - ignoring for now
    if ($options && ! is_array($options)) {
      //print_r("<br>options=");
      //var_dump($options);
    }
  if ($options && is_array($options)) {
      $option = get_array($options, 'class');
      if ($option)
        $html .= " class='$option'";
      $option = get_array($options, 'columns');
        if ($option) $html .= " colspan='$option'";
      $option = get_array($options, 'hfill');
        if ($option) $html .= " colspan='" . $this->columns() . "'";
      $option = get_array($options, 'align');
        if ($option) $html .= " style='text-align:$option'";
    }
    $html .= ">$text</td>";
    return $html;
  }

}

?>
