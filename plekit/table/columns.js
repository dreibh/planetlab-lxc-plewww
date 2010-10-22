
/* $Id: column.js Panos $ */

var filtered_color = "grey";
var normal_color = "black";

//Descriptions overriding the default ones set in Accessors_site.py and configuration 

var sourceComon = '<a target="source_window" href="http://comon.cs.princeton.edu/">CoMon</a>';
var sourceTophat = '<b><a target="source_window" href="http://www.top-hat.info/">TopHat</a></b>';
var sourceTophatAPI = '<b><a target="source_window" href="http://www.top-hat.info/API/">TopHat API</a></b>';
var sourceMySlice = '<b><a target="source_window" href="http://myslice.info/">MySlice</a></b>';
var sourceCymru = '<b><a target="source_window" href="http://www.team-cymru.org/">Team Cymru</a></b>';
var sourceMyPLC = '<b><a target="source_window" href="https://www.planet-lab.eu/db/doc/PLCAPI.php">MyPLC API</a></b>';
var sourceManiacs = '<b><a target="source_window" href="http://www.ece.gatech.edu/research/labs/MANIACS/as_taxonomy/">MANIACS</a></b>';
var sourceMaxmind = '<b><a target="source_window" href="http://www.maxmind.com/app/geolitecity">MaxMind</a></b>';
var sourceMonitor = '<b><a target="source_window" href="http://monitor.planet-lab.org/">Monitor</a></b>';
var selectReferenceNode ='Select reference node: <select id="reference_node" onChange="updateDefaultConf(this.value)"><option value=planetlab-europe-07.ipv6.lip6.fr>planetlab-europe-07.ipv6.lip6.fr</option></select>';
var addButton = '<input id="addButton" type="button" value="Add" onclick=addColumnAjax(document.getElementById("list1").value)></input>';
var deleteButton = '<input id="deleteButton" type="button" value="Delete" onclick=deleteColumn(window.document.getElementById("list1").value)></input>';

var descHOSTNAME = "test";

var titleA = 'Architecture name';
var detailA = '<i>The node architecture.</i>';
var sourceA = '<b>Source:</b> '+sourceMyPLC;
var valuesA = 'Values: <b>x86_64</b>, <b>i386</b>';
var descA = '<span class="myslice title">'+titleA+'</span><p>'+detailA+'<p>'+valuesA+'<p>'+sourceA;

var titlef = 'Operating system';
var detailf = '<i>Fedora or CentOS distribution to use for node or slivers.</i>';
var sourcef = '<b>Source:</b> '+sourceMyPLC;
var descf = '<span class="myslice title">'+titlef+'</span><p>'+detailf+'<p>'+sourcef;

var titleAU = 'Authority';
var detailAU = '<i>The authority of the global PlanetLab federation that the site of the node belongs to.</i>';
var valuesAU = 'Values: <b>PLC</b> (PlanetLab Central), <b>PLE</b> (PlanetLab Europe)';
var sourceAU = '<b>Source:</b> '+sourceMyPLC;
var descAU = '<span class="myslice title">'+titleAU+'</span><p>'+detailAU+'<p>'+valuesAU+'<p>'+sourceAU;

var titleAS = 'Autonomous system ID';
var sourceAS = 'Source: '+sourceCymru+' (via '+sourceTophat+')';
var valuesAS = 'Unit: <b>Integer between 0 and 65535</b>';
var descAS = '<span class="myslice title">'+titleAS+'</span><p>'+valuesAS+'<p>' + sourceAS;

var titleAST = 'Autonomous system type';
var sourceAST = 'Source: '+sourceManiacs;
var valuesAST = 'Values: <b>t1</b> (tier-1), <b>t2</b> (tier-2), <b>edu</b> (university), <b>comp</b> (company), <b>nic</b> (network information centre -- old name for a domain name registry operator), <b>ix</b> (IXP), <b>n/a</b>';
var descAST = '<span class="myslice title">'+titleAST+'</span><p>'+valuesAST+'<p>'+sourceAST;

var titleASN = 'Autonomous system name';
var sourceASN = 'Source: '+sourceTophat;
var descASN = '<span class="myslice title">'+titleASN+'</span><p>'+sourceASN;

