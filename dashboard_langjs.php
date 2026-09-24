<?php
/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.
  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

// Create a Javascript associative array who contain all sentences from module
?>
var LANG_JS = new Array();
function _Tr(key)
{
<?php // will return the default value if LANG_JS[key] is not defined. ?>
    return LANG_JS[key] || key;
}
<?php
//Please USE the "builder" every javascript modify at: /scripts/dashboard_langjs_builder.php
// paste source code below
?>
//START
// chart.helper.js
LANG_JS["Hide"] = <?php echo json_encode(tr("Hide")); ?>;
LANG_JS["no data"] = <?php echo json_encode(tr("no data")); ?>;
LANG_JS["Show"] = <?php echo json_encode(tr("Show")); ?>;

// designer.js
LANG_JS["Add a"] = <?php echo json_encode(tr("Add a")); ?>;
LANG_JS["and"] = <?php echo json_encode(tr("and")); ?>;
LANG_JS["Changed, press to save"] = <?php echo json_encode(tr("Changed, press to save")); ?>;
LANG_JS["Choose height unit"] = <?php echo json_encode(tr("Choose height unit")); ?>;
LANG_JS["Choose width unit"] = <?php echo json_encode(tr("Choose width unit")); ?>;
LANG_JS["element to the dashboard"] = <?php echo json_encode(tr("element to the dashboard")); ?>;
LANG_JS["error found, fix to save"] = <?php echo json_encode(tr("error found, fix to save")); ?>;
LANG_JS["errors found, fix to save"] = <?php echo json_encode(tr("errors found, fix to save")); ?>;
LANG_JS["Height"] = <?php echo json_encode(tr("Height")); ?>;
LANG_JS["Invalid character"] = <?php echo json_encode(tr("Invalid character")); ?>;
LANG_JS["Must be"] = <?php echo json_encode(tr("Must be")); ?>;
LANG_JS["Must be a whole number"] = <?php echo json_encode(tr("Must be a whole number")); ?>;
LANG_JS["Must not use"] = <?php echo json_encode(tr("Must not use")); ?>;
LANG_JS["Must not use style, set it with the options"] = <?php echo json_encode(tr("Must not use style, set it with the options")); ?>;
LANG_JS["Must only use"] = <?php echo json_encode(tr("Must only use")); ?>;
LANG_JS["Must point at another site, or at a dashboard, app or graph view"] = <?php echo json_encode(tr("Must point at another site, or at a dashboard, app or graph view")); ?>;
LANG_JS["Must point at another site, or at an image in"] = <?php echo json_encode(tr("Must point at another site, or at an image in")); ?>;
LANG_JS["Must start with http:// or https://"] = <?php echo json_encode(tr("Must start with http:// or https://")); ?>;
LANG_JS["None"] = <?php echo json_encode(tr("None")); ?>;
LANG_JS["not configured"] = <?php echo json_encode(tr("not configured")); ?>;
LANG_JS["no text"] = <?php echo json_encode(tr("no text")); ?>;
LANG_JS["Off"] = <?php echo json_encode(tr("Off")); ?>;
LANG_JS["On"] = <?php echo json_encode(tr("On")); ?>;
LANG_JS["or less"] = <?php echo json_encode(tr("or less")); ?>;
LANG_JS["or more"] = <?php echo json_encode(tr("or more")); ?>;
LANG_JS["Percentage"] = <?php echo json_encode(tr("Percentage")); ?>;
LANG_JS["Pixels"] = <?php echo json_encode(tr("Pixels")); ?>;
LANG_JS["Too long, the limit is 512 characters"] = <?php echo json_encode(tr("Too long, the limit is 512 characters")); ?>;
LANG_JS["widget not installed"] = <?php echo json_encode(tr("widget not installed")); ?>;
LANG_JS["Width"] = <?php echo json_encode(tr("Width")); ?>;

// disabledwidgets.js
LANG_JS["This widget can act on your account, so it is off until an admin sets enable_action_widgets in settings.ini"] = <?php echo json_encode(tr("This widget can act on your account, so it is off until an admin sets enable_action_widgets in settings.ini")); ?>;
LANG_JS["Widget disabled"] = <?php echo json_encode(tr("Widget disabled")); ?>;

// widget.helper.js
LANG_JS["Automatic"] = <?php echo json_encode(tr("Automatic")); ?>;
LANG_JS["Back"] = <?php echo json_encode(tr("Back")); ?>;
LANG_JS["Bold"] = <?php echo json_encode(tr("Bold")); ?>;
LANG_JS["Center"] = <?php echo json_encode(tr("Center")); ?>;
LANG_JS["Front"] = <?php echo json_encode(tr("Front")); ?>;
LANG_JS["Italic"] = <?php echo json_encode(tr("Italic")); ?>;
LANG_JS["Left"] = <?php echo json_encode(tr("Left")); ?>;
LANG_JS["Normal"] = <?php echo json_encode(tr("Normal")); ?>;
LANG_JS["Oblique"] = <?php echo json_encode(tr("Oblique")); ?>;
LANG_JS["Right"] = <?php echo json_encode(tr("Right")); ?>;

// widgetlist.js
LANG_JS["Html code to show"] = <?php echo json_encode(tr("Html code to show")); ?>;
LANG_JS["Some text"] = <?php echo json_encode(tr("Some text")); ?>;
LANG_JS["Title"] = <?php echo json_encode(tr("Title")); ?>;

