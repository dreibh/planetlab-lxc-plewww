
var filtered_color = "grey";
var normal_color = "black";

//Descriptions overriding the default ones set in Accessors_site.py and configuration 

var sourceComon = '<a class="source-url" target="source_window" href="http://comon.cs.princeton.edu/">CoMon</a>';
var sourceTophat = '<a class="source-url" target="source_window" href="http://www.top-hat.info/">TopHat</a>';
var sourceTophatAPI = '<a class="source-url" target="source_window" href="http://www.top-hat.info/API/">TopHat API</a>';
var sourceMySlice = '<a class="source-url" target="source_window" href="http://myslice.info/">MySlice</a>';
var sourceCymru = '<a class="source-url" target="source_window" href="http://www.team-cymru.org/">Team Cymru</a>';
var sourceSonoma = '<a class="source-url" target="source_window" href="http://sonoma.etomic.org/">SONoMA</a>';
var sourceMyPLC = '<a class="source-url" target="source_window" href="https://www.planet-lab.eu/db/doc/PLCAPI.php">MyPLC API</a>';
var sourceManiacs = '<a class="source-url" target="source_window" href="http://www.ece.gatech.edu/research/labs/MANIACS/as_taxonomy/">MANIACS</a>';
var sourceMaxmind = '<a class="source-url" target="source_window" href="http://www.maxmind.com/app/geolitecity">MaxMind</a>';
var sourceMonitor = '<a class="source-url" target="source_window" href="http://monitor.planet-lab.org/">Monitor</a>';
var hardwareReqs = 'current <a class="info-url" target="info_window" href="http://www.planet-lab.org/hardware">PlanetLab hardware requirement</a>';
var selectReferenceNode ='<div id="refnodes_div"></div>';
var selectReferenceNodeRTT ='<div id="refnodes_rtt_div"></div>';

var descHOSTNAME = "test";

var titleAU = 'Authority';
var detailAU = 'The authority of the global PlanetLab federation that the site of the node belongs to.';
var valuesAU = '<span class="bold">PLC</span> (PlanetLab Central), <span class="bold">PLE</span> (PlanetLab Europe)';
var sourceAU = 'Source: '+sourceMyPLC;
var descAU = '<span class="gray"><span class="column-title">'+titleAU+'</span><p><span class="column-detail">'+detailAU+'</span></p><p>Values: '+valuesAU+'</p><p>Source: '+sourceMyPLC+'</p></span>';

var titleST = 'Status';
var sourceST = 'Source: '+sourceMonitor;
var valuesST = 'Values: <span class="bold">online</span> (up and running), <span class="bold">good</span> (up and running recently), <span class="bold">offline</span> (unreachable today), <span class="bold">down</span> (node unreachable for more than one day), <span class="bold">failboot</span> (reachable, but only by administrators for debugging purposes).';
var descST = '<span class="gray"><span class="column-title">'+titleST+'</span><p>'+valuesST+'</p><p>'+sourceST+'</p></span>';

var titleA = 'Architecture name';
var detailA = 'The node architecture.';
var sourceA = 'Source: '+sourceMyPLC;
var valuesA = '<span class="bold">x86_64</span>, <span class="bold">i386</span>, <span class="bold">n/a</span>';
var descA = '<span class="gray"><span class="column-title">'+titleA+'</span><p><span class="column-detail">'+detailA+'</span></p><p>Values: '+valuesA+'</p><p>'+sourceA+'</p></span>';

var titleAS = 'Autonomous system ID';
var sourceAS = 'Source: '+sourceCymru+' (via '+sourceTophat+')';
var valuesAS = 'Values: <span class="bold">Integer between 0 and 65535</span>, <span class="bold">n/a</span>';
var descAS = '<span class="gray"><span class="column-title">'+titleAS+'</span><p>'+valuesAS+'</p><p>' + sourceAS+'</p></span>';

