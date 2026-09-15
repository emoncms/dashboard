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
    array('feedid' => '821', 'decimals' => '2', 'align' => 'center',
        'scale' => '', 'timeout' => ''));
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
check('dropbox_other keeps its words', widgets($result)[0]['options'],
    array('feedid' => '7', 'units' => 'kW'));

// Free text that only fails because it holds a tag keeps its words. Dropping
// the option takes the author's label with it, and feedvalue prints the word
// undefined in its place when append is set and prepend is not.
$label = function ($value) {
    $result = convert('<div id="5" class="feedvalue" style="position:absolute; '
        . 'top:0px; left:0px; width:100px; height:50px;" feedid="7" append=" W" '
        . 'prepend="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>');
    $options = widgets($result)[0]['options'];
    return array(isset($options['prepend']) ? $options['prepend'] : null,
        implode(',', codes($result)));
};

check('subscript keeps its text', $label('P<sub>L1</sub>: '),
    array('PL1: ', 'option_value_tags_stripped'));
check('a br leaves a space', $label('Estimated<br>Solar:'),
    array('Estimated Solar:', 'option_value_tags_stripped'));
check('a trailing br keeps the spacing', $label('UFH Flow <br>'),
    array('UFH Flow ', 'option_value_tags_stripped'));
check('nothing but a tag is dropped', $label('<b></b>'),
    array(null, 'option_value_dropped'));

// Stripping a tag cannot leave an angle bracket behind, whatever it was
check('a stray bracket is still dropped', $label('a<b>c>d'),
    array(null, 'option_value_dropped'));
check('a broken out attribute is still dropped', $label('"><script>alert(1)</script>'),
    array(null, 'option_value_dropped'));

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
// Empty options are kept
//
// A render script can read an empty option and an absent one differently, so
// an empty one is written as the author left it, see dashboard_convert_options.
// ---------------------------------------------------------------------------

$html = '<div id="6" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" scale="" timeout="" errormessagedisplayed=""></div>';

$result = convert($html);
check('empty options kept', widgets($result)[0]['options'],
    array('feedid' => '7', 'scale' => '', 'timeout' => '',
        'errormessagedisplayed' => ''));
check('empty options written back',
    strpos(render($result), 'scale=""') !== false, true);
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
    '<b>Power</b> now <a href="https://example.com/x" target="_blank" rel="noopener noreferrer">link</a><a>bad</a>'
    . '<img alt="x"><span style="color: red">styled</span>heading six');
check('paragraph warnings', codes($result), array(
    'attribute_dropped', 'style_property_dropped', 'tag_dropped', 'tag_unwrapped',
    'url_dropped', 'url_dropped'
));

// The html of a widget is the content of its box, so an html attribute was not
// written by the designer and is not put back on the page
$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px;" html="&lt;b&gt;x&lt;/b&gt;">y</div>');
check('html attribute dropped', widgets($result)[0]['options'], array());
check('html attribute reported', codes($result), array('option_value_dropped'));
check('html attribute not rendered',
    strpos(render($result), 'html=') === false, true);

// A url pointing back at this emoncms is a request the browser of whoever is
// looking at the dashboard makes, carrying their session. An image needs no
// click, so a src has to name a static file.
$_SERVER['HTTP_HOST'] = 'emoncms.example';

$result = convert(box('<img src="/feed/delete.json?id=1" alt="a">'
    . '<img src="feed/delete.json?id=2" alt="b">'
    . '<img src="https://emoncms.example/feed/delete.json?id=3" alt="c">'
    . '<img src="//emoncms.example/feed/delete.json?id=4" alt="d">'));
check('own site api image dropped', widgets($result)[0]['html'],
    '<img alt="a"><img alt="b"><img alt="c"><img alt="d">');
check('own site api image reported', codes($result),
    array('url_dropped', 'url_dropped', 'url_dropped', 'url_dropped'));

