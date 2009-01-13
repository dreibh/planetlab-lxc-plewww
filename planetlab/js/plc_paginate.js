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

  t.insertBefore(p.cloneNode(true), t.firstChild);
  b.appendChild(p);
}


/* locates a table from its id and alters the classname to reflect new table size */
function plc_table_setsize (table_id,size_id,def_size) {
  var table=document.getElementById(table_id);
  var size_area=document.getElementById(size_id);
  if ( ! size_area.value ) {
    size_area.value=def_size;
  }
  var size=size_area.value;
  table.className=table.className.replace(/paginate-\d+/,"paginate-"+size); 
  tablePaginater.init(table_id);
}

function plc_table_size_reset(table_id, size_id, size) {
  var table=document.getElementById(table_id);
  var size_area=document.getElementById(size_id);
  size_area.value=size;
  table.className=table.className.replace(/paginate-\d+/,"paginate-"+size); 
  tablePaginater.init(table_id);
}
  