var titleAST = 'Autonomous system type';
var sourceAST = 'Source: '+sourceManiacs;
var valuesAST = 'Values: <span class="bold">t1</span> (tier-1), <span class="bold">t2</span> (tier-2), <span class="bold">edu</span> (university), <span class="bold">comp</span> (company), <span class="bold">nic</span> (network information centre -- old name for a domain name registry operator), <span class="bold">ix</span> (IXP), <span class="bold">n/a</span>';
var descAST = '<span class="gray"><span class="column-title">'+titleAST+'</span><p>'+valuesAST+'</p><p>'+sourceAST+'</p></span>';

var titleASN = 'Autonomous system name';
var sourceASN = 'Source: '+sourceTophat;
var descASN = '<span class="gray"><span class="column-title">'+titleASN+'</span><p>'+sourceASN+'</p></span>';

var selectPeriodBU = 'Select period: <select id="selectperiodBU" onChange=updatePeriod("BU",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleBU = 'Bandwidth utilization ';
var sourceBU = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesBU ='Unit: <span class="bold">Kbps</span>';
var detailBU = 'The average transmited bandwidh over the selected period. The period is the most recent for which data is available, with CoMon data being collected by MySlice daily.'
var descBU = '<span class="gray"><span class="column-title">'+titleBU+'</span><p><span class="column-detail">'+detailBU+'</span></p><p>'+selectPeriodBU+'</p><p>'+valuesBU+'</p><p>'+sourceBU+'</p></span>'; 

var titleBW= 'Bandwidth limit';
var sourceBW = 'Source: '+sourceComon;
var valuesBW = 'Unit: <span class="bold">Kbps</span>';
var detailBW = 'The bandwidth limit is a cap on the total outbound bandwidth usage of a node. It is set by the site administrator (PI). For more details see <a targe="source_window" href="http://www.planet-lab.org/doc/BandwidthLimits">Bandwidth Limits (planet-lab.org)</a>.';
var descBW = '<span class="gray"><span class="column-title">'+titleBW+'</span><p><span class="column-detail">'+detailBW+'</span></p><p>'+valuesBW+'</p><p>'+sourceBW+'</p></span>';

var titleCC = 'Number of CPU cores';
var sourceCC = 'Source: '+sourceComon;
var detailCC = 'The number of CPU cores on the node. For reference, the '+hardwareReqs+' is <span class="bold">4 cores min.</span> (Older nodes may have fewer cores).';
var descCC = '<span class="gray"><span class="column-title">'+titleCC+'</span><p><span class="column-detail">'+detailCC+'</span></p><p>'+sourceCC+'</p></span>';

var titleCN = 'Number of CPUs';
var sourceCN = 'Source: '+sourceComon;
var detailCN = 'The number of CPUs on the node. For eeference, the '+hardwareReqs+' is <span class="bold">1 (if quad core) or 2 (if dual core)</span>.';
var descCN = '<span class="gray"><span class="column-title">'+titleCN+'</span><p><span class="column-detail">'+detailCN+'</detail></p><p>'+sourceCN+'</p></span>';

var titleCPC = 'Number of cores per CPU';
var sourceCPC = 'Source: '+sourceComon;
var detailCPC = 'The number of cores per CPU on the node.'; 
var descCPC = '<span class="gray"><span class="column-title">'+titleCPC+'</span><p><span class="column-detail">'+detailCPC+'</span></p><p>'+sourceCPC+'</p></span>';

var titleCR = 'CPU clock rate';
var detailCR = 'The clock rate for the CPUs on the node. For reference, the '+hardwareReqs+' is <span class="bold">2.4 GHz</span>.';
var sourceCR = 'Source: '+sourceComon;
var valuesCR = 'Unit: <span class="bold">GHz</span>';
var descCR = '<span class="gray"><span class="column-title">'+titleCR+'</span><p><span class="column-detail">'+detailCR+'</span></p><p>'+valuesCR+'</p><p>'+sourceCR+'</p></span>';

