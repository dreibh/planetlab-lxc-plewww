
/* $Id: table.js 13009 2009-04-10 10:49:28Z baris $ */

var filtered_color = "grey";
var normal_color = "black";


function inTypeA(header_name) {
	var typeA = ['ST','SN','RES','OS','NRR','NTP','NSR','NSF','NDS','NTH','NEC','LRN','LCY','LPR','LCN','LAT','LON','IP','ASN','AST'];
	return typeA.indexOf(header_name);
}

function inTypeB(header_name) {
	var typeB = ['BW','DS','MS','CC','CR','AS','MU','DU','CN'];
	return typeB.indexOf(header_name);
}

function inTypeC(header_name) {
	var typeC = ['Rw','Rm','Ry','BWw','BWm','BWy','Lw','Lm','Ly','Sw','Sm','Sy','CFw','CFm','CFy','BUw','BUm','BUy','MUw','MUm','MUy','SSHw','SSHm','SSHy'];
	return typeC.indexOf(header_name);
}

function inTypeD(header_name) {
	var typeD = ['HC'];
	return typeD.indexOf(header_name);
}

function debugfilter(s) {
	document.getElementById('debug').innerHTML+=s;
}

function scrollList(tableid) {

debugfilter("here");

if (event.keyCode == 40)
	debugfilter("down");
else if (event.keyCode == 38)
	debugfilter("up");

}

function highlightOption(divid) {

//debugfilter("highlighting option "+divid);

for (var kk in column_headers) {

if (document.getElementById(kk))
	document.getElementById(kk).className = 'out'; 
}

document.getElementById(divid).className = 'selected';

showDescription(divid);

}


function showDescription(h) {

	//debugfilter("showing description "+h);

	if (document.getElementById('selectdescr'))
	{
		if (window['desc'+h])
			document.getElementById('selectdescr').innerHTML = ""+window['desc'+h];
		else if (column_table[h] && column_table[h]['description'])
			document.getElementById('selectdescr').innerHTML = column_table[h]['description'];
		else 
			document.getElementById('selectdescr').innerHTML = "No description provided";
	}
}


function overrideTitles() {

	//debugfilter("<p>overriding...");

	for (var kk in column_headers) {

	//debugfilter("here "+kk);

	if (document.getElementById(kk) && window['title'+kk])
		document.getElementById('htitle'+kk).innerHTML = window['title'+kk];
	}

}

function changeCheckStatus(column) {


if (document.getElementById('selectdescr'))
{
showDescription(document.getElementById(column).value);

if (document.getElementById(column).checked)
	addColumn(document.getElementById(column).value);
else
	deleteColumn(document.getElementById(column).value);
}

}

function updatePeriod(h) {
	deleteColumn2(h, h+'w');
	deleteColumn2(h, h+'m');
	deleteColumn2(h, h+'y');
	addColumn(h);
}

function filterByType(selectedtype) {

var notselectedyet = true;

for (var kk in column_headers) {

	if (document.getElementById(kk))
	{
        	if (window['type'+kk] == selectedtype)
        	{
                	document.getElementById(kk).className = 'in';
                	if (notselectedyet)
                        	highlightOption(kk);
                	notselectedyet = false;
        	}
        	else
                	document.getElementById(kk).className = 'out';
	}
}
}


/*
 
RESET/SAVE CONFIGURATION
*/


function resetColumns() {

	for (var kk in column_table) {

	if (column_table[kk]['visible'] == true && column_table[kk]['fetch'] == false)
		deleteColumn(kk);
	else if (column_table[kk]['visible'] == false && column_table[kk]['fetch'] == true)
		addColumn(kk);
	}

}

function resetCols(which_conf) {

	var target_configuration = "|"+document.getElementById(which_conf).value+"|";
	
	//debugfilter("<p>Target configuration =  "+target_configuration);

	for (var kk in column_table) {
		//debugfilter("in "+kk+" ");

		if (target_configuration.indexOf("|"+kk+"|")>=0)
		{
			if (document.getElementById('check'+kk))
			if (document.getElementById('check'+kk).checked == false)
			{
				debugfilter("<p>Adding "+kk);
				addColumn(kk);
			}
		}
		else
		{
			if (document.getElementById('check'+kk))
			if (document.getElementById('check'+kk).checked == true)
			{
				debugfilter("<p>Deleting "+kk);
				deleteColumn(kk);
			}
		}
	}
}

