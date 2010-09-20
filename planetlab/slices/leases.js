/* need to put some place else in CSS ? */

// space for the nodenames
var x_nodelabel = 200;
// right space after the nodename - removed from the above
var x_sep=20;
// height for the (two) rows of timelabels
var y_header = 12;
// space between nodes
var y_sep = 10;

// 1-grain leases attributes
var x_grain = 20;
var y_node = 15;
var radius= 6;

var anim_delay=500;

/* decorations / headers */
/* note: looks like the 'font' attr is not effective... */

// vertical rules
var attr_rules={'fill':"#888", 'stroke-dasharray':'- ', 'stroke-width':0.5};
// set font-size separately in here rather than depend on the height
var txt_timelabel = {"font": 'Times, "Trebuchet MS", Verdana, Arial, Helvetica, sans-serif', 
		     stroke: "none", fill: "#008", 'font-size': 9};
var txt_allnodes = {"font": '"Trebuchet MS", Verdana, Arial, Helvetica, sans-serif', stroke: "none", fill: "#404"};
var txt_nodelabel = {"font": '"Trebuchet MS", Verdana, Arial, Helvetica, sans-serif', stroke: "none", fill: "#008"};

var attr_timebutton = {'fill':'#bbf', 'stroke': '#338','stroke-width':2, 
		       'stroke-linecap':'round', 'stroke-linejoin':'miter', 'stroke-miterlimit':3};
// keep consistent with sizes above - need for something nicer
var timebutton_path = "M1,0L19,0L10,8L1,0";

/* lease dimensions and colors */
/* refrain from using gradient color, seems to not be animated properly */
/* lease was originally free and is still free */
var attr_lease_free_free={'fill':"#def", 'stroke-width':0.5, 'stroke-dasharray':''};
/* lease was originally free and is now set for our usage */
var attr_lease_free_mine={'fill':"green", 'stroke-width':1, 'stroke-dasharray':'-..'};
/* was mine and is still mine */
var attr_lease_mine_mine={'fill':"#beb", 'stroke-width':0.5, 'stroke-dasharray':''};
/* was mine and is about to be released */
var attr_lease_mine_free={'fill':"white", 'stroke-width':1, 'stroke-dasharray':'-..'};
var attr_lease_other={'fill':"#f88"};

/* other slices name */
var txt_otherslice = {"font": '"Trebuchet MS", Verdana, Arial, Helvetica, sans-serif', stroke: "none", fill: "#444",
		      "font-size": 12 };

