/* the googlemap plugin using api v3 since v2 is bout to be taken down end of 2013 */

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
drupal_set_html_head('<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=<...>&sensor=false"></script>');
drupal_add_js('googlemap/googlemap3.js',TRUE);
drupal_set_html_head('<script type="text/javascript">
function my_googlemap () { ts = new Date().getTime(); googlemap("map_area","http://www.planet-lab.eu/sites/sites.kml?" + ts,52,15,4);} 
onContent(my_googlemap);
</script>');
<div id="map_area" style="width: 640px; height: 480px">  </div></p>
?>
*/

function googlemap (htmlid,kmlurl,centerLat, centerLon, zoom) {
    //  alert ('in googlemap, kmlurl='+kmlurl+',id ='+htmlid);
    /* GBrowserIsCompatible was deprecated in v3
       if (GBrowserIsCompatible()) ...
    */

    var center = new google.maps.LatLng(centerLat, centerLon);
    var options = {
	zoom: zoom,
	center: center,
	mapTypeId: google.maps.MapTypeId.SATELLITEMAP, /* ROADMAP */
	panControl: true,
	zoomControl: true,
	mapTypeControl: true,
	scaleControl: true,
	streetViewControl: true,
	overviewMapControl: true,
    }

    var map = new google.maps.Map(document.getElementById(htmlid), options);

    var layer_options = {
	map: map,
	preserveViewport: true,
	/*suppressInfoWindows*/
    };
    var layer = new google.maps.KmlLayer(kmlurl, layer_options);
}

/* GUnload was deprecated in v3
window.onunload=GUnload; 
*/