var selectPeriodBU = 'Select period: <select id="selectperiodBU" onChange=updatePeriod("BU",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleBU = 'Bandwidth utilization ';
var sourceBU = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesBU ='Unit: <b>Kbps</b>';
var detailBU = '<i>The average transmited bandwidh over the selected period. The period is the most recent for which data is available, with CoMon data being collected by MySlice daily.</i>'
var descBU = '<span class="myslice title">'+titleBU+'</span><p>'+detailBU+'<p>'+selectPeriodBU+'<p>'+valuesBU+'<p>'+sourceBU; 

var titleBW= 'Bandwidth limit';
var sourceBW = 'Source: '+sourceComon;
var valuesBW = 'Unit: <b>Kbps</b>';
var detailBW = '<i>The bandwidth limit is a cap on the total outbound bandwidth usage of a node. It is set by the site administrator (PI). For more details see <a targe="source_window" href="http://www.planet-lab.org/doc/BandwidthLimits">Bandwidth Limits (planet-lab.org)</a></i>.';
var descBW = '<span class="myslice title">'+titleBW+'</span><p>'+detailBW+'<p>'+valuesBW+'<p>'+sourceBW;

var titleCC = 'Number of CPU cores';
var sourceCC = 'Source: '+sourceComon;
var valuesCC = 'Current PlanetLab hardware requirements: 4 cores min. <br><i>(Older nodes may have fewer cores)</i>.';
var descCC = '<span class="myslice title">'+titleCC+'</span><p>'+valuesCC+'<p>'+sourceCC;

var titleCN = 'Number of CPUs';
var sourceCN = 'Source: '+sourceComon;
var valuesCN = 'Current PlanetLab hardware requirements: <b>1 (if quad core) or 2 (if dual core)</b>';
var descCN = '<span class="myslice title">'+titleCN+'</span><p>'+valuesCN+'<p>'+sourceCN;

var titleCR = 'CPU clock rate';
var sourceCR = 'Source: '+sourceComon;
var valuesCR = 'Unit: <b>GHz</b><p>Current PlanetLab hardware requirements: <b>2.4 GHz</b>';
var descCR = '<span class="myslice title">'+titleCR+'</span><p>'+valuesCR+'<p>'+sourceCR;

var selectPeriodCF = 'Select period: <select id="selectperiodCF" onChange=updatePeriod("CF",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleCF = 'Free CPU';
var sourceCF = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesCF = 'Unit: <b>%</b>';
var detailCF = '<i> The average CPU percentage that gets allocated to a test slice named burb that is periodically run by CoMon.</i>';
var descCF = '<span class="myslice title">'+titleCF+'</span><p>'+detailCF+'<p>'+selectPeriodCF+'<p>'+valuesCF+'<p>'+sourceCF; 

var titleDN = 'Toplevel domain name';
var sourceDN = 'Source: '+sourceMyPLC;
var descDN = '<span class="myslice title">'+titleDN+'</span><p>'+sourceDN;

var titleDS = 'Disk size';
var sourceDS = 'Source: '+sourceComon;
var valuesDS = 'Unit: <b>GB</b><p>Current PlanetLab hardware requirements: <b>500 GB</b>';
var descDS = '<span class="myslice title">'+titleDS+'</span><p>'+valuesDS+'<p>'+sourceDS;

var titleDU = 'Current disk utilization';
var sourceDU = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesDU = 'Unit: <b>GB</b>';
var detailDU = '<i> The amount of disk space currently consumed (checked daily).</i>';
var descDU = '<span class="myslice title">'+titleDU+'</span><p>'+detailDU+'<p>'+valuesDU+'<p>'+sourceDU;

var titleDF = 'Disk space free';
var sourceDF = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesDF = 'Unit: <b>GB</b>';
var detailDF = '<i> The amount of disk space currently available (checked daily).</i>';
var descDF = '<span class="myslice title">'+titleDF+'</span><p>'+detailDF+'<p>'+valuesDF+'<p>'+sourceDF;

var titleHC = 'Hop count (pairwise)';
var sourceHC = 'Source: '+sourceTophat;
var detailHC = '<i>TopHat conducts traceroutes every five minutes in a full mesh between all PlanetLab nodes. The hop count is the length of the traceroute from the node to the reference node, based upon the most recently reported traceroute</i>.';
var descHC = '<span class="myslice title">'+titleHC+'</span><p>'+detailHC+'<p>'+selectReferenceNode+'<p>'+sourceHC;