var selectPeriodCF = 'Select period: <select id="selectperiodCF" onChange=updatePeriod("CF",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleCF = 'Free CPU';
var sourceCF = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesCF = 'Unit: <span class="bold">%</span>';
var detailCF = 'The average CPU percentage that gets allocated to a test slice named burb that is periodically run by CoMon.';
var descCF = '<span class="gray"><span class="column-title">'+titleCF+'</span><p><span class="column-detail">'+detailCF+'</span></p><p>'+selectPeriodCF+'</p><p>'+valuesCF+'</p><p>'+sourceCF+'</p></span>'; 

var titleDN = 'Toplevel domain name';
var sourceDN = 'Source: '+sourceMyPLC;
var descDN = '<span class="gray"><span class="column-title">'+titleDN+'</span><p>'+sourceDN+'</p></span>';

var titleDA = 'Date added';
var sourceDA = 'Source: '+sourceMyPLC;
var detailDA = 'The date that the node was added to PlanetLab.';
var descDA = '<span class="gray"><span class="column-title">'+titleDA+'</span><p><span class="column-detail">'+detailDA+'</span></p><p>'+sourceDA+'</p></span>';

var titleDL = 'Deployment';
var detailDL = 'The deployment status.';
var valuesDL = 'Values: <span class="bold">alpha</span>, <span class="bold">beta</span>, <span class="bold">production</span>, <span class="bold">n/a</span>';
var sourceDL = 'Source: '+sourceMyPLC;
var descDL = '<span class="gray"><span class="column-title">'+titleDL+'</span><p><span class="column-detail">'+detailDL+'</span></p><p>'+valuesDL+'</p><p>'+sourceDL+'</p></span>';

var titleDS = 'Disk size';
var detailDS = 'The size of the hard disk available on the node. For reference, the '+hardwareReqs+' is <span class="bold">500 GB</span>.';
var sourceDS = 'Source: '+sourceComon;
var valuesDS = 'Unit: <span class="bold">GB</span>';
var descDS = '<span class="gray"><span class="column-title">'+titleDS+'</span><p><span class="column-detail">'+detailDS+'</span></p><p>'+valuesDS+'</p><p>'+sourceDS+'</p></span>';

var titleDU = 'Current disk utilization';
var sourceDU = 'Source: '+sourceComon;
var valuesDU = 'Unit: <span class="bold">GB</span>';
var detailDU = 'The amount of disk space currently consumed.';
var descDU = '<span class="gray"><span class="column-title">'+titleDU+'</span><p><span class="column-detail">'+detailDU+'</span></p><p>'+valuesDU+'</p><p>'+sourceDU+'</p></span>';

var titleDF = 'Disk space free';
var sourceDF = 'Source: '+sourceComon;
var valuesDF = 'Unit: <span class="bold">GB</span>.';
var detailDF = 'The amount of disk space currently available.';
var descDF = '<span class="gray"><span class="column-title">'+titleDF+'</span><p><span class="column-detail">'+detailDF+'</span></p><p>'+valuesDF+'</p><p>'+sourceDF+'</p></span>';

var titleHC = 'Hop count (pairwise)';
var sourceHC = 'Source: '+sourceTophat;
var detailHC = 'TopHat conducts traceroutes every five minutes in a full mesh between all PlanetLab nodes. The hop count is the length of the traceroute from the node to the reference node, based upon the most recently reported traceroute.';
var descHC = '<span class="gray"><span class="column-title">'+titleHC+'</span><p><span class="column-detail">'+detailHC+'</span></p><p>'+selectReferenceNode+'</p><p>'+sourceHC+'</p></span>';

var titleIP = 'IP address';
var sourceIP = 'Source: '+sourceMyPLC;
var descIP = '<span class="gray"><span class="column-title">'+titleIP+'</span><p>'+sourceIP+'</p></span>';

var selectPeriodL = 'Select period: <select id="selectperiodL" onChange=updatePeriod("L",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleL= 'Load ';
var sourceL = 'Source: '+sourceComon;
var valuesL = 'Unit: <span class="bold">5-minute load</span>';
var detailL = 'The average 5-minute load (as reported by the Unix uptime command) over the selected period.';
var descL = '<span class="gray"><span class="column-title">'+titleL+'</span><p><span class="column-detail">'+detailL+'</span></p><p>'+selectPeriodL+'</p><p>'+valuesL+'</p><p>'+sourceL+'</p></span>'; 