function saveConfiguration(which_conf)
{
	var slice_id = document.getElementById('slice_id').value;

	var target_configuration = document.getElementById(which_conf).value;

	debugfilter("saving configuration "+target_configuration);
	updateColumnConfiguration(slice_id, target_configuration);
}

function updateColumnConfiguration(slice_id, value)
{

        if (window.XMLHttpRequest)
          {// code for IE7+, Firefox, Chrome, Opera, Safari
          xmlhttp=new XMLHttpRequest();
          }
        else
          {// code for IE6, IE5
          xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
          }
        xmlhttp.onreadystatechange=function()
          {
          if (xmlhttp.readyState==4) // && xmlhttp.status==200)
            {
                //value=xmlhttp.responseText;
                //debugfilter(value);
		if (document.getElementById('column_configuration'))
		document.getElementById('column_configuration').value=value;
            }
          }
        xmlhttp.open("GET","/plekit/php/updateConf.php?value="+value+"&slice_id="+slice_id+"&tagName=Configuration",true);

        xmlhttp.send();
}



function resetConfiguration(which_conf)
{
	var slice_id = document.getElementById('slice_id').value;
	var target_configuration = document.getElementById(which_conf).value;

	debugfilter("reseting configuration "+target_configuration);

        if (window.XMLHttpRequest)
          {// code for IE7+, Firefox, Chrome, Opera, Safari
          xmlhttp=new XMLHttpRequest();
          }
        else
          {// code for IE6, IE5
          xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
          }
        xmlhttp.onreadystatechange=function()
          {
          if (xmlhttp.readyState==4) // && xmlhttp.status==200)
            {
                //value=xmlhttp.responseText;
                //debugfilter(value);
                window.location.reload(true);
            }
          }
        xmlhttp.open("GET","/plekit/php/updateConf.php?value="+target_configuration+"&slice_id="+slice_id+"&tagName=Configuration",true);

        xmlhttp.send();
}

function addColumnToConfiguration(column) {


	var old_configuration = document.getElementById('column_configuration').value;
	var slice_id = document.getElementById('slice_id').value;

	var new_configuration = "";

	if (old_configuration != "")
		new_configuration = old_configuration += "|"+column;
	else
		new_configuration = column;

	//debugfilter("new configuration = "+new_configuration);

	updateColumnConfiguration(slice_id, new_configuration);
}



/*
 
ADD/REMOVE COLUMNS

*/


function getHTTPObject()
{
        if (typeof XMLHttpRequest != 'undefined')
        { return new XMLHttpRequest(); }

        try
        { return new ActiveXObject("Msxml2.XMLHTTP"); }
        catch (e)
        {
                try { return new ActiveXObject("Microsoft.XMLHTTP"); }
                catch (e) {}
        }
        return false;
}


function load_html(column, url) {
	var req = getHTTPObject();
	var res;
	req.open('GET', url, true);
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	req.onreadystatechange =
        function() {
                if (req.readyState == 4)
                { updateColumnData(column, req.responseText); }
        }
	req.send(null);
}

function updateColumnData(column,data) {


var headers = column.split("|");
var data_table = data.split("|"); 

//debugfilter("<p>headers[0] = "+headers[0]);
//debugfilter("<p>sample line = "+data_table[2]);


  var node_data;

  var table_id1 = 'nodes';
  var table=$(table_id1);
  var css='#'+table_id1+'>tbody';
  var rows = $$(css)[0].rows;

  var data_array1 = new Array();

  //debugfilter("COLUMN "+column+"<p>");

  for (var node_index = 1; node_index < data_table.length; node_index++) {
 	if (data_table[node_index] == '---potential---')	
		break;
	node_data = data_table[node_index].split(':');

	data_array1[node_data[0]] = new Array();

	for (var h_index=0; h_index < headers.length; h_index++) {
		
		if (node_data[h_index+1] == "")
			data_array1[node_data[0]][h_index] = "n/a";
		else
			data_array1[node_data[0]][h_index] = node_data[h_index+1];
	}

  }
	
  if (rows)
  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];

    for (var column_index=0; column_index < tr.cells.length; column_index++) {
		//debugfilter("<p>node id = "+tr.cells[0].innerHTML);
		var found_index = headers.indexOf(tr.cells[column_index].getAttribute('name'));
		if (found_index != -1)
			tr.cells[column_index].innerHTML = data_array1[tr.cells[0].innerHTML][found_index];
    }
  }