////////////////////////////////////////////////////////////
// the scheduler object
function Scheduler (sliceid, slicename, axisx, axisy, data) {

    // the data contains slice names, and lease_id, we need this to find our own leases (mine)
    this.sliceid=sliceid;
    this.slicename=slicename;
    this.axisx=axisx;
    this.axisy=axisy;
    this.data=data;

    // utilities to keep track of all the leases
    this.leases=[];
    this.append_lease = function (lease) { 
	this.leases.push(lease);
    }

    // how many time slots 
    this.nb_grains = function () { return axisx.length;}

    this.init = function (canvas_id) {
	this.total_width = x_nodelabel + this.nb_grains()*x_grain; 
	this.total_height =   2*y_header /* the timelabels */
			    + 2*y_sep    /* extra space */
                	    + y_node	 /* all-nodes & timebuttons row */ 
         		    + (this.axisy.length)*(y_node+y_sep);  /* the regular nodes and preceding space */
	paper = Raphael (canvas_id, this.total_width+x_sep, this.total_height);

	// maintain the list of nodelabels for the 'all nodes' button
	this.nodelabels=[];

	////////// create the time slots legend
	var top=0;
	var left=x_nodelabel;

	var col=0;
	for (var i=0, len=axisx.length; i < len; ++i) {
	    // pick the printable part
	    timelabel=axisx[i][1];
	    var y = top+y_header;
	    if (col%2 == 0) y += y_header;
	    col +=1;
	    // display time label
	    var timelabel=paper.text(left,y,timelabel).attr(txt_timelabel)
		.attr({"text-anchor":"middle"});
	    // draw vertical line
	    var path_spec="M"+left+" "+(y+y_header/2)+"L"+left+" "+this.total_height;
	    var rule=paper.path(path_spec).attr(attr_rules);
	    left+=x_grain;
	}

	this.granularity=axisx[1][0]-axisx[0][0];

	////////// the row with the timeslot buttons
	// move two lines down
	top += 2*y_header+2*y_sep;
	left=x_nodelabel;
	// all nodes buttons
	var allnodes = paper.text (x_nodelabel-x_sep,top+y_node/2,"All nodes").attr(txt_allnodes)
		.attr ({"font-size":y_node, "text-anchor":"end","baseline":"bottom"});
	allnodes.scheduler=this;
	allnodes.click(allnodes_methods.click);
	// timeslot buttons
	for (var i=0, len=axisx.length; i < len; ++i) {
	    var timebutton=paper.path(timebutton_path).attr({'translation':left+','+top}).attr(attr_timebutton);
	    timebutton.from_time=axisx[i][0];
	    timebutton.scheduler=this;
	    timebutton.click(timebutton_methods.click);
	    left+=x_grain;
	}
	
	//////// the body of the scheduler : loop on nodes
	top += y_node+y_sep;
	var data_index=0;
	for (var i=0, len=axisy.length; i<len; ++i) {
	    nodename=axisy[i];
	    left=0;
	    var nodelabel = paper.text(x_nodelabel-x_sep,top+y_node/2,nodename).attr(txt_nodelabel)
		.attr ({"font-size":y_node, "text-anchor":"end","baseline":"bottom"});
	    nodelabel_methods.selected(nodelabel,1);
	    nodelabel.click(nodelabel_methods.click);
	    this.nodelabels.push(nodelabel);
	    
	    left += x_nodelabel;
	    var grain=0;
	    while (grain < this.nb_grains()) {
		lease_id=data[data_index][0];
		slicename=data[data_index][1];
		duration=data[data_index][2];
		var lease=paper.rect (left,top,x_grain*duration,y_node,radius);
		lease.lease_id=lease_id;
		lease.nodename=nodename;
		lease.nodelabel=nodelabel;
		if (slicename == "") {
		    lease.initial="free";
		    lease_methods.init_free(lease);
		} else if (slicename == this.slicename) {
		    lease.initial="mine";
		    lease_methods.init_mine(lease);
		} else {
		    lease_initial="other";
		    lease_methods.init_other(lease,slicename);
		}
		lease.from_time = axisx[grain%this.nb_grains()][0];
		grain += duration;
		lease.until_time = axisx[grain%this.nb_grains()][0];
		// record scheduler in lease
		lease.scheduler=this;
		// and vice versa
		this.append_lease(lease);
		// move on with the loop
		left += x_grain*duration;
		data_index +=1;
	    }
	    top += y_node + y_sep;
	};
    }

    this.submit = function () {
	document.body.style.cursor = "wait";
	var actions=new Array();
	for (var i=0, len=this.leases.length; i<len; ++i) {
	    var lease=this.leases[i];
	    if (lease.current != lease.initial) {
		var from_time=lease.from_time;
		var until_time=lease.until_time;
		/* scan the leases just after this one and merge if appropriate */
		var j=i+1;
		while (j<len && lease_methods.compare (lease, until_time, this.leases[j])) {
//		    window.console.log('merged index='+j);
		    until_time=this.leases[j].until_time;
		    ++j; ++i;
		}
		if (lease.current!='free') { // lease to add
		    actions.push(new Array('add-leases',
					   new Array(lease.nodename),
					   this.slicename,
					   from_time,
					   until_time));
		} else { // lease to delete
		    actions.push(new Array ('delete-leases',
					    lease.lease_id));
		}
	    }
	}
	sliceid=this.sliceid;
	// once we're done with the side-effect performed in actions.php, we need to refresh this view
	redirect = function (sliceid) {
	    window.location = '/db/slices/slice.php?id=' + sliceid + '&show_details=0&show_nodes=1&show_nodes_resa=1';
	}
	// Ajax.Request comes with prototype
	var ajax=new Ajax.Request('/planetlab/common/actions.php', 
				  {method:'post',
				   parameters:{'action':'manage-leases',
					       'actions':actions.toJSON()},
				   onSuccess: function(transport) {
				       var response = transport.responseText || "no response text";
				       document.body.style.cursor = "default";
				       alert("Server answered:\n\n" + response + "\n\nPress OK to refresh page");
				       redirect(sliceid);
				   },
				   onFailure: function(){ 
				       document.body.style.cursor = "default";
				       alert("Could not reach server, sorry...\n\nPress OK to refresh page");
				       // not too sure what to do here ...
				       redirect(sliceid);
				   },
				  });
    }

    this.clear = function () {
	for (var i=0, len=this.leases.length; i<len; ++i) {
	    var lease=this.leases[i];
	    if (lease.current != lease.initial) {
		if (lease.initial == 'free') lease_methods.init_free(lease,lease_methods.click_mine);
		else			     lease_methods.init_mine(lease,lease_methods.click_free);
	    }
	}
    }

} // end Scheduler

