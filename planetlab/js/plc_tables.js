/* $Id$ */

/* when a table gets paginated, displays context info */
function plc_table_paginator (opts,tablename) {

  if(!("currentPage" in opts)) { return; }
    
  var p = document.createElement('p');
  var t = document.getElementById(tablename+'-fdtablePaginaterWrapTop');
  var b = document.getElementById(tablename+'-fdtablePaginaterWrapBottom');

  /* when there's no visible entry, the pagination code removes the wrappers */
  if ( (!t) || (!b) ) return;

  /* get how many entries are matching:
     opts.visibleRows only holds the contents of the current page
     so we store the number of matching entries in the tbody's classname
     see plc_table_tbody_matching
  */
  var totalMatches = opts.totalRows;
  var tbody=document.getElementById(tablename).getElementsByTagName("tbody")[0];
  var cn=tbody.className;
  if (cn.match (/matching-\d+/)) {
    totalMatches=cn.match(/matching-\d+/)[0].replace("matching-","");
  } 

  var label;

  var matches_text;
  if (totalMatches != opts.totalRows) {
    matches_text = totalMatches + "/" + opts.totalRows;
  } else {
    matches_text = opts.totalRows;
  }
  var first = ((opts.currentPage-1) * opts.rowsPerPage) +1;
  var last = Math.min((opts.currentPage * opts.rowsPerPage),totalMatches);
  var items_text = "Items [" + first + " - " + last + "] of " + matches_text;
  var page_text = "Page " + opts.currentPage + " of " + Math.ceil(totalMatches / opts.rowsPerPage);
  label = items_text + " -- " + page_text;

  p.className = "paginationText";    
  p.appendChild(document.createTextNode(label));

  /*  t.insertBefore(p.cloneNode(true), t.firstChild); */
  b.appendChild(p);
}


/* locates a table from its id and alters the classname to reflect new table size */
function plc_pagesize_set (table_id,size_id,def_size) {
  var table=document.getElementById(table_id);
  var size_area=document.getElementById(size_id);
  if ( ! size_area.value ) {
    size_area.value=def_size;
  }
  var size=size_area.value;
  table.className=table.className.replace(/paginate-\d+/,"paginate-"+size); 
  tablePaginater.init(table_id);
}

function plc_pagesize_reset(table_id, size_id, size) {
  var table=document.getElementById(table_id);
  var size_area=document.getElementById(size_id);
  size_area.value=size;
  table.className=table.className.replace(/paginate-\d+/,"paginate-"+size); 
  tablePaginater.init(table_id);
}
  
/* set or clear the ' invisibleRow' in the tr's classname, according to visible */
function plc_table_row_visible (row,visible) {
  var cn=row.className;
  /* clear */
  cn=cn.replace(" invisibleRow","");
  if (! visible) cn += " invisibleRow";
  row.className=cn;
}

/* maintain the number of matching entries in the <tbody> element's classname */
function plc_table_tbody_matching (tbody, matching) {
  var new_cn="matching-" + matching;
  var cn=tbody.className;
  if (cn.match("matching-")) {
    cn=cn.replace(/matching-\d+/,new_cn);
  } else {
    cn=cn + " " + new_cn;
  }
  cn=cn.replace(/^ +/,"");
  tbody.className=cn;
}

/* scan the table, and mark as visible 
   the rows that match (either AND or OR the patterns) */
function plc_table_filter (table_id,pattern_id,and_id) {
  var tbody = document.getElementById(table_id).getElementsByTagName("tbody")[0];
  var rows=tbody.rows;
  var pattern_area = document.getElementById(pattern_id);
  var pattern_text = pattern_area.value;
  var row_index, row, cells, cell_index, cell, visible;
  var pattern,i;
  var matching_entries=0;
  var and_button=document.getElementById(and_id);
  var and_if_true=and_button.checked;

  // remove whitespaces at the beginning and end
  pattern_text = pattern_text.replace(/[ \t]+$/,"");
  pattern_text = pattern_text.replace(/^[ \t]+/,"");
  // normnalize to lowercase
  pattern_text = pattern_text.toLowerCase();
  
  if (pattern_text.indexOf ("&") != -1) {
    pattern_text = pattern_text.replace(/&/," ");
    pattern_area.value=pattern_text;
    and_button.checked=true;
    return;
  } else if (pattern_text.indexOf ("|") != -1 ) {
    pattern_text = pattern_text.replace(/\|/," ");
    pattern_area.value=pattern_text;
    and_button.checked=false;
    return;
  }
    
  // var counter=0;
  //  window.console.log ("entering plc_table_filter " + table_id);

  var re_brackets = new RegExp ('<[^>]*>','g');
  var patterns = pattern_text.split(" ");

  for (row_index = 0; row=rows[row_index]; row_index++) {
      cells=row.cells;
    
    /*empty pattern */
    if (patterns.length == 0) {
      visible=true;
    } else if (and_if_true) {
      /* AND mode: all patterns must match */
      visible=true;
      for (i in patterns) {
	var pattern_matched=false;
	pattern=new RegExp(patterns[i],"i");
	for (cell_index = 0; cell=cells[cell_index]; cell_index++) {
	  var against=cell.innerHTML;
	  against=against.replace(re_brackets,'');
	  //counter++;
	  //window.console.log ("plc_table_filter is matching " + against + " against " + pattern);
	  if ( against.match(pattern)) {
	    pattern_matched=true;
	    // alert ('AND matched! p='+pattern+' c='+cell.innerHTML);
	    break;	  
	  }
	}
	if ( ! pattern_matched ) visible=false;
      }
    } else {
      /* OR mode: any match is good enough */
      visible=false;
      for (cell_index = 0; cell=cells[cell_index]; cell_index++) {
	for (i in patterns) {
	  pattern=patterns[i];
	  //counter++;
	  if (cell.innerHTML.toLowerCase().match(pattern)) {
	    visible=true;
	    // alert ('OR matched! p='+pattern+' c='+cell.innerHTML);
	    break;
	  }
	}
      }
    }
    //window.console.log ("plc_table_filter has done " + counter + " matches");
    plc_table_row_visible(row,visible);
    if (visible) matching_entries +=1;
  }
  plc_table_tbody_matching(tbody,matching_entries);
  tablePaginater.init(table_id);
}

function plc_table_filter_reset (table_id, pattern_id,and_id) {
  /* reset pattern */
  document.getElementById(pattern_id).value="";
  plc_table_filter (table_id, pattern_id,and_id);
}