var titleLON= 'Longitude';
var sourceLON = 'Source: '+sourceTophat;
var descLON = '<span class="gray"><span class="column-title">'+titleLON+'</span><p>'+sourceLON+'</p></span>';

var titleLAT= 'Latitude';
var sourceLAT = 'Source: '+sourceTophat;
var descLAT = '<span class="gray"><span class="column-title">'+titleLAT+'</span><p>'+sourceLAT+'</p></span>';

var titleLCN= 'Location (Country)';
var sourceLCN = 'Source: '+sourceMaxmind+' (via '+sourceTophat+')';
var detailLCN = 'Based on the latitude and longitude information.';
var descLCN = '<span class="gray"><span class="column-title">'+titleLCN+'</span><p><span class="column-detail">'+detailLCN+'</span></p><p>'+sourceLCN+'</p></span>';

var titleLCT= 'Location (Continent)';
var sourceLCT = 'Source: '+sourceMaxmind+' (via '+sourceTophat+')';
var detailLCT = 'Based on the latitude and longitude information.';
var descLCT = '<span class="gray"><span class="column-title">'+titleLCT+'</span><p><span class="column-detail">'+detailLCT+'</span></p><p>'+sourceLCT+'</p></span>';

var titleLCY= 'Location (City)';
var sourceLCY = 'Source: '+sourceMaxmind+' (via '+sourceTophat+')';
var detailLCY = 'Based on the latitude and longitude information.';
var descLCY = '<span class="gray"><span class="column-title">'+titleLCY+'</span><p><span class="column-detail">'+detailLCY+'</span></p><p>'+sourceLCY+'</p></span>';

var titleLPR= 'Location precision radius';
var sourceLPR = 'Source: '+sourceTophat;
var valuesLPR = 'Unit: <span class="bold">float</span>.';
var detailLPR = 'The radius of the circle corresponding to the error in precision of the geolocalization estimate.';
var descLPR = '<span class="gray"><span class="column-title">'+titleLPR+'</span><p><span class="column-detail">'+detailLPR+'</span></p><p>'+valuesLPR+'</p><p>'+sourceLPR+'</p></span>';

var titleLRN= 'Location (Region)';
var sourceLRN = 'Source: '+sourceMaxmind+' (via '+sourceTophat+')';
var detailLRN = 'Based on the latitude and longitude information.';
var descLRN = '<span class="gray"><span class="column-title">'+titleLRN+'</span><p><span class="column-detail">'+detailLRN+'</span></p><p>'+sourceLRN+'</p></span>';

var titleMS= 'Memory size';
var detailMS = 'The memory size (RAM) available on the node. For reference, the '+hardwareReqs+' is <span class="bold">4 GB</span>.';
var sourceMS = 'Source: '+sourceComon;
var valuesMS = 'Unit: <span class="bold">GB</span>.';
var descMS = '<span class="gray"><span class="column-title">'+titleMS+'</span><p><span class="column-detail">'+detailMS+'</span></p><p>'+valuesMS+'</p><p>'+sourceMS+'</p></span>';

var selectPeriodMU = 'Select period: <select id="selectperiodMU" onChange=updatePeriod("MU",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleMU = 'Memory utilization';
var sourceMU = 'Source: '+sourceComon;
var valuesMU = 'Unit: <span class="bold">%</span>';
var detailMU = 'The average active memory utilization as reported by CoMon.';
var descMU = '<span class="gray"><span class="column-title">'+titleMU+'</span><p><span class="column-detail">'+detailMU+'</span></p><p>'+selectPeriodMU+'</p><p>'+valuesMU+'</p><p>'+sourceMU+'</p></span>'; 

