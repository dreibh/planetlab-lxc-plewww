<?php

drupal_set_html_head('
<script type="text/javascript" src="/planetlab/tablesort/tablesort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/customsort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/paginate.js"></script>
<script type="text/javascript" src="/planetlab/js/plc_tables.js"></script>
<link href="/planetlab/css/plc_tables.css" rel="stylesheet" type="text/css" />
');


//// hash to retrieve the columns and options as passed at table-creation time
// this means that table_id's need to be different across the page,
// which is required anyway for the search and pagesize areas to work properly
$plc_table_hash=array();

////////////////////////////////////////
function plc_table_cell($cell) {
  printf ('<td class="plc_table"> %s </td>',$cell);
}

////////////////////////////////////////
// table_id: <table>'s id tag
// headers: an associative array "label"=>"type" 
// column_sort: the column to sort on at load-time
// options : an associative array to override options (should be passed to both _stsart and _end)
//  - search_area : boolean (default true)
//  - notes_area : boolean (default true)
//  - notes : an array of additional notes
//  - pagesize: the initial pagination size
//  - pagesize_def: the page size when one clicks the pagesize reset button
//  - max_pages: the max number of pages to display in the paginator
function plc_table_start ($table_id, $headers, $column_sort, $options=NULL) {
  if ( ! $options ) $options = array();
  global $plc_table_hash;
  $plc_table_hash[$table_id]=array($headers,$options);
  $search_area = array_key_exists('search_area',$options) ? $options['search_area'] : true;
  $max_pages = array_key_exists('max_pages',$options) ? $options['max_pages'] : 10;
  $pagesize = array_key_exists('pagesize',$options) ? $options['pagesize'] : 25;
  $pagesize_def = array_key_exists('pagesize_def',$options) ? $options['pagesize_def'] : 999;

  if ($search_area) 
    plc_table_search_area($table_id,$pagesize,$pagesize_def);
  plc_table_head($table_id,$headers,$column_sort,$max_pages,$pagesize);
}

function plc_table_end ($table_id) {
  global $plc_table_hash;
  list($headers,$options) = $plc_table_hash[$table_id];

  plc_table_foot($options);
  $notes_area = array_key_exists('notes_area',$options) ? $options['notes_area'] : true;
  if ($notes_area) 
    plc_table_notes($options);
}
		    
////////////////////
function plc_table_search_area ($table_id,$pagesize,$pagesize_def) {
  $pagesize_text_id = $table_id . "_pagesize";
  $search_text_id = $table_id . "_search";
  $search_reset_id = $table_id . "_search_reset";
  $search_and_id = $table_id . "_search_and";
  print <<< EOF
<table class='table_dialogs'> <tr>
<td class='table_flushleft'>
<form class='pagesize'>
   <input class='pagesize_input' type='text' id="$pagesize_text_id" value=$pagesize 
      onkeyup='plc_pagesize_set("$table_id","$pagesize_text_id", $pagesize);' 
      size=3 maxlength=3 /> 
  <label class='pagesize_label'> items/page </label>   
  <img class='table_reset' src="/planetlab/icons/clear.png" 
      onmousedown='plc_pagesize_reset("$table_id","$pagesize_text_id",$pagesize_def);'>
</form>
</td>

<td class='table_flushright'> 
<form class='table_search'>
   <label class='table_search_label'> Search </label> 
   <input class='table_search_input' type='text' id='$search_text_id'
      onkeyup='plc_table_filter("$table_id","$search_text_id","$search_and_id");'
      size=40 maxlength=256 />
   <label>and</label>
   <input id='$search_and_id' class='table_search_and' 
      type='checkbox' checked='checked' 
      onchange='plc_table_filter("$table_id","$search_text_id","$search_and_id");' />
   <img class='table_reset' src="/planetlab/icons/clear.png" 
      onmousedown='plc_table_filter_reset("$table_id","$search_text_id","$search_and_id");'>
</form>
</td>
</tr></table>
EOF;
}

////////////////////////////////////////
function plc_table_head ($table_id,$headers,$column_sort,$max_pages,$pagesize) {
  $paginator=$table_id."_paginator";
  $classname="paginationcallback-".$paginator;
  $classname.=" max-pages-" . $max_pages;
  $classname.=" paginate-" . $pagesize;
  print <<< EOF
<!-- instantiate paginator callback -->
<script type="text/javascript"> 
function $paginator (opts) { plc_table_paginator (opts,"$table_id"); }
</script>
<br/>
<table id="$table_id" cellpadding="0" cellspacing="0" border="0" 
class="plc_table sortable-onload-$column_sort rowstyle-alt colstyle-alt no-arrow $classname">
<thead>
<tr>
EOF;

  foreach ($headers as $label => $type) {
    if ($type == "none" ) {
      $class="";
    } else {
      if ($type == "string") $type="";
      if ($type == "int") $type="";
      if ($type == "float") $type="";
      $class="sortable";
      if ( ! empty($type)) $class .= "-sort" . $type;
    }
    printf ('<th class="%s plc_table">%s</th>',$class,$label);
  }

  print <<< EOF
</tr>
</thead>
<tbody>
EOF;
}

////////////////////////////////////////
function plc_table_foot ($options) {
  print "</tbody><tfoot>";
  print $options['footer'];
  print "</tfoot></table>\n";
}

////////////////////////////////////////
function plc_table_notes ($options) {
  print <<< EOF
<p class='plc_filter_note'> 
Notes: Enter & or | in the search area to alternate between <bold>AND</bold> and <bold>OR</bold> search modes
<br/> 
Hold down the shift key to select multiple columns to sort 
EOF;
  if (array_key_exists('notes',$options)) {
    foreach ($options['notes'] as $line) {
      print "<br/>" . $line . "\n";
    }
  }
  print "</p>";
}

////////////////////////////////////////
function plc_table_title ($text) {
  print "<h2> $text </h2>\n";
}

function plc_table_row_start ($id="") {
  if ( $id) {
    printf ('<tr id="%s">',$id);
  } else {
    print '<tr>';
  }
}
function plc_table_row_end () {
  print "</tr>\n";
}

?>