//potential nodes
if (data_table[node_index] == '---potential---')	
{

  var table_id2 = 'add_nodes';
  var table2=$(table_id2);
  var css2='#'+table_id2+'>tbody';
  var rows2 = $$(css2)[0].rows;

  var data_array2 = new Array();

  //debugfilter("COLUMN "+column+"<p>");

  for (; node_index < data_table.length; node_index++) {
 	if (data_table[node_index] == '')	
		continue;
	node_data = data_table[node_index].split(':');

	data_array2[node_data[0]] = new Array();

	for (var h_index=0; h_index < headers.length; h_index++) {
		
		if (node_data[h_index+1] == "")
			data_array2[node_data[0]][h_index] = "n/a";
		else
			data_array2[node_data[0]][h_index] = node_data[h_index+1];
	}
  }
	
  if (rows)
  for (var row_index = 0; row_index < rows2.length ; row_index++) {
    var tr=rows2[row_index];

    for (var column_index=0; column_index < tr.cells.length; column_index++) {
		var found_index = headers.indexOf(tr.cells[column_index].getAttribute('name'));
		if (found_index != -1)
			tr.cells[column_index].innerHTML = data_array2[tr.cells[0].innerHTML][found_index];
    }
  }

}

  document.getElementById('loadingDiv').innerHTML = ""
}





function addColumnCells(column,header) {

	//debugfilter("adding cells now: "+column+":"+header);
	column_table[header]['visible']=true;

	var cells = document.getElementsByName(header);


	//debugfilter("got cells -"+cells+"- for "+header);
	for(var j = 0; j < cells.length; j++) 
		cells[j].style.display = "table-cell";
}

function addColumnSamples(column) {

	var cellsheader = document.getElementsByName("confheader"+column);
	for(var j = 0; j < cellsheader.length; j++) 
		cellsheader[j].style.display = "table-cell";

}

function addColumnAjax(column) {

	var selectedperiod="";

	if (document.getElementById('selectperiod'+column))
        	selectedperiod = document.getElementById('selectperiod'+column).value;

	var header = column+""+selectedperiod;

	addColumnCells(column, header);

	var t = column_table[header]['tagname'];
	var slice_id = document.getElementById('slice_id').value;
	document.getElementById('loadingDiv').innerHTML = "<img src=/plekit/icons/ajax-loader.gif>LOADING ...";
	var url = "/plekit/php/updateColumn.php?slice_id="+slice_id+"&tagName="+t;
	load_html(header, url);

	addColumnToConfiguration(header);
}


function addColumn2(column) {

	var selectedperiod="";

	if (document.getElementById('selectperiod'+column))
        	selectedperiod = document.getElementById('selectperiod'+column).value;

	var header = column+""+selectedperiod;

	addColumnCells(column,header);

	addColumnToConfiguration(column);

	checkDataToFetch();
}

function addColumn(column) {

	var selectedperiod="";
	var header=column;

	//debugfilter("adding column "+column+" and header "+header);

	if (inTypeC(column)!=-1)
	{
		column = column.substring(0,column.length-1);
	}
	else if (document.getElementById('selectperiod'+column))
	{
        	selectedperiod = document.getElementById('selectperiod'+column).value;
		header = column+""+selectedperiod;
	}

	//debugfilter("adding "+column+","+header);

	addColumnCells(column, header);

	addColumnToConfiguration(header);

	column_table[header]['visible'] = true;
	
	checkDataToFetch();
}