var titleIP = 'IP address';
var sourceIP = 'Source: '+sourceMyPLC;
var descIP = '<span class="myslice title">'+titleIP+'</span><p>'+sourceIP;

var selectPeriodL = 'Select period: <select id="selectperiodL" onChange=updatePeriod("L",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleL= 'Load ';
var sourceL = 'Source: '+sourceComon;
var valuesL = 'Unit: <b>5-minute load</b>';
var detailL = '<i>The average 5-minute load (as reported by the Unix uptime command) over the selected period.</i>';
var descL = '<span class="myslice title">'+titleL+'</span><p>'+detailL+'<p>'+selectPeriodL+'<p>'+valuesL+'<p>'+sourceL; 

var titleLON= 'Longitude';
var sourceLON = 'Source: '+sourceTophat;
var descLON = '<span class="myslice title">'+titleLON+'</span><p>'+sourceLON;

var titleLAT= 'Latitude';
var sourceLAT = 'Source: '+sourceTophat;
var descLAT = '<span class="myslice title">'+titleLAT+'</span><p>'+sourceLAT;

var titleLCN= 'Location (Country)';
var sourceLCN = 'Source: '+sourceMaxmind+' (via '+sourceTophat+')';
var detailLCN = '<i>Based on the latitude and longitude information.</i>';
var descLCN = '<span class="myslice title">'+titleLCN+'</span><p>'+detailLCN+'<p>'+sourceLCN;

var titleLCT= 'Location (Continent)';
var sourceLCT = 'Source: '+sourceMaxmind+' (via '+sourceTophat+')';
var detailLCT = '<i>Based on the latitude and longitude information.</i>';
var descLCT = '<span class="myslice title">'+titleLCT+'</span><p>'+detailLCT+'<p>'+sourceLCT;

var titleLCY= 'Location (City)';
var sourceLCY = 'Source: '+sourceMaxmind+' (via '+sourceTophat+')';
var detailLCY = '<i>Based on the latitude and longitude information.</i>';
var descLCY = '<span class="myslice title">'+titleLCY+'</span><p>'+detailLCY+'<p>'+sourceLCY;

var titleLPR= 'Location precision radius';
var sourceLPR = 'Source: '+sourceTophat;
var valuesLPR = 'Unit: <b>float</b>';
var detailLPR = '<i>The radius of the circle corresponding to the error in precision of the geolocalization estimate.</i>';
var descLPR = '<span class="myslice title">'+titleLPR+'</span><p>'+detailLPR+'<p>'+valuesLPR+'<p>'+sourceLPR;

var titleLRN= 'Location (Region)';
var sourceLRN = 'Source: '+sourceMaxmind+' (via '+sourceTophat+')';
var detailLRN = '<i>Based on the latitude and longitude information.</i>';
var descLRN = '<span class="myslice title">'+titleLRN+'</span><p>'+detailLRN+'<p>'+sourceLRN;

var titleMS= 'Memory size';
var sourceMS = 'Source: '+sourceComon;
var valuesMS = 'Unit: <b>GB</b><p>Current PlanetLab hardware requirements: <b>4 GB</b>.';
var descMS = '<span class="myslice title">'+titleMS+'</span><p>'+valuesMS+'<p>'+sourceMS;

var selectPeriodMU = 'Select period: <select id="selectperiodMU" onChange=updatePeriod("MU",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleMU = 'Memory utilization';
var sourceMU = 'Source: '+sourceComon;
var valuesMU = '<p>Unit: <b>%</b>';
var detailMU = '<i>The average active memory utilization as reported by CoMon.</i>';
var descMU = '<span class="myslice title">'+titleMU+'</span><p>'+detailMU+'<p>'+selectPeriodMU+'<p>'+valuesMU+'<p>'+sourceMU; 

var titleNEC= 'Network information (ETOMIC)';
var sourceNEC = 'Source: '+sourceTophat;
var valuesNEC = 'Values: <b>yes/no</b>';
var detailNEC = '<i>The existence of a colocated ETOMIC box. When an ETOMIC box is present, you have the possibility to conduct high-precision measurements through the '+sourceTophatAPI+'.</i>';
var descNEC = '<span class="myslice title">'+titleNEC+'</span><p>'+detailNEC+'<p>'+valuesNEC+'<p>'+sourceNEC;

