/* $Id$ */

/* when a table gets paginated, displays context info */
function plc_table_update_paginaters (opts,tablename) {

  if(!("currentPage" in opts)) { return; }
    
  var p = document.createElement('p');
  var t = document.getElementById(tablename+'-fdtablePaginaterWrapTop');
  var b = document.getElementById(tablename+'-fdtablePaginaterWrapBottom');

  var first = ((opts.currentPage-1) * opts.rowsPerPage) +1;
  var last = Math.min((opts.currentPage * opts.rowsPerPage),opts.totalRows);
  var items_text = "Items [" + first + " - " + last + "] of " + opts.totalRows;
  var page_text = "Page " + opts.currentPage + " of " + Math.ceil(opts.totalRows / opts.rowsPerPage);
  var label = items_text + " --- " + page_text;

  p.className = "paginationText";    
  p.appendChild(document.createTextNode(label));
                
  /*  t.insertBefore(p.cloneNode(true), t.firstChild); */
  b.appendChild(p);
}


/* locates a table from its id and alters the classname to reflect new table size */
function plc_table_setsize (table_id,size_id,def_size) {
  var table=document.getElementById(table_id);
  var size_area=document.getElementById(size_id);
  var paginate=/paginate-\d+/;
  if ( ! size_area.value ) {
    size_area.value=def_size;
  }
  var size=size_area.value;
  table.className=table.className.replace(paginate,"paginate-"+size); 
  tablePaginater.init(table_id);
}

function plc_table_filter_resetsize(table_id, size_id, size) {
  var table=document.getElementById(table_id);
  var size_area=document.getElementById(size_id);
  var paginate=/paginate-\d+/;
  size_area.value=size;
  table.className=table.className.replace(paginate,"paginate-"+size); 
  tablePaginater.init(table_id);
}
  
