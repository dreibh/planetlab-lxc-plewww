// use localStorage to remember open/closed toggles
function plekit_toggle_store(id) {
    var area=$('toggle-area-'+id);
    key='toggle.'+id;
    flag= (area.visible()) ? 'on' : 'off';
    localStorage.setItem(key,flag);
}
function plekit_toggle_from_store (id) {
    window.console.log('id='+id);
    var area=$('toggle-area-'+id);
    key='toggle.'+id;
    flag=localStorage.getItem(key);
    // on by default
    if (flag=='off') area.hide();
    else area.show();
}

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
    plekit_toggle_store(id);
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