var titleNSN= 'Network information (SONoMA)';
var sourceNSN = 'Source: '+sourceTophat;
var valuesNSN = 'Values: <b>yes/no</b>';
var detailNSN = '<i>The existence of a SONoMA agent. When an SONoMA is present, you have the possibility to have access to high-precision measurements through the '+sourceTophatAPI+'.</i>';
var descNSN = '<span class="myslice title">'+titleNSN+'</span><p>'+detailNSN+'<p>'+valuesNSN+'<p>'+sourceNSN;

var titleNTH= 'Network information (TopHat)';
var sourceNTH = 'Source: '+sourceTophat;
var valuesNTH = 'Values: <b>yes/no</b>';
var detailNTH = '<i>The existence of a colocated TDMI (TopHat Dedicated Measurement Infrastructure) agent. When a TDMI agent is present, you have access to a wide variety of network topology measurements through the '+sourceTophatAPI+'.</i>';
var descNTH = '<span class="myslice title">'+titleNTH+'</span><p>'+detailNTH+'<p>'+valuesNTH+'<p>'+sourceNTH;

var titleNDS= 'Network information (DIMES)';
var sourceNDS = 'Source: '+sourceTophat;
var valuesNDS = 'Values: <b>yes/no</b>';
var detailNDS = '<i>The existence of a colocated DIMES agent. When a DIMES agent is present, you have access to DIMES measurements through the '+sourceTophatAPI+'.</i>';
var descNDS = '<span class="myslice title">'+titleNDS+'</span><p>'+detailNDS+'<p>'+valuesNDS+'<p>'+sourceNDS;

var titleNSF= 'Network information (spoof)';
var sourceNSF = 'Source: '+sourceTophat;
var valuesNSF = '<p>Values: <b>yes/no</b>';
var detailNSF = '<i> Whether the node can send packets successfully (or not) with a spoofed IP source address.</i>';
var descNSF = '<span class="myslice title">'+titleNSF+'</span><p>'+detailNSF+'<p>'+valuesNSF+'<p>'+sourceNSF;

var titleNSR= 'Network information (source route)';
var sourceNSR = 'Source: '+sourceTophat;
var valuesNSR = '<p>Values: <b>yes/no</b>';
var detailNSR = '<i> Whether the node can send packets packets using the IP source route option. See <a target="info_window" href="http://www.networksorcery.com/enp/protocol/ip/option003.htm">here</a>for more info.</i>';
var descNSR = '<span class="myslice title">'+titleNSR+'</span><p>'+detailNSR+'<p>'+valuesNSR+'<p>'+sourceNSR;

var titleNTP= 'Network information (timestamp)';
var sourceNTP = 'Source: '+sourceTophat;
var valuesNTP = '<p>Values: <b>yes/no</b>';
var detailNTP = '<i> Whether the node can send packets packets using the IP timestamp option. See <a target="info_window" href="http://www.networksorcery.com/enp/protocol/ip/option004.htm">here</a>for more info.</i>';
var descNTP = '<span class="myslice title">'+titleNTP+'</span><p>'+detailNTP+'<p>'+valuesNTP+'<p>'+sourceNTP;

var titleNRR= 'Network information (record route)';
var sourceNRR = 'Source: '+sourceTophat;
var valuesNRR = '<p>Values: <b>yes/no</b>';
var detailNRR = '<i> Whether the node can send packets packets using the IP record route option. See <a target="info_window" href="http://www.networksorcery.com/enp/protocol/ip/option007.htm">here</a>for more info.</i>';
var descNRR = '<span class="myslice title">'+titleNRR+'</span><p>'+detailNRR+'<p>'+valuesNRR+'<p>'+sourceNRR;

var titleOS = 'Operating system';
var sourceOS = 'Source: '+sourceMyPLC;
var valuesOS = 'Values: <b>Fedora, Cent/OS, other, n/a</b>';
var descOS = '<span class="myslice title">'+titleOS+'</span><p>'+valuesOS+'<p>'+sourceOS;

