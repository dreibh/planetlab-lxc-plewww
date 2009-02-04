<?php

drupal_set_html_head('
<script type="text/javascript" src="/planetlab/tablesort/tablesort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/customsort.js"></script>
<script type="text/javascript" src="/planetlab/tablesort/paginate.js"></script>
<script type="text/javascript" src="/planetlab/js/plc_tables.js"></script>
<link href="/planetlab/css/plc_tables.css" rel="stylesheet" type="text/css" />
');

////////////////////////////////////////
// table_id: <table>'s id tag - WARNING : do not use '-' in table ids as it's used for generating javascript code
// headers: an associative array "label"=>"type" 
// column_sort: the column to sort on at load-time
// options : an associative array to override options 
//  - search_area : boolean (default true)
//  - pagesize_area : boolean (default true)
//  - notes_area : boolean (default true)
//  - search_width : size in chars of the search text dialog
//  - notes : an array of additional notes
//  - pagesize: the initial pagination size
//  - pagesize_def: the page size when one clicks the pagesize reset button
//  - max_pages: the max number of pages to display in the paginator
//  - footers: an array of table rows (<tr> will be added) for building the table's tfoot area

class PlcTable {
  // mandatory
  var $table_id;
  var $headers;
  var $column_sort;
  // options
  var $search_area;   // boolean (default true)
  var $pagesize_area; // boolean (default true)
  var $notes_area;    // boolean (default true)
  var $search_width;  // size in chars of the search text dialog
  var $pagesize;       // the initial pagination size
  var $pagesize_def;  // the page size when one clicks the pagesize reset button
  var $max_pages;     // the max number of pages to display in the paginator
  var $notes;         // an array of additional notes
  var $footers;       // an array of table rows (<tr> will be added) for building the table's tfoot area

  function PlcTable ($table_id,$headers,$column_sort,$options=NULL) {
    $this->table_id = $table_id;
    $this->headers = $headers;
    $this->column_sort = $column_sort;

    $this->search_area = true;
    $this->pagesize_area = true;
    $this->notes_area = true;
    $this->search_width = 40;
    $this->pagesize = 25;
    $this->pagesize_def = 999;
    $this->max_pages = 10;
    $this->notes = array();
    $this->footers = array();

    $this->set_options ($options);
  }

  function set_options ($options) {
    if ( ! $options)
      return;
    if (array_key_exists('search_area',$options)) $this->search_area=$options['search_area'];
    if (array_key_exists('pagesize_area',$options)) $this->pagesize_area=$options['pagesize_area'];
    if (array_key_exists('notes_area',$options)) $this->notes_area=$options['notes_area'];
    if (array_key_exists('search_width',$options)) $this->search_width=$options['search_width'];
    if (array_key_exists('pagesize',$options)) $this->pagesize=$options['pagesize'];
    if (array_key_exists('pagesize_def',$options)) $this->pagesize_def=$options['pagesize_def'];
    if (array_key_exists('max_pages',$options)) $this->max_pages=$options['max_pages'];

    if (array_key_exists('notes',$options)) $this->notes=array_merge($this->notes,$options['notes']);
    if (array_key_exists('footers',$options)) $this->footers=array_merge($this->footers,$options['footers']);
  }

  ////////////////////
  public function start () {
    $paginator=$this->table_id."_paginator";
    $classname="paginationcallback-".$paginator;
    $classname.=" max-pages-" . $max_pages;
    $classname.=" paginate-" . $pagesize;
  // instantiate paginator callback
    print <<< EOF
<script type="text/javascript"> 
function $paginator (opts) { plc_table_paginator (opts,"$this->table_id"); }
</script>
<br/>
<table id="$this->table_id" cellpadding="0" cellspacing="0" border="0" 
class="plc_table sortable-onload-$this->column_sort rowstyle-alt colstyle-alt no-arrow $classname">
<thead>
EOF;

  if ($this->pagesize_area)
    print $this->pagesize_area_text ();
  if ($this->search_area) 
    print $this->search_area_text ();

  print "<tr>";
  foreach ($this->headers as $label => $type) {
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
  // for convenience, the options that apply to the footer can be passed here
  // typically footers or notes will add up to the ones provided so far
  // makes code more readable, as preparing the footer before the table is displayed is confusing
  function end ($options=NULL) {
    $this->set_options($options);
    print $this->foot_text();
    if ($this->notes_area) 
      print $this->notes_area_text();
  }
		    
  ////////////////////
  function pagesize_area_text () {
    $width=count($this->headers);
    $pagesize_text_id = $this->table_id . "_pagesize";
    $result= <<< EOF
<tr class=pagesize_area><td class=pagesize_area colspan=$width><form class='pagesize'>
   <input class='pagesize_input' type='text' id="$pagesize_text_id" value=$this->pagesize 
      onkeyup='plc_pagesize_set("$this->table_id","$this->pagesize_text_id", $this->pagesize);' 
      size=3 maxlength=3 /> 
  <label class='pagesize_label'> items/page </label>   
  <img class='table_reset' src="/planetlab/icons/clear.png" 
      onmousedown='plc_pagesize_reset("$this->table_id","$pagesize_text_id",$this->pagesize_def);' />
</form></td></tr>
EOF;
    return $result;
}

  ////////////////////
  function search_area_text () {
    $width=count($this->headers);
    $search_text_id = $this->table_id . "_search";
    $search_reset_id = $this->table_id . "_search_reset";
    $search_and_id = $this->table_id . "_search_and";
    $result = <<< EOF
<tr class=search_area><td class=search_area colspan=$width><form class='table_search'>
   <label class='table_search_label'> Search </label> 
   <input class='table_search_input' type='text' id='$search_text_id'
      onkeyup='plc_table_filter("$this->table_id","$search_text_id","$search_and_id");'
      size=$this->search_width maxlength=256 />
   <label>and</label>
   <input id='$search_and_id' class='table_search_and' 
      type='checkbox' checked='checked' 
      onchange='plc_table_filter("$this->table_id","$search_text_id","$search_and_id");' />
   <img class='table_reset' src="/planetlab/icons/clear.png" 
      onmousedown='plc_table_filter_reset("$this->table_id","$search_text_id","$search_and_id");'>
</form></td></tr>
EOF;
    return $result;
  }

  ////////////////////////////////////////
  function foot_text () {
    $result="";
    $result .= "</tbody><tfoot>\n";
    if ($this->footers) 
      foreach ($this->footers as $footer) 
	$result .="<tr>$footer</tr>\n";
    $result .= "</tfoot></table>\n";
    return $result;
  }

  ////////////////////////////////////////
  function notes_area_text () {
    $default_notes =  array(
	"Enter & or | in the search area to alternate between <bold>AND</bold> and <bold>OR</bold> search modes",
	"Hold down the shift key to select multiple columns to sort");

    if ($this->notes)
      $notes=$this->notes;
    else
      $notes=array();
    $notes=array_merge($notes,$default_notes);
    if (! $notes)
      return "";
    $result = "";
    $result .= "<p class='plc_table_note'> <span class='plc_table_note_title'>Notes</span>\n";
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
  public function cell_text ($cell) {
    return "<td>$cell</td>";
  }
  public function cell ($cell) {
    print $this->cell_text($cell);
  }
  
  public static function td_text ($text,$colspan=0,$align=NULL) {
    $result="";
    $result .= "<td";
    if ($colspan) $result .= " colspan=$colspan";
    if ($align) $result .= " style='text-align:$align'";
    $result .= ">$text</td>";
    return $result;
  }

}

?>
