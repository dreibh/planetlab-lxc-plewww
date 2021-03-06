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

/* the PLE snippet for embedding a googlemap in front page */
/*
<p><?php
drupal_set_html_head('<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<..>"
      type="text/javascript"></script>');
drupal_add_js('googlemap/googlemap.js',TRUE);
drupal_set_html_head('<script type="text/javascript">
function my_googlemap () { ts = new Date().getTime(); googlemap("map_area","http://www.planet-lab.eu/sites/sites.kml?" + ts,52,15,4);} 
onContent(my_googlemap);
</script>');
?>
<div id="map_area" style="width: 640px; height: 480px">  </div></p>
*/

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