//////////////////////////////////////// couldn't find how to inhererit from the raphael objects...

//////////////////// the 'all nodes' button
var allnodes_methods = {
    click: function (event) {
	var scheduler=this.scheduler;
	/* decide what to do */
	var unselected=0;
	for (var i=0, len=scheduler.nodelabels.length; i<len; ++i) 
	    if (! scheduler.nodelabels[i].selected) unselected++;
	/* if at least one is not selected : select all */
	var new_state = (unselected >0) ? 1 : 0;
	for (var i=0, len=scheduler.nodelabels.length; i<len; ++i) 
	    nodelabel_methods.selected(scheduler.nodelabels[i],new_state);
    }
}

//////////////////// the buttons for managing the whole timeslot
var timebutton_methods = {

    /* clicking */
    click: function (event) {
	var scheduler = this.scheduler;
	var from_time = this.from_time;
	var until_time = from_time + scheduler.granularity;
	/* scan leases on selected nodes, store in two arrays */
	var relevant_free=[], relevant_mine=[];
	for (var i=0,len=scheduler.leases.length; i<len; ++i) {
	    var scan=scheduler.leases[i];
	    if ( ! scan.nodelabel.selected) continue;
	    // overlap ?
	    if (scan.from_time<=from_time && scan.until_time>=until_time) {
		if (scan.current == "free")       relevant_free.push(scan);
		else if (scan.current == "mine")  relevant_mine.push(scan);
	    }
	}
//	window.console.log("Found " + relevant_free.length + " free and " + relevant_mine.length + " mine");
	/* decide what to do, whether book or release */
	if (relevant_mine.length==0 && relevant_free.length==0) {
	    alert ("Nothing to do in this timeslot on the selected nodes");
	    return;
	}
	// if at least one is free, let's book
	if (relevant_free.length > 0) {
	    for (var i=0, len=relevant_free.length; i<len; ++i) {
		var lease=relevant_free[i];
		lease_methods.init_mine(lease,lease_methods.click_free);
	    }
	// otherwise we unselect
	} else {
	    for (var i=0, len=relevant_mine.length; i<len; ++i) {
		var lease=relevant_mine[i];
		lease_methods.init_free(lease,lease_methods.click_mine);
	    }
	}
    }
}

//////////////////// the nodelabel buttons
var nodelabel_methods = {
    
    // set selected mode and render visually
    selected: function (nodelabel, flag) {
	nodelabel.selected=flag;
	nodelabel.attr({'font-weight': (flag ? 'bold' : 'normal')});
    },

    // toggle selected
    click: function (event) {
	nodelabel_methods.selected( this, ! this.selected );
    }
}


