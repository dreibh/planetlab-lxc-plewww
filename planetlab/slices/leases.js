/* need to put some place else in CSS ? */


/* decorations / headers */

var txt_nodename = {"font": '"Trebuchet MS", Verdana, Arial, Helvetica, sans-serif', stroke: "none", fill: "#008"};
var txt_timeslot = {"font": '"Trebuchet MS", Verdana, Arial, Helvetica, sans-serif', stroke: "none", fill: "#008"};
var attr_rules={'fill':"#888", 'stroke-dasharray':'- ', 'stroke-width':0.5};
var x_nodename = 200;
var x_sep=10;
var y_header = 12
var y_sep = 20

/* lease dimensions and colors */
var anim_delay=500;
var x_grain = 24;
var y_node = 15;
var radius= 6;
/* lease was originally free and is still free */
var attr_lease_free_free={'fill':"#def", 'stroke-width':0.5, 'stroke-dasharray':''};
/* lease was originally free and is now set for our usage */
var attr_lease_free_mine={'fill':"green", 'stroke-width':1, 'stroke-dasharray':'-..'};
/* was mine and is still mine */
var attr_lease_mine_mine={'fill':"#beb", 'stroke-width':0.5, 'stroke-dasharray':''};
/* was mine and is about to be released */
var attr_lease_mine_free={'fill':"white", 'stroke-width':1, 'stroke-dasharray':'-..'};
// refrained from using gradient color, was not animated properly
// var color_lease_mine_free="0-#fff-#def:50-#fff";
var attr_lease_other={'fill':"#f88"};

/* other slices name */
var txt_slice = {"font": '"Trebuchet MS", Verdana, Arial, Helvetica, sans-serif', stroke: "none", fill: "#444",
		 "font-size": 15 };

