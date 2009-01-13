/* $Id$ */

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

// /* scan the table, and mark as visible the rows that have at least one cell that contains the pattern */
function plc_table_filter (table_id,pattern_id) {
  var tbody = document.getElementById(table_id).getElementsByTagName("tbody")[0];
  var rows=tbody.rows;
  var pattern_text = document.getElementById(pattern_id).value;
  var row_index, row, cells, cell_index, cell, visible;
  var pattern,i;
  var matching_entries=0;
  
  // remove whitespaces at the beginning and end
  pattern_text = pattern_text.replace(/[ \t]+$/,"");
  pattern_text = pattern_text.replace(/^[ \t]+/,"");
  
  var patterns = pattern_text.split(" ");

  for (row_index = 0; row=rows[row_index]; row_index++) {
    
    /* xxx deal with empty patterns and whitespaces */
    if (patterns.length == 0) {
      visible=true;
    } else {
      visible=false;
      cells=row.cells;
      for (cell_index = 0; cell=cells[cell_index]; cell_index++) {
	for (i in patterns) {
	  pattern=patterns[i];
	  if (cell.innerHTML.match(pattern)) visible=true;
	}
      }
    }
    plc_table_row_visible(row,visible);
    if (visible) matching_entries +=1;
  }
  plc_table_tbody_matching(tbody,matching_entries);
  tablePaginater.init(table_id);
}

function plc_table_filter_reset (table_id, pattern_id) {
  /* reset pattern */
  document.getElementById(pattern_id).value="";
  plc_table_filter (table_id, pattern_id);
}