// A same-site src is held to the dashboard images directory, the one place
// server-hosted diagrams live. The file extension cannot say whether a path is
// a static file or a routed api call, so a src elsewhere on this site is
// dropped whatever it ends in. A remote image is left alone.
$result = convert(box('<img src="Modules/dashboard/Views/images/SolarDiagram.png" alt="a">'
    . '<img src="/Modules/dashboard/Views/images/SolarDiagram2.png" alt="b">'
    . '<img src="https://example.org/diagram.png" alt="c">'
    . '<img src="https://example.org/render?id=1" alt="d">'));
check('stored image kept', codes($result), array());

// Same server, but not the images directory, so the extension proves nothing
// and the src is dropped: a routed call with an image extension, a path
// outside the directory, a subfolder, and a traversal that is decoded before
// it is checked.
$result = convert(box('<img src="feed/delete.png" alt="a">'
    . '<img src="user/logout.json.png" alt="b">'
    . '<img src="/images/solar.png" alt="c">'
    . '<img src="../pics/pv.jpg" alt="d">'
    . '<img src="Modules/dashboard/Views/images/sub/pv.png" alt="e">'
    . '<img src="Modules/dashboard/Views/images/..%2f..%2f..%2ffeed/delete.png" alt="f">'));
check('non stored same site image dropped', widgets($result)[0]['html'],
    '<img alt="a"><img alt="b"><img alt="c"><img alt="d"><img alt="e"><img alt="f">');
check('non stored same site image reported', codes($result),
    array('url_dropped', 'url_dropped', 'url_dropped', 'url_dropped',
        'url_dropped', 'url_dropped'));

// A trailing dot names the same host, so an absolute url to emoncms.example.
// is the same site and held to the same rules, not left alone as another host.
// A trailing dot on a genuinely different host stays external.
$result = convert(box('<img src="https://emoncms.example./feed/delete.json?id=1" alt="a">'
    . '<a href="https://emoncms.example./feed/delete?id=1">b</a>'
    . '<img src="https://example.org./diagram.png" alt="c">'));
check('trailing dot same host dropped', widgets($result)[0]['html'],
    '<img alt="a"><a>b</a><img src="https://example.org./diagram.png" alt="c" referrerpolicy="no-referrer">');
check('trailing dot same host reported', codes($result),
    array('url_dropped', 'url_dropped'));

// An internal link navigates the page in the viewer's session, and emoncms
// routes on controller and action whatever the path ends in, so a link back at
// this site can reach a GET api: feed/delete with or without an extension,
// app/remove which acts whatever the format, and the index.php?q= front
// controller. The extension cannot say which, so an internal link is dropped.
$result = convert(box('<a href="dashboard/view?id=2">a</a>'
    . '<a href="/feed/delete.json?id=1">b</a>'
    . '<a href="feed/delete?id=1">c</a>'
    . '<a href="/app/remove?id=1">d</a>'
    . '<a href="index.php?q=feed/delete&id=1">e</a>'));
check('internal link dropped', widgets($result)[0]['html'],
    '<a>a</a><a>b</a><a>c</a><a>d</a><a>e</a>');
check('internal link reported', codes($result),
    array('url_dropped', 'url_dropped', 'url_dropped', 'url_dropped', 'url_dropped'));

// A link to another site is left alone. mailto is dropped like every scheme
// that is not http or https: harmless, but no dashboard needs it.
$result = convert(box('<a href="https://openenergymonitor.org">out</a>'));
check('external link kept', codes($result), array());
$result = convert(box('<a href="mailto:a@b.c">mail</a>'));
check('mailto link dropped', widgets($result)[0]['html'], '<a>mail</a>');
check('mailto link reported', codes($result), array('url_dropped'));

// A link opening in another tab is told not to hand that tab a handle to this
// one, on the way in and on the way out
$result = convert(box('<a href="https://example.org/" target="_blank">x</a>'));
check('target carries noopener', widgets($result)[0]['html'],
    '<a href="https://example.org/" target="_blank" rel="noopener noreferrer">x</a>');
