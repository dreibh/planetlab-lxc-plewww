////////// use jstorage to remember open/closed toggles
// store current status
function pletoggle_store(id) {
    var area=$('toggle-area-'+id);
    key='toggle.'+id;
//    window.console.log('storing toggle status for '+id);
    $.jStorage.set(key,area.visible());
}
// restore last status
function pletoggle_from_store (id) {
    key='toggle.'+id;
    // don't do anything if nothing stored
    var stored=$.jStorage.get(key,undefined);
    if (stored==true || stored==false) {
	//    window.console.log('retrieved toggle status for '+id+'=> '+stored);
	pletoggle_set_visible(id,stored);
    }
}

////////// manage a toggle
// toggle it
function pletoggle_toggle(id){

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
    pletoggle_store(id);
}

// for compatibility (monitor)
function plc_toggle (id) { return pletoggle_toggle (id); }

// make sure it's open or closed
function pletoggle_set_visible(id, status) {
    var area=$('toggle-area-'+id);
    if (area.visible()!=status) pletoggle_toggle (id);
}

// toggle the attached info box
function pletoggle_toggle_info(id){

    // need to take care of the area as well
    var area=$('toggle-area-'+id);
    var info=$('toggle-info-'+id);
    if (area.visible() && info.visible()) {
//	window.console.log('PTI hiding');
	info.hide();
    } else {
	// make sure area is visible, take of the triggers
//	window.console.log('PTI showing');
	pletoggle_set_visible(id,true);
	info.show();
    }
}

