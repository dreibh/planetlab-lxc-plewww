/* need to put some place else in CSS ? */

var x_nodename = 100;
var x_grain = 20;
var y_header = 10
var y_node = 15;
var y_sep = 5
var radius=5;

var leases_namespace = {

    init_scheduler: function () {
	// Grab the data
	var data = [],
        axisx = [],
        axisy = [],
        table = $$("table#leases_data")[0];
	table.getElementsBySelector("tbody>tr>td").each(function (x) {
            data.push(getInnerText(x));
	});
	table.getElementsBySelector("tbody>tr>th").each(function (x) {
            axisy.push(getInnerText(x));
	});
	table.getElementsBySelector("thead>tr>th").each(function (x) {
            axisx.push(getInnerText(x));
	});

	var slicename = getInnerText(table.getElementsBySelector("thead>tr>td")[0]);
	table.hide();
	var nb_nodes = axisy.length, nb_grains = axisx.length;
	var total_width = x_nodename + nb_grains*x_grain;
	var total_height = y_header + nb_nodes*(y_node+y_sep);
	paper = Raphael("leases_area", total_width, total_height,10);
//	alert ('nodes=' + nb_nodes + ' grains=' + nb_grains + ' data items=' + data.length + ' slicename=' + slicename);

//        color = table.css("color");
	var top=0;
	var data_index=0;
	axisy.each(function (node) {
	    left=0;
	    var label = paper.text(x_nodename/2,top+y_node/2,axisy[node]);
	    label.attr ("font-size",y_node);
	
	    left += x_nodename;
	    var grain=0;
	    while (grain < nb_grains) {
		var rect=paper.rect (left,top,x_grain,y_node,radius);
		var color;
		if (data[data_index] != "") {
		    if (data[data_index] == slicename) color="#0f0";
		    else color="#f00";
//		alert('based on data value [' + data[data_index] + '] - got color ' + color);
		    rect.attr("fill",color);
		}
		left += x_grain;
		grain += 1;
		data_index +=1;
	    }
	    top += y_node;
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
