<?php

require_once 'tophat_api.php';

drupal_set_html_head('
<script type="text/javascript" src="/plekit/table/columns.js"></script>
');

class PlekitColumns {

var $column_configuration = "";
var $reference_nodes = array();
var $first_time = false;

var $all_headers = array();
var $this_table_headers = array();
var $visible_headers = array();

var $fix_columns = array();
var $tag_columns = array();
var $extra_columns = array();

var $comon_live_data = "";
var $tophat_live_data = "";
var $ComonData = array();
var $TopHatData = array();
var $TopHatAgents = array();

var $table_ids;

var $HopCount = array();
var $RTT = array();

function PlekitColumns ($column_configuration, $fix_columns, $tag_columns, $extra_columns=NULL, $this_table_headers=NULL) {

	if ($column_configuration != NULL) {
	$this->fix_columns = $fix_columns;
	$this->tag_columns = $tag_columns;
	$this->extra_columns = $extra_columns;

	$this->prepare_headers();
	$this->parse_configuration($column_configuration);

	$this->visible_headers = $this->get_visible();
	}
}



/*

INFO/HEADERS

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
			$this->tophat_live_data.=",".$this->all_headers[$conf[0]]['tagname'];
	}

}


		


/*

CELLS

*/

function convert_data($value, $data_type) {

	//print "converting ".$value." as ".$data_type;

	if ($value == "" || $value == null || $value == "n/a" || $value == "None")
		return "n/a";

	if ($data_type == "string")
		return $value;

	if ($data_type == "date") 
		return date("Y-m-d", $value);

	if ($data_type == "uptime") 
		return (int)((int) $value / 86400);

	if (is_numeric($value))
		return ((int) ($value * 10))/10;
	
	return $value;

}

function getTopHatAgents() {

	$tophat_auth = array( 'AuthMethod' => 'password', 'Username' => 'guest@top-hat.info', 'AuthString' => 'guest');
	$tophat_api = new TopHatAPI($tophat_auth);

	//print ("Requesting tophat agents...");
	//print_r($r);

	$values = $tophat_api->Get('agents', 'latest', array('colocated.platform_name' => array('SONoMA', 'DIMES', 'ETOMIC', 'TDMI'), 'platform_name'=> 'TDMI'), array('hostname', 'colocated.peer_name', 'colocated.platform_name'));

	$result = array();

	if ($values) foreach ($values as $t) {
		//print_r($t);
		//print("<hr>");
		$result[$t['hostname']] = "";
		foreach ($t['colocated'] as $ll) {

			if (strpos($result[$t['hostname']]['all'],$ll['platform_name']) === false) {
				if ($result[$t['hostname']]['all'] != "")
					$result[$t['hostname']]['all'] .= ",";
				$result[$t['hostname']]['all'] .= $ll['platform_name'];
			}

			if ($ll['platform_name'] == 'SONoMA') {
			if (strpos($result[$t['hostname']]['sonoma'],$ll['peer_name']) === false) {
					if ($result[$t['hostname']]['sonoma'] != "")
						$result[$t['hostname']]['sonoma'] .= ",";
					$result[$t['hostname']]['sonoma'] .= $ll['peer_name'];
			}
			}

			if ($ll['platform_name'] == 'TDMI') {
			if (strpos($result[$t['hostname']]['tdmi'],$ll['peer_name']) === false) {
				if ($result[$t['hostname']]['tdmi'] != "")
					$result[$t['hostname']]['tdmi'] .= ",";
				$result[$t['hostname']]['tdmi'] .= $ll['peer_name'];
			}
			}
		}
	}

	$this->TopHatAgents = $result;

	//print_r($this->TopHatAgents);

	return $result;
}

function getTopHatData($data, $planetlab_nodes) {

	$tophat_auth = array( 'AuthMethod' => 'password', 'Username' => 'guest@top-hat.info', 'AuthString' => 'guest');
	$tophat_api = new TopHatAPI($tophat_auth);

	$requested_data = explode(",", $data);

	$r = array ('hostname');
	
	foreach ($requested_data as $rd)
		if ($rd) $r[] = $rd;

	//print ("Requesting data from TopHat ...");
	//print_r($r);

	$values = $tophat_api->Get('ips', 'latest', array('hostname' => $planetlab_nodes), $r );

	$result = array();

	if ($values) foreach ($values as $t)
		foreach ($requested_data as $rd)
			if ($rd) $result[$t['hostname']][$rd] = $t[$rd];

	//print_r($result);

	return $result;
}


function comon_query_nodes($requested_data) {

	$comon_url = "http://comon.cs.princeton.edu";
	$comon_api_url = "status/tabulator.cgi?table=table_nodeviewshort&format=formatcsv&dumpcols='name";

	if (MYSLICE_COMON_URL != "")
		$comon_url = MYSLICE_COMON_URL;

	$url = $comon_url."/".$comon_api_url.$requested_data."'";

	//print ("Retrieving comon data for url ".$url);

	$sPattern = '\', \'';
	$sReplace = '|';

	$str=@file_get_contents($url);

	if ($str === false)
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
		$this->ComonData= $this->comon_query_nodes($this->comon_live_data);
		//print_r($this->ComonData);
	}

//TopHat per_node data
	if ($this->tophat_live_data != "")
	{
		$dd = array();

		if ($all_nodes) foreach ($all_nodes as $n)
			$dd[] = $n['hostname'];

		//print("Calling tophat api for ".$this->tophat_live_data);
		$st = time() + microtime();
		$this->TopHatData = $this->getTopHatData($this->tophat_live_data, $dd);
		//printf(" (%.2f ms)<br/>", (time() + microtime()-$st)*100);
		//print_r($this->TopHatData);
	}

}


function cells($table, $node) {

//$node_string = "";

foreach ($this->all_headers as $h) {

if (!$h['fixed']) { 

if ($h['visible'] != "") {

if ($h['source'] == "comon")
{
	//print("<br>Searching for ".$h['tagname']."at ".$node);
	if ($this->ComonData != "")
        	$value = $this->convert_data($this->ComonData[$node['hostname']][$h['tagname']], $h['tagname']);
	else
		$value = "n/a";

        $table->cell($value,array('name'=>$h['header'], 'display'=>'table-cell'));
	//$node_string.= "\"".$value."\",";
}
else if ($h['source'] == "tophat")
{
	if ($this->TopHatData != "")
        	$value = $this->convert_data($this->TopHatData[$node['hostname']][$h['tagname']], $h['type']);
	else
		$value = "n/a";

        $table->cell($value,array('name'=>$h['header'], 'display'=>'table-cell'));
	//$node_string.= "\"".$value."\",";
}
else
{
        //$value = $node[$h['tagname']];
        $value = $this->convert_data($node[$h['tagname']], $h['type']);
        $table->cell($value,array('name'=>$h['header'], 'display'=>'table-cell'));
	//$node_string.= "\"".$value."\",";
}
}
else 
	if ($node[$h['tagname']])
	{
        	$value = $this->convert_data($node[$h['tagname']], $h['type']);
        	$table->cell($value, array('name'=>$h['header'], 'display'=>'none'));
	}
	else
        	$table->cell("n/a", array('name'=>$h['header'], 'display'=>'none'));
}
}

//return $node_string;

}


/*

HTML

*/


function javascript_init() {

print("<script type='text/javascript'>");
print("highlightOption('AU');");
print("overrideTitles();");
print("</script>");

}

function quickselect_html() {

$quickselection = "<select id='quicklist' onChange=changeSelectStatus(this.value)><option value='0'>Short column descriptions and quick add/remove</option>";
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
               	$optionclass = "in";
	else
               	$optionclass = "out";

	if ($prev_label == $h['label'])
		continue;

	$prev_label = $h['label'];

	$quickselection.="<option id='option'".$h['label']." class='".$optionclass."' value='".$h['label']."'><span class='bold'>".$h['label']."</span>:&nbsp;".$h['title']."</option>";

}

$quickselection.="</select>";

return $quickselection;

}