var titleMA= 'Measurement agents';
var sourceMA = 'Source: '+sourceTophat;
var valuesMA = 'Values: <span class="bold">ETOMIC</span>, <span class="bold">SONoMA</span>, <span class="bold">TDMI</span>, <span class="bold">DIMES</span>.';
var detailMA = 'Co-located measurement agents.';
var descMA = '<span class="gray"><span class="column-title">'+titleMA+'</span><p><span class="column-detail">'+detailMA+'</span></p><p>'+valuesMA+'</p><p>'+sourceMA+'</p></span>';

var titleMAS= 'Measurement agent SONoMA';
var sourceMAS = 'Source: '+sourceTophat;
var valuesMAS = 'Values: <span class="bold">Node type</span> (e.g., PLE, APE)';
var detailMAS = 'The existence of a SONoMA agent. When an SONoMA is present, you have the possibility to have access to high-precision measurements through the '+sourceTophatAPI+'.';
var descMAS = '<span class="gray"><span class="column-title">'+titleMAS+'</span><p><span class="column-detail">'+detailMAS+'</span></p><p>'+valuesMAS+'</p><p>'+sourceMAS+'</p></span>';

var titleMAE= 'Measurement agent ETOMIC';
var sourceMAE = 'Source: '+sourceTophat;
var valuesMAE = 'Values: <span class="bold">yes/no</span>';
var detailMAE = 'The existence of a colocated ETOMIC box. When an ETOMIC box is present, you have the possibility to conduct high-precision measurements through the '+sourceTophatAPI+'.';
var descMAE = '<span class="gray"><span class="column-title">'+titleMAE+'</span><p><span class="column-detail">'+detailMAE+'</span></p><p>'+valuesMAE+'</p><p>'+sourceMAE+'</p></span>';

var titleMAT= 'Measurement agent TDMI';
var sourceMAT = 'Source: '+sourceTophat;
var valuesMAT = 'Values: <span class="bold">yes/no</span>';
var detailMAT = 'The existence of a colocated TDMI (TopHat Dedicated Measurement Infrastructure) agent. When a TDMI agent is present, you have access to a wide variety of network topology measurements through the '+sourceTophatAPI+'.';
var descMAT = '<span class="gray"><span class="column-title">'+titleMAT+'</span><p><span class="column-detail">'+detailMAT+'</span></p><p>'+valuesMAT+'</p><p>'+sourceMAT+'</p></span>';

var titleMAD= 'Measurement agent DIMES';
var sourceMAD = 'Source: '+sourceTophat;
var valuesMAD = 'Values: <span class="bold">yes/no</span>';
var detailMAD = 'The existence of a colocated DIMES agent. When a DIMES agent is present, you have access to DIMES measurements through the '+sourceTophatAPI+'.';
var descMAD = '<span class="gray"><span class="column-title">'+titleMAD+'</span><p><span class="column-detail">'+detailMAD+'</span></p><p>'+valuesMAD+'</p><p>'+sourceMAD+'</p></span>';

var titleNSF= 'Network information (spoof)';
var sourceNSF = 'Source: '+sourceTophat;
var valuesNSF = 'Values: <span class="bold">yes/no</span>';
var detailNSF = 'Whether the node can send packets successfully (or not) with a spoofed IP source address.';
var descNSF = '<span class="gray"><span class="column-title">'+titleNSF+'</span><p><span class="column-detail">'+detailNSF+'</span></p><p>'+valuesNSF+'</p><p>'+sourceNSF+'</p></span>';

var titleNSR= 'Network information (source route)';
var sourceNSR = 'Source: '+sourceTophat;
var valuesNSR = 'Values: <span class="bold">yes/no</span>';
var detailNSR = 'Whether the node can send packets packets using the IP source route option. See <a target="info_window" href="http://www.networksorcery.com/enp/protocol/ip/option003.htm">here</a>for more info.';
var descNSR = '<span class="gray"><span class="column-title">'+titleNSR+'</span><p><span class="column-detail">'+detailNSR+'</span></p><p>'+valuesNSR+'</p><p>'+sourceNSR+'</p></span>';