// bar_render.js
LANG_JS["Bold"] = <?php echo json_encode(tr("Bold")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Colour for min. and max. bars"] = <?php echo json_encode(tr("Colour for min. and max. bars")); ?>;
LANG_JS["Colour of title and values"] = <?php echo json_encode(tr("Colour of title and values")); ?>;
LANG_JS["Colour to draw bar in"] = <?php echo json_encode(tr("Colour to draw bar in")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Display Min. and Max. ?"] = <?php echo json_encode(tr("Display Min. and Max. ?")); ?>;
LANG_JS["Error Message"] = <?php echo json_encode(tr("Error Message")); ?>;
LANG_JS["Error message displayed when timeout is reached"] = <?php echo json_encode(tr("Error message displayed when timeout is reached")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font used"] = <?php echo json_encode(tr("Font used")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Grad. Num."] = <?php echo json_encode(tr("Grad. Num.")); ?>;
LANG_JS["Graduations"] = <?php echo json_encode(tr("Graduations")); ?>;
LANG_JS["How many graduation lines to draw (only relevant if graduations are on)"] = <?php echo json_encode(tr("How many graduation lines to draw (only relevant if graduations are on)")); ?>;
LANG_JS["Label Colour"] = <?php echo json_encode(tr("Label Colour")); ?>;
LANG_JS["Max. feed"] = <?php echo json_encode(tr("Max. feed")); ?>;
LANG_JS["Max value"] = <?php echo json_encode(tr("Max value")); ?>;
LANG_JS["Max value to show"] = <?php echo json_encode(tr("Max value to show")); ?>;
LANG_JS["Min. feed"] = <?php echo json_encode(tr("Min. feed")); ?>;
LANG_JS["Min / Max ?"] = <?php echo json_encode(tr("Min / Max ?")); ?>;
LANG_JS["No"] = <?php echo json_encode(tr("No")); ?>;
LANG_JS["Normal"] = <?php echo json_encode(tr("Normal")); ?>;
LANG_JS["Offset"] = <?php echo json_encode(tr("Offset")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["Should the graduations be shown"] = <?php echo json_encode(tr("Should the graduations be shown")); ?>;
LANG_JS["Static offset. Subtracted from value before computing"] = <?php echo json_encode(tr("Static offset. Subtracted from value before computing")); ?>;
LANG_JS["The feed for the maximum value"] = <?php echo json_encode(tr("The feed for the maximum value")); ?>;
LANG_JS["The feed for the minimum value"] = <?php echo json_encode(tr("The feed for the minimum value")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["Timeout without feed update in seconds (empty is never)"] = <?php echo json_encode(tr("Timeout without feed update in seconds (empty is never)")); ?>;
LANG_JS["Title"] = <?php echo json_encode(tr("Title")); ?>;
LANG_JS["Title of bar"] = <?php echo json_encode(tr("Title of bar")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units to show"] = <?php echo json_encode(tr("Units to show")); ?>;
LANG_JS["Value is multiplied by scale before display. Defaults to 1"] = <?php echo json_encode(tr("Value is multiplied by scale before display. Defaults to 1")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;
LANG_JS["Yes"] = <?php echo json_encode(tr("Yes")); ?>;

// battery_render.js
LANG_JS["Battery title"] = <?php echo json_encode(tr("Battery title")); ?>;
LANG_JS["Bold"] = <?php echo json_encode(tr("Bold")); ?>;
LANG_JS["Color of the label"] = <?php echo json_encode(tr("Color of the label")); ?>;
LANG_JS["Colour label"] = <?php echo json_encode(tr("Colour label")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Display style"] = <?php echo json_encode(tr("Display style")); ?>;
LANG_JS["Error Message"] = <?php echo json_encode(tr("Error Message")); ?>;
LANG_JS["Error message displayed when timeout is reached"] = <?php echo json_encode(tr("Error message displayed when timeout is reached")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Label font"] = <?php echo json_encode(tr("Label font")); ?>;
LANG_JS["Max value"] = <?php echo json_encode(tr("Max value")); ?>;
LANG_JS["Max value to show"] = <?php echo json_encode(tr("Max value to show")); ?>;
LANG_JS["Min value"] = <?php echo json_encode(tr("Min value")); ?>;
LANG_JS["Min value to show"] = <?php echo json_encode(tr("Min value to show")); ?>;
LANG_JS["Normal"] = <?php echo json_encode(tr("Normal")); ?>;
LANG_JS["Number of blocks"] = <?php echo json_encode(tr("Number of blocks")); ?>;
LANG_JS["Number of blocks to display"] = <?php echo json_encode(tr("Number of blocks to display")); ?>;
LANG_JS["Offset"] = <?php echo json_encode(tr("Offset")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["Static offset. Subtracted from value before computing"] = <?php echo json_encode(tr("Static offset. Subtracted from value before computing")); ?>;
LANG_JS["Style"] = <?php echo json_encode(tr("Style")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["Timeout without feed update in seconds (empty is never)"] = <?php echo json_encode(tr("Timeout without feed update in seconds (empty is never)")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units to show"] = <?php echo json_encode(tr("Units to show")); ?>;
LANG_JS["Value is multiplied by scale before display"] = <?php echo json_encode(tr("Value is multiplied by scale before display")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;
LANG_JS["With colour gradients"] = <?php echo json_encode(tr("With colour gradients")); ?>;
LANG_JS["Without colour gradients"] = <?php echo json_encode(tr("Without colour gradients")); ?>;

// button_render.js
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure."] = <?php echo json_encode(tr("Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure.")); ?>;
LANG_JS["Starting value"] = <?php echo json_encode(tr("Starting value")); ?>;
LANG_JS["Value"] = <?php echo json_encode(tr("Value")); ?>;

// curl_render.js
LANG_JS["0=rd, 1=gn, 2=gy, 3=bu, 4=vio, 5=ye, >5=bk"] = <?php echo json_encode(tr("0=rd, 1=gn, 2=gy, 3=bu, 4=vio, 5=ye, >5=bk")); ?>;
LANG_JS["Button Text"] = <?php echo json_encode(tr("Button Text")); ?>;
LANG_JS["Caption"] = <?php echo json_encode(tr("Caption")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Confirmation"] = <?php echo json_encode(tr("Confirmation")); ?>;
LANG_JS["Confirmation Box: yes/no"] = <?php echo json_encode(tr("Confirmation Box: yes/no")); ?>;
LANG_JS["Data to send"] = <?php echo json_encode(tr("Data to send")); ?>;
LANG_JS["Do you want to continue?"] = <?php echo json_encode(tr("Do you want to continue?")); ?>;
LANG_JS["GET/POST"] = <?php echo json_encode(tr("GET/POST")); ?>;
LANG_JS["HTTPS"] = <?php echo json_encode(tr("HTTPS")); ?>;
LANG_JS["in milliseconds"] = <?php echo json_encode(tr("in milliseconds")); ?>;
LANG_JS["IP"] = <?php echo json_encode(tr("IP")); ?>;
LANG_JS["IP address of server (server must have cross origin allowed to work, will not generally work on servers other than localhost)"] = <?php echo json_encode(tr("IP address of server (server must have cross origin allowed to work, will not generally work on servers other than localhost)")); ?>;
LANG_JS["Listen port of server"] = <?php echo json_encode(tr("Listen port of server")); ?>;
LANG_JS["Method"] = <?php echo json_encode(tr("Method")); ?>;
LANG_JS["Payload"] = <?php echo json_encode(tr("Payload")); ?>;
LANG_JS["Port"] = <?php echo json_encode(tr("Port")); ?>;
LANG_JS["This dashboard is asking your browser to send a request to"] = <?php echo json_encode(tr("This dashboard is asking your browser to send a request to")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["URL"] = <?php echo json_encode(tr("URL")); ?>;
LANG_JS["URL example: node/param"] = <?php echo json_encode(tr("URL example: node/param")); ?>;
LANG_JS["yes/no"] = <?php echo json_encode(tr("yes/no")); ?>;

// cylinder_render.js
LANG_JS["Back"] = <?php echo json_encode(tr("Back")); ?>;
LANG_JS["Bottom feed value"] = <?php echo json_encode(tr("Bottom feed value")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Feed Bottom"] = <?php echo json_encode(tr("Feed Bottom")); ?>;
LANG_JS["Feed Top"] = <?php echo json_encode(tr("Feed Top")); ?>;
LANG_JS["Front"] = <?php echo json_encode(tr("Front")); ?>;
LANG_JS["No display"] = <?php echo json_encode(tr("No display")); ?>;
LANG_JS["Temp unit"] = <?php echo json_encode(tr("Temp unit")); ?>;
LANG_JS["Top feed value"] = <?php echo json_encode(tr("Top feed value")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units of the choosen temp feed"] = <?php echo json_encode(tr("Units of the choosen temp feed")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;

// dewpoint_render.js
LANG_JS["Alignment"] = <?php echo json_encode(tr("Alignment")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Colour used for display"] = <?php echo json_encode(tr("Colour used for display")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font used for display"] = <?php echo json_encode(tr("Font used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Humidity"] = <?php echo json_encode(tr("Humidity")); ?>;
LANG_JS["Relative humidity in %"] = <?php echo json_encode(tr("Relative humidity in %")); ?>;
LANG_JS["Size"] = <?php echo json_encode(tr("Size")); ?>;
LANG_JS["Temperature"] = <?php echo json_encode(tr("Temperature")); ?>;
LANG_JS["Temperature feed"] = <?php echo json_encode(tr("Temperature feed")); ?>;
LANG_JS["Temp unit"] = <?php echo json_encode(tr("Temp unit")); ?>;
LANG_JS["Text size in px to use"] = <?php echo json_encode(tr("Text size in px to use")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units of the choosen temp feed"] = <?php echo json_encode(tr("Units of the choosen temp feed")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;

// dial_render.js
LANG_JS["Black <-> White, Zero at left"] = <?php echo json_encode(tr("Black <-> White, Zero at left")); ?>;
LANG_JS["Blue <-> Red, Zero at upper-left"] = <?php echo json_encode(tr("Blue <-> Red, Zero at upper-left")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Display Min. and Max. ?"] = <?php echo json_encode(tr("Display Min. and Max. ?")); ?>;
LANG_JS["Error Message"] = <?php echo json_encode(tr("Error Message")); ?>;
LANG_JS["Error message displayed when timeout is reached"] = <?php echo json_encode(tr("Error message displayed when timeout is reached")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Graduations"] = <?php echo json_encode(tr("Graduations")); ?>;
LANG_JS["Green <-> Red, Zero at center"] = <?php echo json_encode(tr("Green <-> Red, Zero at center")); ?>;
LANG_JS["Green <-> Red, Zero at left"] = <?php echo json_encode(tr("Green <-> Red, Zero at left")); ?>;
LANG_JS["Green center <-> orange edges, Zero at center"] = <?php echo json_encode(tr("Green center <-> orange edges, Zero at center")); ?>;
LANG_JS["Light <-> dark blue, Zero at left"] = <?php echo json_encode(tr("Light <-> dark blue, Zero at left")); ?>;
LANG_JS["Light <-> dark cyan, Zero at left"] = <?php echo json_encode(tr("Light <-> dark cyan, Zero at left")); ?>;
LANG_JS["Light <-> dark green, Zero at left"] = <?php echo json_encode(tr("Light <-> dark green, Zero at left")); ?>;
LANG_JS["Light <-> dark grey, alternating, Zero at left"] = <?php echo json_encode(tr("Light <-> dark grey, alternating, Zero at left")); ?>;
LANG_JS["Light <-> dark lime, Zero at left"] = <?php echo json_encode(tr("Light <-> dark lime, Zero at left")); ?>;
LANG_JS["Light <-> dark mint, Zero at left"] = <?php echo json_encode(tr("Light <-> dark mint, Zero at left")); ?>;
LANG_JS["Light <-> dark orange, Zero at left"] = <?php echo json_encode(tr("Light <-> dark orange, Zero at left")); ?>;
LANG_JS["Light <-> dark pink, Zero at left"] = <?php echo json_encode(tr("Light <-> dark pink, Zero at left")); ?>;
LANG_JS["Light <-> dark purple, Zero at left"] = <?php echo json_encode(tr("Light <-> dark purple, Zero at left")); ?>;
LANG_JS["Light <-> dark red, Zero at left"] = <?php echo json_encode(tr("Light <-> dark red, Zero at left")); ?>;
LANG_JS["Light <-> dark royal blue, Zero at left"] = <?php echo json_encode(tr("Light <-> dark royal blue, Zero at left")); ?>;
LANG_JS["Light <-> dark yellow, Zero at left"] = <?php echo json_encode(tr("Light <-> dark yellow, Zero at left")); ?>;
LANG_JS["Light blue <-> Red, Zero at mid-left"] = <?php echo json_encode(tr("Light blue <-> Red, Zero at mid-left")); ?>;
LANG_JS["Max. feed"] = <?php echo json_encode(tr("Max. feed")); ?>;
LANG_JS["Max value"] = <?php echo json_encode(tr("Max value")); ?>;
LANG_JS["Max value to show"] = <?php echo json_encode(tr("Max value to show")); ?>;
LANG_JS["Min. feed"] = <?php echo json_encode(tr("Min. feed")); ?>;
LANG_JS["Min / Max ?"] = <?php echo json_encode(tr("Min / Max ?")); ?>;
LANG_JS["No"] = <?php echo json_encode(tr("No")); ?>;
LANG_JS["Offset"] = <?php echo json_encode(tr("Offset")); ?>;
LANG_JS["Rainbow!, Zero at left"] = <?php echo json_encode(tr("Rainbow!, Zero at left")); ?>;
LANG_JS["Red <-> Dark Red, Zero at left"] = <?php echo json_encode(tr("Red <-> Dark Red, Zero at left")); ?>;
LANG_JS["Red <-> Green, Zero at center"] = <?php echo json_encode(tr("Red <-> Green, Zero at center")); ?>;
LANG_JS["Red <-> Green, Zero at left"] = <?php echo json_encode(tr("Red <-> Green, Zero at left")); ?>;
LANG_JS["Reverse Rainbow!, Zero at left"] = <?php echo json_encode(tr("Reverse Rainbow!, Zero at left")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["Should the graduation limits be shown"] = <?php echo json_encode(tr("Should the graduation limits be shown")); ?>;
LANG_JS["Static offset. Subtracted from value before computing needle position"] = <?php echo json_encode(tr("Static offset. Subtracted from value before computing needle position")); ?>;
LANG_JS["The feed for the maximum value"] = <?php echo json_encode(tr("The feed for the maximum value")); ?>;
LANG_JS["The feed for the minimum value"] = <?php echo json_encode(tr("The feed for the minimum value")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["Timeout without feed update in seconds (empty is never)"] = <?php echo json_encode(tr("Timeout without feed update in seconds (empty is never)")); ?>;
LANG_JS["Type"] = <?php echo json_encode(tr("Type")); ?>;
LANG_JS["Type to show"] = <?php echo json_encode(tr("Type to show")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units to show"] = <?php echo json_encode(tr("Units to show")); ?>;
LANG_JS["Value is multiplied by scale before display"] = <?php echo json_encode(tr("Value is multiplied by scale before display")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;
LANG_JS["Yes"] = <?php echo json_encode(tr("Yes")); ?>;

// feedtime_render.js
LANG_JS["Alignment"] = <?php echo json_encode(tr("Alignment")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Colour used for display"] = <?php echo json_encode(tr("Colour used for display")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font used for display"] = <?php echo json_encode(tr("Font used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Size"] = <?php echo json_encode(tr("Size")); ?>;
LANG_JS["Text size in px to use"] = <?php echo json_encode(tr("Text size in px to use")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units to show"] = <?php echo json_encode(tr("Units to show")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;

// feedtimestamp_render.js
LANG_JS["Alignment"] = <?php echo json_encode(tr("Alignment")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Colour used for display"] = <?php echo json_encode(tr("Colour used for display")); ?>;
LANG_JS["Date format"] = <?php echo json_encode(tr("Date format")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font used for display"] = <?php echo json_encode(tr("Font used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Not displayed"] = <?php echo json_encode(tr("Not displayed")); ?>;
LANG_JS["Size"] = <?php echo json_encode(tr("Size")); ?>;
LANG_JS["Text size in px to use"] = <?php echo json_encode(tr("Text size in px to use")); ?>;
LANG_JS["Time format"] = <?php echo json_encode(tr("Time format")); ?>;

// feedvalue_render.js
LANG_JS["Alignment"] = <?php echo json_encode(tr("Alignment")); ?>;
LANG_JS["Append Text"] = <?php echo json_encode(tr("Append Text")); ?>;
LANG_JS["Append Text (Units)"] = <?php echo json_encode(tr("Append Text (Units)")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Colour1"] = <?php echo json_encode(tr("Colour1")); ?>;
LANG_JS["Colour2"] = <?php echo json_encode(tr("Colour2")); ?>;
LANG_JS["Colour3"] = <?php echo json_encode(tr("Colour3")); ?>;
LANG_JS["Colour for range above Threshold2"] = <?php echo json_encode(tr("Colour for range above Threshold2")); ?>;
LANG_JS["Colour for range below Threshold1"] = <?php echo json_encode(tr("Colour for range below Threshold1")); ?>;
LANG_JS["Colour for range between Threshold1 and Threshold2"] = <?php echo json_encode(tr("Colour for range between Threshold1 and Threshold2")); ?>;
LANG_JS["Colour used for display"] = <?php echo json_encode(tr("Colour used for display")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Error Message"] = <?php echo json_encode(tr("Error Message")); ?>;
LANG_JS["Error message displayed when timeout is reached"] = <?php echo json_encode(tr("Error message displayed when timeout is reached")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font used for display"] = <?php echo json_encode(tr("Font used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Prepend Text"] = <?php echo json_encode(tr("Prepend Text")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["scale"] = <?php echo json_encode(tr("scale")); ?>;
LANG_JS["Size"] = <?php echo json_encode(tr("Size")); ?>;
LANG_JS["Text size in px to use"] = <?php echo json_encode(tr("Text size in px to use")); ?>;
LANG_JS["Threshold1"] = <?php echo json_encode(tr("Threshold1")); ?>;
LANG_JS["Threshold1 value"] = <?php echo json_encode(tr("Threshold1 value")); ?>;
LANG_JS["Threshold2"] = <?php echo json_encode(tr("Threshold2")); ?>;
LANG_JS["Threshold2 value"] = <?php echo json_encode(tr("Threshold2 value")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["Timeout without feed update in seconds (empty is never)"] = <?php echo json_encode(tr("Timeout without feed update in seconds (empty is never)")); ?>;

// frostpoint_render.js
LANG_JS["Alignment"] = <?php echo json_encode(tr("Alignment")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Colour used for display"] = <?php echo json_encode(tr("Colour used for display")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font used for display"] = <?php echo json_encode(tr("Font used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Humidity"] = <?php echo json_encode(tr("Humidity")); ?>;
LANG_JS["Relative humidity in %"] = <?php echo json_encode(tr("Relative humidity in %")); ?>;
LANG_JS["Size"] = <?php echo json_encode(tr("Size")); ?>;
LANG_JS["Temperature"] = <?php echo json_encode(tr("Temperature")); ?>;
LANG_JS["Temperature feed"] = <?php echo json_encode(tr("Temperature feed")); ?>;
LANG_JS["Temp unit"] = <?php echo json_encode(tr("Temp unit")); ?>;
LANG_JS["Text size in px to use"] = <?php echo json_encode(tr("Text size in px to use")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units of the choosen temp feed"] = <?php echo json_encode(tr("Units of the choosen temp feed")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;

// heatindex_render.js
LANG_JS["Alignment"] = <?php echo json_encode(tr("Alignment")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Colour used for display"] = <?php echo json_encode(tr("Colour used for display")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Display Nothing"] = <?php echo json_encode(tr("Display Nothing")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font used for display"] = <?php echo json_encode(tr("Font used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Formula applied for T between 50°F and aproximately 80°F"] = <?php echo json_encode(tr("Formula applied for T between 50°F and aproximately 80°F")); ?>;
LANG_JS["Formula applied for T lower than 50°F"] = <?php echo json_encode(tr("Formula applied for T lower than 50°F")); ?>;
LANG_JS["Heat Index"] = <?php echo json_encode(tr("Heat Index")); ?>;
LANG_JS["Humidity"] = <?php echo json_encode(tr("Humidity")); ?>;
LANG_JS["Relative humidity in %"] = <?php echo json_encode(tr("Relative humidity in %")); ?>;
LANG_JS["Rule 1"] = <?php echo json_encode(tr("Rule 1")); ?>;
LANG_JS["Rule 2"] = <?php echo json_encode(tr("Rule 2")); ?>;
LANG_JS["Size"] = <?php echo json_encode(tr("Size")); ?>;
LANG_JS["Temperature"] = <?php echo json_encode(tr("Temperature")); ?>;
LANG_JS["Temperature Feed"] = <?php echo json_encode(tr("Temperature Feed")); ?>;
LANG_JS["Temperature feed"] = <?php echo json_encode(tr("Temperature feed")); ?>;
LANG_JS["Temp unit"] = <?php echo json_encode(tr("Temp unit")); ?>;
LANG_JS["Text size in px to use"] = <?php echo json_encode(tr("Text size in px to use")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units of the choosen temp feed"] = <?php echo json_encode(tr("Units of the choosen temp feed")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;

// humidex_render.js
LANG_JS["Alignment"] = <?php echo json_encode(tr("Alignment")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Colour used for display"] = <?php echo json_encode(tr("Colour used for display")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font used for display"] = <?php echo json_encode(tr("Font used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Humidity"] = <?php echo json_encode(tr("Humidity")); ?>;
LANG_JS["Relative humidity in %"] = <?php echo json_encode(tr("Relative humidity in %")); ?>;
LANG_JS["Size"] = <?php echo json_encode(tr("Size")); ?>;
LANG_JS["Temperature"] = <?php echo json_encode(tr("Temperature")); ?>;
LANG_JS["Temperature feed"] = <?php echo json_encode(tr("Temperature feed")); ?>;
LANG_JS["Temp unit"] = <?php echo json_encode(tr("Temp unit")); ?>;
LANG_JS["Text size in px to use"] = <?php echo json_encode(tr("Text size in px to use")); ?>;
LANG_JS["Units of the choosen temp feed"] = <?php echo json_encode(tr("Units of the choosen temp feed")); ?>;

// image_render.js
LANG_JS["Address of the image to show"] = <?php echo json_encode(tr("Address of the image to show")); ?>;
LANG_JS["Alt text"] = <?php echo json_encode(tr("Alt text")); ?>;
LANG_JS["Describes the image to a reader who cannot see it"] = <?php echo json_encode(tr("Describes the image to a reader who cannot see it")); ?>;
LANG_JS["Fill the box and crop"] = <?php echo json_encode(tr("Fill the box and crop")); ?>;
LANG_JS["Fit"] = <?php echo json_encode(tr("Fit")); ?>;
LANG_JS["Fit inside the box"] = <?php echo json_encode(tr("Fit inside the box")); ?>;
LANG_JS["How the image fills the box"] = <?php echo json_encode(tr("How the image fills the box")); ?>;
LANG_JS["Image url"] = <?php echo json_encode(tr("Image url")); ?>;
LANG_JS["Link"] = <?php echo json_encode(tr("Link")); ?>;
LANG_JS["No image url"] = <?php echo json_encode(tr("No image url")); ?>;
LANG_JS["Optional address to open when the image is clicked"] = <?php echo json_encode(tr("Optional address to open when the image is clicked")); ?>;
LANG_JS["Stretch to the box"] = <?php echo json_encode(tr("Stretch to the box")); ?>;
LANG_JS["This http image is blocked on an https page"] = <?php echo json_encode(tr("This http image is blocked on an https page")); ?>;

// isactivefeed_render.js
LANG_JS["Circle"] = <?php echo json_encode(tr("Circle")); ?>;
LANG_JS["Colour1"] = <?php echo json_encode(tr("Colour1")); ?>;
LANG_JS["Colour2"] = <?php echo json_encode(tr("Colour2")); ?>;
LANG_JS["Colour3"] = <?php echo json_encode(tr("Colour3")); ?>;
LANG_JS["Colour for range above Threshold2"] = <?php echo json_encode(tr("Colour for range above Threshold2")); ?>;
LANG_JS["Colour for range below Threshold1"] = <?php echo json_encode(tr("Colour for range below Threshold1")); ?>;
LANG_JS["Colour for range between Threshold1 and Threshold2"] = <?php echo json_encode(tr("Colour for range between Threshold1 and Threshold2")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Shape"] = <?php echo json_encode(tr("Shape")); ?>;
LANG_JS["Square"] = <?php echo json_encode(tr("Square")); ?>;
LANG_JS["Star 5 spikes"] = <?php echo json_encode(tr("Star 5 spikes")); ?>;
LANG_JS["Star 6 spikes"] = <?php echo json_encode(tr("Star 6 spikes")); ?>;
LANG_JS["Threshold1"] = <?php echo json_encode(tr("Threshold1")); ?>;
LANG_JS["Threshold1 in seconds"] = <?php echo json_encode(tr("Threshold1 in seconds")); ?>;
LANG_JS["Threshold2"] = <?php echo json_encode(tr("Threshold2")); ?>;
LANG_JS["Threshold2 in seconds"] = <?php echo json_encode(tr("Threshold2 in seconds")); ?>;
LANG_JS["Triangle &#9650;"] = <?php echo json_encode(tr("Triangle &#9650;")); ?>;
LANG_JS["Triangle &#9654;"] = <?php echo json_encode(tr("Triangle &#9654;")); ?>;
LANG_JS["Triangle &#9660;"] = <?php echo json_encode(tr("Triangle &#9660;")); ?>;
LANG_JS["Triangle &#x25C0;"] = <?php echo json_encode(tr("Triangle &#x25C0;")); ?>;

// jgauge_render.js
LANG_JS["Error Message"] = <?php echo json_encode(tr("Error Message")); ?>;
LANG_JS["Error message displayed when timeout is reached"] = <?php echo json_encode(tr("Error message displayed when timeout is reached")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Max value"] = <?php echo json_encode(tr("Max value")); ?>;
LANG_JS["Max value to show"] = <?php echo json_encode(tr("Max value to show")); ?>;
LANG_JS["Min value"] = <?php echo json_encode(tr("Min value")); ?>;
LANG_JS["Min value to show"] = <?php echo json_encode(tr("Min value to show")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["Scale applied to value"] = <?php echo json_encode(tr("Scale applied to value")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["Timeout without feed update in seconds (empty is never)"] = <?php echo json_encode(tr("Timeout without feed update in seconds (empty is never)")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units to show"] = <?php echo json_encode(tr("Units to show")); ?>;

// jgauge2_render.js
LANG_JS["Error Message"] = <?php echo json_encode(tr("Error Message")); ?>;
LANG_JS["Error message displayed when timeout is reached"] = <?php echo json_encode(tr("Error message displayed when timeout is reached")); ?>;
LANG_JS["Feed 1"] = <?php echo json_encode(tr("Feed 1")); ?>;
LANG_JS["Feed 2"] = <?php echo json_encode(tr("Feed 2")); ?>;
LANG_JS["Feed 2 (Min/Max for example)"] = <?php echo json_encode(tr("Feed 2 (Min/Max for example)")); ?>;
LANG_JS["Max value"] = <?php echo json_encode(tr("Max value")); ?>;
LANG_JS["Max value to show"] = <?php echo json_encode(tr("Max value to show")); ?>;
LANG_JS["Min value"] = <?php echo json_encode(tr("Min value")); ?>;
LANG_JS["Min value to show"] = <?php echo json_encode(tr("Min value to show")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["Scale applied to value"] = <?php echo json_encode(tr("Scale applied to value")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["Timeout without feed update in seconds (empty is never)"] = <?php echo json_encode(tr("Timeout without feed update in seconds (empty is never)")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units to show"] = <?php echo json_encode(tr("Units to show")); ?>;

// kwhperiod_render.js
LANG_JS["Annual data"] = <?php echo json_encode(tr("Annual data")); ?>;
LANG_JS["Convert the periodic energy use to kWh per Day"] = <?php echo json_encode(tr("Convert the periodic energy use to kWh per Day")); ?>;
LANG_JS["Period kWh/day"] = <?php echo json_encode(tr("Period kWh/day")); ?>;
LANG_JS["Set to True to use last year's data"] = <?php echo json_encode(tr("Set to True to use last year's data")); ?>;

// led_render.js
LANG_JS["Display style"] = <?php echo json_encode(tr("Display style")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Style"] = <?php echo json_encode(tr("Style")); ?>;
LANG_JS["With colour gradients"] = <?php echo json_encode(tr("With colour gradients")); ?>;
LANG_JS["Without colour gradients"] = <?php echo json_encode(tr("Without colour gradients")); ?>;

// orderbars_render.js
LANG_JS["Axis, label and legend colour in hex. Blank is use default."] = <?php echo json_encode(tr("Axis, label and legend colour in hex. Blank is use default.")); ?>;
LANG_JS["Axis colour"] = <?php echo json_encode(tr("Axis colour")); ?>;
LANG_JS["Background"] = <?php echo json_encode(tr("Background")); ?>;
LANG_JS["Background colour in hex. Blank is white."] = <?php echo json_encode(tr("Background colour in hex. Blank is white.")); ?>;
LANG_JS["delta"] = <?php echo json_encode(tr("delta")); ?>;
LANG_JS["Draws daily readings as bars sorted by size, largest first. Position of a bar is its rank, there is no time axis."] = <?php echo json_encode(tr("Draws daily readings as bars sorted by size, largest first. Position of a bar is its rank, there is no time axis.")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed source"] = <?php echo json_encode(tr("Feed source")); ?>;
LANG_JS["No feed selected"] = <?php echo json_encode(tr("No feed selected")); ?>;
LANG_JS["Order bars"] = <?php echo json_encode(tr("Order bars")); ?>;
LANG_JS["Show difference between each bar"] = <?php echo json_encode(tr("Show difference between each bar")); ?>;

// panel_render.js
LANG_JS["Background"] = <?php echo json_encode(tr("Background")); ?>;
LANG_JS["Background colour"] = <?php echo json_encode(tr("Background colour")); ?>;
LANG_JS["Background opacity in per cent, 0 is clear"] = <?php echo json_encode(tr("Background opacity in per cent, 0 is clear")); ?>;
LANG_JS["Border colour"] = <?php echo json_encode(tr("Border colour")); ?>;
LANG_JS["Border width"] = <?php echo json_encode(tr("Border width")); ?>;
LANG_JS["Border width in px, 0 for no border"] = <?php echo json_encode(tr("Border width in px, 0 for no border")); ?>;
LANG_JS["Corner radius"] = <?php echo json_encode(tr("Corner radius")); ?>;
LANG_JS["Corner radius in px"] = <?php echo json_encode(tr("Corner radius in px")); ?>;
LANG_JS["Drop shadow"] = <?php echo json_encode(tr("Drop shadow")); ?>;
LANG_JS["Glow"] = <?php echo json_encode(tr("Glow")); ?>;
LANG_JS["Glow in the border colour"] = <?php echo json_encode(tr("Glow in the border colour")); ?>;
LANG_JS["Large drop shadow"] = <?php echo json_encode(tr("Large drop shadow")); ?>;
LANG_JS["None"] = <?php echo json_encode(tr("None")); ?>;
LANG_JS["Opacity"] = <?php echo json_encode(tr("Opacity")); ?>;
LANG_JS["Shadow"] = <?php echo json_encode(tr("Shadow")); ?>;
LANG_JS["Shadow around the box"] = <?php echo json_encode(tr("Shadow around the box")); ?>;
LANG_JS["Small drop shadow"] = <?php echo json_encode(tr("Small drop shadow")); ?>;
LANG_JS["Soft glow"] = <?php echo json_encode(tr("Soft glow")); ?>;
LANG_JS["Strong glow"] = <?php echo json_encode(tr("Strong glow")); ?>;

// realtime_render.js
LANG_JS["Axis, label and legend colour in hex. Blank is use default."] = <?php echo json_encode(tr("Axis, label and legend colour in hex. Blank is use default.")); ?>;
LANG_JS["Axis colour"] = <?php echo json_encode(tr("Axis colour")); ?>;
LANG_JS["Background"] = <?php echo json_encode(tr("Background")); ?>;
LANG_JS["Background colour in hex. Blank is white."] = <?php echo json_encode(tr("Background colour in hex. Blank is white.")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Default visible window interval"] = <?php echo json_encode(tr("Default visible window interval")); ?>;
LANG_JS["Display power as kW"] = <?php echo json_encode(tr("Display power as kW")); ?>;
LANG_JS["Draws one feed as a line over the last few minutes and appends each new reading as it arrives. Zoom sets the window."] = <?php echo json_encode(tr("Draws one feed as a line over the last few minutes and appends each new reading as it arrives. Zoom sets the window.")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed source"] = <?php echo json_encode(tr("Feed source")); ?>;
LANG_JS["hour"] = <?php echo json_encode(tr("hour")); ?>;
LANG_JS["Line colour in hex. Blank is use default."] = <?php echo json_encode(tr("Line colour in hex. Blank is use default.")); ?>;
LANG_JS["minute"] = <?php echo json_encode(tr("minute")); ?>;
LANG_JS["minutes"] = <?php echo json_encode(tr("minutes")); ?>;
LANG_JS["No feed selected"] = <?php echo json_encode(tr("No feed selected")); ?>;
LANG_JS["Realtime"] = <?php echo json_encode(tr("Realtime")); ?>;
LANG_JS["Zoom"] = <?php echo json_encode(tr("Zoom")); ?>;

// signal_render.js
LANG_JS["Bold"] = <?php echo json_encode(tr("Bold")); ?>;
LANG_JS["Color of the label"] = <?php echo json_encode(tr("Color of the label")); ?>;
LANG_JS["Color of the signal"] = <?php echo json_encode(tr("Color of the signal")); ?>;
LANG_JS["Colour label"] = <?php echo json_encode(tr("Colour label")); ?>;
LANG_JS["Colour signal"] = <?php echo json_encode(tr("Colour signal")); ?>;
LANG_JS["Error Message"] = <?php echo json_encode(tr("Error Message")); ?>;
LANG_JS["Error message displayed when timeout is reached"] = <?php echo json_encode(tr("Error message displayed when timeout is reached")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Label font"] = <?php echo json_encode(tr("Label font")); ?>;
LANG_JS["Max value"] = <?php echo json_encode(tr("Max value")); ?>;
LANG_JS["Max value to show"] = <?php echo json_encode(tr("Max value to show")); ?>;
LANG_JS["Normal"] = <?php echo json_encode(tr("Normal")); ?>;
LANG_JS["Offset"] = <?php echo json_encode(tr("Offset")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["Signal title"] = <?php echo json_encode(tr("Signal title")); ?>;
LANG_JS["Static offset. Subtracted from value before computing"] = <?php echo json_encode(tr("Static offset. Subtracted from value before computing")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["Timeout without feed update in seconds (empty is never)"] = <?php echo json_encode(tr("Timeout without feed update in seconds (empty is never)")); ?>;
LANG_JS["Value is multiplied by scale before display"] = <?php echo json_encode(tr("Value is multiplied by scale before display")); ?>;

// stackedsolar_render.js
LANG_JS["Axis, label and legend colour in hex. Blank is use default."] = <?php echo json_encode(tr("Axis, label and legend colour in hex. Blank is use default.")); ?>;
LANG_JS["Axis colour"] = <?php echo json_encode(tr("Axis colour")); ?>;
LANG_JS["Background"] = <?php echo json_encode(tr("Background")); ?>;
LANG_JS["Background colour in hex. Blank is white."] = <?php echo json_encode(tr("Background colour in hex. Blank is white.")); ?>;
LANG_JS["Consumption"] = <?php echo json_encode(tr("Consumption")); ?>;
LANG_JS["Consumption feed value"] = <?php echo json_encode(tr("Consumption feed value")); ?>;
LANG_JS["delta"] = <?php echo json_encode(tr("delta")); ?>;
LANG_JS["Draws solar, import and export stacked by month, worked out from a solar feed and a consumption feed. A click on a bar shows the days of that month."] = <?php echo json_encode(tr("Draws solar, import and export stacked by month, worked out from a solar feed and a consumption feed. A click on a bar shows the days of that month.")); ?>;
LANG_JS["Export"] = <?php echo json_encode(tr("Export")); ?>;
LANG_JS["Import"] = <?php echo json_encode(tr("Import")); ?>;
LANG_JS["kWh/d"] = <?php echo json_encode(tr("kWh/d")); ?>;
LANG_JS["kWh/month"] = <?php echo json_encode(tr("kWh/month")); ?>;
LANG_JS["No feed selected"] = <?php echo json_encode(tr("No feed selected")); ?>;
LANG_JS["Show difference between each bar"] = <?php echo json_encode(tr("Show difference between each bar")); ?>;
LANG_JS["Solar"] = <?php echo json_encode(tr("Solar")); ?>;
LANG_JS["Solar feed value"] = <?php echo json_encode(tr("Solar feed value")); ?>;
LANG_JS["Stacked solar"] = <?php echo json_encode(tr("Stacked solar")); ?>;

// sun_render.js
LANG_JS["Bold"] = <?php echo json_encode(tr("Bold")); ?>;
LANG_JS["Color of the label"] = <?php echo json_encode(tr("Color of the label")); ?>;
LANG_JS["Colour label"] = <?php echo json_encode(tr("Colour label")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Error Message"] = <?php echo json_encode(tr("Error Message")); ?>;
LANG_JS["Error message displayed when timeout is reached"] = <?php echo json_encode(tr("Error message displayed when timeout is reached")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Label font"] = <?php echo json_encode(tr("Label font")); ?>;
LANG_JS["Max value"] = <?php echo json_encode(tr("Max value")); ?>;
LANG_JS["Max value to show"] = <?php echo json_encode(tr("Max value to show")); ?>;
LANG_JS["Normal"] = <?php echo json_encode(tr("Normal")); ?>;
LANG_JS["Offset"] = <?php echo json_encode(tr("Offset")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["solar title"] = <?php echo json_encode(tr("solar title")); ?>;
LANG_JS["Solar title"] = <?php echo json_encode(tr("Solar title")); ?>;
LANG_JS["Static offset. Subtracted from value before computing"] = <?php echo json_encode(tr("Static offset. Subtracted from value before computing")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["Timeout without feed update in seconds (empty is never)"] = <?php echo json_encode(tr("Timeout without feed update in seconds (empty is never)")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units to show"] = <?php echo json_encode(tr("Units to show")); ?>;
LANG_JS["Value is multiplied by scale before display"] = <?php echo json_encode(tr("Value is multiplied by scale before display")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;

// text_render.js
LANG_JS["Align"] = <?php echo json_encode(tr("Align")); ?>;
LANG_JS["Bold"] = <?php echo json_encode(tr("Bold")); ?>;
LANG_JS["Bottom"] = <?php echo json_encode(tr("Bottom")); ?>;
LANG_JS["Center"] = <?php echo json_encode(tr("Center")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Default"] = <?php echo json_encode(tr("Default")); ?>;
LANG_JS["Degrees, -180 to 180"] = <?php echo json_encode(tr("Degrees, -180 to 180")); ?>;
LANG_JS["Font"] = <?php echo json_encode(tr("Font")); ?>;
LANG_JS["Font family"] = <?php echo json_encode(tr("Font family")); ?>;
LANG_JS["Horizontal alignment"] = <?php echo json_encode(tr("Horizontal alignment")); ?>;
LANG_JS["Left"] = <?php echo json_encode(tr("Left")); ?>;
LANG_JS["Middle"] = <?php echo json_encode(tr("Middle")); ?>;
LANG_JS["Normal"] = <?php echo json_encode(tr("Normal")); ?>;
LANG_JS["Normal or bold"] = <?php echo json_encode(tr("Normal or bold")); ?>;
LANG_JS["Right"] = <?php echo json_encode(tr("Right")); ?>;
LANG_JS["Rotate"] = <?php echo json_encode(tr("Rotate")); ?>;
LANG_JS["Size"] = <?php echo json_encode(tr("Size")); ?>;
LANG_JS["Some text"] = <?php echo json_encode(tr("Some text")); ?>;
LANG_JS["Text"] = <?php echo json_encode(tr("Text")); ?>;
LANG_JS["Text colour"] = <?php echo json_encode(tr("Text colour")); ?>;
LANG_JS["Text size in px"] = <?php echo json_encode(tr("Text size in px")); ?>;
LANG_JS["The text to show. b, i, u, sub, a and br may be used in it."] = <?php echo json_encode(tr("The text to show. b, i, u, sub, a and br may be used in it.")); ?>;
LANG_JS["Top"] = <?php echo json_encode(tr("Top")); ?>;
LANG_JS["Vertical"] = <?php echo json_encode(tr("Vertical")); ?>;
LANG_JS["Vertical alignment"] = <?php echo json_encode(tr("Vertical alignment")); ?>;
LANG_JS["Weight"] = <?php echo json_encode(tr("Weight")); ?>;

// thermometer_render.js
LANG_JS["Bold"] = <?php echo json_encode(tr("Bold")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Colour for min. and max. bars"] = <?php echo json_encode(tr("Colour for min. and max. bars")); ?>;
LANG_JS["Colour of title and values"] = <?php echo json_encode(tr("Colour of title and values")); ?>;
LANG_JS["Decimals"] = <?php echo json_encode(tr("Decimals")); ?>;
LANG_JS["Decimals to show"] = <?php echo json_encode(tr("Decimals to show")); ?>;
LANG_JS["Display Min. and Max. ?"] = <?php echo json_encode(tr("Display Min. and Max. ?")); ?>;
LANG_JS["Error Message"] = <?php echo json_encode(tr("Error Message")); ?>;
LANG_JS["Error message displayed when timeout is reached"] = <?php echo json_encode(tr("Error message displayed when timeout is reached")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Font style"] = <?php echo json_encode(tr("Font style")); ?>;
LANG_JS["Font style used for display"] = <?php echo json_encode(tr("Font style used for display")); ?>;
LANG_JS["Font used"] = <?php echo json_encode(tr("Font used")); ?>;
LANG_JS["Font weight"] = <?php echo json_encode(tr("Font weight")); ?>;
LANG_JS["Font weight used for display"] = <?php echo json_encode(tr("Font weight used for display")); ?>;
LANG_JS["Grad. Num."] = <?php echo json_encode(tr("Grad. Num.")); ?>;
LANG_JS["Graduations"] = <?php echo json_encode(tr("Graduations")); ?>;
LANG_JS["How many graduation lines to draw (only relevant if graduations are on)"] = <?php echo json_encode(tr("How many graduation lines to draw (only relevant if graduations are on)")); ?>;
LANG_JS["Label Colour"] = <?php echo json_encode(tr("Label Colour")); ?>;
LANG_JS["Max. feed"] = <?php echo json_encode(tr("Max. feed")); ?>;
LANG_JS["Max value"] = <?php echo json_encode(tr("Max value")); ?>;
LANG_JS["Max value to show"] = <?php echo json_encode(tr("Max value to show")); ?>;
LANG_JS["Min. feed"] = <?php echo json_encode(tr("Min. feed")); ?>;
LANG_JS["Min / Max ?"] = <?php echo json_encode(tr("Min / Max ?")); ?>;
LANG_JS["Min value"] = <?php echo json_encode(tr("Min value")); ?>;
LANG_JS["Min value to show"] = <?php echo json_encode(tr("Min value to show")); ?>;
LANG_JS["No"] = <?php echo json_encode(tr("No")); ?>;
LANG_JS["Normal"] = <?php echo json_encode(tr("Normal")); ?>;
LANG_JS["Offset"] = <?php echo json_encode(tr("Offset")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["Should the graduations be shown"] = <?php echo json_encode(tr("Should the graduations be shown")); ?>;
LANG_JS["Static offset. Subtracted from value before computing"] = <?php echo json_encode(tr("Static offset. Subtracted from value before computing")); ?>;
LANG_JS["The feed for the maximum value"] = <?php echo json_encode(tr("The feed for the maximum value")); ?>;
LANG_JS["The feed for the minimum value"] = <?php echo json_encode(tr("The feed for the minimum value")); ?>;
LANG_JS["Timeout"] = <?php echo json_encode(tr("Timeout")); ?>;
LANG_JS["Timeout without feed update in seconds (empty is never)"] = <?php echo json_encode(tr("Timeout without feed update in seconds (empty is never)")); ?>;
LANG_JS["Title"] = <?php echo json_encode(tr("Title")); ?>;
LANG_JS["Title of thermometer"] = <?php echo json_encode(tr("Title of thermometer")); ?>;
LANG_JS["Unit position"] = <?php echo json_encode(tr("Unit position")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units to show"] = <?php echo json_encode(tr("Units to show")); ?>;
LANG_JS["Value is multiplied by scale before display. Defaults to 1"] = <?php echo json_encode(tr("Value is multiplied by scale before display. Defaults to 1")); ?>;
LANG_JS["Where should the unit be shown"] = <?php echo json_encode(tr("Where should the unit be shown")); ?>;
LANG_JS["Yes"] = <?php echo json_encode(tr("Yes")); ?>;

// thresholds_render.js
LANG_JS["Circle"] = <?php echo json_encode(tr("Circle")); ?>;
LANG_JS["Colour1"] = <?php echo json_encode(tr("Colour1")); ?>;
LANG_JS["Colour2"] = <?php echo json_encode(tr("Colour2")); ?>;
LANG_JS["Colour3"] = <?php echo json_encode(tr("Colour3")); ?>;
LANG_JS["Colour for range above Threshold2"] = <?php echo json_encode(tr("Colour for range above Threshold2")); ?>;
LANG_JS["Colour for range below Threshold1"] = <?php echo json_encode(tr("Colour for range below Threshold1")); ?>;
LANG_JS["Colour for range between Threshold1 and Threshold2"] = <?php echo json_encode(tr("Colour for range between Threshold1 and Threshold2")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Shape"] = <?php echo json_encode(tr("Shape")); ?>;
LANG_JS["Square"] = <?php echo json_encode(tr("Square")); ?>;
LANG_JS["Star 5 spikes"] = <?php echo json_encode(tr("Star 5 spikes")); ?>;
LANG_JS["Star 6 spikes"] = <?php echo json_encode(tr("Star 6 spikes")); ?>;
LANG_JS["Threshold1"] = <?php echo json_encode(tr("Threshold1")); ?>;
LANG_JS["Threshold1 value"] = <?php echo json_encode(tr("Threshold1 value")); ?>;
LANG_JS["Threshold2"] = <?php echo json_encode(tr("Threshold2")); ?>;
LANG_JS["Threshold2 value"] = <?php echo json_encode(tr("Threshold2 value")); ?>;
LANG_JS["Triangle &#9650;"] = <?php echo json_encode(tr("Triangle &#9650;")); ?>;
LANG_JS["Triangle &#9654;"] = <?php echo json_encode(tr("Triangle &#9654;")); ?>;
LANG_JS["Triangle &#9660;"] = <?php echo json_encode(tr("Triangle &#9660;")); ?>;
LANG_JS["Triangle &#x25C0;"] = <?php echo json_encode(tr("Triangle &#x25C0;")); ?>;

// timecompare_render.js
LANG_JS["Axis, label and legend colour in hex. Blank is use default."] = <?php echo json_encode(tr("Axis, label and legend colour in hex. Blank is use default.")); ?>;
LANG_JS["Axis colour"] = <?php echo json_encode(tr("Axis colour")); ?>;
LANG_JS["Background"] = <?php echo json_encode(tr("Background")); ?>;
LANG_JS["Background colour in hex. Blank is white."] = <?php echo json_encode(tr("Background colour in hex. Blank is white.")); ?>;
LANG_JS["Blank reads as many as the box is wide"] = <?php echo json_encode(tr("Blank reads as many as the box is wide")); ?>;
LANG_JS["Current"] = <?php echo json_encode(tr("Current")); ?>;
LANG_JS["Data points"] = <?php echo json_encode(tr("Data points")); ?>;
LANG_JS["Day"] = <?php echo json_encode(tr("Day")); ?>;
LANG_JS["days"] = <?php echo json_encode(tr("days")); ?>;
LANG_JS["Days"] = <?php echo json_encode(tr("Days")); ?>;
LANG_JS["Default visible window interval"] = <?php echo json_encode(tr("Default visible window interval")); ?>;
LANG_JS["Depth"] = <?php echo json_encode(tr("Depth")); ?>;
LANG_JS["Draws one feed several times over, each line one period further back, so this week sits on top of last week. Zoom sets the window and the distance between the lines. Depth is the number of lines."] = <?php echo json_encode(tr("Draws one feed several times over, each line one period further back, so this week sits on top of last week. Zoom sets the window and the distance between the lines. Depth is the number of lines.")); ?>;
LANG_JS["Earlier"] = <?php echo json_encode(tr("Earlier")); ?>;
LANG_JS["Expand"] = <?php echo json_encode(tr("Expand")); ?>;
LANG_JS["Feed"] = <?php echo json_encode(tr("Feed")); ?>;
LANG_JS["Feed source"] = <?php echo json_encode(tr("Feed source")); ?>;
LANG_JS["Fill"] = <?php echo json_encode(tr("Fill")); ?>;
LANG_JS["Fill under line: 1 for on, or an opacity below 1"] = <?php echo json_encode(tr("Fill under line: 1 for on, or an opacity below 1")); ?>;
LANG_JS["Hours"] = <?php echo json_encode(tr("Hours")); ?>;
LANG_JS["Later"] = <?php echo json_encode(tr("Later")); ?>;
LANG_JS["Month"] = <?php echo json_encode(tr("Month")); ?>;
LANG_JS["Months"] = <?php echo json_encode(tr("Months")); ?>;
LANG_JS["No feed selected"] = <?php echo json_encode(tr("No feed selected")); ?>;
LANG_JS["Number of lines"] = <?php echo json_encode(tr("Number of lines")); ?>;
LANG_JS["Prior"] = <?php echo json_encode(tr("Prior")); ?>;
LANG_JS["Seconds"] = <?php echo json_encode(tr("Seconds")); ?>;
LANG_JS["Time compare"] = <?php echo json_encode(tr("Time compare")); ?>;
LANG_JS["Week"] = <?php echo json_encode(tr("Week")); ?>;
LANG_JS["Weeks"] = <?php echo json_encode(tr("Weeks")); ?>;
LANG_JS["Year"] = <?php echo json_encode(tr("Year")); ?>;
LANG_JS["Years"] = <?php echo json_encode(tr("Years")); ?>;
LANG_JS["Zoom"] = <?php echo json_encode(tr("Zoom")); ?>;
LANG_JS["Zoom In"] = <?php echo json_encode(tr("Zoom In")); ?>;
LANG_JS["Zoom Out"] = <?php echo json_encode(tr("Zoom Out")); ?>;

// windrose_render.js
LANG_JS["Feed value"] = <?php echo json_encode(tr("Feed value")); ?>;
LANG_JS["Feed Wind"] = <?php echo json_encode(tr("Feed Wind")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["Scale applied to value"] = <?php echo json_encode(tr("Scale applied to value")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units to show for value"] = <?php echo json_encode(tr("Units to show for value")); ?>;
LANG_JS["Value shown (wind speed)"] = <?php echo json_encode(tr("Value shown (wind speed)")); ?>;
LANG_JS["Wind direction"] = <?php echo json_encode(tr("Wind direction")); ?>;

// zoom_render.js
LANG_JS["0 = before value, 1 = after value"] = <?php echo json_encode(tr("0 = before value, 1 = after value")); ?>;
LANG_JS["Annual"] = <?php echo json_encode(tr("Annual")); ?>;
LANG_JS["Average:"] = <?php echo json_encode(tr("Average:")); ?>;
LANG_JS["a week"] = <?php echo json_encode(tr("a week")); ?>;
LANG_JS["Axis, label and legend colour in hex. Blank is use default."] = <?php echo json_encode(tr("Axis, label and legend colour in hex. Blank is use default.")); ?>;
LANG_JS["Axis colour"] = <?php echo json_encode(tr("Axis colour")); ?>;
LANG_JS["a year"] = <?php echo json_encode(tr("a year")); ?>;
LANG_JS["Back"] = <?php echo json_encode(tr("Back")); ?>;
LANG_JS["Background"] = <?php echo json_encode(tr("Background")); ?>;
LANG_JS["Background colour in hex. Blank is clear."] = <?php echo json_encode(tr("Background colour in hex. Blank is clear.")); ?>;
LANG_JS["Back to"] = <?php echo json_encode(tr("Back to")); ?>;
LANG_JS["Bar colour in hex. Blank is use default."] = <?php echo json_encode(tr("Bar colour in hex. Blank is use default.")); ?>;
LANG_JS["Colour"] = <?php echo json_encode(tr("Colour")); ?>;
LANG_JS["Currency"] = <?php echo json_encode(tr("Currency")); ?>;
LANG_JS["Currency position"] = <?php echo json_encode(tr("Currency position")); ?>;
LANG_JS["Currency to show"] = <?php echo json_encode(tr("Currency to show")); ?>;
LANG_JS["Daily"] = <?php echo json_encode(tr("Daily")); ?>;
LANG_JS["Days"] = <?php echo json_encode(tr("Days")); ?>;
LANG_JS["days"] = <?php echo json_encode(tr("days")); ?>;
LANG_JS["delta"] = <?php echo json_encode(tr("delta")); ?>;
LANG_JS["Draws energy per day as bars with a cost worked out from the unit price. Opens on years and steps into the months of a year, the days of a month and the power of one day when a bar is clicked."] = <?php echo json_encode(tr("Draws energy per day as bars with a cost worked out from the unit price. Opens on years and steps into the months of a year, the days of a month and the power of one day when a bar is clicked.")); ?>;
LANG_JS["Earlier"] = <?php echo json_encode(tr("Earlier")); ?>;
LANG_JS["Expand"] = <?php echo json_encode(tr("Expand")); ?>;
LANG_JS["kWh"] = <?php echo json_encode(tr("kWh")); ?>;
LANG_JS["kwhd"] = <?php echo json_encode(tr("kwhd")); ?>;
LANG_JS["kwhd source"] = <?php echo json_encode(tr("kwhd source")); ?>;
LANG_JS["Kwh price"] = <?php echo json_encode(tr("Kwh price")); ?>;
LANG_JS["Later"] = <?php echo json_encode(tr("Later")); ?>;
LANG_JS["Monthly"] = <?php echo json_encode(tr("Monthly")); ?>;
LANG_JS["Months"] = <?php echo json_encode(tr("Months")); ?>;
LANG_JS["Multiply the kwhd feed by this"] = <?php echo json_encode(tr("Multiply the kwhd feed by this")); ?>;
LANG_JS["No feed selected"] = <?php echo json_encode(tr("No feed selected")); ?>;
LANG_JS["Opens on"] = <?php echo json_encode(tr("Opens on")); ?>;
LANG_JS["Power"] = <?php echo json_encode(tr("Power")); ?>;
LANG_JS["Power to show. Leave empty to draw the bars only"] = <?php echo json_encode(tr("Power to show. Leave empty to draw the bars only")); ?>;
LANG_JS["Scale"] = <?php echo json_encode(tr("Scale")); ?>;
LANG_JS["Set kwh price"] = <?php echo json_encode(tr("Set kwh price")); ?>;
LANG_JS["Show difference between each bar"] = <?php echo json_encode(tr("Show difference between each bar")); ?>;
LANG_JS["The view the widget opens on"] = <?php echo json_encode(tr("The view the widget opens on")); ?>;
LANG_JS["Total:"] = <?php echo json_encode(tr("Total:")); ?>;
LANG_JS["Unit price:"] = <?php echo json_encode(tr("Unit price:")); ?>;
LANG_JS["Units"] = <?php echo json_encode(tr("Units")); ?>;
LANG_JS["Units of the daily feed. Blank is kWh."] = <?php echo json_encode(tr("Units of the daily feed. Blank is kWh.")); ?>;
LANG_JS["Years"] = <?php echo json_encode(tr("Years")); ?>;
LANG_JS["Zoom"] = <?php echo json_encode(tr("Zoom")); ?>;
LANG_JS["Zoom In"] = <?php echo json_encode(tr("Zoom In")); ?>;
LANG_JS["Zoom Out"] = <?php echo json_encode(tr("Zoom Out")); ?>;
//END 
