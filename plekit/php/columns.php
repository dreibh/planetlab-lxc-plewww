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
		{
			if ($this->all_headers[$conf[0]]['tagname'] == 'hopcount')
			{
				$this->reference_nodes['hopcount'] = $conf[1];
				//print ("ref node in configuration = ".$conf[1]);
				$this->all_headers[$conf[0]]['refnode']=$this->reference_nodes['hopcount'];
			}
			else if (strpos($this->all_headers[$conf[0]]['tagname'],"agents") === false)
				$this->tophat_live_data.=",".$this->all_headers[$conf[0]]['tagname'];
                	//$threshold = explode(",",$conf[1]);
                	//$this->all_headers[$conf[0]]['threshold']=$threshold;
		}

		//print_r($this->all_headers[$conf[0]]);

/*
		else if ($this->inTypeC($conf[0]))
        	{
                	$threshold = explode(",",$conf[1]);
                	$this->all_headers[$conf[0]]['threshold']=$threshold;
        	}
        	else if ($this->inTypeA($conf[0]))
        	{
                	$exclude_list = explode(",",$conf[1]);
                	$this->all_headers[$conf[0]]['exclude_list']=$exclude_list;
        	}
*/
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

function getTopHatRefNodes() {

	$tophat_auth = array( 'AuthMethod' => 'password', 'Username' => 'guest@top-hat.info', 'AuthString' => 'guest');
	$tophat_api = new TopHatAPI($tophat_auth);

	//print "calling tophat for agents";

	$agents = $tophat_api->Get('agents', 'latest', array('peer_name'=>array('PLC', 'PLE'), 'agent_status'=> 'OK'),  array('hostname'));

	//print_r($agents);

	return $agents;

}

function getPairwise($ref_node, $planetlab_nodes, $command, $data) {

	$tophat_auth = array( 'AuthMethod' => 'password', 'Username' => 'guest@top-hat.info', 'AuthString' => 'guest');
	$tophat_api = new TopHatAPI($tophat_auth);

	$traceroute = $tophat_api->Get($command, 'latest', array('src_hostname' => $ref_node, 'dst_hostname' => $planetlab_nodes), array('dst_hostname', $data) );

	print "Got result: ".$traceroute;

	$hopcount = array();

	if ($traceroute) 
	{
		foreach ($traceroute as $t)
		{
			$hopcount[$t['dst_hostname']]=$t[$data];
			//print "  current: ".$t['dst_hostname'].":".$t['hop_count'];
		}

		return $hopcount;
	}
	else
		return "";
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

	$str=file_get_contents($url);

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

//TopHat pairwise data

	$this->HopCount = "";
	$this->RTT = "";

	if ($this->reference_nodes != "")
	{
		//print_r($this->reference_nodes);

		$dd = array();

		if ($all_nodes) foreach ($all_nodes as $n)
			$dd[] = $n['hostname'];

		$st = time() + microtime();
		if ($this->headerIsVisible("HC"))
		{
			print("[NEW] Calling tophat api for HopCount with reference node = ".$this->reference_nodes['hopcount']);
			$this->HopCount = $this->getPairwise($this->reference_nodes['hopcount'], $dd, 'traceroute', 'hop_count');
		}
		else 

		if ($this->headerIsVisible("RTT"))
		{
			print("[NEW] Calling tophat api for RTT with reference node = ".$this->reference_nodes['rtt']);
			$this->RTT = $this->getPairwise($this->reference_nodes['rtt'], $dd, 'rtt','rtt');
		}

		//printf(" (%.2f ms)<br/>", (time() + microtime()-$st)*100);
		print_r($this->HopCount);
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

//$node_string = "";

foreach ($this->all_headers as $h) {

if (!$h['fixed']) { 

if ($h['visible'] != "") {

/*
if ($this->inTypeB($h['header']))
{
        $value = $node[$h['tagname']];
        $v = $this->checkThreshold($value, $h['threshold'], $h['header']);
        $table->cell($v[0],$v[1]);
}
else if ($this->inTypeA($h['header']))
{
        $value = $node[$h['tagname']];
        $v = $this->excludeItems($value, $h['exclude_list'], $h['header']);
        $table->cell($v[0],$v[1]);
}
*/
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
	//print("<br>Searching for ".$h['tagname']."at ".$node);
	if ($h['tagname'] == "hopcount")
	{
		//print "value = ".$this->HopCount[$node['hostname']];
		//$value = "hc";
		if ($this->HopCount != "")
        		$value = $this->HopCount[$node['hostname']];
		else
			$value = "n/a";
	}
	else if ($h['tagname'] == "rtt")
	{
		if ($this->RTT != "")
			if ($this->RTT[$node['hostname']] != "")
        			$value = $this->RTT[$node['hostname']];
			else
				$value = "n/a";
		else
			$value = "n/a";
	}	
	else if ($h['tagname'] == "agents")
	{
		if ($this->TopHatAgents != "")
			if ($this->TopHatAgents[$node['hostname']] != "")
        			$value = $this->TopHatAgents[$node['hostname']]['all'];
			else
				$value = "n/a";
		else
			$value = "n/a";
	}	
	else if ($h['tagname'] == "agents_tdmi")
	{
		if ($this->TopHatAgents != "")
			if ($this->TopHatAgents[$node['hostname']] != "")
        			$value = $this->TopHatAgents[$node['hostname']]['tdmi'];
			else
				$value = "n/a";
		else
			$value = "n/a";
	}	
	else if ($h['tagname'] == "agents_sonoma")
	{
		if ($this->TopHatAgents != "")
			if ($this->TopHatAgents[$node['hostname']] != "")
        			$value = $this->TopHatAgents[$node['hostname']]['sonoma'];
			else
				$value = "n/a";
		else
			$value = "n/a";
	}	
	else
	{
		if ($this->TopHatData != "")
        		$value = $this->convert_data($this->TopHatData[$node['hostname']][$h['tagname']], $h['type']);
		else
			$value = "n/a";
	}

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

$refnodes = $this->getTopHatRefNodes();
//$tophat_agents = $this->getTopHatAgents();
$ref_nodes = "";
foreach ($refnodes as $r)
{
	if ($r['hostname'] == $this->reference_nodes['hopcount'])
		$selected = "selected=selected";
	else
		$selected = "";

	$ref_nodes = $ref_nodes."<option value=".$r['hostname']." ".$selected.">".$r['hostname']."</option>";
}

print("<input type='hidden' id='selected_reference_node' value='".$this->reference_nodes['hopcount']."' />");

print("<script type='text/javascript'>");
print("highlightOption('AU');");
print("overrideTitles();");
print "var ref_nodes_select =\"Select reference node: <select id='refnodeHC' onChange='updateReferenceNode(this.id,this.value)'>".$ref_nodes."</select>\";";
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

//<input type='hidden' id='fdesc".$h['label']."' value='".$h['description']."'></input>
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