check('target is quiet', codes($result), array());
check('noopener survives the round trip',
    widgets(convert(render($result)))[0]['html'], widgets($result)[0]['html']);

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

// position and z-index stay off the list: they lift a box out of the page or
// restack it
$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px; z-index:100;">x</div>');
check('box cannot restack or lift itself', isset(widgets($result)[0]['style']), false);

// transform is allowed to rotate, which turns a box where it stands, and
// nothing else, see dashboard_convert_style_rotate_only
$box = 'position:absolute; top:0px; left:0px; width:10px; height:10px; ';
$result = convert('<div id="1" class="paragraph" style="' . $box
    . 'transform:rotate(-90deg);">x</div>');
check('box may rotate', widgets($result)[0]['style'],
    array('transform' => 'rotate(-90deg)'));
check('rotation is not a warning', codes($result), array());

foreach (array('rotate(90deg)', 'rotate(-90deg)', 'rotate(.25turn)',
    'rotate(+1.5rad)', 'ROTATE( 90DEG )') as $value) {
    check("angle $value kept", dashboard_convert_style_rotate_only($value), true);
}
foreach (array('rotate(90deg) rotate(90deg)', 'rotate3d(0,0,1,90deg)',
    'rotate(90deg) translate(1px)', 'rotate()', 'rotate(90deg', 'none') as $value) {
    check("angle $value refused", dashboard_convert_style_rotate_only($value), false);
}

foreach (array('translate(100px, 0)', 'scale(40)', 'matrix(1,0,0,1,80,80)',
    'rotate(90deg) translate(100px)', 'translate(10px) rotate(90deg)',
    'rotate(90deg) scale(4)') as $value) {
    $result = convert('<div id="1" class="paragraph" style="' . $box
        . 'transform:' . $value . ';">x</div>');
    check("transform $value dropped", isset(widgets($result)[0]['style']), false);
    check("transform $value warned",
        in_array('style_value_dropped', codes($result)), true);
}

// The prefixed copies say the same thing as the property now kept, so they go
// without a warning
$result = convert('<div id="1" class="paragraph" style="' . $box
    . '-webkit-transform:rotate(90deg); -moz-transform:rotate(90deg); '
    . 'transform:rotate(90deg);">x</div>');
check('prefixed transform quiet', codes($result), array());
check('prefixed transform not kept', widgets($result)[0]['style'],
    array('transform' => 'rotate(90deg)'));

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

// A negative margin inside the html lifts content out of the widget box and
// over the emoncms menu bar, so it goes the way of a negative top
$result = convert(box('<p style="margin-top:-500px">x</p>'));
check('negative margin dropped', widgets($result)[0]['html'], '<p>x</p>');
check('negative margin reported', codes($result), array('style_value_dropped'));
$result = convert(box('<p style="margin:0 -20px">x</p>'));
check('negative margin shorthand dropped', widgets($result)[0]['html'], '<p>x</p>');
$result = convert(box('<p style="margin-left:-2px">x</p>'));
check('negative margin dropped on the way out', render($result),
    '<div id="1" class="paragraph" style="position:absolute; margin: 0; '
    . 'top:0px; left:0px; width:200px; height:60px;"><p>x</p></div>');

// A negative top is clamped to the top of the page, a negative left is left
// alone, there is nothing to sit on top of off the side of the page
$result = convert('<div id="1" class="paragraph" style="position:absolute; '
    . 'top:-500px; left:-20px; width:10px; height:10px;">x</div>');
check('negative top stored as written', widgets($result)[0]['y'], -500);
check('negative top clamped on the way out',
    strpos(render($result), 'top:0px; left:-20px;') !== false, true);

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

// A class of more than one token is hand written markup, not a widget. The
// renderer will not draw a type carrying a space, so the converter does not
// write one into the document.
$result = convert('<div id="1" class="flex bg-gray-50" style="position:absolute; '
    . 'top:0px; left:0px; width:10px; height:10px;">panel</div>');
check('multi token class warned',
    in_array('widget_type_not_one_token', codes($result)), true);
