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

require_once dirname(__FILE__) . "/../dashboard_render.php";

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

function render($result)
{
    $rendered = dashboard_render($result['document']);
    return $rendered['html'];
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

// Every attribute name in a piece of rendered markup, read by parsing it.
function dashboard_test_attributes($html)
{
    $names = array();
    $root = dashboard_convert_parse($html);
    if ($root === null) return $names;

    $stack = array($root);
    while (count($stack)) {
        $node = array_pop($stack);
        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            foreach ($child->attributes as $attribute) $names[] = $attribute->nodeName;
            $stack[] = $child;
        }
    }
    return $names;
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
// A free text option cannot carry a tag
// ---------------------------------------------------------------------------

// feedvalue puts prepend, append, units and errormessagedisplayed into the
// page with .html(), so an angle bracket in one of them would open a tag
$html = '<div id="5" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" '
    . 'units="&lt;img src=x onerror=alert(1)&gt;" '
    . 'prepend="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;" '
    . 'append="&lt;svg onload=alert(1)&gt;" '
    . 'errormessagedisplayed="&lt;iframe src=javascript:alert(1)&gt;"></div>';

$result = convert($html);
check('free text tag dropped', widgets($result)[0]['options'], array('feedid' => '7'));
check('free text tag warned', codes($result), array('option_value_dropped',
    'option_value_dropped', 'option_value_dropped', 'option_value_dropped'));
check('no angle bracket in the rendered options',
    strpos(render($result), 'img src=x'), false);

// dropbox_other holds free text as well, see the units option of bar and dial
$result = convert('<div id="5" class="dial" style="position:absolute; top:0px; '
    . 'left:0px; width:100px; height:50px;" feedid="7" '
    . 'units="&lt;b&gt;kW&lt;/b&gt;"></div>');
check('dropbox_other tag dropped', widgets($result)[0]['options'], array('feedid' => '7'));

// What authors actually write in these is kept, quotes included. The curl
// widget sends a json payload through one, and a url through another.
$html = '<div id="5" class="curl" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" ip="10.0.0.5" port="80" url="api?a=1&amp;b=2" '
    . 'payload="{&quot;state&quot;:1}" caption="Turn on" method="POST"></div>';

$result = convert($html);
check('free text kept', widgets($result)[0]['options'], array(
    'ip' => '10.0.0.5', 'port' => '80', 'url' => 'api?a=1&b=2',
    'payload' => '{"state":1}', 'caption' => 'Turn on', 'method' => 'POST'));
check('free text quiet', codes($result), array());
check('free text quoting is escaped on the way out',
    strpos(render($result), 'payload="{&quot;state&quot;:1}"') !== false, true);

$html = '<div id="5" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" units=" kWh" prepend="&#163;" '
    . 'append="&#176;C" scale="0.001" errormessagedisplayed="Feed timeout!"></div>';

$result = convert($html);
check('unicode free text kept', widgets($result)[0]['options'], array(
    'feedid' => '7', 'units' => ' kWh', 'prepend' => "\xc2\xa3",
    'append' => "\xc2\xb0C", 'scale' => '0.001',
    'errormessagedisplayed' => 'Feed timeout!'));
check('unicode free text quiet', codes($result), array());

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

// Styling authors write, from the style property counts in the census
$styling = 'font: bold 22px / 60px Helvetica; border-bottom: 2px solid #333; '
    . 'border-color: #9b9b9b; border-radius: 25px; background: black; opacity: 0.8; '
    . 'display: flex; justify-content: center; align-items: center; overflow: hidden; '
    . 'max-width: 100%; table-layout: fixed; white-space: nowrap; padding-bottom: 0';

$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . "left:0px; width:10px; height:10px; $styling;\">x</div>");
check('box styling kept', codes($result), array());
check('box styling complete', count(widgets($result)[0]['style']), 14);

// A value cannot fetch or run whatever the property is
$result = convert(box('<span style="background:url(//example.com/x.png); '
    . 'border-color:expression(alert(1)); box-shadow:0 0 0 red">x</span>'));
check('style values still filtered', widgets($result)[0]['html'],
    '<span style="box-shadow: 0 0 0 red">x</span>');

