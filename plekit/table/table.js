/* $Id$ */

/* when a table gets paginated, displays context info */
function plekit_table_paginator (opts,table_id) {

  if(!("currentPage" in opts)) { return; }
    
  var p = document.createElement('p');
  var table=$(table_id);
  var t = $(table_id+'-fdtablePaginaterWrapTop');
  var b = $(table_id+'-fdtablePaginaterWrapBottom');

  /* when there's no visible entry, the pagination code removes the wrappers */
  if ( (!t) || (!b) ) return;

  /* get how many entries are matching:
     opts.visibleRows only holds the contents of the current page
     so we store the number of matching entries in the table 'matching' attribute
  */
  var totalMatches = opts.totalRows;
  var matching=table['matching'];
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
function plekit_pagesize_set (table_id,size_id,def_size) {
  var table=document.getElementById(table_id);
  var size_area=document.getElementById(size_id);
  if ( ! size_area.value ) {
    size_area.value=def_size;
  }
  var size=size_area.value;
  table.className=table.className.replace(/paginate-\d+/,"paginate-"+size); 
  tablePaginater.init(table_id);
}

function plekit_pagesize_reset(table_id, size_id, size) {
  var table=document.getElementById(table_id);
  var size_area=document.getElementById(size_id);
  size_area.value=size;
  table.className=table.className.replace(/paginate-\d+/,"paginate-"+size); 
  tablePaginater.init(table_id);
}
  
/* set or clear the ' invisibleRow' in the tr's classname, according to visible */
function plekit_table_row_visible (row,visible) {
  var cn=row.className;
  /* clear */
  cn=cn.replace(" invisibleRow","");
  if (! visible) cn += " invisibleRow";
  row.className=cn;
}

// Working around MSIE...
if ('undefined' == typeof Node)
    Node = { ELEMENT_NODE: 1, TEXT_NODE: 3 };

// Extract actual text from a DOM node (remove internal tags and so on)
function getInnerText(node) {
	var result = '';
	if (Node.TEXT_NODE == node.nodeType)
		return node.nodeValue;
	if (Node.ELEMENT_NODE != node.nodeType)
		return '';
	for (var index = 0; index < node.childNodes.length; ++index)
		result += getInnerText(node.childNodes.item(index));
	return result;
} // getInnerText

// cache in the <tr> node the concatenation of the innerTexts of its cells
function plekit_tr_text (tr) {
  // if cached, use it
  if (tr['text_to_match']) return tr['text_to_match'];
  // otherwise compute it
  var text="";
  var cells=tr.cells;
  for (var i=0; i<cells.length; i++) 
    text += getInnerText(cells[i]) + " ";
  text = text.strip().toLowerCase();
  tr['text_to_match'] = text;
  return text;
}

/* scan the table, and mark as visible 
   the rows that match (either AND or OR the patterns) */
function plekit_table_filter (table_id,pattern_id,and_id) {
  var table=$(table_id);
  var css='#'+table_id+'>tbody';
  var rows = $$(css)[0].rows;
  var pattern_area = $(pattern_id);
  var pattern_text = pattern_area.value;
  var matching_entries=0;
  var and_button=$(and_id);
  var and_if_true=and_button.checked;

  // canonicalize white spaces 
  pattern_text = pattern_text.replace(/^\s+/, '').replace(/\s+$/, '').replace(/\s+/g,' ');
  
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

  // if we're running with the same pattern
  var previous_pattern=table['previous_pattern'];
  var previous_mode=table['previous_mode'];
  if ( (previous_pattern == pattern_text) && (previous_mode == and_if_true) ) {
    window.console.log ('no change in pattern');
    return;
  }

  // re compile all patterns 
  var pattern_texts = pattern_text.strip().split(" ");
  var searches=new Array();
  var patterns=new Array();
  for (var i=0; i < pattern_texts.length; i++) {
    window.console.log ('compiled ' + i + '-th pattern = [' + pattern_texts[i] + ']');
    // ignore case
    searches[i]=pattern_texts[i].toLowerCase();
    patterns[i]=new RegExp(pattern_texts[i],"i");
  }

  // scan rows, elaborate 'visible'
  window.console.log ('we have ' + rows.length + ' rows');
  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];
    var visible=false;
    
    /*empty pattern */
    if (patterns.length == 0) {
      visible=true;
    } else if (and_if_true) {
      /* AND mode: all patterns must match */
      visible=true;
      var against=plekit_tr_text (tr);
      for (var search_index=0; search_index<searches.length; search_index++) {
	var search=searches[search_index];
	match_attempts++;
	if ( against.search(search) < 0) {
	  visible=false;
	  break;	  
	}
      }
    } else {
      /* OR mode: any match is good enough */
      visible=false;
      var against = plekit_tr_text(tr);
      for (var search_index=0; search_index < searches.length; search_index++) {
	var search=searches[search_index];
	match_attempts++;
	if (against.search(search) >= 0) {
	  visible=true;
	  break;
	}
      }
    }

    plekit_table_row_visible(tr,visible);
    if (visible) matching_entries +=1;
  }
  // save for next run
  table['previous_pattern']=pattern_text;
  table['previous_mode']=and_if_true;
  
  var end=(new Date).getTime();
  var match_ms=end-start;

  // optimize useless calls to init, by comparing # of matching entries
  var previous_matching=table['previous_matching'];
  if (matching_entries == previous_matching) {
    window.console.log ('same # of matching entries - skipped redisplay');
    window.console.log ("plekit_table_filter: " + 
			match_attempts + " matches - " +
			matching_entries + " lines - " 
			+ "match=" + match_ms + " ms");
    return;
  }
  
  table['matching']=matching_entries;
  table['match_attempts']=match_attempts;
  tablePaginater.init(table_id);
  var end2=(new Date).getTime();
  var paginate_ms=end2-end;
  window.console.log ("plekit_table_filter: " + 
		      match_attempts + " matches - " +
		      matching_entries + " lines - " 
		      + "match=" + match_ms + " ms - "
		      + "paginate=" + paginate_ms + " ms");
  
}

function plekit_table_filter_reset (table_id, pattern_id,and_id) {
  /* reset pattern */
  document.getElementById(pattern_id).value="";
  plekit_table_filter (table_id, pattern_id,and_id);
}
