function plekit_toggle(id){

    var area=$('toggle-area-'+id);
    area.toggle();
    var visible=$('toggle-image-visible-'+id);
    var hidden=$('toggle-image-hidden-'+id);
    if (area.visible()) {
	visible.show();
	hidden.hide();
    } else {
	visible.hide();
	hidden.show();
    }
}

// make sure it's open
function plekit_toggle_show(id) {
    var area=$('toggle-area-'+id);
    if (!area.visible()) plekit_toggle (id);
}

// open or close the info box
function plekit_toggle_info(id){

    // need to take care of the area as well
    var area=$('toggle-area-'+id);
    var info=$('toggle-info-'+id);
    if (area.visible() && info.visible()) {
	window.console.log('PTI hiding');
	info.hide();
    } else {
	// make sure area is visible, take of the triggers
	window.console.log('PTI showing');
	plekit_toggle_show(id);
	info.show();
    }
}