check('multi token class dropped', count(widgets($result)), 0);

// ---------------------------------------------------------------------------
// A page wrapped in something that is not a widget
// ---------------------------------------------------------------------------

// A tag opened and never closed takes every box after it as its content. The
// boxes are ordinary ones, so they are read rather than lost with the wrapper.
$box = 'style="position:absolute; top:5px; left:5px; width:10px; height:10px;"';
$result = convert('<b>'
    . '<div id="1" class="dial" ' . $box . ' feedid="7"></div>'
    . '<div id="2" class="heading" ' . $box . '>Title</div>');
check('wrapped boxes are read', count(widgets($result)), 2);
check('wrapped box keeps its type', widgets($result)[0]['type'], 'dial');
check('wrapped box keeps its geometry',
    array(widgets($result)[0]['x'], widgets($result)[0]['y']), array(5, 5));
check('wrapped box keeps its options', widgets($result)[0]['options'],
    array('feedid' => '7'));

// However deep the wrapper goes
$result = convert('<div id="page"><div id="row">'
    . '<div id="1" class="dial" ' . $box . '></div></div></div>');
check('a box is found at any depth', count(widgets($result)), 1);

// The editor's own textarea, pasted into the content column. A browser draws
// what is inside it as text, so the migration does not draw it either.
$result = convert('<textarea name="content">'
    . '<div id="1" class="dial" ' . $box . '></div></textarea>');
check('a box inside a textarea is not read', count(widgets($result)), 0);

// Stray markup holding no box is passed over rather than walked into
$result = convert('<div><p>notes to self</p></div>');
check('markup with no box is left alone', count(widgets($result)), 0);
check('and says only that it has no type', codes($result),
    array('widget_without_type'));

// A widget inside a widget is still dropped. A widget is never looked through.
$result = convert('<div id="1" class="paragraph" ' . $box . '>'
    . '<div id="2" class="dial" ' . $box . '></div>text</div>');
check('a nested widget is still dropped', count(widgets($result)), 1);
check('and the text box is what is kept', widgets($result)[0]['type'], 'paragraph');

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

// A relative path points back at this site and is dropped, including one with a
// colon after the first separator, which the host parser reads as relative
// rather than a scheme. Only an external http or https link is kept; mailto
// goes with the other schemes.
$result = convert(box('<a href="dashboard/view?id=2">a</a><a href="x/y:z">b</a>'
    . '<a href="https://example.com/x">c</a><a href="mailto:someone@example.com">d</a>'));
$kept = $result['document']['widgets'][0]['html'];
check('relative url dropped', strpos($kept, 'dashboard/view') === false, true);
check('colon after a separator dropped', strpos($kept, 'x/y:z') === false, true);
check('https url kept', strpos($kept, 'https://example.com/x') !== false, true);
check('mailto url dropped', strpos($kept, 'mailto:someone@example.com') === false, true);

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
// Raw text elements.
//
// libxml parses the content of xmp, noembed, noframes and plaintext into a
// CDATA section, and saveHTML writes a CDATA section out verbatim wherever it
// ends up. Unwrapping the element carried that live markup into the page, and
// one more level of nesting carried it past the second pass the renderer runs.
// ---------------------------------------------------------------------------

attack('raw text element', '<xmp><img src=x onerror=alert(1)></xmp>',
    array('onerror', 'alert'));

attack('nested raw text element', '<xmp><xmp><img src=x onerror=alert(1)></xmp></xmp>',
    array('onerror', 'alert', '<xmp'));

attack('raw text element holding a script', '<xmp><script>alert(1)</script></xmp>',
    array('<script', 'alert'));

attack('noembed', '<noembed><noembed><img src=x onerror=alert(1)></noembed></noembed>',
    array('onerror', 'alert'));

attack('noframes', '<noframes><noframes><svg onload=alert(1)></svg></noframes></noframes>',
    array('onload', 'alert', '<svg'));