////////////////////////////////////////////////////////////
// the scheduler object
function Scheduler (slicename, axisx, axisy, data) {

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

    this.init = function (id) {
	this.total_width = x_nodename + this.nb_grains()*x_grain; 
	this.total_height = 2*y_header + this.axisy.length*(y_node+y_sep); 
	paper = Raphael (id, this.total_width, this.total_height);

	// create the time slots legend
	var top=0;
	var left=x_nodename;

	var col=0;
	for (var i=0, len=axisx.length; i < len; ++i) {
	    // pick the printable part
	    timeslot=axisx[i][1];
	    var y = top+y_header;
	    if (col%2 == 0) y += y_header;
	    col +=1;
	    var timelabel=paper.text(left,y,timeslot).attr(txt_timeslot)
		.attr({"font-size":y_header, "text-anchor":"middle"});
	    var path_spec="M"+left+" "+(y+y_header/2)+"L"+left+" "+this.total_height;
	    var rule=paper.path(path_spec).attr(attr_rules);
	    left+=x_grain;
	}

	// move to the lines below: 
	top += 2*y_header+y_sep;
    
	var data_index=0;
	for (var i=0, len=axisy.length; i<len; ++i) {
	    node=axisy[i];
	    left=0;
	    var nodelabel = paper.text(x_nodename-x_sep,top+y_node/2,node).attr(txt_nodename)
		.attr ({"font-size":y_node, "text-anchor":"end","baseline":"bottom"});
	    
	    left += x_nodename;
	    var grain=0;
	    while (grain < this.nb_grains()) {
		slicename=data[data_index][0];
		duration=data[data_index][1];
		var lease=paper.rect (left,top,x_grain*duration,y_node,radius);
		lease.nodename=node;
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
	for (var i=0, len=this.leases.length; i<len; ++i) {
	    var lease=this.leases[i];
	    if (lease.current != lease.initial) {
		var from_time=lease.from_time;
		var until_time=lease.until_time;
		/* scan the leases just after this one and merge if appropriate */
		var j=i+1;
		while (j<len && lease_methods.compare (lease, until_time, this.leases[j])) {
		    window.console.log('merged index='+j);
		    until_time=this.leases[j].until_time;
		    ++j; ++i;
		}
		var method=(lease.current=='free') ? 'DeleteLeases' : 'AddLeases';
		window.console.log(method + "(" + 
				   "[" + lease.nodename + "]," + 
				   this.slicename + "," +
				   from_time + "," +
				   until_time + ')');
	    }
	}
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

    /* initialize mode buttons */
    this.init_mode = function (default_mode, node_button, timeslot_button) {
	this.node_button=node_button;
	this.timeslot_button=timeslot_button;
	var scheduler=this;
	/* xxx set callbacks on buttons */
	node_button.onclick = function () { scheduler.set_mode('node'); }
	timeslot_button.onclick = function () { scheduler.set_mode('timeslot'); }
	this.set_mode(default_mode);
    }

    /* expecting mode to be either 'node' or 'timeslot' */
    this.set_mode = function (mode) {
	this.mode=mode;
	var active_button = (mode=='node') ? this.node_button : this.timeslot_button;
	active_button.checked='checked';
    }
	

} // end Scheduler

//////////////////////////////////////// couldn't find how to inhererit from the raphael objects...
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
	if (scheduler.mode=='node') {
	    lease_methods.init_mine(this,lease_methods.click_free);
	} else {
	    for (var i=0, len=scheduler.leases.length; i<len; ++i) {
		scan=scheduler.leases[i];
		// overlap ?
		if (scan.from_time<=this.from_time && scan.until_time>=this.until_time) 
		    if (scan.current == "free") lease_methods.init_mine(scan,lease_methods.click_free);
	    }
	}
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
	if (scheduler.mode=='node') {
	    lease_methods.init_free(this, lease_methods.click_mine);
	} else {
	    for (var i=0, len=scheduler.leases.length; i<len; ++i) {
		scan=scheduler.leases[i];
		// overlap ?
		if (scan.from_time<=this.from_time && scan.until_time>=this.until_time) {
		    if (scan.current == "mine") lease_methods.init_free(scan,lease_methods.click_mine);
		}
		// the other ones just remain as they are
	    }
	}
    },


    init_other: function (lease, slicename) {
	lease.animate (attr_lease_other,anim_delay);
	/* a text obj to display the name of the slice that owns that lease */
	var slicelabel = paper.text (lease.attr("x")+lease.attr("width")/2,
				     lease.attr("y")+lease.attr("height")/2,slicename).attr(txt_slice);
	/* hide it right away */
	slicelabel.hide();
	/* record it */
	lease.label=slicelabel;
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
    // the nodenames
    table.getElementsBySelector("tbody>tr>th").each(function (cell) {
        axisy.push(getInnerText(cell));
    });
    // the timeslot labels
    table.getElementsBySelector("thead>tr>th").each(function (cell) {
	/* [0]: timestamp -- [1]: displayable*/
        axisx.push(getInnerText(cell).split("&"));
    });
    // leases - expect colspan to describe length in grains
    table.getElementsBySelector("tbody>tr>td").each(function (cell) {
        data.push(new Array (getInnerText(cell),cell.colSpan));
    });
    // slicename : the upper-left cell
    var scheduler = new Scheduler (getInnerText(table.getElementsBySelector("thead>tr>td")[0]), axisx, axisy, data);
    table.hide();
    // leases_area is a <div> created by slice.php as a placeholder
    scheduler.init ("leases_area");

    var submit=$$("button#leases_submit")[0];
    submit.onclick = function () { scheduler.submit(); }
    var clear=$$("button#leases_clear")[0];
    clear.onclick = function () { scheduler.clear(); }

    var node_button=$$("input#leases_mode_node")[0];
    var timeslot_button=$$("input#leases_mode_timeslot")[0];
    scheduler.init_mode ('timeslot',node_button,timeslot_button);

}

Event.observe(window, 'load', init_scheduler);