var titleNTP= 'Network information (timestamp)';
var sourceNTP = 'Source: '+sourceTophat;
var valuesNTP = 'Values: <span class="bold">yes/no</span>';
var detailNTP = 'Whether the node can send packets packets using the IP timestamp option. See <a target="info_window" href="http://www.networksorcery.com/enp/protocol/ip/option004.htm">here</a>for more info.';
var descNTP = '<span class="gray"><span class="column-title">'+titleNTP+'</span><p><span class="column-detail">'+detailNTP+'</span></p><p>'+valuesNTP+'</p><p>'+sourceNTP+'</p></span>';

var titleNRR= 'Network information (record route)';
var sourceNRR = 'Source: '+sourceTophat;
var valuesNRR = 'Values: <span class="bold">yes/no</span>';
var detailNRR = 'Whether the node can send packets packets using the IP record route option. See <a target="info_window" href="http://www.networksorcery.com/enp/protocol/ip/option007.htm">here</a>for more info.';
var descNRR = '<span class="gray"><span class="column-title">'+titleNRR+'</span><p><span class="column-detail">'+detailNRR+'</span></p><p>'+valuesNRR+'</p><p>'+sourceNRR+'</p></span>';

var titleOS = 'Operating system';
var detailOS = 'Fedora or CentOS distribution to use for node or slivers.';
var sourceOS = 'Source: '+sourceMyPLC;
var valuesOS = 'Values: <span class="bold">f8, f12, Cent/OS, other, n/a</span>';
var descOS = '<span class="gray"><span class="column-title">'+titleOS+'</span><p><span class="column-detail">'+detailOS+'</span></p><p>'+valuesOS+'</p><p>'+sourceOS+'</p></span>';

var titleRTT = 'Round Trip Time (pairwise)';
var detailRTT = 'The round trip time between a selected SONoMA agent and PlanetLab nodes.';
var sourceRTT = 'Source: '+sourceSonoma+' (via '+sourceTophat+ ')';
var descRTT = '<span class="gray"><span class="column-title">'+titleRTT+'</span><p><span class="column-detail">'+detailRTT+'</span></p><p>'+selectReferenceNodeRTT+'</p><p>'+sourceRTT+'</p></span>';

var selectPeriodR = 'Select period: <select id="selectperiodR" onChange=updatePeriod("R",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleR = 'Reliability';
var sourceR = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var detailR = 'CoMon queries nodes every 5 minutes, for 255 queries per day. The average reliability is the percentage of queries over the selected period for which CoMon reports a value. The period is the most recent for which data is available, with CoMon data being collected by MySlice daily.';
var valuesR = 'Unit: <span class="bold">%</span>';
var descR = '<span class="gray"><span class="column-title">'+titleR+'</span><p><span class="column-detail">'+detailR+'</span></p><p>'+selectPeriodR+'</p><p>'+valuesR+'</p><p>'+sourceR+'</p></span>'; 

var titleRES = 'Reservation capabilities';
var sourceRES = 'Source: '+sourceMyPLC;
//var valuesRES = 'Values: <span class="bold">yes/no</span>';
var valuesRES = 'Values: <span class="bold">-R-</span> (if yes)';
var detailRES = 'Whether the node can be reserved for a certain duration. Your slivers will be available <span class=bold>only during timeslots where you have obtained leases (see tab above)</span>.  <p>Please note that as of August 2010 this feature is experimental.  Feedback is appreciated at <a href="mailto:devel@planet-lab.org">devel@planet-lab.org</a></p>';
var descRES = '<span class="gray"><span class="column-title">'+titleRES+'</span><p><span class="column-detail">'+detailRES+'</span></p><p>'+valuesRES+'</p><p>'+sourceRES+'</p></span>';