var selectPeriodR = 'Select period: <select id="selectperiodR" onChange=updatePeriod("R",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleR = 'Reliability';
var sourceR = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var detailR = '<i>CoMon queries nodes every 5 minutes, for 255 queries per day. The average reliability is the percentage of queries over the selected period for which CoMon reports a value. The period is the most recent for which data is available, with CoMon data being collected by MySlice daily.</i>';
var valuesR = 'Unit: <b>%</b>';
var descR = '<span class="myslice title">'+titleR+'</span><p>'+detailR+'<p>'+selectPeriodR+'<p>'+valuesR+'<p>'+sourceR; 

var titleRES = 'Reservation capabilities';
var sourceRES = 'Source: '+sourceMyPLC;
//var valuesRES = 'Values: <b>yes/no</b>';
var valuesRES = 'Values: <b>-R-</b> (if yes)';
var detailRES = '<i> Whether the node can be reserved for a certain duration.<br>Your slivers will be available <span class=bold>only during timeslots where you have obtained leases (see tab above)</span></i>.  <p>Please note that as of August 2010 this feature is experimental.  Feedback is appreciated at <a href="mailto:devel@planet-lab.org">devel@planet-lab.org</a>';
var descRES = '<span class="myslice title">'+titleRES+'</span><p>'+detailRES+'<p>'+valuesRES+'<p>'+sourceRES;

var selectPeriodS = 'Select period: <select id="selectperiodS" onChange=updatePeriod("S",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleS = 'Active slices';
var sourceS = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesS = 'Unit: <b>%</b>';
var detailS = '<i>Average number of active slices over the selected period for which CoMon reports a value. The period is the most recent for which data is available, with CoMon data being collected by MySlice daily.</i>';
var descS = '<span class="myslice title">'+titleS+'</span><p>'+detailS+'<p>'+selectPeriodS+'<p>'+valuesS+'<p>'+sourceS; 

var titleSN = 'Site name';
var sourceSN = 'Source: '+sourceMyPLC;
var descSN = '<span class="myslice title">'+titleSN+'</span><p>'+sourceSN;

var selectPeriodSSH = 'Select period: <select id="selectperiodSSH" onChange=updatePeriod("SSH",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleSSH = 'Average SSH response delay';
var valuesSSH = 'Unit: <b>%</b>';
var detailSSH = '<i>The average response delay of the node to SSH logins over the selected period for which CoMon reports a value. The period is the most recent for which data is available, with CoMon data being collected by MySlice daily.</i>';
var sourceSSH ='Source: '+sourceComon+' (via '+sourceMySlice+')';
var descSSH = '<span class="myslice title">'+titleSSH+'</span><p>'+detailSSH+'<p>'+selectPeriodSSH+'<p>'+valuesSSH+'<p>'+sourceSSH; 

var titleST = 'Status';
var sourceST = 'Source: '+sourceMonitor;
var valuesST = 'Values: <b>online</b> (up and running), <b>good</b> (up and running recently), <b>offline</b> (unreachable today), <b>down</b> (node unreachable for more than one day), <b>failboot</b> (reachable, but only by administrators for debugging purposes).';
var descST = '<span class="myslice title">'+titleST+'</span><p>'+valuesST+'<p>'+sourceST;


//Categorization of columns in different types, useful for filtering 

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


//Debugging
function debugfilter(s) {
	document.getElementById('debug').innerHTML+=s;
}


//Called when a column is selected. It displays the detailed description
//on the right panel

function highlightOption(divid) {

	//debugfilter("highlighting option "+divid);

	var columns = document.getElementsByName('columnlist');
	for(var j = 0; j < columns.length; j++)
		columns[j].className = 'out'; 

	document.getElementById(divid).className = 'selected';

	showDescription(divid);

//panos: to IMPROVE 
	if (document.getElementById('selectperiod'+divid))
		document.getElementById('selectperiod'+divid).value = document.getElementById('period'+divid).value;

}


//Displays the detailed column description 

function showDescription(h) {

	//debugfilter("showing description "+h);

//Checks if the detailed description div exists 
	if (document.getElementById('selectdescr'))
	{
		//Checks if there is a detailed description defined
		if (window['desc'+h])
			document.getElementById('selectdescr').innerHTML = ""+window['desc'+h];
		//else if (document.getElementById('fdesc'+h))
			//document.getElementById('selectdescr').innerHTML = document.getElementById('fdesc'+h).value;
		else 
			document.getElementById('selectdescr').innerHTML = "No detailed description provided";
	}
}


