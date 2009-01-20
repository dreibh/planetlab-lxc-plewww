/*
 
  Animated miniTabs by frequency decoder (http://www.frequency-decoder.com/)
 
  Based on an idea by Rob L Glazebrook (http://www.rootarcana.com/test/smartmini/) itself
  derived from the original idea of Stephen Clark (http://www.sgclark.com/sandbox/minislide/)
 
  Adjusted by Thierry Parmentelat -- INRIA - uses only forms rather than <a> tags, for supporting http-POST

*/
 
var miniTab = {
 currentTab:     0,
 activeTab:      0,
 destX:          0,
 destW:          0,
 t:              0,
 b:              0,
 c:              0,
 d:              20,
 animInterval:   null,
 sliderObj:      null,
 aHeight:        0,
 ul:             [],
 liArr:          [],
 aArr:           [],
        
 init: function() {
    if(!document.getElementById || !document.getElementById("miniflex")) return;
 
    miniTab.ul          = document.getElementById("miniflex");
    miniTab.liArr       = miniTab.ul.getElementsByTagName("li");
    // Thierry: the original impl. relied on <a> links rather than forms
    miniTab.aArr        = miniTab.ul.getElementsByTagName("input");
 
    for(var i = 0, li; li = miniTab.liArr[i]; i++) {
      li.onmouseover = miniTab.aArr[i].onfocus = function(e) {
	var pos = 0;
	var elem = this.nodeName == "LI" ? this : this.parentNode;
	while(elem.previousSibling) {
	  elem = elem.previousSibling;
	  if(elem.tagName && elem.tagName == "LI") pos++;
 
	}
	miniTab.initSlide(pos);
      }
    }
 
    miniTab.ul.onmouseout = function(e) {
      miniTab.initSlide(miniTab.currentTab);
    };
 
    for(var i = 0, a; a = miniTab.aArr[i]; i++) {
      if(a.className.search("active") != -1) {
	miniTab.activeTab = miniTab.currentTab = i;
      }
      a.style.borderBottom  = "0px";
      /*a.style.paddingBottom = "6px";*/
    }
 
    miniTab.slideObj                = miniTab.ul.parentNode.appendChild(document.createElement("div"));
    miniTab.slideObj.appendChild(document.createTextNode(String.fromCharCode(160)));
    miniTab.slideObj.id             = "animated-tab";
    miniTab.slideObj.style.top      = (miniTab.ul.offsetTop + miniTab.liArr[miniTab.activeTab].offsetTop + miniTab.aArr[miniTab.activeTab].offsetTop) + "px";
    miniTab.slideObj.style.left     = (miniTab.ul.offsetLeft + miniTab.liArr[miniTab.activeTab].offsetLeft + miniTab.aArr[miniTab.activeTab].offsetLeft) + "px";
    miniTab.slideObj.style.width    = miniTab.aArr[miniTab.activeTab].offsetWidth + "px";
    miniTab.aHeight                 = miniTab.ul.offsetTop + miniTab.liArr[miniTab.activeTab].offsetTop + miniTab.aArr[miniTab.activeTab].offsetTop;
 
    miniTab.initSlide(miniTab.activeTab, true);
 
    var intervalMethod = function() { miniTab.slideIt(); }
    miniTab.animInterval = setInterval(intervalMethod,10);
  },
 
 cleanUp: function() {
    clearInterval(miniTab.animInterval);
    miniTab.animInterval = null;
  },
 
 initSlide: function(pos, force) {
    if(!force && pos == miniTab.activeTab) return;
    miniTab.activeTab = pos;
    miniTab.initAnim();
  },
 
 /* search for input with type != hidden */
 locateSubmitInput: function () {
    inputs=miniTab.liArr[miniTab.activeTab].getElementsByTagName("input");
    for(var i=0,input; inputs[i]; i++) {
      input=inputs[i];
      if (input.type == "submit") return input;
    }
  },
    
 initAnim: function() {
    var input=miniTab.locateSubmitInput();
    miniTab.destX = parseInt(miniTab.liArr[miniTab.activeTab].offsetLeft + input.offsetLeft + miniTab.ul.offsetLeft);
    miniTab.destW = parseInt(input.offsetWidth);
    miniTab.t = 0;
    miniTab.b = miniTab.slideObj.offsetLeft;
    miniTab.c = miniTab.destX - miniTab.b;
 
    miniTab.bW = miniTab.slideObj.offsetWidth;
    miniTab.cW = miniTab.destW - miniTab.bW;
 
    miniTab.slideObj.style.top = (miniTab.ul.offsetTop + miniTab.liArr[miniTab.activeTab].offsetTop + miniTab.aArr[miniTab.activeTab].offsetTop) + "px";
  },
 
 slideIt:function() {
 
    // Has the browser text size changed?
    if(miniTab.aHeight != miniTab.ul.offsetTop + miniTab.liArr[miniTab.activeTab].offsetTop + miniTab.aArr[miniTab.activeTab].offsetTop) {
      miniTab.initAnim();
      miniTab.aHeight = miniTab.ul.offsetTop + miniTab.liArr[miniTab.activeTab].offsetTop + miniTab.aArr[miniTab.activeTab].offsetTop
    };
 
    if(miniTab.t++ < miniTab.d) {
      var x = miniTab.animate(miniTab.t,miniTab.b,miniTab.c,miniTab.d);
      var w = miniTab.animate(miniTab.t,miniTab.bW,miniTab.cW,miniTab.d);
 
      miniTab.slideObj.style.left = parseInt(x) + "px";
      miniTab.slideObj.style.width = parseInt(w) + "px";
    } else {
      miniTab.slideObj.style.left = miniTab.destX + "px";
      miniTab.slideObj.style.width = miniTab.destW +"px";
    }
  },
 
 animate: function(t,b,c,d) {
    if ((t/=d/2) < 1) return c/2*t*t + b;
    return -c/2 * ((--t)*(t-2) - 1) + b;
  }
}
 
window.onload = miniTab.init;
window.onunload = miniTab.cleanUp;
 