function checkDataToFetch() {

var dataExist = false;

for (var kk in column_table) {

	if (document.getElementById(kk))
	{
		if (column_table[kk]['visible'] == true && column_table[kk]['fetch'] == false)
		{
			document.getElementById('fetchbutton').disabled = false;
			document.getElementById('fetchbutton').style.color = 'red';
			dataExist = true;
		}
	}
}

if (!dataExist)
{
	document.getElementById('fetchbutton').disabled = true;
	document.getElementById('fetchbutton').style.color = 'grey';
}

}


function fetchData() {

var tagnames = "";
var headers = "";

for (var kk in column_table) {

if (column_table[kk]['visible'] == true && column_table[kk]['fetch'] == false)
	if (tagnames == "")
	{	
		tagnames = column_table[kk]['tagname'];
		headers = kk;
	}
	else
	{
		tagnames += "|"+column_table[kk]['tagname'];
		headers += "|"+kk;
	}
}

//debugfilter("fetching these columns: "+tagnames+ "("+headers+")");

	var slice_id = document.getElementById('slice_id').value;
	document.getElementById('loadingDiv').innerHTML = "&nbsp;&nbsp;&nbsp;<img src=/plekit/icons/ajax-loader.gif>&nbsp;Loading data. Please wait ...";
	var url = "/plekit/php/updateColumn.php?slice_id="+slice_id+"&tagName="+tagnames;
	load_html(headers, url);
}

function deleteColumnCells(column, header) {

	column_table[header]['visible']=false;

	var cells = document.getElementsByName(header);
	for(var j = 0; j < cells.length; j++) 
		cells[j].style.display = "none";

}

function deleteColumnSample() {
	var cellsheader = document.getElementsByName("confheader"+column);
	for(var j = 0; j < cellsheader.length; j++) 
		cellsheader[j].style.display = "none";

}

function deleteColumnFromConfiguration(column) {


	var old_configuration = document.getElementById('column_configuration').value;
	var slice_id = document.getElementById('slice_id').value;

	var old_columns = old_configuration.split("|");
	var new_columns = new Array();

	for (var column_index = 0; column_index < old_columns.length ; column_index++) {
		var conf = old_columns[column_index].split(':');
		if (conf[0] != column)
			new_columns.push(old_columns[column_index]);
	}

	var new_configuration = new_columns.join("|");
	updateColumnConfiguration(slice_id, new_configuration);

	checkDataToFetch();
}

function deleteColumn2(column, header) {

	deleteColumnCells(column,header);

	deleteColumnFromConfiguration(column);

	column_table[header]['visible'] = false;
	document.getElementById('check'+column).checked = false;
}

function deleteColumn(column) {

	var selectedperiod="";
	var header=column;

	if (inTypeC(column)!=-1)
	{
		column = column.substring(0,column.length-1);
	}
	else if (document.getElementById('selectperiod'+column))
	{
        	selectedperiod = document.getElementById('selectperiod'+column).value;
		header = column+""+selectedperiod;
	}

	//debugfilter("deleting "+column+","+header);

	deleteColumnCells(column,header);

	deleteColumnFromConfiguration(header);

	column_table[header]['visible'] = false;

	//document.getElementById('check'+column).checked = false;
}