//////////////////// the lease buttons
var lease_methods = {
    
    /* in the process of merging leases before posting to the API */
    compare: function (lease, until_time, next_lease) {
	return (next_lease['nodename'] == lease['nodename'] &&
		next_lease['from_time'] == until_time &&
		next_lease['initial'] == lease['initial'] &&
		next_lease['current'] == lease['current']);
    },

    init_free: function (lease, unclick) {
	lease.current="free";
	// set color
	lease.animate((lease.initial=="free") ? attr_lease_free_free : attr_lease_mine_free,anim_delay);
	// keep track of the current status
	// record action
	lease.click (lease_methods.click_free);
	if (unclick) lease.unclick(unclick);
    },
		     
    // find out all the currently free leases that overlap this one
    click_free: function (event) {
	var scheduler = this.scheduler;
	lease_methods.init_mine(this,lease_methods.click_free);
    },

    init_mine: function (lease, unclick) {
	lease.current="mine";
	lease.animate((lease.initial=="mine") ? attr_lease_mine_mine : attr_lease_free_mine,anim_delay);
	lease.click (lease_methods.click_mine);
	if (unclick) lease.unclick(unclick);
    },

    click_mine: function (event) {
	var scheduler = this.scheduler;
	// this lease was originally free but is now marked for booking
	// we free just this lease
	lease_methods.init_free(this, lease_methods.click_mine);
    },


    init_other: function (lease, slicename) {
	lease.animate (attr_lease_other,anim_delay);
	/* a text obj to display the name of the slice that owns that lease */
	var otherslicelabel = paper.text (lease.attr("x")+lease.attr("width")/2,
					  // xxx
					  lease.attr("y")+lease.attr("height")/2,slicename).attr(txt_otherslice);
	/* hide it right away */
	otherslicelabel.hide();
	/* record it */
	lease.label=otherslicelabel;
	lease.hover ( function (e) { this.label.toFront(); this.label.show(); },
		      function (e) { this.label.hide(); } ); 
    },
}

function init_scheduler () {
    // Grab the data
    var data = [], axisx = [], axisy = [];
    var table = $$("table#leases_data")[0];
    // no reservable nodes - no data
    if ( ! table) return;
    // the nodelabels
    table.getElementsBySelector("tbody>tr>th").each(function (cell) {
        axisy.push(getInnerText(cell));
    });
    // the timeslot labels
    table.getElementsBySelector("thead>tr>th").each(function (cell) {
	/* [0]: timestamp -- [1]: displayable*/
        axisx.push(getInnerText(cell).split("&"));
    });
    // leases - expect colspan to describe length in grains
    // the text contents is expected to be lease_id & slicename
    table.getElementsBySelector("tbody>tr>td").each(function (cell) {
	var cell_data;
	slice_attributes=getInnerText(cell).split('&');
	// booked leases come with lease id and slice name
	if (slice_attributes.length == 2) {
	    // leases is booked : slice_id, slice_name, duration in grains
	    cell_data=new Array (slice_attributes[0], slice_attributes[1], cell.colSpan);
	} else {
	    cell_data = new Array ('','',cell.colSpan);
	}
        data.push(cell_data);
    });
    // sliceid & slicename : the upper-left cell
    var slice_attributes = getInnerText(table.getElementsBySelector("thead>tr>td")[0]).split('&');
    var sliceid=slice_attributes[0];
    var slicename=slice_attributes[1];
    var scheduler = new Scheduler (sliceid,slicename, axisx, axisy, data);
    table.hide();
    // leases_area is a <div> created by slice.php as a placeholder
    scheduler.init ("leases_area");

    var submit=$$("button#leases_submit")[0];
    submit.onclick = function () { scheduler.submit(); }
    var clear=$$("button#leases_clear")[0];
    clear.onclick = function () { scheduler.clear(); }

}

Event.observe(window, 'load', init_scheduler);