// A fetch does not need url(). Every function in a value has to be one of the
// few that only compute one, see dashboard_convert_allowed_style_functions.
foreach (array('url(//example.com/x.png)', 'image-set("//example.com/x.png" 1x)',
    '-webkit-image-set("//example.com/x.png" 1x)', 'element(#page)',
    'expression(alert(1))', 'cross-fade(red, blue)') as $value) {
    check("style value $value refused",
        dashboard_convert_style_value_allowed($value), false);
}
foreach (array('rgb(255, 221, 221)', 'rgba(0, 0, 0, .5)', 'calc(100% - 10px)',
    'bold 22px / 60px Helvetica', '1px solid rgb(0, 0, 0)') as $value) {
    check("style value $value kept",
        dashboard_convert_style_value_allowed($value), true);
}

// position, z-index and transform stay off the list: they lift a box out of
// the page, restack it, or move it without changing its geometry
$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px; z-index:100; transform:rotate(-90deg);">x</div>');
check('box cannot restack or move itself', isset(widgets($result)[0]['style']), false);

// The margin longhands go the way of the shorthand on a box, because the
// renderer writes margin: 0 and a later margin-top would win
$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px; margin-top:-500px; color:red;">x</div>');
check('box margin longhand dropped', widgets($result)[0]['style'], array('color' => 'red'));
$result = convert(box('<p style="margin-top:8px">x</p>'));
check('margin longhand kept in html', widgets($result)[0]['html'],
    '<p style="margin-top: 8px">x</p>');

// Opacity is floored, so a widget cannot be left invisible and still clickable
$result = convert(box('<span style="opacity:0">x</span>'));
check('opacity floored', widgets($result)[0]['html'],
    '<span style="opacity: 0.2">x</span>');
check('opacity floor reported', codes($result), array('opacity_raised'));
$result = convert(box('<span style="opacity:0.05%">x</span>'));
check('opacity per cent floored', widgets($result)[0]['html'],
    '<span style="opacity: 20%">x</span>');
$result = convert(box('<span style="opacity:0.8">x</span>'));
check('opacity above the floor kept', widgets($result)[0]['html'],
    '<span style="opacity: 0.8">x</span>');
check('opacity above the floor quiet', codes($result), array());
$result = convert(box('<span style="opacity:calc(0.1)">x</span>'));
check('opacity that cannot be read is dropped', widgets($result)[0]['html'],
    '<span>x</span>');
$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px; opacity:0;">x</div>');
check('box opacity floored', widgets($result)[0]['style'], array('opacity' => '0.2'));

// Extension styling is dropped without telling the author, there is nothing
// for them to act on
$result = convert(box('<span style="font-variant-caps:normal; font-stretch:100%; '
    . 'word-break:normal; pointer-events:auto; font-kerning:auto; '
    . '--darkreader-inline-color:red; user-select:none; font-width:100%; '
    . 'font-size-adjust:none; font-feature-settings:normal; '
    . 'font-optical-sizing:auto; font-variation-settings:normal">x</span>'));
check('extension styling dropped', widgets($result)[0]['html'], '<span>x</span>');
check('extension styling quiet', codes($result), array());
$result = convert(box('<span style="mix-blend-mode:multiply">x</span>'));
check('unknown property still reported', codes($result),
    array('style_property_dropped'));

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
    . 'width:120px; height:120px; color:red;" feedid="4" max="10" someoption="x"></div>';

$result = convert($html);
$widget = widgets($result)[0];
check('unknown widget marked', $widget['unknown'], true);
// Nothing declares the widget, so nothing says which of its attributes are
// options and which would act on the page, see the unknown widget types
// section of SCHEMA.md.
check('unknown widget options dropped', $widget['options'], array());
check('unknown widget keeps its box style', $widget['style'], array('color' => 'red'));
check('unknown widget warned', codes($result), array(
    'unknown_widget_option_dropped', 'unknown_widget_option_dropped',
    'unknown_widget_option_dropped', 'widget_type_unknown'));

// An event handler is shaped like an option name, which is why none of them
// are kept.
$html = '<div id="1" class="notawidget" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;" onmouseover="alert(1)"></div>';