/*
 

HIGHLIGHTING



function updateColumnThreshold(column, minT, maxT) {

debugfilter("updating threshold for "+column+" with "+minT+" and "+maxT);

var cells = document.getElementsByName(column);

for(var j = 0; j < cells.length; j++) 
{
var val = parseFloat(cells[j].innerHTML);

if (val >= minT && val <= maxT)
	cells[j].style.color = filtered_color;
else
	cells[j].style.color = normal_color;
}

var old_configuration = document.getElementById('column_configuration').value;
var slice_id = document.getElementById('slice_id').value;

var old_columns = old_configuration.split("|");
var new_columns = new Array();

for (var column_index = 0; column_index < old_columns.length ; column_index++) {
	var conf = old_columns[column_index].split(':');
	if (conf[0] != column)
		new_columns.push(old_columns[column_index]);
	else
		new_columns.push(column+":"+minT+","+maxT);
}

var new_configuration = new_columns.join("|");

updateColumnConfiguration(slice_id, new_configuration);

}

function updateExcludeList(column, excludeList) {

//debugfilter("updating list");
debugfilter("updating list for "+column+" with "+excludeList);

var cells = document.getElementsByName(column);

for(var j = 1; j < cells.length; j++) 
{
var val = cells[j].innerHTML;

if (excludeList == val)
	cells[j].style.color = filtered_color;
else
	cells[j].style.color = normal_color;
}

var old_configuration = document.getElementById('column_configuration').value;
var slice_id = document.getElementById('slice_id').value;

var old_columns = old_configuration.split("|");
var new_columns = new Array();

for (var column_index = 0; column_index < old_columns.length ; column_index++) {
	var conf = old_columns[column_index].split(':');
	if (conf[0] != column)
		new_columns.push(old_columns[column_index]);
	else
		new_columns.push(column+":"+excludeList);
}

var new_configuration = new_columns.join("|");

updateColumnConfiguration(slice_id, new_configuration);

}



/*
 
ROW FILTERING


function plekit_table_showAll (slicetable_id) {

  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;

  // scan rows, elaborate 'visible'
  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];
    var visible=true;
    plekit_table_row_visible(tr,visible);
  }

    plekit_table_count_filtered(slicetable_id);

  tablePaginater.init(slicetable_id);
  
}

function plekit_table_count_filtered (slicetable_id) {
  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;

  var no_filtered=0;

  // scan rows, elaborate 'visible'
  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];
    var filtered = false;

    for (var column_index=0; column_index < tr.cells.length; column_index++) 
		if (tr.cells[column_index].style.color == "red")
			filtered = true;

	if (filtered)
	no_filtered++;

  }

  debugfilter(no_filtered+' nodes do not satisfy the requested threshold');
}


function plekit_table_hide_filtered (slicetable_id) {
  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;

  var reg = /(^|\s)invisibleRow(\s|$)/;


  if (!document.getElementById('filtercheck').checked)
  {
	plekit_table_showAll(slicetable_id);
	return;
  }

  var hidden=0;

  // scan rows, elaborate 'visible'
  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];
    var visible=true;

    for (var column_index=0; column_index < tr.cells.length; column_index++) {
		if (tr.cells[column_index].style.color == filtered_color)
			visible = false;
    }
    if (!visible)
    	hidden++;

    plekit_table_row_visible(tr,visible);
  }

  //debugfilter('hidden '+hidden+' nodes');
  debugfilter(hidden+' nodes do not satisfy the requested threshold (hidden)');
  
  tablePaginater.init(slicetable_id);
}




function plekit_table_apply_config(slicetable_id, configuration) {

var new_configuration = document.getElementById('new_conf').value;
var all_columns = new_configuration.split("|");

var min_values = new Array();
var max_values = new Array();


for (var column_index = 0; column_index < all_columns.length ; column_index++) {

	var conf = all_columns[column_index].split(':');
	
	if (inTypeB(conf[0]) != -1)
	{
		var threshold = conf[1].split(',');
		if (threshold.length == 2)
		{
		min_values.push(parseFloat(threshold[0]));
		max_values.push(parseFloat(threshold[1]));
		}
	}
	else if (inTypeC(conf[0]) == -1)
	{
		var threshold = conf[2].split(',');
		if (threshold.length == 2)
		{
		min_values.push(parseInt(threshold[0]));
		max_values.push(parseInt(threshold[1]));
		}
	}
	else
	{
		min_values.push(-1);
		max_values.push(-1);
	}
	
}

  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;


  var no_filtered=0;

  for (var row_index = 0; row_index < rows.length ; row_index++) {

    	var tr=rows[row_index];

	var filtered = false;

	for (var column_index = 0; column_index < all_columns.length ; column_index++) 
	if (min_values[column_index]!=-1)
	{
		var val = parseFloat(tr.cells[3+column_index].innerHTML);
		
		if (val >= min_values[column_index] && val <= max_values[column_index])
		{
			tr.cells[3+column_index].style.color = filtered_color;
			filtered = true;
		}
		else
			tr.cells[3+column_index].style.color = normal_color;
	}
	else
		if (tr.cells[3+column_index].style.color == filtered_color)
			filtered = true;
		

	if (filtered)
	no_filtered++;
  }

  debugfilter(no_filtered+' nodes do not satisfy the requested threshold');

  //tablePaginater.init(slicetable_id);

}


function reset_select () {
  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;

var action = document.getElementById('onlyselected');
action.checked=false;

  // scan rows, elaborate 'visible'
  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];

    document.getElementById("check"+tr.id).checked=false;

  }

  plekit_table_count_nodes();
}

}


function plekit_table_select_filter () {
  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;

  var reg = /(^|\s)invisibleRow(\s|$)/;

  var action = document.getElementById('onlyselected');
  if (!action.checked)
	plekit_table_reset_filter();

  // scan rows, elaborate 'visible'
  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];
    var visible=true;

    if (action.checked)
    {
         if(tr.className.search(reg) == -1) 
              if(!document.getElementById("check"+tr.id).checked)
	          visible=false;
    }

    if(tr.className.search(reg) != -1) 
	visible=false;

    plekit_table_row_visible(tr,visible);
  }
  
  tablePaginater.init(slicetable_id);
  plekit_table_count_nodes();
}

function plekit_table_select_filter2 () {
  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;

  var reg = /(^|\s)invisibleRow(\s|$)/;

  var action = document.getElementById('onlyselected');

  // scan rows, elaborate 'visible'
  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];
    var visible=true;

    if (action.checked)
    {
         if(tr.className.search(reg) == -1) 
              if(!document.getElementById("check"+tr.id).checked)
	          visible=false;
    }

    if(tr.className.search(reg) != -1) 
	visible=false;

    plekit_table_row_visible(tr,visible);
  }
  
  tablePaginater.init(slicetable_id);
  plekit_table_count_nodes();
}

function CheckTopNodes(n) {
  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;
  var reg = /(^|\s)invisibleRow(\s|$)/;

  var checked=0;

  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];

    if(tr.className.search(reg) == -1) {
	if (checked<n)
	{
		document.getElementById("check"+tr.id).checked=true;
		checked++;
	}
	else
	{
		document.getElementById("check"+tr.id).checked=false;
	};
    };
   };
};


function CheckRandomNodes(n) {
  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;
  var reg = /(^|\s)invisibleRow(\s|$)/;

  var r = n/plekit_table_visible_count();
  var checked=0;

  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];

    if(tr.className.search(reg) == -1) {
    	if(Math.random() < r) {
		document.getElementById("check"+tr.id).checked=true;
		checked++;
	};
	if (checked>=n)
		break;
     };
  };
};


function plekit_table_visible_count() {
  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;
  var reg = /(^|\s)invisibleRow(\s|$)/;
  var v=0;

  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];

	if(tr.className.search(reg) == -1) 
	{
		v++;
	}
  }

  return v;
}


function plekit_table_count_nodes() {
  var table=$(slicetable_id);
  var css='#'+slicetable_id+'>tbody';
  var rows = $$(css)[0].rows;
  var reg = /(^|\s)invisibleRow(\s|$)/;
  var n=0;
  var v=0;
  var s=0;

  for (var row_index = 0; row_index < rows.length ; row_index++) {
    var tr=rows[row_index];

	n++;
	var ch = document.getElementById("check"+tr.id);

	if(tr.className.search(reg) == -1) 
		v++;
	else
	{
		if (ch.checked)
			ch.checked=false;
	};

	if (ch.checked)
		s++;
	
   };

   var dd = document.getElementById('node_statistics');
   dd.innerHTML = "Total: "+n+" - Shown: "+v+" - Selected: "+s;
};

function AutoSelect()
{
  var a = document.getElementById('automatic').value;
  var n = parseInt(document.getElementById('no_nodes').value);

  if (isNaN(n))
	return;

  if (a == "random")
         CheckRandomNodes(n);
  else if (a == "top")
         CheckTopNodes(n);

  plekit_table_select_filter2();
  plekit_table_count_nodes();
}

*/
