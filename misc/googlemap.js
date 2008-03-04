/*
 * performs the equivalent of <body onload=create_map onunload=GUnload>
 * needs the allSites global var that is suppoesd to have been defined 
 * in /var/www/html/sites/plc-sites.js by plc-map.py
 */

window.onDomReady = DomReady;

//Setup the event
function DomReady(fn) {
  //W3C
  if(document.addEventListener) {
      document.addEventListener("DOMContentLoaded", fn, false);
  } else {
    //IE
    document.onreadystatechange = function(){readyState(fn)}
  }
}

//IE execute function
function readyState(fn) {
  // Thierry: initial version from the internet read 
  // if(document.readyState == "interactive") 
  // I have noticed that 
  // (*) on first load, I was hitting this point only once with complete
  // (*) on reload I get here twice with "interactive" and then "complete"
  // so as a quick'n dirty way :
  if(document.readyState == "complete") {
    fn();
  }
}

window.onDomReady(create_map);

/* initial center */
centerLat=52;
centerLon=15;
/* initial zoom level */
initZoom=4;

function decode_utf8( s )
{
  return decodeURIComponent( escape( s ) );
}

function create_marker (map, point, site) {
  var marker=new GMarker(point);
  var html='<a href="/db/sites/index.php?id=' + site.site_id + '">' + decode_utf8(site.name) + '</a>\n';
  html += '<br><a href="/db/nodes/index.php?site_id=' + site.site_id +'">' + site.nb_nodes + ' Nodes</a>\n';
  if (site.peername) {
    html += '<br> <a href="/db/peers/index.php?id=' + site.peer_id + '">' + decode_utf8(site.peername) + '</a>\n';
  }
  /* display site name with url on info window - triggers on click */
  GEvent.addListener(marker, 'click', function() {marker.openInfoWindowHtml(html);});
  /* double click - clear info window */
  GEvent.addListener(marker, 'dblclick', function() {marker.closeInfoWindow();});
  /* required before setImage can be called */
  map.addOverlay(marker);
  /* set different layouts for local/foreign */
  /* google originals are in http://maps.google.com/mapfiles/ms/icons/blue-dot.png or blue.png or ... */
  if (!site.peername) {
    /* local */
    marker.setImage('/misc/google-ple.png');
  } else {
    marker.setImage('/misc/google-plc.png');
  }
  return marker;
}

function create_map() {
  if (GBrowserIsCompatible()) {
    var map = new GMap2(document.getElementById("googlemap"));
    map.setCenter(new GLatLng(centerLat, centerLon), initZoom);
    map.addControl(new GLargeMapControl());
    map.addControl(new GMapTypeControl());
    map.addControl(new GOverviewMapControl());
    /*var geocoder = new GClientGeocoder();*/
    map.setMapType(G_SATELLITE_MAP);
    
    
    for (i=0;i<allSites.length;i++) {
      var site=allSites[i];
      /* discard unspecified sites, and sites with no nodes */
      if ( (site.nb_nodes != 0) && ( (site.lat!=0.0) || (site.lon!=0.0)) ) {
	  var point = new GLatLng(site.lat,site.lon);
	  create_marker(map,point,site);
      }
    }
  }
}

window.onunload=GUnload;


