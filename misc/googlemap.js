/* this function should solve the issue of calling a function
 * upon page load, that badly depends on the browser
 * see an example in http://svn.planet-lab.org/wiki/GooglemapSetup
 */
function onContent(f){
  var 
    a=onContent,
    b=navigator.userAgent,
    d=document,
    w=window,
    c="onContent",
    e="addEventListener",
    o="opera",
    r="readyState",
    s="<scr".concat("ipt defer src='//:' on",r,"change='if(this.",r,"==\"complete\"){this.parentNode.removeChild(this);",c,".",c,"()}'></scr","ipt>");
  a[c]=(function(o){return function(){a[c]=function(){};for(a=arguments.callee;!a.done;a.done=1)f(o?o():o)}})(a[c]);
  if(d[e])d[e]("DOMContentLoaded",a[c],false);
  if(/WebKit|Khtml/i.test(b)||(w[o]&&parseInt(w[o].version())<9))(function(){/loaded|complete/.test(d[r])?a[c]():setTimeout(arguments.callee,1)})();
  else if(/MSIE/i.test(b))d.write(s);
}

function googlemap (htmlid,kmlurl,centerLat, centerLon, zoom) {
  //  alert ('in googlemap, kmlurl='+kmlurl+',id ='+htmlid);
  if (GBrowserIsCompatible()) {
    var map = new GMap2(document.getElementById(htmlid));
    map.setCenter(new GLatLng(centerLat, centerLon), zoom);
    map.addControl(new GLargeMapControl());
    map.addControl(new GMapTypeControl());
    map.addControl(new GOverviewMapControl());
    /*var geocoder = new GClientGeocoder();*/
    map.setMapType(G_SATELLITE_MAP);
    
    geoXml = new GGeoXml(kmlurl);
    map.addOverlay(geoXml);
  }
}

window.onunload=GUnload;
