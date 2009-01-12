/* $Id$ */

/* set or clear the ' invisibleRow' in the tr's classname, according to visible */
function plc_row_set_classname (row,visible) {
  var cn=row.className;
  /* clear */
  cn=cn.replace(" invisibleRow","");
  if (! visible) cn += " invisibleRow";
  row.className=cn;
}

/* scan the table, and mark as visible the rows that have at least one cell that contains the pattern */
function plc_filter_table(table_id,pattern_id) {
  var 
    rows=document.getElementById(table_id).getElementsByTagName("tbody")[0].rows,
    patterns=document.getElementById(pattern_id).value.split(" "),
    row_index, row, cells,cell_index,cell,visible;
  
  for (row_index = 0; row=rows[row_index]; row_index++) {
    
    /* xxx deal with empty patterns and whitespaces */
    if (patterns.length == 0) {
      visible=true;
    } else {
      visible=false;
      cells=row.cells;
      for (cell_index = 0; cell=cells[cell_index]; cell_index++) {
	for (var i in patterns) {
	  pattern=patterns[i];
	  if (cell.innerHTML.match(pattern)) visible=true;
	}
      }
    }
    plc_row_set_classname(row,visible);
  }
  tablePaginater.init(table_id);
}
