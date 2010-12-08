<?php

require_once 'tophat_api.php';

drupal_set_html_head('
<script type="text/javascript" src="/plekit/table/columns.js"></script>
');

class PlekitColumns {

var $column_configuration = "";
var $reference_node = "";
var $first_time = false;

var $all_headers = array();
var $this_table_headers = array();
var $visible_headers = array();

var $fix_columns = array();
var $tag_columns = array();
var $extra_columns = array();

var $comon_live_data = "";
var $tophat_live_data = "";
var $live_data = array();

var $table_ids;

var $HopCount = array();

  function PlekitColumns ($column_configuration, $fix_columns, $tag_columns, $extra_columns=NULL, $this_table_headers=NULL) {

	if ($column_configuration != NULL) {
	$this->fix_columns = $fix_columns;
	$this->tag_columns = $tag_columns;
	$this->extra_columns = $extra_columns;

	//print("<p>FIX<p>");
	//print_r($this->fix_columns);
	//print("<p>TAG<p>");
	//print_r($this->tag_columns);
	//print("<p>EXTRA<p>");
	//print_r($this->extra_columns);

	$this->prepare_headers();
	$this->parse_configuration($column_configuration);

	$this->visible_headers = $this->get_visible();
	}

	//print("<p>VISIBLE<p>");
	//print_r($this->visible_headers);

}



/*

INFO

*/

function prepare_headers() {

foreach ($this->fix_columns as $column) {
$this->all_headers[$column['header']]=array('header'=>$column['header'],'type'=>$column['type'],'tagname'=>$column['tagname'],'title'=>$column['title'], 'description'=>$column['title'], 'label'=>$column['header'], 'fixed'=>true, 'visible'=>false, 'source'=>'myplc');
}

$tmp_headers = array();

if ($this->extra_columns)
foreach ($this->extra_columns as $column) {
$tmp_headers[$column['header']]=array('header'=>$column['header'],'type'=>$column['type'],'tagname'=>$column['tagname'],'title'=>$column['title'], 'description'=>$column['title'], 'label'=>$column['header'], 'fetched'=>$column['fetched'], 'visible'=>false, 'source'=>$column['source']);

}

if ($this->tag_columns)
foreach ($this->tag_columns as $column) {

if ($column['headerId'] != "")
	$headerId = $column['headerId'];
else
	$headerId = $column['header'];

$tmp_headers[$headerId]=array('header'=>$headerId,'type'=>$column['type'],'tagname'=>$column['tagname'],'title'=>$column['title'], 'description'=>$column['title'], 'label'=>$column['header'],'visible'=>false, 'source'=>'myplc');
}

usort ($tmp_headers, create_function('$col1,$col2','return strcmp($col1["label"],$col2["label"]);'));

foreach ($tmp_headers as $t) 
$this->all_headers[$t['header']] = $t;

//$this->all_headers = array_merge($this->all_headers, $tmp_headers);

//print($this->print_headers());

return $this->all_headers;

}


function get_headers() {

return $this->all_headers;

}

function get_selected_period($label) {

if ($this->all_headers[$label."w"]['visible'])
	return "w";
else if ($this->all_headers[$label."m"]['visible'])
	return "m";
else if ($this->all_headers[$label."y"]['visible'])
	return "y";
else if ($this->all_headers[$label]['visible'])
	return "";
	
return "";
}

function node_tags() {

	$fetched_tags = array('node_id','hostname');	

	foreach ($this->all_headers as $h)
	{
		if ($h['visible'] == true && $h['tagname'] != "" && !$h['fetched'] && $h['source']=="myplc")
			$fetched_tags[] = $h['tagname'];
	}

	return $fetched_tags;
}

function print_headers() {

	$headers = "";	

	foreach ($this->all_headers as $l => $h)
	{
		$headers.="<br>[".$l."]=".$h['header'].":".$h['label'].":".$h['tagname'].":".$h['visible'];
	}
	return $headers;
}

function get_visible() {

	$visibleHeaders = array();	

	foreach ($this->all_headers as $h)
	{
		if ($h['visible'] == true)
			$visibleHeaders[] = $h['header'];
	}
	return $visibleHeaders;
}

function headerIsVisible($header_name) {

$headersToShow = $this->visible_headers;

if (in_array($header_name, $headersToShow))
	return true;

if ($this->inTypeC($header_name."w"))
	return (in_array($header_name."w", $headersToShow) || in_array($header_name."m", $headersToShow) || in_array($header_name."y", $headersToShow));
}




/*

CONFIGURATION

*/


function parse_configuration($column_configuration) {

	$this->column_configuration = $column_configuration;
	//$this->default_configuration = $default_configuration;

	//print($this->print_headers());

	$columns_conf = explode("|", $column_configuration);


	foreach ($columns_conf as $c)
	{
        	$conf = explode(":",$c);

		if ($conf[0] == "default")
			continue;

		if (!$this->all_headers[$conf[0]])
			continue;

                $this->all_headers[$conf[0]]['visible']=true;

		if ($this->all_headers[$conf[0]]['source'] == "comon")
			$this->comon_live_data.=",".$this->all_headers[$conf[0]]['tagname'];

		if ($this->all_headers[$conf[0]]['source'] == "tophat")
		{
                	$this->reference_node = $conf[1];
			print ("ref node in configuration = ".$conf[1]);
                	$this->reference_node = "planetlab-europe-07.ipv6.lip6.fr";
                	$this->all_headers[$conf[0]]['refnode']=$this->reference_node;
                	//$threshold = explode(",",$conf[1]);
                	//$this->all_headers[$conf[0]]['threshold']=$threshold;
		}

		//print("<p>-".$conf[0]."-should be visible now - ".$this->all_headers[$conf[0]]['visible']);
		//print_r($this->all_headers[$conf[0]]);

/*
		if ($conf[1] == "f")
			continue;

		else if ($this->inTypeC($conf[0]))
        	{
                	$this->all_headers[$conf[0]]['duration']= substr($conf[0], strlen($conf[0])-1, strlen($conf[0]));
                	$threshold = explode(",",$conf[1]);
                	$this->all_headers[$conf[0]]['threshold']=$threshold;
        	}
        	else if ($this->inTypeA($conf[0]))
        	{
                	$exclude_list = explode(",",$conf[1]);
                	$this->all_headers[$conf[0]]['exclude_list']=$exclude_list;
        	}
		else
        	{
                	$threshold = explode(",",$conf[1]);
                	$this->all_headers[$conf[0]]['threshold']=$threshold;
        	}
*/
	}

}


		


/*

CELLS

*/

function convert_data($value, $data_type) {

	if ($value == "" || $value == null || $value == "n/a" || $value == "None")
		return "n/a";

	if ($data_type == "string")
		return $value;

	if ($data_type == "date") 
		return date("Y-m-d", $value);

	if ($data_type == "uptime") 
		return (int)((int) $value / 86400);
	
	return ((int) ($value * 10))/10;

}

function getHopCount($ref_node, $planetlab_nodes) {

	$tophat_auth = array( 'AuthMethod' => 'password', 'Username' => 'guest', 'AuthString' => 'guest');
	$tophat_api = new TopHatAPI($tophat_auth);

	$traceroute = $tophat_api->Get('traceroute', 'latest', array('src_hostname' => $ref_node, 'dst_hostname' => $planetlab_nodes), array('dst_hostname', 'hop_count') );

	$hopcount = array();

	if ($traceroute) foreach ($traceroute as $t)
		$hopcount[$t['dst_hostname']]=$t['hop_count'];

	return $hopcount;
}

function comon_query_nodes($requested_data) {
	//$base_url = 'http://comon.cs.princeton.edu/status/tabulator.cgi?forma=nameonly&';
	$base_url = "http://comon.cs.princeton.edu/status/tabulator.cgi?table=table_nodeviewshort&format=formatcsv&dumpcols='name";

	$url = $base_url.$requested_data."'";

	//print ("retriecing comon data for url ".$url);

	$sPattern = '\', \'';
	$sReplace = '|';

	if( false == ($str=file_get_contents($url)))
       		return '';

     	$result=preg_replace( $sPattern, $sReplace, $str );
	$sPattern = '/\s+/';
	$sReplace = ';';
     	$result=preg_replace( $sPattern, $sReplace, $result );

	$comon_data = explode(";", $result);
	$cl = array();
	$comon_values = array();

	foreach ($comon_data as $cd) {
		$cc = explode("|", $cd);
		if ($cc[0] == "name") {
			$cl = $cc;
		}
		$comon_values[$cc[0]] = array();
		$cindex=1;
		foreach ($cl as $cltag) {
			if ($cltag != "name")
				$comon_values[$cc[0]][$cltag] = $cc[$cindex++];
		}
	}

	return $comon_values;
}


//Depending on the columns selected more data might need to be fetched from
//external sources

function fetch_live_data($all_nodes) {

	//print("<p>fetching live data<p>");

//comon data
	if ($this->comon_live_data != "") {
	
		//print ("live data to be fetched =".$this->comon_live_data);
		$this->live_data = $this->comon_query_nodes($this->comon_live_data);
		//print_r($this->live_data);
	}


//TopHat pairwise data
	//if ($this->tophat_live_data != "")
	if ($this->reference_node != "")
	{
		$dd = array();

		if ($all_nodes) foreach ($all_nodes as $n)
			$dd[] = $n['hostname'];

		print("Calling tophat api for reference node = ".$this->reference_node);
		$st = time() + microtime();
		$HopCount = $this->getHopCount($this->reference_node, $dd);
		printf(" (%.2f ms)<br/>", (time() + microtime()-$st)*100);
		print_r($HopCount);
	}
}


function excludeItems($value, $exclude_list, $hh) {

        if ($value == "")
                $value = "n/a";

        if ($exclude_list)
        if (in_array($value, $exclude_list))
                return array($value, array('name'=>$hh, 'display'=>'table-cell'));
        else
                return array($value, array('name'=>$hh, 'display'=>'table-cell'));

        return array($value, array('name'=>$hh, 'display'=>'table-cell'));
}


function checkThreshold($value, $threshold, $hh) {

        if ($value == "")
                return array("n/a", array('name'=>$hh, 'display'=>'table-cell'));

        if ($threshold)
        if ((float) $value >= (float) $threshold[0] && (float) $value <= (float) $threshold[1])
                return array(round($value,1), array('name'=>$hh, 'display'=>'table-cell'));
        else
                return array(round($value,1), array('name'=>$hh, 'display'=>'table-cell'));

        return array(round($value,1), array('name'=>$hh, 'display'=>'table-cell'));
}


function cells($table, $node) {

foreach ($this->all_headers as $h)
{
if (!$h['fixed']) { 

if ($h['visible'] != "")
{

/*
if ($this->inTypeC($h['header']))
{
        $tagname = $h['tagname'];
        $value = $node[$tagname];
        $v = $this->checkThreshold($value, $h['threshold'], $h['header']);
        $table->cell($v[0],$v[1]);
}
else if ($this->inTypeB($h['header']))
{
        $value = $node[$h['tagname']];
        $v = $this->checkThreshold($value, $h['threshold'], $h['header']);
        $table->cell($v[0],$v[1]);
}
else if ($this->inTypeD($h['header']))
{
        $value = $this->HopCount[$node['hostname']];
        $v = $this->excludeItems($value, $h['threshold'], $h['header']);
        $table->cell($v[0],$v[1]);
}
else if ($this->inTypeA($h['header']))
{
        $value = $node[$h['tagname']];
        $v = $this->excludeItems($value, $h['exclude_list'], $h['header']);
        $table->cell($v[0],$v[1]);
}
*/
if ($h['type'] == "date")
{
        $value = $this->convert_data($node[$h['tagname']], $h['type']);
        $table->cell($value,array('name'=>$h['header'], 'display'=>'table-cell'));
}
else if ($h['source'] == "comon")
{
	//print("<br>Searching for ".$h['tagname']."at ".$node);
        $value = $this->convert_data($this->live_data[$node['hostname']][$h['tagname']], $h['tagname']);

        $table->cell($value,array('name'=>$h['header'], 'display'=>'table-cell'));
}
else
{
        //$value = $node[$h['tagname']];
        $value = $this->convert_data($node[$h['tagname']], $h['type']);
        $table->cell($value,array('name'=>$h['header'], 'display'=>'table-cell'));
}
}
else 
	if ($node[$h['tagname']])
	{
        	$value = $this->convert_data($node[$h['tagname']], $h['type']);
        	$table->cell($value, array('name'=>$h['header'], 'display'=>'none'));
	}
	else
		if ($node[$h['fetched']])
        		$table->cell("n/a", array('name'=>$h['header'], 'display'=>'none'));
		else
        		$table->cell("??", array('name'=>$h['header'], 'display'=>'none'));
}
}

}


/*

HTML

*/


function javascript_init() {

print("<input type='hidden' id='selected_reference_node' value='".$this->reference_node."' />");

print("<script type='text/javascript'>");
print("highlightOption('AU');");
print("overrideTitles();");
print("</script>");

}

function quickselect_html() {

//return '<p>This link uses the onclick event handler.<br><a href="#" onclick="setVisible(\'quicklist\');return false" target="_self">Open popup</a></p>';


$quickselection = "<select id='quicklist' onChange=changeSelectStatus(this.value)><option value='0'>Short column descriptions and quick add/remove</option>";
//$quickselection = "<select id='quicklist'><option value='0'>Short column descriptions and quick add/remove</option>";
$prev_label="";
$optionclass = "out";
foreach ($this->all_headers as $h)
{
	if ($h['header'] == "hostname" || $h['header'] == "ID")
		continue;

	if ($h['fixed'])
		$disabled = "disabled=true";
	else
		$disabled = "";

        if ($this->headerIsVisible($h['label']))
	{
               	$optionclass = "in";
		//$selected = "selected=selected";
	}
	else
	{
               	$optionclass = "out";
		//$selected = "";
	}

	if ($prev_label == $h['label'])
		continue;

	$prev_label = $h['label'];


//$quickselection.="<option onclick=\"debugfilter('here2');removeSelectHandler(this);\" id='option'".$h['label']." class='".$optionclass."' value='".$h['label']."'><b>".$h['label']."</b>:&nbsp;".$h['title']."</option>";
$quickselection.="<option id='option'".$h['label']." class='".$optionclass."' value='".$h['label']."'><b>".$h['label']."</b>:&nbsp;".$h['title']."</option>";
}


$quickselection.="</select>";

return $quickselection;

}


function configuration_panel_html($showDescription) {

if ($showDescription)
	$table_width = 700;
else
	$table_width = 350;

print("<table align=center cellpadding=10 width=".$table_width.">");
print("<tr><th>Add/remove columns</th>");

if ($showDescription)
	print("<th>Column description and configuration</th>");

print("</tr><tr><td valign=top width=300>");

	print('<div id="scrolldiv" style="border : solid 2px grey; padding:4px; width:300px; height:180px; overflow:auto;">');
print ("<table>");
	$prev_label="";
	$optionclass = "out";
	foreach ($this->all_headers as $h)
	{
		if ($h['header'] == "hostname" || $h['header'] == "ID")
			continue;

		if ($h['fixed'])
			$disabled = "disabled=true";
		else
			$disabled = "";

        	if ($this->headerIsVisible($h['label']))
		{
                	$selected = "checked=true";
			$fetch = "true";
			//print("header ".$h['label']." checked!");
		}
        	else
		{
			$selected = "";
			if ($h['fetched'])
				$fetch = "true";
			else
				$fetch = "false";
		}

		print("<input type='hidden' id='tagname".$h['header']."' value='".$h['tagname']."'></input>");

		if ($prev_label == $h['label'])
			continue;

		$prev_label = $h['label'];
		$period = $this->get_selected_period($h['label']);

//<input type='hidden' id='fdesc".$h['label']."' value='".$h['description']."'></input>
        	print ("<tr><td>
<input type='hidden' id='fetched".$h['label']."' value=',".$period.",".$fetch."'></input>
<input type='hidden' id='period".$h['label']."' value='".$period."'></input>
<input type='hidden' id='type".$h['label']."' value='".$h['type']."'></input>
<input type='hidden' id='source".$h['label']."' value='".$h['source']."'></input>
		<div id='".$h['label']."' name='columnlist' class='".$optionclass."' onclick='highlightOption(this.id)'>
<table width=280 id='table".$h['label']."'><tr>
<td bgcolor=#CAE8EA align=center width=30><b><span style='color:#3399CC'>".$h['label']."</span></b></td> 
<td align=left>&nbsp;<span style='height:10px' id ='htitle".$h['label']."'>".$h['title']."</span>&nbsp;</td>
<td align=right width=20>&nbsp;<span style='height:10px' id ='loading".$h['label']."'></span>&nbsp;</td>
<td align=right width=20><input id='check".$h['label']."' name='".$h['tagname']."' type='checkbox' ".$selected." ".$disabled." autocomplete='off' value='".$h['label']."' onclick='changeCheckStatus(this.id)'></input></td>
</tr></table></div></td></tr>");
	}

	print("</table> </div></td>");

if ($showDescription)
{
	print("<td valign=top width=400>");
	print("<div class='myslice' id='selectdescr'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div></td>");
}

print("</tr>");
//print("<tr><td align=center>");
//print("<input type='button' value='Reset' onclick=resetCols('previousConf') />");
//print("<input type='button' value='Default' onclick=saveConfiguration('defaultConf') />");
//print("<input type='button' value='Reset table' onclick=\"resetConfiguration()\" />");
//print("</td>");
//print("&nbsp;<input type='button' value='Save configuration' onclick=saveConfiguration('column_configuration') />");
//print("&nbsp;<input type='button' id='fetchbutton' onclick='fetchData()' value='Fetch data' disabled=true /> </td>");

if ($showDescription)
	print("<td></td>");

print(" </tr> </table>");
}



function column_filter () {

echo <<< EOF

Highlight <select onChange="filterByType(this.value)">
<option value="none">None</option>
<option value="capabilities">Capabilities</option>
<option value="statistics">Statistics</option>
<option value="network">Network</option>
<option value="pairwise">Pairwise</option>
<option value="other">Other</option>
</select>
<p>

EOF;
}

  function column_html ($colHeader, $colName, $colId, $fulldesc, $visible) {

	if ($visible) 
		$display = 'display:table-cell';
	else 
		$display = 'color:red;display:none';

    return "
	<th class='sample plekit_table' name='confheader".$colHeader."' id='testid' style='".$display."'>
	<div id=\"".$colId."\" onclick=\"showDescription('".$colHeader."')\" onmouseover=\"showDescription('".$colHeader."')\">$colHeader</div>
       	</th>
	";
  }

  function column_fix_html ($colHeader, $colName, $colId) {

	$display = 'display:table-cell';

        $res="<th name='confheader".$colHeader."' class='fix plekit_table' style='$display'>";
		$res.= "<div id='$colId' onmouseover=\"showDescription('".$colHeader."')\">$colHeader</div></th>";

	return $res;
  }


function graph_html($colHeader) {

	return "<p><img src='/planetlab/slices/graph.png' width='20' align='BOTTOM'><input type='checkbox' id='graph".$colHeader."'></input> Show details on mouse over";

	}

function threshold_html($colHeader) {

	$updatecall = "updateColumnThreshold('".$colHeader."',window.document.getElementById('min".$colHeader."').value,window.document.getElementById('max".$colHeader."').value);";

	$bubble="<b>Grey-out values between</b>  <input type='text' id='min".$colHeader."' size='2' value='5'> (low) and <input type='text' id='max".$colHeader."' size='2' value='90'> (high) <input type='submit' value='Update' onclick=".$updatecall.">&nbsp;</input>"; 

	return $bublle;
}


/*

UTILS

*/

//simple strings
function inTypeA($header_name) {
	$typeA = array('ST','SN','RES','OS','NRR','NTP','NSR','NSF','NDS','NTH','NEC','LRN','LCY','LPR','LCN','LAT','LON','IP','ASN','AST');
	return in_array($header_name, $typeA);
}

//integers
function inTypeB($header_name) {
	$typeB = array('BW','DS','MS','CC','CR','AS','DU','CN');
	return in_array($header_name, $typeB);
}

//statistical values
function inTypeC($header_name) {
	$typeC = array('Rw','Rm','Ry','Lw','Lm','Ly','Sw','Sm','Sy','CFw','CFm','CFy','BUw','BUm','BUy','MUw','MUm','MUy','SSHw','SSHm','SSHy');
	return in_array($header_name, $typeC);
}

//tophat
function inTypeD($header_name) {
	$typeD = array('HC');
	return in_array($header_name, $typeD);
}


function removeDuration($header)
{
	if ($this->inTypeC($header))
		return substr($header, 0, strlen($header)-1);
	else
		return $header;
}

}

?>


