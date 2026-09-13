/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:  http://openenergymonitor.org

  Marks the action widgets left in a dashboard when enable_action_widgets is
  off. Their render scripts are not loaded in that case, so the elements stay
  empty and read as a broken dashboard rather than a disabled feature.
*/

function disabled_widgets_mark(classnames) {
  var label = (typeof _Tr === "function") ? _Tr("Widget disabled") : "Widget disabled";
  var hint = (typeof _Tr === "function")
    ? _Tr("This widget can act on your account, so it is off until an admin sets enable_action_widgets in settings.ini")
    : "This widget can act on your account, so it is off until an admin sets enable_action_widgets in settings.ini";

  for (var i = 0; i < classnames.length; i++) {
    var elements = document.getElementsByClassName(classnames[i]);
    for (var j = 0; j < elements.length; j++) {
      var element = elements[j];
      // Leave anything a render script has already drawn into
      if (element.children.length || element.textContent.trim() !== "") continue;
      element.classList.add("widget-disabled");
      element.setAttribute("title", hint);
      element.textContent = label;
    }
  }
}

function disabled_widgets_init(classnames) {
  // The dashboard content is printed after this script is loaded
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      disabled_widgets_mark(classnames);
    });
  } else {
    disabled_widgets_mark(classnames);
  }
}
