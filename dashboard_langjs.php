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
// designer.js
LANG_JS["Add a"] = '<?php echo addslashes(tr("Add a")); ?>';
LANG_JS["Changed, press to save"] = '<?php echo addslashes(tr("Changed, press to save")); ?>';
LANG_JS["Choose height unit"] = '<?php echo addslashes(tr("Choose height unit")); ?>';
LANG_JS["Choose width unit"] = '<?php echo addslashes(tr("Choose width unit")); ?>';
LANG_JS["element to the dashboard"] = '<?php echo addslashes(tr("element to the dashboard")); ?>';
LANG_JS["Height"] = '<?php echo addslashes(tr("Height")); ?>';
LANG_JS["Off"] = '<?php echo addslashes(tr("Off")); ?>';
LANG_JS["On"] = '<?php echo addslashes(tr("On")); ?>';
LANG_JS["Percentage"] = '<?php echo addslashes(tr("Percentage")); ?>';
LANG_JS["Pixels"] = '<?php echo addslashes(tr("Pixels")); ?>';
LANG_JS["Width"] = '<?php echo addslashes(tr("Width")); ?>';

// widgetlist.js
LANG_JS["Html code to show"] = '<?php echo addslashes(tr("Html code to show")); ?>';
LANG_JS["Some text"] = '<?php echo addslashes(tr("Some text")); ?>';
LANG_JS["Title"] = '<?php echo addslashes(tr("Title")); ?>';

