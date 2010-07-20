/* need to put some place else in CSS ? */

var color_otherslice="#f08080";
var color_thisslice="#a5e0af";
var color_free="#fff";
var color_rules="#888";

var x_txt = {"font": 'Fontin-Sans, Arial', stroke: "none", fill: "#008"},
var y_txt = {"font": 'Fontin-Sans, Arial', stroke: "none", fill: "#800"},
var x_nodename = 200;
var x_grain = 20;
var x_sep=10;
var y_header = 12
var y_node = 15;
var y_sep = 10
var radius= 6;

var leases_namespace = {

    init_scheduler: function () {
	// Grab the data
	var data = [],
        axisx = [],
        axisy = [],
        table = $$("table#leases_data")[0];
	// the nodenames
	table.getElementsBySelector("tbody>tr>th").each(function (cell) {
            axisy.push(getInnerText(cell));
	});
	// the timeslot labels
	table.getElementsBySelector("thead>tr>th").each(function (cell) {
            axisx.push(getInnerText(cell));
	});
	// leases - expect colspan to describe length in grains
	table.getElementsBySelector("tbody>tr>td").each(function (cell) {
            data.push(new Array (getInnerText(cell),cell.colSpan));
	});
	// slicename : the upper-left cell
	var this_slicename = getInnerText(table.getElementsBySelector("thead>tr>td")[0]);
	table.hide();
	var nb_nodes = axisy.length, nb_grains = axisx.length;
	var total_width = x_nodename + nb_grains*x_grain;
	var total_height = 2*y_header + nb_nodes*(y_node+y_sep);
	paper = Raphael("leases_area", total_width, total_height,10);
//        color = table.css("color");

	var top=0;
        var left=x_nodename;

	// the time slots legend
	var col=0;
        axisx.each (function (timeslot) {
	    var y = top+y_header;
	    if (col%2 == 0) y += y_header;
	    col +=1;
	    var label=paper.text(left,y,timeslot).attr(y_txt).attr({"font-size":y_header,
								    "text-anchor":"middle"});
	    var path_spec="M"+left+" "+(y+y_header/2)+"L"+left+" "+total_height;
	    var rule=paper.path(path_spec).attr({'stroke':1,"fill":color_rules});
	    left+=x_grain;
	});

        top += 2*y_header+y_sep;
	    
	var data_index=0;
	axisy.each(function (node) {
	    left=0;
	    var label = paper.text(x_nodename-x_sep,top+y_node/2,node).attr(x_txt).attr ({"font-size":y_node,
											"text-anchor":"end",
										        "baseline":"bottom"});
	
	    left += x_nodename;
	    var grain=0;
	    while (grain < nb_grains) {
		slicename=data[data_index][0];
		duration=data[data_index][1];
		var lease=paper.rect (left,top,x_grain*duration,y_node,radius);
		var color;
		if (slicename == "") color=color_free;
		else if (slicename == this_slicename) color=color_thisslice;
		else {
		    color=color_otherslice;
		    /* attempt to display the name of the slice that owns that lease - not working too well */
		    var label = paper.text (left+(x_grain*duration)/2,top+y_node/2,slicename).attr("display","none");
		    label.hide();
		    lease[0].onmouseover = function () { label.show(); }
		    lease[0].onmouseout = function () { label.hide(); }
		}
		lease.attr("fill",color);
		grain += duration;
		left += x_grain*duration;
		data_index +=1;
	    }
	    top += y_node + y_sep;
	});
    }

/*
	r.rect(10, 10, total_width-20, total_height-20, radius).attr({fill: "#888", stroke: "#fff"});
	for (var i = 0, ii = axisx.length; i < ii; i++) {
            r.text(leftgutter + X * (i + .5), 294, axisx[i]).attr(txt);
	}
	for (var i = 0, ii = axisy.length; i < ii; i++) {
            r.text(10, Y * (i + .5), axisy[i]).attr(txt);
	}
	var o = 0;
	for (var i = 0, ii = axisy.length; i < ii; i++) {
            for (var j = 0, jj = axisx.length; j < jj; j++) {
		var R = data[o] && Math.min(Math.round(Math.sqrt(data[o] / Math.PI) * 4), max);
		if (R) {
                    (function (dx, dy, R, value) {
			var color = "hsb(" + [(1 - R / max) * .5, 1, .75] + ")";
			var dt = r.circle(dx + 60 + R, dy + 10, R).attr({stroke: "none", fill: color});
			if (R < 6) {
                            var bg = r.circle(dx + 60 + R, dy + 10, 6).attr({stroke: "none", fill: "#000", opacity: .4}).hide();
			}
			var lbl = r.text(dx + 60 + R, dy + 10, data[o])
                            .attr({"font": '10px Fontin-Sans, Arial', stroke: "none", fill: "#fff"}).hide();
			var dot = r.circle(dx + 60 + R, dy + 10, max).attr({stroke: "none", fill: "#000", opacity: 0});
			dot[0].onmouseover = function () {
                            if (bg) {
				bg.show();
                            } else {
				var clr = Raphael.rgb2hsb(color);
				clr.b = .5;
				dt.attr("fill", Raphael.hsb2rgb(clr).hex);
                            }
                            lbl.show();
			};
			dot[0].onmouseout = function () {
                            if (bg) {
				bg.hide();
                            } else {
				dt.attr("fill", color);
                            }
                            lbl.hide();
			};
                    })(leftgutter + X * (j + .5) - 60 - R, Y * (i + .5) - 10, R, data[o]);
		}
		o++;
            }
	}
    }
*/
};

Event.observe(window, 'load', leases_namespace.init_scheduler);