function configuration_panel_html($showDescription) {

if ($showDescription)
	$table_width = 700;
else
	$table_width = 350;

print("<table class='center' width='".$table_width."px'>");
print("<tr><th class='top'>Add/remove columns</th>");

if ($showDescription)
	print("<th class='top'>Column description and configuration</th>");

print("</tr><tr><td class='top' width='300px'>");

	print('<div id="scrolldiv">');
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

        	print ("<tr><td>
<input type='hidden' id='fetched".$h['label']."' value=',".$period.",".$fetch."'></input>
<input type='hidden' id='period".$h['label']."' value='".$period."'></input>
<input type='hidden' id='type".$h['label']."' value='".$h['type']."'></input>
<input type='hidden' id='source".$h['label']."' value='".$h['source']."'></input>
		<div id='".$h['label']."' name='columnlist' class='".$optionclass."' onclick='highlightOption(this.id)'>
<table class='columnlist' id='table".$h['label']."'><tr>
<td class='header'><span class='header'>".$h['label']."</span></td> 
<td align=left>&nbsp;<span class='short' id ='htitle".$h['label']."'>".$h['title']."</span>&nbsp;</td>
<td class='smallright'>&nbsp;<span class='short' id ='loading".$h['label']."'></span>&nbsp;</td>
<td class='smallright'><input id='check".$h['label']."' name='".$h['tagname']."' type='checkbox' ".$selected." ".$disabled." autocomplete='off' value='".$h['label']."' onclick='changeCheckStatus(this.id)'></input></td>
</tr></table></div></td></tr>");
	}

	print("</table> </div></td>");

if ($showDescription)
{
	print("<td class='top' width='400px'>");
	print("<div id='selectdescr'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div></td>");
}

print("</tr>");

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


