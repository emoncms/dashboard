<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

Tests for the dashboard content converter.

  php Modules/dashboard/tools/convert_test.php

The fixtures are shapes taken from the census of stored content, not invented
ones, so a change that breaks real dashboards fails here.
*/

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') die("cli only\n");

require_once dirname(__FILE__) . "/../dashboard_convert.php";

$passed = 0;
$failed = 0;

function check($name, $got, $want)
{
    global $passed, $failed;
    if ($got === $want) {
        $passed++;
        return;
    }
    $failed++;
    echo "FAIL $name\n";
    echo "  want: " . json_encode($want, JSON_UNESCAPED_SLASHES) . "\n";
    echo "  got:  " . json_encode($got, JSON_UNESCAPED_SLASHES) . "\n";
}

function convert($html)
{
    return dashboard_convert($html);
}

function codes($result)
{
    $codes = array();
    foreach ($result['warnings'] as $warning) $codes[] = $warning['code'];
    sort($codes);
    return $codes;
}

function widgets($result)
{
    return $result['document']['widgets'];
}

// ---------------------------------------------------------------------------
// A widget the designer wrote and a render script has drawn into
// ---------------------------------------------------------------------------

$html = '<div id="1" class="feedvalue" style="position: absolute; margin: 0px; '
    . 'top: 40px; left: 20px; width: 120px; height: 60px; color: rgb(68, 68, 204); '
    . 'font: normal bold 18px Helvetica; text-align: center; line-height: 60px;" '
    . 'feedid="821" decimals="2" align="center" scale="" timeout="">2.95 kWh</div>';

$result = convert($html);
$widget = widgets($result)[0];

check('feedvalue type', $widget['type'], 'feedvalue');
check('feedvalue geometry', array($widget['x'], $widget['y'], $widget['w'], $widget['h']),
    array(20, 40, 120, 60));
check('feedvalue units', array($widget['wunit'], $widget['hunit']), array('px', 'px'));
check('feedvalue options', $widget['options'],
    array('feedid' => '821', 'decimals' => '2', 'align' => 'center'));
check('feedvalue keeps no html', isset($widget['html']), false);
check('feedvalue keeps no box style', isset($widget['style']), false);
check('feedvalue is clean', codes($result), array());

// ---------------------------------------------------------------------------
// The style broken by htmlspecialchars_decode in set_content
//
// A font family with a space in it comes out of the browser as
// font: ... 18px &quot;Arial Black&quot;, the decode turns the entities back
// into quote characters, and the style attribute ends early. The geometry sits
// in front of the font so it survives, and the options are unaffected.
// ---------------------------------------------------------------------------

$html = '<div id="9" class="feedvalue" style="position: absolute; margin: 0px; '
    . 'top: 100px; left: 10px; width: 140px; height: 60px; color: rgb(68, 68, 204); '
    . 'font: normal bold 18px " arial="" black="" ;="" text-align:="" center="" '
    . 'feedid="821" font="9" size="6" bis_skin_checked="1"></div>';

$result = convert($html);
$widget = widgets($result)[0];

check('broken style keeps geometry', array($widget['x'], $widget['y'], $widget['w'], $widget['h']),
    array(10, 100, 140, 60));
check('broken style keeps options', $widget['options'],
    array('feedid' => '821', 'font' => '9', 'size' => '6'));
check('broken style fragments named', count(array_filter(codes($result),
    function ($c) { return $c === 'broken_style_attribute'; })), 5);
check('broken style extension attribute named', in_array('browser_extension_attribute', codes($result)), true);
check('broken style loses nothing else', in_array('option_unknown_dropped', codes($result)), false);

// ---------------------------------------------------------------------------
// The designer artefact from a dropbox_other option
// ---------------------------------------------------------------------------

$html = '<div id="2" class="dial" style="position:absolute; top:0px; left:0px; '
    . 'width:200px; height:200px;" feedid="5" units="kW" units_dropdown="kW"></div>';

$result = convert($html);
check('dropdown artefact named', codes($result), array('designer_artefact_attribute'));
check('dropdown artefact does not take the option with it',
    widgets($result)[0]['options'], array('feedid' => '5', 'units' => 'kW'));

// ---------------------------------------------------------------------------
// Option names are stored the way the registry declares them
// ---------------------------------------------------------------------------

$html = '<div id="3" class="kwhperiod" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" periodlength="1" uselastyear="1"></div>';

$result = convert($html);
check('mixed case option names restored', array_keys(widgets($result)[0]['options']),
    array('feedid', 'periodLength', 'useLastYear'));

// ---------------------------------------------------------------------------
// Legacy options the render scripts still read
// ---------------------------------------------------------------------------

$html = '<div id="4" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" units="W" unitend="0"></div>';

$result = convert($html);
check('legacy options kept', widgets($result)[0]['options'],
    array('feedid' => '7', 'units' => 'W', 'unitend' => '0'));
check('legacy options are not a warning', codes($result), array());

// ---------------------------------------------------------------------------
// An option value that is not one of the declared ones
// ---------------------------------------------------------------------------

$html = '<div id="5" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" align="middle" decimals="2"></div>';

$result = convert($html);
check('bad dropbox value dropped', widgets($result)[0]['options'],
    array('feedid' => '7', 'decimals' => '2'));
check('bad dropbox value warned', codes($result), array('option_value_dropped'));

// ---------------------------------------------------------------------------
// Empty options are left out
// ---------------------------------------------------------------------------

$html = '<div id="6" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" scale="" timeout="" errormessagedisplayed=""></div>';

$result = convert($html);
check('empty options omitted', widgets($result)[0]['options'], array('feedid' => '7'));
check('empty options are not a warning', codes($result), array());