//Overrides the titles of the columns as they are shown in the column selection panel.
//If no overriding variable exists the tag's description is used
function overrideTitles() {

	var columns = document.getElementsByName('columnlist');

	for(var j = 0; j < columns.length; j++)
	{
		var kk = columns[j].id;
		if (window['title'+kk])
			document.getElementById('htitle'+kk).innerHTML = window['title'+kk];
	}
}

//When the checkbox is clicked. Adds/removes column respectively
function changeCheckStatus(column) {

	if (document.getElementById('selectdescr'))
		showDescription(document.getElementById(column).value);

	if (document.getElementById(column).checked)
		addColumn(document.getElementById(column).value, true);
	else
		deleteColumn(document.getElementById(column).value);
}

function removeSelectHandler(object)
{
	debugfilter(object);
        object.onclick = null;
}


//This function is used when the alternative "quick" selection list is used
function changeSelectStatus(column) {

	var optionClass = "";
	var selected_index = document.getElementById('quicklist').selectedIndex;

	if (document.getElementById('quicklist') && selected_index != 0)
	{

		optionClass = document.getElementById('quicklist').options[selected_index].className;

		if (optionClass == "in")
		{
			deleteColumn(document.getElementById('quicklist').value);
			document.getElementById('quicklist').options[selected_index].className = "out";
			document.getElementById('quicklist').value="0";
		}
		else
		{
			addColumn(document.getElementById('quicklist').value, true);
			document.getElementById('quicklist').options[selected_index].className = "in";
			document.getElementById('quicklist').value="0";
		}
	}
}

//When the period of an already selected column is changed
function updatePeriod(h, new_period) {

	var old_period = document.getElementById('period'+h).value;
	document.getElementById('period'+h).value=new_period;

	//debugfilter(h+''+old_period+'-'+h+''+new_period);
	if (document.getElementById('check'+h).checked)
	{
		deleteColumnCells(h+''+old_period);
		addColumnCells(h+''+new_period);
		addColumnAjax(h, h+''+new_period);

		replaceColumnConfiguration(h+''+old_period,h+''+new_period);
	}
}

