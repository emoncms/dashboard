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
//Please USE the "builder" every javascript modify at: /script/dashboard_langjs_builder.php
// paste source code below
?>
//START
// designer.js
LANG_JS["Changed, press to save"] = '<?php echo addslashes(_("Changed, press to save")); ?>';
LANG_JS["Choose height unit"] = '<?php echo addslashes(_("Choose height unit")); ?>';
LANG_JS["Choose width unit"] = '<?php echo addslashes(_("Choose width unit")); ?>';
LANG_JS["Height"] = '<?php echo addslashes(_("Height")); ?>';
LANG_JS["Off"] = '<?php echo addslashes(_("Off")); ?>';
LANG_JS["On"] = '<?php echo addslashes(_("On")); ?>';
LANG_JS["Percentage"] = '<?php echo addslashes(_("Percentage")); ?>';
LANG_JS["Pixels"] = '<?php echo addslashes(_("Pixels")); ?>';
LANG_JS["Width"] = '<?php echo addslashes(_("Width")); ?>';

// bar_render.js
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Colour for min. and max. bars"] = '<?php echo addslashes(_("Colour for min. and max. bars")); ?>';
LANG_JS["Colour of title and values"] = '<?php echo addslashes(_("Colour of title and values")); ?>';
LANG_JS["Colour to draw bar in"] = '<?php echo addslashes(_("Colour to draw bar in")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Display Min. and Max. ?"] = '<?php echo addslashes(_("Display Min. and Max. ?")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(_("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(_("Font style used for display")); ?>';
LANG_JS["Font used"] = '<?php echo addslashes(_("Font used")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(_("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(_("Font weight used for display")); ?>';
LANG_JS["Grad. Num."] = '<?php echo addslashes(_("Grad. Num.")); ?>';
LANG_JS["Graduations"] = '<?php echo addslashes(_("Graduations")); ?>';
LANG_JS["How many graduation lines to draw (only relevant if graduations are on)"] = '<?php echo addslashes(_("How many graduation lines to draw (only relevant if graduations are on)")); ?>';
LANG_JS["Label Colour"] = '<?php echo addslashes(_("Label Colour")); ?>';
LANG_JS["Max. feed"] = '<?php echo addslashes(_("Max. feed")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Min. feed"] = '<?php echo addslashes(_("Min. feed")); ?>';
LANG_JS["Min / Max ?"] = '<?php echo addslashes(_("Min / Max ?")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(_("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Should the graduations be shown"] = '<?php echo addslashes(_("Should the graduations be shown")); ?>';
LANG_JS["Static offset. Subtracted from value before computing"] = '<?php echo addslashes(_("Static offset. Subtracted from value before computing")); ?>';
LANG_JS["The feed for the maximum value"] = '<?php echo addslashes(_("The feed for the maximum value")); ?>';
LANG_JS["The feed for the minimum value"] = '<?php echo addslashes(_("The feed for the minimum value")); ?>';
LANG_JS["Title"] = '<?php echo addslashes(_("Title")); ?>';
LANG_JS["Title of bar"] = '<?php echo addslashes(_("Title of bar")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(_("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display. Defaults to 1"] = '<?php echo addslashes(_("Value is multiplied by scale before display. Defaults to 1")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(_("Where should the unit be shown")); ?>';

// battery_render.js
LANG_JS["Battery title"] = '<?php echo addslashes(_("Battery title")); ?>';
LANG_JS["Color of the label"] = '<?php echo addslashes(_("Color of the label")); ?>';
LANG_JS["Colour label"] = '<?php echo addslashes(_("Colour label")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Display style"] = '<?php echo addslashes(_("Display style")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(_("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(_("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(_("Font style used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(_("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(_("Font weight used for display")); ?>';
LANG_JS["Label font"] = '<?php echo addslashes(_("Label font")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Min value"] = '<?php echo addslashes(_("Min value")); ?>';
LANG_JS["Min value to show"] = '<?php echo addslashes(_("Min value to show")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(_("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Static offset. Subtracted from value before computing"] = '<?php echo addslashes(_("Static offset. Subtracted from value before computing")); ?>';
LANG_JS["Style"] = '<?php echo addslashes(_("Style")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(_("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display"] = '<?php echo addslashes(_("Value is multiplied by scale before display")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(_("Where should the unit be shown")); ?>';

// button_render.js
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure."] = '<?php echo addslashes(_("Feed to set, control with caution, make sure device being controlled can operate safely in event of emoncms failure.")); ?>';
LANG_JS["Starting value"] = '<?php echo addslashes(_("Starting value")); ?>';
LANG_JS["Value"] = '<?php echo addslashes(_("Value")); ?>';

// curl_render.js
LANG_JS["0=rd, 1=gn, 2=gy, 3=bu, 4=vio, 5=ye, >5=bk"] = '<?php echo addslashes(_("0=rd, 1=gn, 2=gy, 3=bu, 4=vio, 5=ye, >5=bk")); ?>';
LANG_JS["Button Text"] = '<?php echo addslashes(_("Button Text")); ?>';
LANG_JS["Caption"] = '<?php echo addslashes(_("Caption")); ?>';
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Confirmation"] = '<?php echo addslashes(_("Confirmation")); ?>';
LANG_JS["Confirmation Box: yes/no"] = '<?php echo addslashes(_("Confirmation Box: yes/no")); ?>';
LANG_JS["Data to send"] = '<?php echo addslashes(_("Data to send")); ?>';
LANG_JS["Do you want to continue?"] = '<?php echo addslashes(_("Do you want to continue?")); ?>';
LANG_JS["GET/POST"] = '<?php echo addslashes(_("GET/POST")); ?>';
LANG_JS["HTTPS"] = '<?php echo addslashes(_("HTTPS")); ?>';
LANG_JS["in milliseconds"] = '<?php echo addslashes(_("in milliseconds")); ?>';
LANG_JS["IP"] = '<?php echo addslashes(_("IP")); ?>';
LANG_JS["IP address of server"] = '<?php echo addslashes(_("IP address of server")); ?>';
LANG_JS["Listen port of server"] = '<?php echo addslashes(_("Listen port of server")); ?>';
LANG_JS["Method"] = '<?php echo addslashes(_("Method")); ?>';
LANG_JS["Payload"] = '<?php echo addslashes(_("Payload")); ?>';
LANG_JS["Port"] = '<?php echo addslashes(_("Port")); ?>';
LANG_JS["Timeout"] = '<?php echo addslashes(_("Timeout")); ?>';
LANG_JS["URL"] = '<?php echo addslashes(_("URL")); ?>';
LANG_JS["URL example: node/param"] = '<?php echo addslashes(_("URL example: node/param")); ?>';
LANG_JS["yes/no"] = '<?php echo addslashes(_("yes/no")); ?>';

// cylinder_render.js
LANG_JS["Bottom feed value"] = '<?php echo addslashes(_("Bottom feed value")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Feed Bottom"] = '<?php echo addslashes(_("Feed Bottom")); ?>';
LANG_JS["Feed Top"] = '<?php echo addslashes(_("Feed Top")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(_("Temp unit")); ?>';
LANG_JS["Top feed value"] = '<?php echo addslashes(_("Top feed value")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(_("Unit position")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(_("Units of the choosen temp feed")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(_("Where should the unit be shown")); ?>';

// dewpoint_render.js
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(_("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(_("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(_("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(_("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(_("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(_("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(_("Font weight used for display")); ?>';
LANG_JS["Humidity"] = '<?php echo addslashes(_("Humidity")); ?>';
LANG_JS["Relative humidity in %"] = '<?php echo addslashes(_("Relative humidity in %")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(_("Size")); ?>';
LANG_JS["Temperature"] = '<?php echo addslashes(_("Temperature")); ?>';
LANG_JS["Temperature feed"] = '<?php echo addslashes(_("Temperature feed")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(_("Temp unit")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(_("Text size in px to use")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(_("Unit position")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(_("Units of the choosen temp feed")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(_("Where should the unit be shown")); ?>';

// dial_render.js
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Display Min. and Max. ?"] = '<?php echo addslashes(_("Display Min. and Max. ?")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Graduations"] = '<?php echo addslashes(_("Graduations")); ?>';
LANG_JS["Max. feed"] = '<?php echo addslashes(_("Max. feed")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Min. feed"] = '<?php echo addslashes(_("Min. feed")); ?>';
LANG_JS["Min / Max ?"] = '<?php echo addslashes(_("Min / Max ?")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(_("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Should the graduation limits be shown"] = '<?php echo addslashes(_("Should the graduation limits be shown")); ?>';
LANG_JS["Static offset. Subtracted from value before computing needle position"] = '<?php echo addslashes(_("Static offset. Subtracted from value before computing needle position")); ?>';
LANG_JS["The feed for the maximum value"] = '<?php echo addslashes(_("The feed for the maximum value")); ?>';
LANG_JS["The feed for the minimum value"] = '<?php echo addslashes(_("The feed for the minimum value")); ?>';
LANG_JS["Type"] = '<?php echo addslashes(_("Type")); ?>';
LANG_JS["Type to show"] = '<?php echo addslashes(_("Type to show")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(_("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display"] = '<?php echo addslashes(_("Value is multiplied by scale before display")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(_("Where should the unit be shown")); ?>';

// feedvalue_render.js
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(_("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(_("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(_("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(_("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(_("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(_("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(_("Font weight used for display")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(_("Size")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(_("Text size in px to use")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(_("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(_("Where should the unit be shown")); ?>';

// frostpoint_render.js
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(_("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(_("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(_("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(_("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(_("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(_("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(_("Font weight used for display")); ?>';
LANG_JS["Humidity"] = '<?php echo addslashes(_("Humidity")); ?>';
LANG_JS["Relative humidity in %"] = '<?php echo addslashes(_("Relative humidity in %")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(_("Size")); ?>';
LANG_JS["Temperature"] = '<?php echo addslashes(_("Temperature")); ?>';
LANG_JS["Temperature feed"] = '<?php echo addslashes(_("Temperature feed")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(_("Temp unit")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(_("Text size in px to use")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(_("Unit position")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(_("Units of the choosen temp feed")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(_("Where should the unit be shown")); ?>';

// heatindex_render.js
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(_("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(_("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(_("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(_("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(_("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(_("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(_("Font weight used for display")); ?>';
LANG_JS["Formula applied for T between 50°F and aproximately 80°F"] = '<?php echo addslashes(_("Formula applied for T between 50°F and aproximately 80°F")); ?>';
LANG_JS["Formula applied for T lower than 50°F"] = '<?php echo addslashes(_("Formula applied for T lower than 50°F")); ?>';
LANG_JS["Humidity"] = '<?php echo addslashes(_("Humidity")); ?>';
LANG_JS["Relative humidity in %"] = '<?php echo addslashes(_("Relative humidity in %")); ?>';
LANG_JS["Rule 1"] = '<?php echo addslashes(_("Rule 1")); ?>';
LANG_JS["Rule 2"] = '<?php echo addslashes(_("Rule 2")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(_("Size")); ?>';
LANG_JS["Temperature"] = '<?php echo addslashes(_("Temperature")); ?>';
LANG_JS["Temperature feed"] = '<?php echo addslashes(_("Temperature feed")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(_("Temp unit")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(_("Text size in px to use")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(_("Unit position")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(_("Units of the choosen temp feed")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(_("Where should the unit be shown")); ?>';

// humidex_render.js
LANG_JS["Colour"] = '<?php echo addslashes(_("Colour")); ?>';
LANG_JS["Colour used for display"] = '<?php echo addslashes(_("Colour used for display")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(_("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(_("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(_("Font style used for display")); ?>';
LANG_JS["Font used for display"] = '<?php echo addslashes(_("Font used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(_("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(_("Font weight used for display")); ?>';
LANG_JS["Humidity"] = '<?php echo addslashes(_("Humidity")); ?>';
LANG_JS["Relative humidity in %"] = '<?php echo addslashes(_("Relative humidity in %")); ?>';
LANG_JS["Size"] = '<?php echo addslashes(_("Size")); ?>';
LANG_JS["Temperature"] = '<?php echo addslashes(_("Temperature")); ?>';
LANG_JS["Temperature feed"] = '<?php echo addslashes(_("Temperature feed")); ?>';
LANG_JS["Temp unit"] = '<?php echo addslashes(_("Temp unit")); ?>';
LANG_JS["Text size in px to use"] = '<?php echo addslashes(_("Text size in px to use")); ?>';
LANG_JS["Units of the choosen temp feed"] = '<?php echo addslashes(_("Units of the choosen temp feed")); ?>';

// isactivefeed_render.js
LANG_JS["Colour1"] = '<?php echo addslashes(_("Colour1")); ?>';
LANG_JS["Colour2"] = '<?php echo addslashes(_("Colour2")); ?>';
LANG_JS["Colour3"] = '<?php echo addslashes(_("Colour3")); ?>';
LANG_JS["Colour for range above Threshold2"] = '<?php echo addslashes(_("Colour for range above Threshold2")); ?>';
LANG_JS["Colour for range below Threshold1"] = '<?php echo addslashes(_("Colour for range below Threshold1")); ?>';
LANG_JS["Colour for range between Threshold1 and Threshold2"] = '<?php echo addslashes(_("Colour for range between Threshold1 and Threshold2")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Shape"] = '<?php echo addslashes(_("Shape")); ?>';
LANG_JS["Threshold1"] = '<?php echo addslashes(_("Threshold1")); ?>';
LANG_JS["Threshold1 in seconds"] = '<?php echo addslashes(_("Threshold1 in seconds")); ?>';
LANG_JS["Threshold2"] = '<?php echo addslashes(_("Threshold2")); ?>';
LANG_JS["Threshold2 in seconds"] = '<?php echo addslashes(_("Threshold2 in seconds")); ?>';

// jgauge_render.js
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Min value"] = '<?php echo addslashes(_("Min value")); ?>';
LANG_JS["Min value to show"] = '<?php echo addslashes(_("Min value to show")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Scale applied to value"] = '<?php echo addslashes(_("Scale applied to value")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';

// jgauge2_render.js
LANG_JS["Feed 1"] = '<?php echo addslashes(_("Feed 1")); ?>';
LANG_JS["Feed 2"] = '<?php echo addslashes(_("Feed 2")); ?>';
LANG_JS["Feed 2 (Min/Max for example)"] = '<?php echo addslashes(_("Feed 2 (Min/Max for example)")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Min value"] = '<?php echo addslashes(_("Min value")); ?>';
LANG_JS["Min value to show"] = '<?php echo addslashes(_("Min value to show")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Scale applied to value"] = '<?php echo addslashes(_("Scale applied to value")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';

// led_render.js
LANG_JS["Display style"] = '<?php echo addslashes(_("Display style")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Style"] = '<?php echo addslashes(_("Style")); ?>';

// signal_render.js
LANG_JS["Color of the label"] = '<?php echo addslashes(_("Color of the label")); ?>';
LANG_JS["Color of the signal"] = '<?php echo addslashes(_("Color of the signal")); ?>';
LANG_JS["Colour label"] = '<?php echo addslashes(_("Colour label")); ?>';
LANG_JS["Colour signal"] = '<?php echo addslashes(_("Colour signal")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(_("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(_("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(_("Font style used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(_("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(_("Font weight used for display")); ?>';
LANG_JS["Label font"] = '<?php echo addslashes(_("Label font")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(_("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Signal title"] = '<?php echo addslashes(_("Signal title")); ?>';
LANG_JS["Static offset. Subtracted from value before computing"] = '<?php echo addslashes(_("Static offset. Subtracted from value before computing")); ?>';
LANG_JS["Value is multiplied by scale before display"] = '<?php echo addslashes(_("Value is multiplied by scale before display")); ?>';

// sun_render.js
LANG_JS["Color of the label"] = '<?php echo addslashes(_("Color of the label")); ?>';
LANG_JS["Colour label"] = '<?php echo addslashes(_("Colour label")); ?>';
LANG_JS["Decimals"] = '<?php echo addslashes(_("Decimals")); ?>';
LANG_JS["Decimals to show"] = '<?php echo addslashes(_("Decimals to show")); ?>';
LANG_JS["Feed"] = '<?php echo addslashes(_("Feed")); ?>';
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Font"] = '<?php echo addslashes(_("Font")); ?>';
LANG_JS["Font style"] = '<?php echo addslashes(_("Font style")); ?>';
LANG_JS["Font style used for display"] = '<?php echo addslashes(_("Font style used for display")); ?>';
LANG_JS["Font weight"] = '<?php echo addslashes(_("Font weight")); ?>';
LANG_JS["Font weight used for display"] = '<?php echo addslashes(_("Font weight used for display")); ?>';
LANG_JS["Label font"] = '<?php echo addslashes(_("Label font")); ?>';
LANG_JS["Max value"] = '<?php echo addslashes(_("Max value")); ?>';
LANG_JS["Max value to show"] = '<?php echo addslashes(_("Max value to show")); ?>';
LANG_JS["Offset"] = '<?php echo addslashes(_("Offset")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["solar title"] = '<?php echo addslashes(_("solar title")); ?>';
LANG_JS["Solar title"] = '<?php echo addslashes(_("Solar title")); ?>';
LANG_JS["Static offset. Subtracted from value before computing"] = '<?php echo addslashes(_("Static offset. Subtracted from value before computing")); ?>';
LANG_JS["Unit position"] = '<?php echo addslashes(_("Unit position")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show"] = '<?php echo addslashes(_("Units to show")); ?>';
LANG_JS["Value is multiplied by scale before display"] = '<?php echo addslashes(_("Value is multiplied by scale before display")); ?>';
LANG_JS["Where should the unit be shown"] = '<?php echo addslashes(_("Where should the unit be shown")); ?>';

// windrose_render.js
LANG_JS["Feed value"] = '<?php echo addslashes(_("Feed value")); ?>';
LANG_JS["Feed Wind"] = '<?php echo addslashes(_("Feed Wind")); ?>';
LANG_JS["Scale"] = '<?php echo addslashes(_("Scale")); ?>';
LANG_JS["Scale applied to value"] = '<?php echo addslashes(_("Scale applied to value")); ?>';
LANG_JS["Units"] = '<?php echo addslashes(_("Units")); ?>';
LANG_JS["Units to show for value"] = '<?php echo addslashes(_("Units to show for value")); ?>';
LANG_JS["Value shown (wind speed)"] = '<?php echo addslashes(_("Value shown (wind speed)")); ?>';
LANG_JS["Wind direction"] = '<?php echo addslashes(_("Wind direction")); ?>';
//END 
