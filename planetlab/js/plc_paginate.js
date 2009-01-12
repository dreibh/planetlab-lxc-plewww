/* $Id$ */

function displayTextInfo (opts,tablename) {

  if(!("currentPage" in opts)) { return; }
    
  var p = document.createElement('p'),
    t = document.getElementById(tablename+'-fdtablePaginaterWrapTop'),
    b = document.getElementById(tablename+'-fdtablePaginaterWrapBottom');
  
  p.className = "paginationText";    
  p.appendChild(document.createTextNode("Showing page " + opts.currentPage + " of " + Math.ceil(opts.totalRows / opts.rowsPerPage)));
                
  t.insertBefore(p.cloneNode(true), t.firstChild);
  b.appendChild(p);
}