/*
 
RESET/SAVE CONFIGURATION
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

function closeMessage(tab)
{
	var current_conf = document.getElementById('show_configuration').value;
	var value = '';

	if (current_conf != "")
		current_conf += ";";

	if (tab == 'reservable') {	
        document.getElementById('note_reservable_div').style.display = "none";
	if (current_conf.indexOf('reservable') != -1)
		return;
	value = current_conf+'reservable';
	}

	if (tab == 'columns') {	
        document.getElementById('note_columns_div').style.display = "none";
	if (current_conf.indexOf('columns') != -1)
		return;
	value = current_conf+'columns';
	}

	var slice_id = document.getElementById('slice_id').value;
	var person_id = document.getElementById('person_id').value;
	var tag_id = document.getElementById('show_tag_id').value;
	
        var url = "/plekit/php/updateConfiguration.php?value="+value+"&slice_id="+slice_id+"&person_id="+person_id+"&tag_id="+tag_id;
	//debugfilter("updating conf with "+url);
	document.getElementById('show_configuration').value = value;

	var req = getHTTPObject();
	req.open('GET', url, true);
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	req.onreadystatechange =
        function() {
                if (req.readyState == 4)
                { debugfilter(req.responseText); }
        }
	req.send(null);

}


function updateColumnConfiguration(value, reload)
{
	var person_id = document.getElementById('person_id').value;
	var slice_id = document.getElementById('slice_id').value;
	var tag_id = document.getElementById('conf_tag_id').value;
	var full_column_configuration = document.getElementById('full_column_configuration').value;

	//debugfilter("<br>OLD = "+full_column_configuration);
	//debugfilter("<br>value = "+value);
	//
	
	var old_columns = full_column_configuration.split(";");
	var new_columns = new Array();

	for (var column_index = 0; column_index < old_columns.length ; column_index++) {
		new_columns.push(old_columns[column_index]);
		if (old_columns[column_index] != slice_id)
			new_columns.push(old_columns[++column_index]);
		else
		{
			if (value != "")
				new_columns.push(value);
			else
				new_columns.push("default");
				
			column_index++;
		}
	}

	var new_configuration = new_columns.join(";");
	//debugfilter("<br>NEW = "+new_configuration);

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
                //debugfilter(value+"-----"+new_configuration);
		document.getElementById('column_configuration').value=value;
		document.getElementById('full_column_configuration').value=new_configuration;

		if (reload)
			window.location.reload(true);
            }
          }

        xmlhttp.open("GET","/plekit/php/updateConfiguration.php?value="+new_configuration+"&slice_id="+slice_id+"&person_id="+person_id+"&tag_id="+tag_id,true);
        //xmlhttp.open("GET","/plekit/php/updateConf.php?value="+value+"&slice_id="+slice_id+"&person_id="+person_id+"&tagName=Columnconf",true);

        xmlhttp.send();
}

function logSortingAction(person_id, slice_id, value)
{


	var req = getHTTPObject();
        var url = "/plekit/php/logSorting.php?value="+value+"&slice_id="+slice_id+"&person_id="+person_id;

	req.open('GET', url, true);
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	req.onreadystatechange =
        function() {
                if (req.readyState == 4)
                { debugfilter(req.responseText); }
        }
	req.send(null);
}

function sortCompleteCallback(tableid) {

	var slice_id = document.getElementById('slice_id').value;
	var person_id = document.getElementById('person_id').value;

	var ths = document.getElementById(tableid).getElementsByTagName("th");
	for(var i = 0, th; th = ths[i]; i++) {
	if (th.className.indexOf("Sort") != -1)
	{
		var hclass = th.className;
		var column = hclass.substr(hclass.indexOf("column"),hclass.indexOf("column")+1);
		var sortdirection = hclass.substr(hclass.indexOf("Sort")-8,hclass.indexOf("Sort"));
		if (column.indexOf("column-1")==-1 && column.indexOf("column-0")==-1)
			logSortingAction(person_id, slice_id, tableid+"|"+column+"|"+sortdirection);
	}
	}
}


function addColumnToConfiguration(column) {

	var old_configuration = document.getElementById('column_configuration').value;

	var new_configuration = "";

	if (old_configuration != "")
		new_configuration = old_configuration += "|"+column;
	else
		new_configuration = column;

	//debugfilter("new configuration = "+new_configuration);

	updateColumnConfiguration(new_configuration, false);
}


function deleteColumnFromConfiguration(column) {

	var old_configuration = document.getElementById('column_configuration').value;

	var old_columns = old_configuration.split("|");
	var new_columns = new Array();

	for (var column_index = 0; column_index < old_columns.length ; column_index++) {
		var conf = old_columns[column_index].split(':');
		if (conf[0] != column)
			new_columns.push(old_columns[column_index]);
	}

	var new_configuration = new_columns.join("|");
	updateColumnConfiguration(new_configuration, false);

}

function replaceColumnConfiguration(column_old, column_new) {

	var old_configuration = document.getElementById('column_configuration').value;

	var old_columns = old_configuration.split("|");
	var new_columns = new Array();

	for (var column_index = 0; column_index < old_columns.length ; column_index++) {
		var conf = old_columns[column_index].split(':');
		if (conf[0] != column_old)
			new_columns.push(old_columns[column_index]);
		else
			new_columns.push(column_new);
	}

	var new_configuration = new_columns.join("|");
	
	updateColumnConfiguration(new_configuration);
}

/*
 
ADD/REMOVE COLUMNS

*/




function load_data(column, header, url) {

	//debugfilter("<br>loading "+url);
	var req = getHTTPObject();
	var res;
	req.open('GET', url, true);
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	req.onreadystatechange =
        function() {
                if (req.readyState == 4)
                { updateColumnData(column, header, req.responseText); }
        }
	req.send(null);
}