var selectPeriodS = 'Select period: <select id="selectperiodS" onChange=updatePeriod("S",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleS = 'Active slices';
var sourceS = 'Source: '+sourceComon+' (via '+sourceMySlice+')';
var valuesS = 'Unit: <span class="bold">%</span>';
var detailS = 'Average number of active slices over the selected period for which CoMon reports a value. The period is the most recent for which data is available, with CoMon data being collected by MySlice daily.';
var descS = '<span class="gray"><span class="column-title">'+titleS+'</span><p><span class="column-detail">'+detailS+'</span></p><p>'+selectPeriodS+'</p><p>'+valuesS+'</p><p>'+sourceS+'</p></span>'; 

var titleSM= 'Slices in memory';
var detailSM = 'The total number of slices in memory (both active and inactive).';
var sourceSM = 'Source: '+sourceComon;
var descSM = '<span class="gray"><span class="column-title">'+titleSM+'</span><p><span class="column-detail">'+detailSM+'</span></p><p>'+sourceSM+'</p></span>';

var titleSN = 'Site name';
var sourceSN = 'Source: '+sourceMyPLC;
var descSN = '<span class="gray"><span class="column-title">'+titleSN+'</span><p>'+sourceSN+'</p></span>';

var selectPeriodSSH = 'Select period: <select id="selectperiodSSH" onChange=updatePeriod("SSH",this.value)><option value="">Latest</option><option value=w>Week</option><option value=m>Month</option><option value=y>Year</option></select>';
var titleSSH = 'Average SSH response delay';
var valuesSSH = 'Unit: <span class="bold">msecs</span>';
var detailSSH = 'The average response delay of the node to SSH logins over the selected period for which CoMon reports a value. The period is the most recent for which data is available, with CoMon data being collected by MySlice daily.';
var sourceSSH ='Source: '+sourceComon+' (via '+sourceMySlice+')';
var descSSH = '<span class="gray"><span class="column-title">'+titleSSH+'</span><p><span class="column-detail">'+detailSSH+'</span></p><p>'+selectPeriodSSH+'</p><p>'+valuesSSH+'</p><p>'+sourceSSH+'</p></span>'; 