// bar_render.js
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(tr("Colour")); ?>';
LANG_JS["Colour for min. and max. bars"] = '<?php echo addslashes(tr("Colour for min. and max. bars")); ?>';
LANG_JS["Colour of title and values"] = '<?php echo addslashes(tr("Colour of title and values")); ?>';
LANG_JS["Colour to draw bar in"] = '<?php echo addslashes(tr("Colour to draw bar in")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Display Min. and Max. ?"] = '<?php echo addslashes(tr("Display Min. and Max. ?")); ?>';
LANG_JS["Error Message"] = '<?php echo addslashes(tr("Error Message")); ?>';
LANG_JS["Error message displayed when timeout is reached"] = '<?php echo addslashes(tr("Error message displayed when timeout is reached")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font used"] = '<?php echo addslashes(tr("Font used")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["Grad. Num."] = '<?php echo addslashes(tr("Grad. Num.")); ?>';
LANG_JS["Graduations"] = '<?php echo addslashes(tr("Graduations")); ?>';
LANG_JS["How many graduation lines to draw (only relevant if graduations are on)"] = '<?php echo addslashes(tr("How many graduation lines to draw (only relevant if graduations are on)")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Label Colour"] = '<?php echo addslashes(tr("Label Colour")); ?>';
LANG_JS["Max. feed"] = '<?php echo addslashes(tr("Max. feed")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(tr("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(tr("Max value to show")); ?>';
LANG_JS["Min. feed"] = '<?php echo addslashes(tr("Min. feed")); ?>';
LANG_JS["Min / Max ?"] = '<?php echo addslashes(tr("Min / Max ?")); ?>';
LANG_JS["No"] = '<?php echo addslashes(tr("No")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(tr("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["Should the graduations be shown"] = '<?php echo addslashes(tr("Should the graduations be shown")); ?>';
LANG_JS["Static offset. Subtracted from value before computing"] = '<?php echo addslashes(tr("Static offset. Subtracted from value before computing")); ?>';
LANG_JS["The feed for the maximum value"] = '<?php echo addslashes(tr("The feed for the maximum value")); ?>';
LANG_JS["The feed for the minimum value"] = '<?php echo addslashes(tr("The feed for the minimum value")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["Timeout without feed update in seconds (empty is never)"] = '<?php echo addslashes(tr("Timeout without feed update in seconds (empty is never)")); ?>';
LANG_JS["Title"] = '<?php echo addslashes(tr("Title")); ?>';
LANG_JS["Title of bar"] = '<?php echo addslashes(tr("Title of bar")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(tr("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(tr("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display. Defaults to 1"] = '<?php echo addslashes(tr("Value is multiplied by scale before display. Defaults to 1")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';
LANG_JS["Yes"] = '<?php echo addslashes(tr("Yes")); ?>';

// battery_render.js
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Battery title"] = '<?php echo addslashes(tr("Battery title")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Color of the label"] = '<?php echo addslashes(tr("Color of the label")); ?>';
LANG_JS["Colour label"] = '<?php echo addslashes(tr("Colour label")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Display style"] = '<?php echo addslashes(tr("Display style")); ?>';
LANG_JS["Error Message"] = '<?php echo addslashes(tr("Error Message")); ?>';
LANG_JS["Error message displayed when timeout is reached"] = '<?php echo addslashes(tr("Error message displayed when timeout is reached")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(tr("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Label font"] = '<?php echo addslashes(tr("Label font")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(tr("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(tr("Max value to show")); ?>';
LANG_JS["Min value"] = '<?php echo addslashes(tr("Min value")); ?>';
LANG_JS["Min value to show"] = '<?php echo addslashes(tr("Min value to show")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Number of blocks"] = '<?php echo addslashes(tr("Number of blocks")); ?>';
LANG_JS["Number of blocks to display"] = '<?php echo addslashes(tr("Number of blocks to display")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(tr("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["Static offset. Subtracted from value before computing"] = '<?php echo addslashes(tr("Static offset. Subtracted from value before computing")); ?>';
LANG_JS["Style"] = '<?php echo addslashes(tr("Style")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["Timeout without feed update in seconds (empty is never)"] = '<?php echo addslashes(tr("Timeout without feed update in seconds (empty is never)")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(tr("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(tr("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display"] = '<?php echo addslashes(tr("Value is multiplied by scale before display")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';
LANG_JS["With colour gradients"] = '<?php echo addslashes(tr("With colour gradients")); ?>';
LANG_JS["Without colour gradients"] = '<?php echo addslashes(tr("Without colour gradients")); ?>';

// button_render.js
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure."] = '<?php echo addslashes(tr("Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure.")); ?>';
LANG_JS["Starting value"] = '<?php echo addslashes(tr("Starting value")); ?>';
LANG_JS["Value"] = '<?php echo addslashes(tr("Value")); ?>';

// curl_render.js
LANG_JS["0=rd, 1=gn, 2=gy, 3=bu, 4=vio, 5=ye, >5=bk"] = '<?php echo addslashes(tr("0=rd, 1=gn, 2=gy, 3=bu, 4=vio, 5=ye, >5=bk")); ?>';
LANG_JS["Button Text"] = '<?php echo addslashes(tr("Button Text")); ?>';
LANG_JS["Caption"] = '<?php echo addslashes(tr("Caption")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(tr("Colour")); ?>';
LANG_JS["Confirmation"] = '<?php echo addslashes(tr("Confirmation")); ?>';
LANG_JS["Confirmation Box: yes/no"] = '<?php echo addslashes(tr("Confirmation Box: yes/no")); ?>';
LANG_JS["Data to send"] = '<?php echo addslashes(tr("Data to send")); ?>';
LANG_JS["Do you want to continue?"] = '<?php echo addslashes(tr("Do you want to continue?")); ?>';
LANG_JS["GET/POST"] = '<?php echo addslashes(tr("GET/POST")); ?>';
LANG_JS["HTTPS"] = '<?php echo addslashes(tr("HTTPS")); ?>';
LANG_JS["in milliseconds"] = '<?php echo addslashes(tr("in milliseconds")); ?>';
LANG_JS["IP"] = '<?php echo addslashes(tr("IP")); ?>';
LANG_JS["IP address of server (server must have cross origin allowed to work, will not generally work on servers other than localhost)"] = '<?php echo addslashes(tr("IP address of server (server must have cross origin allowed to work, will not generally work on servers other than localhost)")); ?>';
LANG_JS["Listen port of server"] = '<?php echo addslashes(tr("Listen port of server")); ?>';
LANG_JS["Method"] = '<?php echo addslashes(tr("Method")); ?>';
LANG_JS["Payload"] = '<?php echo addslashes(tr("Payload")); ?>';
LANG_JS["Port"] = '<?php echo addslashes(tr("Port")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["URL"] = '<?php echo addslashes(tr("URL")); ?>';
LANG_JS["URL example: node/param"] = '<?php echo addslashes(tr("URL example: node/param")); ?>';
LANG_JS["yes/no"] = '<?php echo addslashes(tr("yes/no")); ?>';

// cylinder_render.js
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Bottom feed value"] = '<?php echo addslashes(tr("Bottom feed value")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Feed Bottom"] = '<?php echo addslashes(tr("Feed Bottom")); ?>';
LANG_JS["Feed Top"] = '<?php echo addslashes(tr("Feed Top")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["No display"] = '<?php echo addslashes(tr("No display")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(tr("Temp unit")); ?>';
LANG_JS["Top feed value"] = '<?php echo addslashes(tr("Top feed value")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(tr("Units of the choosen temp feed")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';

// dewpoint_render.js
LANG_JS["Alignment"] = '<?php echo addslashes(tr("Alignment")); ?>';
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Center"] = '<?php echo addslashes(tr("Center")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(tr("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(tr("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(tr("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(tr("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["Humidity"] = '<?php echo addslashes(tr("Humidity")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Left"] = '<?php echo addslashes(tr("Left")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Relative humidity in %"] = '<?php echo addslashes(tr("Relative humidity in %")); ?>';
LANG_JS["Right"] = '<?php echo addslashes(tr("Right")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(tr("Size")); ?>';
LANG_JS["Temperature"] = '<?php echo addslashes(tr("Temperature")); ?>';
LANG_JS["Temperature feed"] = '<?php echo addslashes(tr("Temperature feed")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(tr("Temp unit")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(tr("Text size in px to use")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(tr("Units of the choosen temp feed")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';

// dial_render.js
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Black <-> White, Zero at left"] = '<?php echo addslashes(tr("Black <-> White, Zero at left")); ?>';
LANG_JS["Blue <-> Red, Zero at upper-left"] = '<?php echo addslashes(tr("Blue <-> Red, Zero at upper-left")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Display Min. and Max. ?"] = '<?php echo addslashes(tr("Display Min. and Max. ?")); ?>';
LANG_JS["Error Message"] = '<?php echo addslashes(tr("Error Message")); ?>';
LANG_JS["Error message displayed when timeout is reached"] = '<?php echo addslashes(tr("Error message displayed when timeout is reached")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["Graduations"] = '<?php echo addslashes(tr("Graduations")); ?>';
LANG_JS["Green <-> Red, Zero at center"] = '<?php echo addslashes(tr("Green <-> Red, Zero at center")); ?>';
LANG_JS["Green <-> Red, Zero at left"] = '<?php echo addslashes(tr("Green <-> Red, Zero at left")); ?>';
LANG_JS["Green center <-> orange edges, Zero at center"] = '<?php echo addslashes(tr("Green center <-> orange edges, Zero at center")); ?>';
LANG_JS["Light <-> dark blue, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark blue, Zero at left")); ?>';
LANG_JS["Light <-> dark cyan, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark cyan, Zero at left")); ?>';
LANG_JS["Light <-> dark green, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark green, Zero at left")); ?>';
LANG_JS["Light <-> dark grey, alternating, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark grey, alternating, Zero at left")); ?>';
LANG_JS["Light <-> dark lime, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark lime, Zero at left")); ?>';
LANG_JS["Light <-> dark mint, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark mint, Zero at left")); ?>';
LANG_JS["Light <-> dark orange, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark orange, Zero at left")); ?>';
LANG_JS["Light <-> dark pink, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark pink, Zero at left")); ?>';
LANG_JS["Light <-> dark purple, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark purple, Zero at left")); ?>';
LANG_JS["Light <-> dark red, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark red, Zero at left")); ?>';
LANG_JS["Light <-> dark royal blue, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark royal blue, Zero at left")); ?>';
LANG_JS["Light <-> dark yellow, Zero at left"] = '<?php echo addslashes(tr("Light <-> dark yellow, Zero at left")); ?>';
LANG_JS["Light blue <-> Red, Zero at mid-left"] = '<?php echo addslashes(tr("Light blue <-> Red, Zero at mid-left")); ?>';
LANG_JS["Max. feed"] = '<?php echo addslashes(tr("Max. feed")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(tr("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(tr("Max value to show")); ?>';
LANG_JS["Min. feed"] = '<?php echo addslashes(tr("Min. feed")); ?>';
LANG_JS["Min / Max ?"] = '<?php echo addslashes(tr("Min / Max ?")); ?>';
LANG_JS["No"] = '<?php echo addslashes(tr("No")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(tr("Offset")); ?>';
LANG_JS["Rainbow!, Zero at left"] = '<?php echo addslashes(tr("Rainbow!, Zero at left")); ?>';
LANG_JS["Red <-> Dark Red, Zero at left"] = '<?php echo addslashes(tr("Red <-> Dark Red, Zero at left")); ?>';
LANG_JS["Red <-> Green, Zero at center"] = '<?php echo addslashes(tr("Red <-> Green, Zero at center")); ?>';
LANG_JS["Red <-> Green, Zero at left"] = '<?php echo addslashes(tr("Red <-> Green, Zero at left")); ?>';
LANG_JS["Reverse Rainbow!, Zero at left"] = '<?php echo addslashes(tr("Reverse Rainbow!, Zero at left")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["Should the graduation limits be shown"] = '<?php echo addslashes(tr("Should the graduation limits be shown")); ?>';
LANG_JS["Static offset. Subtracted from value before computing needle position"] = '<?php echo addslashes(tr("Static offset. Subtracted from value before computing needle position")); ?>';
LANG_JS["The feed for the maximum value"] = '<?php echo addslashes(tr("The feed for the maximum value")); ?>';
LANG_JS["The feed for the minimum value"] = '<?php echo addslashes(tr("The feed for the minimum value")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["Timeout without feed update in seconds (empty is never)"] = '<?php echo addslashes(tr("Timeout without feed update in seconds (empty is never)")); ?>';
LANG_JS["Type"] = '<?php echo addslashes(tr("Type")); ?>';
LANG_JS["Type to show"] = '<?php echo addslashes(tr("Type to show")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(tr("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(tr("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display"] = '<?php echo addslashes(tr("Value is multiplied by scale before display")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';
LANG_JS["Yes"] = '<?php echo addslashes(tr("Yes")); ?>';

// feedtime_render.js
LANG_JS["Alignment"] = '<?php echo addslashes(tr("Alignment")); ?>';
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Center"] = '<?php echo addslashes(tr("Center")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(tr("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(tr("Colour used for display")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(tr("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(tr("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Left"] = '<?php echo addslashes(tr("Left")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Right"] = '<?php echo addslashes(tr("Right")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(tr("Size")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(tr("Text size in px to use")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(tr("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(tr("Units to show")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';

// feedvalue_render.js
LANG_JS["Alignment"] = '<?php echo addslashes(tr("Alignment")); ?>';
LANG_JS["Append Text"] = '<?php echo addslashes(tr("Append Text")); ?>';
LANG_JS["Append Text (Units)"] = '<?php echo addslashes(tr("Append Text (Units)")); ?>';
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Center"] = '<?php echo addslashes(tr("Center")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(tr("Colour")); ?>';
LANG_JS["Colour1"] = '<?php echo addslashes(tr("Colour1")); ?>';
LANG_JS["Colour2"] = '<?php echo addslashes(tr("Colour2")); ?>';
LANG_JS["Colour3"] = '<?php echo addslashes(tr("Colour3")); ?>';
LANG_JS["Colour for range above Threshold2"] = '<?php echo addslashes(tr("Colour for range above Threshold2")); ?>';
LANG_JS["Colour for range below Threshold1"] = '<?php echo addslashes(tr("Colour for range below Threshold1")); ?>';
LANG_JS["Colour for range between Threshold1 and Threshold2"] = '<?php echo addslashes(tr("Colour for range between Threshold1 and Threshold2")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(tr("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Error Message"] = '<?php echo addslashes(tr("Error Message")); ?>';
LANG_JS["Error message displayed when timeout is reached"] = '<?php echo addslashes(tr("Error message displayed when timeout is reached")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(tr("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(tr("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Left"] = '<?php echo addslashes(tr("Left")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Prepend Text"] = '<?php echo addslashes(tr("Prepend Text")); ?>';
LANG_JS["Right"] = '<?php echo addslashes(tr("Right")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["scale"] = '<?php echo addslashes(tr("scale")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(tr("Size")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(tr("Text size in px to use")); ?>';
LANG_JS["Threshold1"] = '<?php echo addslashes(tr("Threshold1")); ?>';
LANG_JS["Threshold1 value"] = '<?php echo addslashes(tr("Threshold1 value")); ?>';
LANG_JS["Threshold2"] = '<?php echo addslashes(tr("Threshold2")); ?>';
LANG_JS["Threshold2 value"] = '<?php echo addslashes(tr("Threshold2 value")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["Timeout without feed update in seconds (empty is never)"] = '<?php echo addslashes(tr("Timeout without feed update in seconds (empty is never)")); ?>';

// frostpoint_render.js
LANG_JS["Alignment"] = '<?php echo addslashes(tr("Alignment")); ?>';
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Center"] = '<?php echo addslashes(tr("Center")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(tr("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(tr("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(tr("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(tr("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["Humidity"] = '<?php echo addslashes(tr("Humidity")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Left"] = '<?php echo addslashes(tr("Left")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Relative humidity in %"] = '<?php echo addslashes(tr("Relative humidity in %")); ?>';
LANG_JS["Right"] = '<?php echo addslashes(tr("Right")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(tr("Size")); ?>';
LANG_JS["Temperature"] = '<?php echo addslashes(tr("Temperature")); ?>';
LANG_JS["Temperature feed"] = '<?php echo addslashes(tr("Temperature feed")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(tr("Temp unit")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(tr("Text size in px to use")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(tr("Units of the choosen temp feed")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';

// heatindex_render.js
LANG_JS["Alignment"] = '<?php echo addslashes(tr("Alignment")); ?>';
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Center"] = '<?php echo addslashes(tr("Center")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(tr("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(tr("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Display Nothing"] = '<?php echo addslashes(tr("Display Nothing")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(tr("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(tr("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Formula applied for T between 50°F and aproximately 80°F"] = '<?php echo addslashes(tr("Formula applied for T between 50°F and aproximately 80°F")); ?>';
LANG_JS["Formula applied for T lower than 50°F"] = '<?php echo addslashes(tr("Formula applied for T lower than 50°F")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["Heat Index"] = '<?php echo addslashes(tr("Heat Index")); ?>';
LANG_JS["Humidity"] = '<?php echo addslashes(tr("Humidity")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Left"] = '<?php echo addslashes(tr("Left")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Relative humidity in %"] = '<?php echo addslashes(tr("Relative humidity in %")); ?>';
LANG_JS["Right"] = '<?php echo addslashes(tr("Right")); ?>';
LANG_JS["Rule 1"] = '<?php echo addslashes(tr("Rule 1")); ?>';
LANG_JS["Rule 2"] = '<?php echo addslashes(tr("Rule 2")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(tr("Size")); ?>';
LANG_JS["Temperature"] = '<?php echo addslashes(tr("Temperature")); ?>';
LANG_JS["Temperature Feed"] = '<?php echo addslashes(tr("Temperature Feed")); ?>';
LANG_JS["Temperature feed"] = '<?php echo addslashes(tr("Temperature feed")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(tr("Temp unit")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(tr("Text size in px to use")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(tr("Units of the choosen temp feed")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';

// humidex_render.js
LANG_JS["Alignment"] = '<?php echo addslashes(tr("Alignment")); ?>';
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Center"] = '<?php echo addslashes(tr("Center")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(tr("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(tr("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(tr("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(tr("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Humidity"] = '<?php echo addslashes(tr("Humidity")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Left"] = '<?php echo addslashes(tr("Left")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Relative humidity in %"] = '<?php echo addslashes(tr("Relative humidity in %")); ?>';
LANG_JS["Right"] = '<?php echo addslashes(tr("Right")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(tr("Size")); ?>';
LANG_JS["Temperature"] = '<?php echo addslashes(tr("Temperature")); ?>';
LANG_JS["Temperature feed"] = '<?php echo addslashes(tr("Temperature feed")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(tr("Temp unit")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(tr("Text size in px to use")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(tr("Units of the choosen temp feed")); ?>';

// isactivefeed_render.js
LANG_JS["Circle"] = '<?php echo addslashes(tr("Circle")); ?>';
LANG_JS["Colour1"] = '<?php echo addslashes(tr("Colour1")); ?>';
LANG_JS["Colour2"] = '<?php echo addslashes(tr("Colour2")); ?>';
LANG_JS["Colour3"] = '<?php echo addslashes(tr("Colour3")); ?>';
LANG_JS["Colour for range above Threshold2"] = '<?php echo addslashes(tr("Colour for range above Threshold2")); ?>';
LANG_JS["Colour for range below Threshold1"] = '<?php echo addslashes(tr("Colour for range below Threshold1")); ?>';
LANG_JS["Colour for range between Threshold1 and Threshold2"] = '<?php echo addslashes(tr("Colour for range between Threshold1 and Threshold2")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Shape"] = '<?php echo addslashes(tr("Shape")); ?>';
LANG_JS["Square"] = '<?php echo addslashes(tr("Square")); ?>';
LANG_JS["Star 5 spikes"] = '<?php echo addslashes(tr("Star 5 spikes")); ?>';
LANG_JS["Star 6 spikes"] = '<?php echo addslashes(tr("Star 6 spikes")); ?>';
LANG_JS["Threshold1"] = '<?php echo addslashes(tr("Threshold1")); ?>';
LANG_JS["Threshold1 in seconds"] = '<?php echo addslashes(tr("Threshold1 in seconds")); ?>';
LANG_JS["Threshold2"] = '<?php echo addslashes(tr("Threshold2")); ?>';
LANG_JS["Threshold2 in seconds"] = '<?php echo addslashes(tr("Threshold2 in seconds")); ?>';
LANG_JS["Triangle &#9650;"] = '<?php echo addslashes(tr("Triangle &#9650;")); ?>';
LANG_JS["Triangle &#9654;"] = '<?php echo addslashes(tr("Triangle &#9654;")); ?>';
LANG_JS["Triangle &#9660;"] = '<?php echo addslashes(tr("Triangle &#9660;")); ?>';
LANG_JS["Triangle &#x25C0;"] = '<?php echo addslashes(tr("Triangle &#x25C0;")); ?>';

// jgauge_render.js
LANG_JS["Error Message"] = '<?php echo addslashes(tr("Error Message")); ?>';
LANG_JS["Error message displayed when timeout is reached"] = '<?php echo addslashes(tr("Error message displayed when timeout is reached")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(tr("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(tr("Max value to show")); ?>';
LANG_JS["Min value"] = '<?php echo addslashes(tr("Min value")); ?>';
LANG_JS["Min value to show"] = '<?php echo addslashes(tr("Min value to show")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["Scale applied to value"] = '<?php echo addslashes(tr("Scale applied to value")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["Timeout without feed update in seconds (empty is never)"] = '<?php echo addslashes(tr("Timeout without feed update in seconds (empty is never)")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(tr("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(tr("Units to show")); ?>';

// jgauge2_render.js
LANG_JS["Error Message"] = '<?php echo addslashes(tr("Error Message")); ?>';
LANG_JS["Error message displayed when timeout is reached"] = '<?php echo addslashes(tr("Error message displayed when timeout is reached")); ?>';
LANG_JS["Feed 1"] = '<?php echo addslashes(tr("Feed 1")); ?>';
LANG_JS["Feed 2"] = '<?php echo addslashes(tr("Feed 2")); ?>';
LANG_JS["Feed 2 (Min/Max for example)"] = '<?php echo addslashes(tr("Feed 2 (Min/Max for example)")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(tr("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(tr("Max value to show")); ?>';
LANG_JS["Min value"] = '<?php echo addslashes(tr("Min value")); ?>';
LANG_JS["Min value to show"] = '<?php echo addslashes(tr("Min value to show")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["Scale applied to value"] = '<?php echo addslashes(tr("Scale applied to value")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["Timeout without feed update in seconds (empty is never)"] = '<?php echo addslashes(tr("Timeout without feed update in seconds (empty is never)")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(tr("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(tr("Units to show")); ?>';

// kwhperiod_render.js
LANG_JS["Annual data"] = '<?php echo addslashes(tr("Annual data")); ?>';
LANG_JS["Convert the periodic energy use to kWh per Day"] = '<?php echo addslashes(tr("Convert the periodic energy use to kWh per Day")); ?>';
LANG_JS["Period kWh/day"] = '<?php echo addslashes(tr("Period kWh/day")); ?>';
LANG_JS["Set to True to use last year's data"] = '<?php echo addslashes(tr("Set to True to use last year's data")); ?>';

// led_render.js
LANG_JS["Display style"] = '<?php echo addslashes(tr("Display style")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Style"] = '<?php echo addslashes(tr("Style")); ?>';
LANG_JS["With colour gradients"] = '<?php echo addslashes(tr("With colour gradients")); ?>';
LANG_JS["Without colour gradients"] = '<?php echo addslashes(tr("Without colour gradients")); ?>';

// signal_render.js
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Color of the label"] = '<?php echo addslashes(tr("Color of the label")); ?>';
LANG_JS["Color of the signal"] = '<?php echo addslashes(tr("Color of the signal")); ?>';
LANG_JS["Colour label"] = '<?php echo addslashes(tr("Colour label")); ?>';
LANG_JS["Colour signal"] = '<?php echo addslashes(tr("Colour signal")); ?>';
LANG_JS["Error Message"] = '<?php echo addslashes(tr("Error Message")); ?>';
LANG_JS["Error message displayed when timeout is reached"] = '<?php echo addslashes(tr("Error message displayed when timeout is reached")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(tr("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Label font"] = '<?php echo addslashes(tr("Label font")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(tr("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(tr("Max value to show")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(tr("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["Signal title"] = '<?php echo addslashes(tr("Signal title")); ?>';
LANG_JS["Static offset. Subtracted from value before computing"] = '<?php echo addslashes(tr("Static offset. Subtracted from value before computing")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["Timeout without feed update in seconds (empty is never)"] = '<?php echo addslashes(tr("Timeout without feed update in seconds (empty is never)")); ?>';
LANG_JS["Value is multiplied by scale before display"] = '<?php echo addslashes(tr("Value is multiplied by scale before display")); ?>';

// sun_render.js
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Color of the label"] = '<?php echo addslashes(tr("Color of the label")); ?>';
LANG_JS["Colour label"] = '<?php echo addslashes(tr("Colour label")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Error Message"] = '<?php echo addslashes(tr("Error Message")); ?>';
LANG_JS["Error message displayed when timeout is reached"] = '<?php echo addslashes(tr("Error message displayed when timeout is reached")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(tr("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Label font"] = '<?php echo addslashes(tr("Label font")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(tr("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(tr("Max value to show")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(tr("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["solar title"] = '<?php echo addslashes(tr("solar title")); ?>';
LANG_JS["Solar title"] = '<?php echo addslashes(tr("Solar title")); ?>';
LANG_JS["Static offset. Subtracted from value before computing"] = '<?php echo addslashes(tr("Static offset. Subtracted from value before computing")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["Timeout without feed update in seconds (empty is never)"] = '<?php echo addslashes(tr("Timeout without feed update in seconds (empty is never)")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(tr("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(tr("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display"] = '<?php echo addslashes(tr("Value is multiplied by scale before display")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';

// thermometer_render.js
LANG_JS["Automatic"] = '<?php echo addslashes(tr("Automatic")); ?>';
LANG_JS["Back"] = '<?php echo addslashes(tr("Back")); ?>';
LANG_JS["Bold"] = '<?php echo addslashes(tr("Bold")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(tr("Colour")); ?>';
LANG_JS["Colour for min. and max. bars"] = '<?php echo addslashes(tr("Colour for min. and max. bars")); ?>';
LANG_JS["Colour of title and values"] = '<?php echo addslashes(tr("Colour of title and values")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(tr("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(tr("Decimals to show")); ?>';
LANG_JS["Display Min. and Max. ?"] = '<?php echo addslashes(tr("Display Min. and Max. ?")); ?>';
LANG_JS["Error Message"] = '<?php echo addslashes(tr("Error Message")); ?>';
LANG_JS["Error message displayed when timeout is reached"] = '<?php echo addslashes(tr("Error message displayed when timeout is reached")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(tr("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(tr("Font style used for display")); ?>';
LANG_JS["Font used"] = '<?php echo addslashes(tr("Font used")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(tr("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(tr("Font weight used for display")); ?>';
LANG_JS["Front"] = '<?php echo addslashes(tr("Front")); ?>';
LANG_JS["Grad. Num."] = '<?php echo addslashes(tr("Grad. Num.")); ?>';
LANG_JS["Graduations"] = '<?php echo addslashes(tr("Graduations")); ?>';
LANG_JS["How many graduation lines to draw (only relevant if graduations are on)"] = '<?php echo addslashes(tr("How many graduation lines to draw (only relevant if graduations are on)")); ?>';
LANG_JS["Italic"] = '<?php echo addslashes(tr("Italic")); ?>';
LANG_JS["Label Colour"] = '<?php echo addslashes(tr("Label Colour")); ?>';
LANG_JS["Max. feed"] = '<?php echo addslashes(tr("Max. feed")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(tr("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(tr("Max value to show")); ?>';
LANG_JS["Min. feed"] = '<?php echo addslashes(tr("Min. feed")); ?>';
LANG_JS["Min / Max ?"] = '<?php echo addslashes(tr("Min / Max ?")); ?>';
LANG_JS["Min value"] = '<?php echo addslashes(tr("Min value")); ?>';
LANG_JS["Min value to show"] = '<?php echo addslashes(tr("Min value to show")); ?>';
LANG_JS["No"] = '<?php echo addslashes(tr("No")); ?>';
LANG_JS["Normal"] = '<?php echo addslashes(tr("Normal")); ?>';
LANG_JS["Oblique"] = '<?php echo addslashes(tr("Oblique")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(tr("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["Should the graduations be shown"] = '<?php echo addslashes(tr("Should the graduations be shown")); ?>';
LANG_JS["Static offset. Subtracted from value before computing"] = '<?php echo addslashes(tr("Static offset. Subtracted from value before computing")); ?>';
LANG_JS["The feed for the maximum value"] = '<?php echo addslashes(tr("The feed for the maximum value")); ?>';
LANG_JS["The feed for the minimum value"] = '<?php echo addslashes(tr("The feed for the minimum value")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(tr("Timeout")); ?>';
LANG_JS["Timeout without feed update in seconds (empty is never)"] = '<?php echo addslashes(tr("Timeout without feed update in seconds (empty is never)")); ?>';
LANG_JS["Title"] = '<?php echo addslashes(tr("Title")); ?>';
LANG_JS["Title of thermometer"] = '<?php echo addslashes(tr("Title of thermometer")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(tr("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(tr("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(tr("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display. Defaults to 1"] = '<?php echo addslashes(tr("Value is multiplied by scale before display. Defaults to 1")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(tr("Where should the unit be shown")); ?>';
LANG_JS["Yes"] = '<?php echo addslashes(tr("Yes")); ?>';

// thresholds_render.js
LANG_JS["Circle"] = '<?php echo addslashes(tr("Circle")); ?>';
LANG_JS["Colour1"] = '<?php echo addslashes(tr("Colour1")); ?>';
LANG_JS["Colour2"] = '<?php echo addslashes(tr("Colour2")); ?>';
LANG_JS["Colour3"] = '<?php echo addslashes(tr("Colour3")); ?>';
LANG_JS["Colour for range above Threshold2"] = '<?php echo addslashes(tr("Colour for range above Threshold2")); ?>';
LANG_JS["Colour for range below Threshold1"] = '<?php echo addslashes(tr("Colour for range below Threshold1")); ?>';
LANG_JS["Colour for range between Threshold1 and Threshold2"] = '<?php echo addslashes(tr("Colour for range between Threshold1 and Threshold2")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(tr("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Shape"] = '<?php echo addslashes(tr("Shape")); ?>';
LANG_JS["Square"] = '<?php echo addslashes(tr("Square")); ?>';
LANG_JS["Star 5 spikes"] = '<?php echo addslashes(tr("Star 5 spikes")); ?>';
LANG_JS["Star 6 spikes"] = '<?php echo addslashes(tr("Star 6 spikes")); ?>';
LANG_JS["Threshold1"] = '<?php echo addslashes(tr("Threshold1")); ?>';
LANG_JS["Threshold1 value"] = '<?php echo addslashes(tr("Threshold1 value")); ?>';
LANG_JS["Threshold2"] = '<?php echo addslashes(tr("Threshold2")); ?>';
LANG_JS["Threshold2 value"] = '<?php echo addslashes(tr("Threshold2 value")); ?>';
LANG_JS["Triangle &#9650;"] = '<?php echo addslashes(tr("Triangle &#9650;")); ?>';
LANG_JS["Triangle &#9654;"] = '<?php echo addslashes(tr("Triangle &#9654;")); ?>';
LANG_JS["Triangle &#9660;"] = '<?php echo addslashes(tr("Triangle &#9660;")); ?>';
LANG_JS["Triangle &#x25C0;"] = '<?php echo addslashes(tr("Triangle &#x25C0;")); ?>';

// windrose_render.js
LANG_JS["Feed value"] = '<?php echo addslashes(tr("Feed value")); ?>';
LANG_JS["Feed Wind"] = '<?php echo addslashes(tr("Feed Wind")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(tr("Scale")); ?>';
LANG_JS["Scale applied to value"] = '<?php echo addslashes(tr("Scale applied to value")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(tr("Units")); ?>';
LANG_JS["Units to show for value"] = '<?php echo addslashes(tr("Units to show for value")); ?>';
LANG_JS["Value shown (wind speed)"] = '<?php echo addslashes(tr("Value shown (wind speed)")); ?>';
LANG_JS["Wind direction"] = '<?php echo addslashes(tr("Wind direction")); ?>';
//END 