function updateColumnData(column, header, data) {

var headers = header.split("|");
var data_table = data.split("|"); 

//debugfilter("<p>headers[0] = "+headers[0]);
//debugfilter("<p>data[2] = "+data_table[2]);

//debugfilter("data = "+data);

if (data != "")
{

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
		//debugfilter("<p>node id = "+tr.cells[0].innerHTML+" - "+tr.cells[column_index].getAttribute('name'));
		if (tr.cells[column_index].getAttribute('name'))
		{
		var found_index = headers.indexOf(tr.cells[column_index].getAttribute('name'));
		if (found_index != -1)
			//debugfilter(tr.cells[0].innerHTML+"-"+found_index);
			tr.cells[column_index].innerHTML = data_array1[tr.cells[0].innerHTML][found_index];
		}
    }
  }

  fdTableSort.init(table_id1);
  tablePaginater.init(table_id1);

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
		if (tr.cells[column_index].getAttribute('name'))
		{
		var found_index = headers.indexOf(tr.cells[column_index].getAttribute('name'));
		if (found_index != -1)
			tr.cells[column_index].innerHTML = data_array2[tr.cells[0].innerHTML][found_index];
		}
    }
  }

  //fdTableSort.removeTableCache(table_id2);
  fdTableSort.init(table_id2);
  tablePaginater.init(table_id2);
}

}

document.getElementById('loading'+column).innerHTML = "";

}


function addColumnCells(header) {

	var cells = document.getElementsByName(header);

	//debugfilter("got cells -"+cells+"- for "+header);
	for(var j = 0; j < cells.length; j++) 
		cells[j].style.display = "table-cell";
}

function addSampleCells(column) {

	var cellsheader = document.getElementsByName("confheader"+column);
	for(var j = 0; j < cellsheader.length; j++) 
		cellsheader[j].style.display = "table-cell";

}

function addColumnAjax(column, header) {

	//var t = document.getElementById('check'+column).name;
	var t = document.getElementById('tagname'+header).value;
	var slice_id = document.getElementById('slice_id').value;

        var selectedperiod = document.getElementById('period'+column).value;

	var fetched = document.getElementById('fetched'+column).value;
	var to_load = false;

	//debugfilter("<br>adding "+column+","+header+','+fetched+','+t);

	if (fetched.indexOf("false")!=-1)
	{
		to_load = true;
		document.getElementById('fetched'+column).value = ','+selectedperiod+',true';
	}
	else if (fetched.indexOf(','+selectedperiod+',')==-1)
	{
			to_load = true;
			document.getElementById('fetched'+column).value = ','+selectedperiod+''+fetched;
	}

	if (to_load)
	{
		var url = "/plekit/php/updateColumn.php?slice_id="+slice_id+"&tagName="+t;
		load_data(column, header, url);
	}
}



function addColumn(column, fetch) {

	var selectedperiod="";
	var header=column;

	document.getElementById('loading'+column).innerHTML = "<img width=10 src=/plekit/icons/ajax-loader.gif>";

	if (inTypeC(column)!=-1)
	{
		column = column.substring(0,column.length-1);
	}

        selectedperiod = document.getElementById('period'+column).value;
	header = column+""+selectedperiod;

	//debugfilter("adding column "+column+" and header "+header);

	addColumnCells(header);

	if (fetch)
		addColumnAjax(column, header);
	else
		document.getElementById('loading'+column).innerHTML = "";

	addColumnToConfiguration(header);
	
}


function deleteColumnCells(header) {

	var cells = document.getElementsByName(header);
	for(var j = 0; j < cells.length; j++) 
		cells[j].style.display = "none";

}



function deleteColumn(column) {

	var selectedperiod="";
	var header=column;

        selectedperiod = document.getElementById('period'+column).value;
	header = column+""+selectedperiod;

	//debugfilter("deleting "+column+","+header);

	deleteColumnCells(header);

	deleteColumnFromConfiguration(header);

	//document.getElementById('check'+column).checked = false;
}

function scrollList() {
debugfilter("here "+document.getElementById('scrolldiv').focused);
if (event.keyCode == 40)
	debugfilter("down");
else if (event.keyCode == 38)
	debugfilter("up");
}

/* 
 
EXTRA

//to be used for scrolling the column list with down/up arrows 


function resetColumns() {

	for (var kk in column_table) {

	if (column_table[kk]['visible'] == true && column_table[kk]['fetch'] == false)
		deleteColumn(kk);
	else if (column_table[kk]['visible'] == false && column_table[kk]['fetch'] == true)
		addColumn(kk, true);
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
				addColumn(kk, true);
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


function deleteColumnSample() {
	var cellsheader = document.getElementsByName("confheader"+column);
	for(var j = 0; j < cellsheader.length; j++) 
		cellsheader[j].style.display = "none";

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
	load_data(headers, url);
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
