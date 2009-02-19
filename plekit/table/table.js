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
     so we store the number of matching entries in the tbody's 'matching' attribute
  */
  var totalMatches = opts.totalRows;
  var tbody=document.getElementById(tablename).getElementsByTagName("tbody")[0];
  var matching=tbody['matching'];
  if (matching) totalMatches = matching;

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

// from a cell, extract visible text by removing <> and cache in 'plc_text' attribute
var re_brackets = new RegExp ('<[^>]*>','g');

function plc_table_cell_text (cell) {
  if (cell['plc_text']) return cell['plc_text'];
  var text = cell.innerHTML;
  // remove what's between <>
  text = text.replace(re_brackets,'');
  cell['plc_text'] = text;
  return text;
}

/* scan the table, and mark as visible 
   the rows that match (either AND or OR the patterns) */
function plc_table_filter (table_id,pattern_id,and_id) {
  var tbody = document.getElementById(table_id).getElementsByTagName("tbody")[0];
  var rows=tbody.rows;
  var pattern_area = document.getElementById(pattern_id);
  var pattern_text = pattern_area.value;
  var row_index, row, cells, cell_index, cell, visible;
  var matching_entries=0;
  var and_button=document.getElementById(and_id);
  var and_if_true=and_button.checked;

  // remove whitespaces at the beginning and end
  pattern_text = pattern_text.replace(/[ \t]+$/,"");
  pattern_text = pattern_text.replace(/^[ \t]+/,"");
  
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
    
  var match_attempts=0;
  var start=(new Date).getTime();

  // re compile all patterns - ignore case
  var pattern_texts = pattern_text.split(" ");
  var patterns=new Array();
  for (var i=0; i < pattern_texts.length; i++) {
    window.console.log ('compiled ' + i + '-th pattern = <' + pattern_texts[i] + '>');
    patterns[i]=new RegExp(pattern_texts[i],"i");
  }

  // scan rows
  for (row_index = 0; row=rows[row_index]; row_index++) {
      cells=row.cells;
    
    /*empty pattern */
    if (patterns.length == 0) {
      visible=true;
    } else if (and_if_true) {
      /* AND mode: all patterns must match */
      visible=true;
      for (i in patterns) {
	var matched=false;
	var pattern=patterns[i];
	for (cell_index = 0; cell=cells[cell_index]; cell_index++) {
	  var against=plc_table_cell_text (cell);
	  match_attempts++;
	  if ( against.match(pattern)) {
	    matched=true;
	    break;	  
	  }
	}
	if ( ! matched ) visible=false;
      }
    } else {
      /* OR mode: any match is good enough */
      visible=false;
      for (cell_index = 0; cell=cells[cell_index]; cell_index++) {
	var against = cell.plc_table_cell_text(cell);
	for (i in patterns) {
	  pattern=patterns[i];
	  match_attempts++;
	  if (against.match(pattern)) {
	    visible=true;
	    // alert ('OR matched! p='+pattern+' c='+cell.innerHTML);
	    break;
	  }
	}
      }
    }
    plc_table_row_visible(row,visible);
    if (visible) matching_entries +=1;
  }
  var end=(new Date).getTime();
  var ms=end-start;
  window.console.log ("plc_table_filter: " + 
		      match_attempts + " matches - " +
		      matching_entries + " lines - " + ms + " ms");
  tbody['matching']=matching_entries;
  tbody['match_attempts']=match_attempts;
  tablePaginater.init(table_id);
}

function plc_table_filter_reset (table_id, pattern_id,and_id) {
  /* reset pattern */
  document.getElementById(pattern_id).value="";
  plc_table_filter (table_id, pattern_id,and_id);
}