$result = convert($html);
check('unknown widget handler dropped', widgets($result)[0]['options'], array());

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
// The allowlist
//
// These replace the AntiXSS filter, which looked for markup known to be
// dangerous and refused the save when it found any. Each case is checked twice,
// once on what the converter stores and once on what the renderer puts back in
// the page, because both sides run the allowlist.
// ---------------------------------------------------------------------------

function box($inner)
{
    return '<div id="1" class="paragraph" style="position:absolute; margin:0; top:0px; '
        . 'left:0px; width:200px; height:60px;">' . $inner . '</div>';
}

function attack($name, $inner, $forbidden, $kept = null)
{
    $result = convert(box($inner));
    $document = $result['document'];

    // What set_content stores. The meta block records how a conversion went
    // and is not written by the editor, see set_content in dashboard_model.php.
    unset($document['meta']);
    $stored = dashboard_convert_encode($document);

    $rendered = dashboard_render($document);
    $html = $rendered['html'];

    foreach ($forbidden as $needle) {
        check("$name: not stored, $needle", stripos($stored, $needle) !== false, false);
        check("$name: not rendered, $needle", stripos($html, $needle) !== false, false);
    }
    if ($kept !== null) {
        check("$name: text kept", strpos($html, $kept) !== false, true);
    }
}

attack('script tag', 'ok<script>alert(1)</script>',
    array('<script', 'alert(1)'), 'ok');

attack('event handler', '<span onclick="alert(1)" onmouseover="alert(2)">hover</span>',
    array('onclick', 'onmouseover', 'alert'), 'hover');

attack('unquoted event handler', '<img src=x onerror=alert(1)>',
    array('onerror', 'alert'));

attack('javascript url', '<a href="javascript:alert(1)">go</a>',
    array('javascript', 'alert'), 'go');

attack('javascript url with whitespace', '<a href="  java&#9;script:alert(1)">go</a>',
    array('javascript', 'java	script', 'alert'), 'go');

attack('javascript url in mixed case', '<a href="JaVaScRiPt:alert(1)">go</a>',
    array('alert'), 'go');

attack('data url in an image', '<img src="data:text/html;base64,PHNjcmlwdD4=" alt="x">',
    array('data:', 'base64'));

attack('vbscript url', '<a href="vbscript:msgbox(1)">go</a>',
    array('vbscript', 'msgbox'), 'go');

attack('svg with an animation handler', '<svg><animate onbegin="alert(1)"></svg>text',
    array('<svg', 'onbegin', 'alert'), 'text');

attack('iframe', '<iframe src="https://evil.example/x"></iframe>text',
    array('<iframe', 'evil.example'), 'text');

attack('object and embed', '<object data="x.swf"></object><embed src="y.swf">',
    array('<object', '<embed', 'x.swf', 'y.swf'));

attack('form controls', '<form action="/x"><input name="a"><button>go</button></form>',
    array('<form', '<input', '<button'));

attack('style tag', '<style>body{background:url(//evil.example/x)}</style>text',
    array('<style>', 'evil.example'), 'text');

attack('url in an inline style', '<span style="background-image:url(//evil.example/x)">text</span>',
    array('url(', 'evil.example'), 'text');

attack('expression in an inline style', '<span style="width:expression(alert(1))">text</span>',
    array('expression', 'alert'), 'text');

attack('meta refresh', '<meta http-equiv="refresh" content="0;url=//evil.example">text',
    array('<meta', 'evil.example'), 'text');

attack('base tag', '<base href="//evil.example/">text',
    array('<base', 'evil.example'), 'text');

attack('link stylesheet', '<link rel="stylesheet" href="//evil.example/x.css">text',
    array('<link', 'evil.example'), 'text');

attack('noscript wrapper', '<noscript><p title="</noscript><img src=x onerror=alert(1)>">',
    array('onerror', 'alert'));

attack('broken nesting', '<b><i>text</b></i><script>alert(1)</script>',
    array('<script', 'alert'), 'text');

attack('comment hiding markup', 'a<!-- <script>alert(1)</script> -->b',
    array('<script', 'alert', '<!--'));