var titleUT = 'Uptime';
var sourceUT = 'Source: '+sourceComon;
var valuesUT = 'Unit: <span class="bold">days</span>';
var detailUT = 'The continuous uptime until the moment that the page is loaded, as reported by the CoMon html query API.';
var descUT = '<span class="gray"><span class="column-title">'+titleUT+'</span><p><span class="column-detail">'+detailUT+'</span></p><p>'+valuesUT+'</p><p>'+sourceUT+'</p></span>';



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

	if (document.getElementById('selectdescr'))
	{
		//Checks if there is a detailed description defined
		if (window['desc'+h])
			document.getElementById('selectdescr').innerHTML = ""+window['desc'+h];
		else 
			document.getElementById('selectdescr').innerHTML = "No detailed description provided";
	}

	if (document.getElementById('refnodes_div'))
		document.getElementById('refnodes_div').innerHTML = ref_nodes_select;

	if (document.getElementById('refnodes_rtt_div'))
		document.getElementById('refnodes_rtt_div').innerHTML = ref_nodes_select_rtt;
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

	var h = document.getElementById(column).value;

	if (document.getElementById('selectdescr'))
		showDescription(h);

	//debugfilter("HERE: "+column+" - "+document.getElementById('type '+column).value);
	//debugfilter("HERE: "+column);


	if (document.getElementById(column).checked)
		addColumn(h, true, document.getElementById('type'+h).value);
	else
		deleteColumn(h);
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
	
        var url = "/plekit/php/updateConfiguration.php?value="+value+"&slice_id="+slice_id+"&person_id="+person_id+"&tag_name=showconf&tag_id="+tag_id;
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

        xmlhttp.open("GET","/plekit/php/updateConfiguration.php?value="+new_configuration+"&slice_id="+slice_id+"&person_id="+person_id+"&tag_name=columnconf&tag_id="+tag_id,true);
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
		//debugfilter("sorted"+th.getAttribute("name"));

		var column_name = th.getAttribute("name");
		var hclass = th.className;
		var column = hclass.substr(hclass.indexOf("column"),hclass.indexOf("column")+1);
		var sortdirection = "forward";
		if (hclass.indexOf("reverse")!=-1)
			sortdirection = "reverse";

		if (column.indexOf("column-1")==-1 && column.indexOf("column-0")==-1)
			logSortingAction(person_id, slice_id, tableid+"|"+column_name+"|"+sortdirection);
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

	var olds = column_old.split(':');

	for (var column_index = 0; column_index < old_columns.length ; column_index++) {
		var conf = old_columns[column_index].split(':');
		if (conf[0] != olds[0])
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

function convert_data(value, data_source, data_type, tagname) {

	//debugfilter("v["+tagname+"]="+value+"-");

	if (value == "" || value == "n/a" || value == null || value == "NaN" || value == "None")
		return "n/a";

	if (tagname == "uptime") {
		return parseInt((parseFloat(value) / 86400));
	}
	
	if (data_type == "date") {

		var date = new Date(value*1000);

		var year = date.getFullYear();
		var month = date.getMonth()+1;
		if (month < 10)
			month = "0"+month;
		var day = date.getDate();
		if (day < 10)
			day = "0"+day;
	
		return year + '-' + month + '-' + day;
	}

	return value;
}


function load_data(column, header, url, data_source, data_type, tagname) {

	//debugfilter("<br>loading "+url);
	var req = getHTTPObject();
	var res;
	req.open('GET', url, true);
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	req.onreadystatechange =
        function() {
                if (req.readyState == 4)
                { updateColumnData(column, header, req.responseText, data_source, data_type, tagname); }
        }
	req.send(null);
}


function updateColumnData(column, header, data, data_source, data_type, tagname) {

var headers = header.split("|");
var data_table = data.split("|"); 

//debugfilter("<p>headers[0] = "+headers[0]);
//debugfilter("<p>data[2] = "+data_table[2]);

//debugfilter("data = "+data + " with type "+data_type + " and source "+data_source);
//debugfilter("<p>data table length = "+data_table.length);

if (data_table.length > 1)
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
		
		if (node_data[h_index+1] == "" || node_data[h_index+1] == "None")
			data_array1[node_data[0]][h_index] = "n/a";
		else
			data_array1[node_data[0]][h_index] = convert_data(node_data[h_index+1], data_source, data_type, tagname);
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

  //debugfilter("Reset sorting .....");
  //tablePaginater.init(table_id1);
  fdTableSort.init(table_id1);

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
			data_array2[node_data[0]][h_index] = convert_data(node_data[h_index+1], data_source, data_type, tagname);
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
  fdTableSort.init(table_id2);
  //tablePaginater.init(table_id2);

}

  //fdTableSort.removeTableCache(table_id2);
  //document.getElementById('loading'+column).innerHTML = "";
  
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
	var data_source = document.getElementById('source'+column).value;
	var data_type = document.getElementById('type'+column).value;
	var to_load = false;

	//debugfilter("<br>adding "+column+","+header+','+fetched+','+t+','+data_source+','+data_type);

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
		//debugfilter("ready to load ... ");
		document.getElementById('loading'+column).innerHTML = "<img width=10 src=/plekit/icons/ajax-loader.gif>";
		var url = "/plekit/php/updateColumn.php?slice_id="+slice_id+"&tagName="+t+"&data_type="+data_type+"&data_source="+data_source;
		//debugfilter("calling "+url);
		load_data(column, header, url, data_source, data_type, t);
	}
}



function addColumn(column, fetch) {

	var selectedperiod="";
	var header=column;
	var conf="";


	if (inTypeC(column)!=-1)
	{
		column = column.substring(0,column.length-1);
	}

        selectedperiod = document.getElementById('period'+column).value;
	header = column+""+selectedperiod;

	conf = header;

	//debugfilter("adding column "+column+" and header "+header+" and conf = "+conf);

	addColumnCells(header);

	if (fetch)
		addColumnAjax(column, header);

	addColumnToConfiguration(conf);
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
}


