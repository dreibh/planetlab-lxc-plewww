<?php

drupal_set_html_head('
<script type="text/javascript" src="/planetlab/tablesort/tablesort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/customsort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/paginate.js"></script>
<script type="text/javascript" src="/planetlab/js/plc_tables.js"></script>
<link href="/planetlab/css/plc_tables.css" rel="stylesheet" type="text/css" />
');


//// hash to retrieve the headers and options as passed at table-creation time
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
//  - pagesize_area : boolean (default true)
//  - notes_area : boolean (default true)
//  - notes : an array of additional notes
//  - pagesize: the initial pagination size
//  - pagesize_def: the page size when one clicks the pagesize reset button
//  - max_pages: the max number of pages to display in the paginator
//  - footers: a list of table rows (<tr> will be added) for building the table's tfoot area
function plc_table_start ($table_id, $headers, $column_sort, $options=NULL) {
  if ( ! $options ) $options = array();
  global $plc_table_hash;
  $plc_table_hash[$table_id]=array($headers,$options);
  $search_area = array_key_exists('search_area',$options) ? $options['search_area'] : true;
  $pagesize_area = array_key_exists('pagesize_area',$options) ? $options['pagesize_area'] : true;
  $max_pages = array_key_exists('max_pages',$options) ? $options['max_pages'] : 10;
  $pagesize = array_key_exists('pagesize',$options) ? $options['pagesize'] : 25;
  $pagesize_def = array_key_exists('pagesize_def',$options) ? $options['pagesize_def'] : 999;

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
EOF;

  if ($pagesize_area) plc_table_pagesize_area ($table_id,$headers,$pagesize, $pagesize_def);
  if ($search_area) plc_table_search_area ($table_id, $headers);

  print "<tr>";
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

////////////////////
function plc_table_pagesize_area ($table_id,$headers,$pagesize,$pagesize_def) {
  $width=count($headers);
  $pagesize_text_id = $table_id . "_pagesize";
  print <<< EOF
<tr class=pagesize_area><td class=pagesize_area colspan=$width><form class='pagesize'>
   <input class='pagesize_input' type='text' id="$pagesize_text_id" value=$pagesize 
      onkeyup='plc_pagesize_set("$table_id","$pagesize_text_id", $pagesize);' 
      size=3 maxlength=3 /> 
  <label class='pagesize_label'> items/page </label>   
  <img class='table_reset' src="/planetlab/icons/clear.png" 
      onmousedown='plc_pagesize_reset("$table_id","$pagesize_text_id",$pagesize_def);' />
</form></td></tr>
EOF;
}

////////////////////
function plc_table_search_area ($table_id,$headers) {
  $width=count($headers);
  $search_text_id = $table_id . "_search";
  $search_reset_id = $table_id . "_search_reset";
  $search_and_id = $table_id . "_search_and";
  print <<< EOF
<tr class=search_area><td class=search_area colspan=$width><form class='table_search'>
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
</form></td></tr>
EOF;
}

////////////////////////////////////////
// for convenience, the options that apply to the footer only can be passed in plc_table_end()
// they add up to the ones provided to the begin clause
// makes code more readable, as preparing the footer before the table is displayed is confusing
function plc_table_end ($table_id,$options_end=NULL) {
  global $plc_table_hash;
  list($headers,$options) = $plc_table_hash[$table_id];
  if ($options_end) 
    $options=array_merge($options,$options_end);

  plc_table_foot($options);
  $notes_area = array_key_exists('notes_area',$options) ? $options['notes_area'] : true;
  if ($notes_area) 
    plc_table_notes($options);
}
		    
////////////////////////////////////////
function plc_table_foot ($options) {
  print "</tbody><tfoot>";
  if ($options['footers']) 
    foreach ($options['footers'] as $footer) 
      print "<tr> $footer </tr>";
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

function plc_table_td_text ($text,$colspan=0,$align=NULL) {
  $result="";
  $result .= "<td";
  if ($colspan) $result .= " colspan=$colspan";
  if ($align) $result .= " style='text-align:$align'";
  $result .= ">$text</td>";
  return $result;
}

?>