attack('null byte in a url', "<a href=\"java\0script:alert(1)\">go</a>",
    array('alert'), 'go');

attack('colon dressed up as a path', '<a href="java&#xfffd;script:alert(1)">go</a>',
    array('alert'), 'go');

// A relative path is still allowed, including one with a colon after the first
// separator, and so are the three schemes the allowlist names.
$result = convert(box('<a href="dashboard/view?id=2">a</a><a href="x/y:z">b</a>'
    . '<a href="https://example.com/x">c</a><a href="mailto:someone@example.com">d</a>'));
$kept = $result['document']['widgets'][0]['html'];
check('relative url kept', strpos($kept, 'dashboard/view?id=2') !== false, true);
check('colon after a separator kept', strpos($kept, 'x/y:z') !== false, true);
check('https url kept', strpos($kept, 'https://example.com/x') !== false, true);
check('mailto url kept', strpos($kept, 'mailto:someone@example.com') !== false, true);

// An option value cannot break out of the attribute it is written into
$result = convert('<div id="1" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;" feedid="1&quot; onload=&quot;alert(1)"></div>');
$html = dashboard_render($result['document'])['html'];
check('option value cannot break out', stripos($html, 'onload') !== false, false);

// Neither can a widget type
$result = convert('<div id="1" class="x&quot; onload=&quot;alert(1)" style="position:absolute; '
    . 'top:0px; left:0px; width:10px; height:10px;"></div>');
$html = dashboard_render($result['document'])['html'];
check('widget type cannot break out', stripos($html, 'onload=') !== false, false);

// A document that reached the column some other way is still filtered on output
$planted = array('version' => 1, 'widgets' => array(array(
    'type' => 'paragraph', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10,
    'options' => array(),
    'html' => '<script>alert(1)</script><a href="javascript:alert(2)">go</a>'
)));
$html = dashboard_render($planted)['html'];
check('planted document is filtered on output',
    stripos($html, 'script') !== false || stripos($html, 'javascript') !== false, false);

// So is a document holding the options an earlier converter carried through
// for an undeclared widget
$planted = array('version' => 1, 'widgets' => array(array(
    'type' => 'jgauge3', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'unknown' => true,
    'options' => array('onmouseover' => 'alert(1)', 'feedid' => '4')
)));
$rendered = dashboard_render($planted);
check('planted unknown widget writes no attributes',
    preg_match('/\son\w+\s*=|feedid/i', $rendered['html']) > 0, false);
check('planted unknown widget is reported',
    in_array('unknown_widget_option_dropped', array_column($rendered['errors'], 'code')), true);
check('planted unknown widget is drawn',
    strpos($rendered['html'], 'dashboard-placeholder') !== false, true);

// No widget of any kind puts an event handler on the page
$every = array('version' => 1, 'widgets' => array());
foreach (array('feedvalue', 'paragraph', 'Container-White', 'jgauge3', 'notawidget') as $type) {
    $every['widgets'][] = array(
        'type' => $type, 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10,
        'options' => array('onclick' => 'alert(1)', 'units' => '" onload="alert(2)'),
        'html' => '<b onmouseover="alert(3)">t</b>',
        'style' => array('color' => 'red')
    );
}
// Read back as markup rather than as a string: an option value may legitimately
// contain the text of a handler, escaped, and that is not a handler.
$handlers = array();
foreach (dashboard_test_attributes(dashboard_render($every)['html']) as $name) {
    if (preg_match('/^on/i', $name)) $handlers[] = $name;
}
check('no event handler reaches the page', $handlers, array());

// A widget that may not hold html does not get to
$planted = array('version' => 1, 'widgets' => array(array(
    'type' => 'paragraph', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'options' => array()
)));
$planted['widgets'][0]['type'] = 'feedvalue';
$planted['widgets'][0]['html'] = '<b>text</b>';
$rendered = dashboard_render($planted);
check('html on a data widget is refused', strpos($rendered['html'], '<b>') !== false, false);
check('html on a data widget is reported', $rendered['errors'][0]['code'],
    'html_not_allowed_on_type');

// ---------------------------------------------------------------------------

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