// ---------------------------------------------------------------------------
// Generated children of a data widget go without comment
// ---------------------------------------------------------------------------

$html = '<div id="7" class="dial" style="position:absolute; top:0px; left:0px; '
    . 'width:200px; height:200px;" feedid="5"><canvas id="can-7" width="200" height="200">'
    . '</canvas><div id="can-7-tooltip-1"></div><div id="can-7-tooltip-2"></div></div>';

$result = convert($html);
check('generated children discarded quietly', codes($result), array());
check('generated children leave no html', isset(widgets($result)[0]['html']), false);

$html = '<div id="8" class="multigraph" style="position:absolute; top:0px; left:0px; '
    . 'width:400px; height:300px;" mid="3"><iframe src="/vis/multigraph?embed=1&mid=3">'
    . '</iframe></div>';

$result = convert($html);
check('generated iframe discarded quietly', codes($result), array());

// ---------------------------------------------------------------------------
// Text widgets
// ---------------------------------------------------------------------------

$html = '<div id="10" class="paragraph" style="position:absolute; margin:0; top:0px; '
    . 'left:0px; width:50%; height:60px; color: #333333;"><b>Power</b> now '
    . '<a href="https://example.com/x" target="_blank">link</a>'
    . '<a href="javascript:alert(1)">bad</a>'
    . '<img src="data:image/png;base64,AAAA" alt="x">'
    . '<span onclick="alert(1)" style="color:red; position:fixed">styled</span>'
    . '<script>alert(1)</script><h6>heading six</h6></div>';

$result = convert($html);
$widget = widgets($result)[0];

check('paragraph width unit', array($widget['w'], $widget['wunit']), array(50, 'pc'));
check('paragraph box style kept', $widget['style'], array('color' => '#333333'));
check('paragraph html', $widget['html'],
    '<b>Power</b> now <a href="https://example.com/x" target="_blank">link</a><a>bad</a>'
    . '<img alt="x"><span style="color: red">styled</span>heading six');
check('paragraph warnings', codes($result), array(
    'attribute_dropped', 'style_property_dropped', 'tag_dropped', 'tag_unwrapped',
    'url_dropped', 'url_dropped'
));

// A container holding a hand built table
$html = '<div id="11" class="Container-White" style="position:absolute; top:0px; '
    . 'left:0px; width:200px; height:200px;"><table border="1" cellpadding="2">'
    . '<tbody><tr><th colspan="2">Head</th></tr><tr><td>a</td><td>b</td></tr></tbody>'
    . '</table></div>';

$result = convert($html);
check('container table kept', widgets($result)[0]['html'],
    '<table border="1" cellpadding="2"><tbody><tr><th colspan="2">Head</th></tr>'
    . '<tr><td>a</td><td>b</td></tr></tbody></table>');
check('container table is clean', codes($result), array());

// ---------------------------------------------------------------------------
// The cases decided against keeping
// ---------------------------------------------------------------------------

$html = '<div id="12" class="paragraph" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:60px;">text<div id="13" class="feedvalue" feedid="2">1.2</div></div>';

$result = convert($html);
check('nested widget dropped', codes($result), array('nested_widget_dropped'));
check('nested widget text kept', widgets($result)[0]['html'], 'text');

$html = '<div id="14" class="paragraph" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:60px;"><iframe src="https://youtube.com/embed/a"></iframe></div>';

$result = convert($html);
check('hand added iframe dropped', codes($result), array('iframe_dropped'));
check('hand added iframe src reported', $result['warnings'][0]['detail'],
    'https://youtube.com/embed/a');

// ---------------------------------------------------------------------------
// Unknown widget types
// ---------------------------------------------------------------------------

$html = '<div id="15" class="jgauge3" style="position:absolute; top:5px; left:5px; '
    . 'width:120px; height:120px;" feedid="4" max="10" someoption="x"></div>';

$result = convert($html);
$widget = widgets($result)[0];
check('unknown widget marked', $widget['unknown'], true);
check('unknown widget options untouched', $widget['options'],
    array('feedid' => '4', 'max' => '10', 'someoption' => 'x'));
check('unknown widget warned', codes($result), array('widget_type_unknown'));

// ---------------------------------------------------------------------------
// Nothing outside a widget, and nothing without a type
// ---------------------------------------------------------------------------

$result = convert('loose text<div id="16" style="top:0px; left:0px; width:1px; height:1px;"></div>');
check('loose text warned', in_array('text_outside_widget', codes($result)), true);
check('widget without a class dropped', in_array('widget_without_type', codes($result)), true);
check('nothing kept', count(widgets($result)), 0);

// ---------------------------------------------------------------------------
// Encoding
// ---------------------------------------------------------------------------

$result = convert('<div id="17" class="Container-White" style="position:absolute; '
    . 'top:0px; left:0px; width:10px; height:10px;"></div>');
$json = dashboard_convert_encode($result['document']);
check('empty options encode as an object', strpos($json, '"options":{}') !== false, true);

$decoded = json_decode($json, true);
check('document round trips', $decoded['widgets'][0]['type'], 'Container-White');

// Non ASCII text has to survive the entity handling libxml needs
$result = convert('<div id="18" class="paragraph" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px;">Température 20°C ↑</div>');
check('non ascii text kept', widgets($result)[0]['html'], 'Température 20°C ↑');

// ---------------------------------------------------------------------------
// Content the converter cannot read
// ---------------------------------------------------------------------------

$result = convert('');
check('empty content converts to nothing', count(widgets($result)), 0);
check('empty content is clean', codes($result), array());

// ---------------------------------------------------------------------------

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