attack('plaintext', '<plaintext><plaintext><img src=x onerror=alert(1)>',
    array('onerror', 'alert'));

// The sanitiser is run again by the renderer, so its output has to be its own
// fixed point. Anything that survives one pass and not the next is markup it
// emitted live, which is what the raw text elements above did.
$idempotent = array(
    '<xmp><img src=x onerror=alert(1)></xmp>',
    '<noembed><b>t</b></noembed>',
    '<p style="color:red">t</p><a href="https://example.com/x" target="_blank">l</a>',
    '<img src="https://example.com/pv.png" alt="pv">',
    '<table><tr><td align="left">c</td></tr></table>',
);
foreach ($idempotent as $i => $html) {
    $w = array();
    $once = dashboard_convert_sanitise_html($html, $w, 0);
    $twice = dashboard_convert_sanitise_html($once, $w, 0);
    check("sanitiser is its own fixed point $i", $twice, $once);
}

// ---------------------------------------------------------------------------
// Documents that reached the column some other way.
//
// The renderer runs the allowlist on the way out, so a style value may not end
// its own declaration and start another. A value carrying a semicolon wrote
// position and z-index onto the box past the property allowlist.
// ---------------------------------------------------------------------------

$planted = array('version' => 1, 'widgets' => array(array(
    'type' => 'paragraph', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'options' => array(),
    'style' => array('background' => 'red;position:fixed;top:0;z-index:9999')
)));
$html = dashboard_render($planted)['html'];
check('style value may not start a declaration', stripos($html, 'z-index') !== false, false);
check('style value may not carry position', stripos($html, 'position:fixed') !== false, false);

// A value that is not a string used to reach strlen and stop the page with a
// TypeError rather than being dropped.
$planted['widgets'][0]['style'] = array('color' => array('red'));
$rendered = dashboard_render($planted);
check('a style value that is not a string is dropped',
    strpos($rendered['html'], 'color') !== false, false);
check('a style value that is not a string is reported',
    $rendered['errors'][0]['code'], 'style_declaration_unreadable');

// An image keeps the referer of its fetch off the site it is fetched from. The
// dashboard url carries an apikey or a readkey when the page was opened with
// one, see set_referrer_policy in core.php.
$w = array();
$marked = dashboard_convert_sanitise_html(
    '<img src="https://example.com/pv.png" alt="pv">', $w, 0);
check('a remote image sends no referer',
    strpos($marked, 'referrerpolicy="no-referrer"') !== false, true);

// An author cannot ask for a weaker policy than that.
$w = array();
$marked = dashboard_convert_sanitise_html(
    '<img src="https://example.com/pv.png" referrerpolicy="unsafe-url">', $w, 0);
check('a weaker referer policy is overwritten',
    strpos($marked, 'unsafe-url') !== false, false);

// ---------------------------------------------------------------------------
// Attributes in the reserved xml and xmlns namespaces.
//
// removeAttribute cannot remove one. libxml holds them apart from the rest and
// the call returns having done nothing, so each was reported as dropped and
// stayed on the element. Inert in an html page, and off the allowlist all the
// same, see dashboard_convert_attributes.
// ---------------------------------------------------------------------------

$reserved = array(
    '<a xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="javascript:alert(1)">x</a>',
    '<p xmlns="urn:x">t</p>',
    '<p xmlns:onclick="alert(1)">t</p>',
    '<p xml:lang="en">t</p>',
    '<p xml:base="//example.com/">t</p>',
);
foreach ($reserved as $i => $html) {
    $w = array();
    $out = dashboard_convert_sanitise_html($html, $w, 0);
    check("reserved namespace attribute is gone $i", strpos($out, 'xml') === false, true);
}

// What the element carries otherwise is untouched
$w = array();
check('an allowed attribute survives a reserved one',
    dashboard_convert_sanitise_html('<a xml:lang="en" href="https://example.com/x">x</a>', $w, 0),
    '<a href="https://example.com/x">x</a>');

// ---------------------------------------------------------------------------

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
