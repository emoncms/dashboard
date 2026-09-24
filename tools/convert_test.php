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

if (php_sapi_name() !== 'cli') {
    die("cli only\n");
}

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

function convert($html, $options = [])
{
    return dashboard_convert($html, $options);
}

function render($result)
{
    $rendered = dashboard_render($result['document']);
    return $rendered['html'];
}

function codes($result)
{
    $codes = [];
    foreach ($result['warnings'] as $warning) {
        $codes[] = $warning['code'];
    }
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
    $names = [];
    $root = dashboard_convert_parse($html);
    if ($root === null) {
        return $names;
    }

    $stack = [$root];
    while (count($stack)) {
        $node = array_pop($stack);
        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            foreach ($child->attributes as $attribute) {
                $names[] = $attribute->nodeName;
            }
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
check(
    'feedvalue geometry',
    [$widget['x'], $widget['y'], $widget['w'], $widget['h']],
    [20, 40, 120, 60]
);
check('feedvalue units', [$widget['wunit'], $widget['hunit']], ['px', 'px']);
check(
    'feedvalue options',
    $widget['options'],
    ['feedid' => '821', 'decimals' => '2', 'align' => 'center',
        'scale' => '',
        'timeout' => ''
    ]
);
check('feedvalue keeps no html', isset($widget['html']), false);
check('feedvalue keeps no box style', isset($widget['style']), false);
check('feedvalue is clean', codes($result), []);

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

check(
    'broken style keeps geometry',
    [$widget['x'], $widget['y'], $widget['w'], $widget['h']],
    [10, 100, 140, 60]
);
check(
    'broken style keeps options',
    $widget['options'],
    ['feedid' => '821', 'font' => '9', 'size' => '6']
);
// libxml before 2.14 drops the fragment named ";", so it is not checked.
$fragments = [];
foreach ($result['warnings'] as $warning) {
    if ($warning['code'] === 'broken_style_attribute') {
        $fragments[] = $warning['detail'];
    }
}
check(
    'broken style fragments named',
    array_values(array_intersect(['arial', 'black', 'text-align:', 'center'], $fragments)),
    ['arial', 'black', 'text-align:', 'center']
);
check('broken style extension attribute named', in_array('browser_extension_attribute', codes($result)), true);
check('broken style loses nothing else', in_array('option_unknown_dropped', codes($result)), false);

// ---------------------------------------------------------------------------
// The designer artefact from a dropbox_other option
// ---------------------------------------------------------------------------

$html = '<div id="2" class="dial" style="position:absolute; top:0px; left:0px; '
    . 'width:200px; height:200px;" feedid="5" units="kW" units_dropdown="kW"></div>';

$result = convert($html);
check('dropdown artefact named', codes($result), ['designer_artefact_attribute']);
check(
    'dropdown artefact does not take the option with it',
    widgets($result)[0]['options'],
    ['feedid' => '5', 'units' => 'kW']
);

// ---------------------------------------------------------------------------
// Option names are stored the way the registry declares them
// ---------------------------------------------------------------------------

$html = '<div id="3" class="kwhperiod" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" periodlength="1" uselastyear="1"></div>';

$result = convert($html);
check(
    'mixed case option names restored',
    array_keys(widgets($result)[0]['options']),
    ['feedid', 'periodLength', 'useLastYear']
);

// ---------------------------------------------------------------------------
// Legacy options the render scripts still read
// ---------------------------------------------------------------------------

$html = '<div id="4" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" units="W" unitend="0"></div>';

$result = convert($html);
check(
    'legacy options kept',
    widgets($result)[0]['options'],
    ['feedid' => '7', 'units' => 'W', 'unitend' => '0']
);
check('legacy options are not a warning', codes($result), []);

// ---------------------------------------------------------------------------
// An option value that is not one of the declared ones
// ---------------------------------------------------------------------------

$html = '<div id="5" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" align="middle" decimals="2"></div>';

$result = convert($html);
check(
    'bad dropbox value dropped',
    widgets($result)[0]['options'],
    ['feedid' => '7', 'decimals' => '2']
);
check('bad dropbox value warned', codes($result), ['option_value_dropped']);

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
check('free text tag dropped', widgets($result)[0]['options'], ['feedid' => '7']);
check('free text tag warned', codes($result), ['option_value_dropped',
    'option_value_dropped', 'option_value_dropped', 'option_value_dropped'
]);
check(
    'no angle bracket in the rendered options',
    strpos(render($result), 'img src=x'),
    false
);

// dropbox_other holds free text as well, see the units option of bar and dial
$result = convert('<div id="5" class="dial" style="position:absolute; top:0px; '
    . 'left:0px; width:100px; height:50px;" feedid="7" '
    . 'units="&lt;b&gt;kW&lt;/b&gt;"></div>');
check(
    'dropbox_other keeps its words',
    widgets($result)[0]['options'],
    ['feedid' => '7', 'units' => 'kW']
);

// Free text that only fails because it holds a tag keeps its words. Dropping
// the option takes the author's label with it, and feedvalue prints the word
// undefined in its place when append is set and prepend is not.
$label = function ($value) {
    $result = convert('<div id="5" class="feedvalue" style="position:absolute; '
        . 'top:0px; left:0px; width:100px; height:50px;" feedid="7" append=" W" '
        . 'prepend="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></div>');
    $options = widgets($result)[0]['options'];
    return [isset($options['prepend']) ? $options['prepend'] : null,
        implode(',', codes($result))
    ];
};

check(
    'subscript keeps its text',
    $label('P<sub>L1</sub>: '),
    ['PL1: ', 'option_value_tags_stripped']
);
check(
    'a br leaves a space',
    $label('Estimated<br>Solar:'),
    ['Estimated Solar:', 'option_value_tags_stripped']
);
check(
    'a trailing br keeps the spacing',
    $label('UFH Flow <br>'),
    ['UFH Flow ', 'option_value_tags_stripped']
);
check(
    'nothing but a tag is dropped',
    $label('<b></b>'),
    [null, 'option_value_dropped']
);

// Stripping a tag cannot leave an angle bracket behind, whatever it was
check(
    'a stray bracket is still dropped',
    $label('a<b>c>d'),
    [null, 'option_value_dropped']
);
check(
    'a broken out attribute is still dropped',
    $label('"><script>alert(1)</script>'),
    [null, 'option_value_dropped']
);

// What authors actually write in these is kept, quotes included. The curl
// widget sends a json payload through one, and a url through another.
$html = '<div id="5" class="curl" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" ip="10.0.0.5" port="80" url="api?a=1&amp;b=2" '
    . 'payload="{&quot;state&quot;:1}" caption="Turn on" method="POST"></div>';

$result = convert($html);
check('free text kept', widgets($result)[0]['options'], [
    'ip' => '10.0.0.5', 'port' => '80', 'url' => 'api?a=1&b=2',
    'payload' => '{"state":1}', 'caption' => 'Turn on', 'method' => 'POST'
]);
check('free text quiet', codes($result), []);
check(
    'free text quoting is escaped on the way out',
    strpos(render($result), 'payload="{&quot;state&quot;:1}"') !== false,
    true
);

$html = '<div id="5" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" units=" kWh" prepend="&#163;" '
    . 'append="&#176;C" scale="0.001" errormessagedisplayed="Feed timeout!"></div>';

$result = convert($html);
check('unicode free text kept', widgets($result)[0]['options'], [
    'feedid' => '7', 'units' => ' kWh', 'prepend' => "\xc2\xa3",
    'append' => "\xc2\xb0C", 'scale' => '0.001',
    'errormessagedisplayed' => 'Feed timeout!'
]);
check('unicode free text quiet', codes($result), []);

// ---------------------------------------------------------------------------
// Empty options are kept
//
// A render script can read an empty option and an absent one differently, so
// an empty one is written as the author left it, see dashboard_convert_options.
// ---------------------------------------------------------------------------

$html = '<div id="6" class="feedvalue" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:50px;" feedid="7" scale="" timeout="" errormessagedisplayed=""></div>';

$result = convert($html);
check(
    'empty options kept',
    widgets($result)[0]['options'],
    ['feedid' => '7', 'scale' => '', 'timeout' => '',
        'errormessagedisplayed' => ''
    ]
);
check(
    'empty options written back',
    strpos(render($result), 'scale=""') !== false,
    true
);
check('empty options are not a warning', codes($result), []);

// ---------------------------------------------------------------------------
// Generated children of a data widget go without comment
// ---------------------------------------------------------------------------

$html = '<div id="7" class="dial" style="position:absolute; top:0px; left:0px; '
    . 'width:200px; height:200px;" feedid="5"><canvas id="can-7" width="200" height="200">'
    . '</canvas><div id="can-7-tooltip-1"></div><div id="can-7-tooltip-2"></div></div>';

$result = convert($html);
check('generated children discarded quietly', codes($result), []);
check('generated children leave no html', isset(widgets($result)[0]['html']), false);

$html = '<div id="8" class="multigraph" style="position:absolute; top:0px; left:0px; '
    . 'width:400px; height:300px;" mid="3"><iframe src="/vis/multigraph?embed=1&mid=3">'
    . '</iframe></div>';

$result = convert($html);
check('generated iframe discarded quietly', codes($result), []);

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

check('paragraph width unit', [$widget['w'], $widget['wunit']], [50, 'pc']);
check('paragraph box style kept', $widget['style'], ['color' => '#333333']);
check(
    'paragraph html',
    $widget['html'],
    '<b>Power</b> now <a href="https://example.com/x" target="_blank" rel="noopener noreferrer">link</a><a>bad</a>'
    . '<img alt="x"><span style="color: red">styled</span>heading six'
);
check('paragraph warnings', codes($result), [
    'attribute_dropped', 'style_property_dropped', 'tag_dropped', 'tag_unwrapped',
    'url_dropped', 'url_dropped'
]);

// The html of a widget is the content of its box, so an html attribute was not
// written by the designer and is not put back on the page
$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px;" html="&lt;b&gt;x&lt;/b&gt;">y</div>');
check('html attribute dropped', widgets($result)[0]['options'], []);
check('html attribute reported', codes($result), ['option_value_dropped']);
check(
    'html attribute not rendered',
    strpos(render($result), 'html=') === false,
    true
);

// A url pointing back at this emoncms is a request the browser of whoever is
// looking at the dashboard makes, carrying their session. An image needs no
// click, so a src has to name a static file.
$_SERVER['HTTP_HOST'] = 'emoncms.example';

$result = convert(box('<img src="/feed/delete.json?id=1" alt="a">'
    . '<img src="feed/delete.json?id=2" alt="b">'
    . '<img src="https://emoncms.example/feed/delete.json?id=3" alt="c">'
    . '<img src="//emoncms.example/feed/delete.json?id=4" alt="d">'));
check(
    'own site api image dropped',
    widgets($result)[0]['html'],
    '<img alt="a"><img alt="b"><img alt="c"><img alt="d">'
);
check(
    'own site api image reported',
    codes($result),
    ['url_dropped', 'url_dropped', 'url_dropped', 'url_dropped']
);

// A same-site src is held to the dashboard images directory, the one place
// server-hosted diagrams live. The file extension cannot say whether a path is
// a static file or a routed api call, so a src elsewhere on this site is
// dropped whatever it ends in. A remote image is left alone.
$result = convert(box('<img src="Modules/dashboard/Views/images/SolarDiagram.png" alt="a">'
    . '<img src="/Modules/dashboard/Views/images/SolarDiagram2.png" alt="b">'
    . '<img src="https://example.org/diagram.png" alt="c">'
    . '<img src="https://example.org/render?id=1" alt="d">'));
check('stored image kept', codes($result), []);

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
check(
    'non stored same site image dropped',
    widgets($result)[0]['html'],
    '<img alt="a"><img alt="b"><img alt="c"><img alt="d"><img alt="e"><img alt="f">'
);
check(
    'non stored same site image reported',
    codes($result),
    ['url_dropped', 'url_dropped', 'url_dropped', 'url_dropped',
        'url_dropped',
        'url_dropped'
    ]
);

// A trailing dot names the same host, so an absolute url to emoncms.example.
// is the same site and held to the same rules, not left alone as another host.
// A trailing dot on a genuinely different host stays external.
$result = convert(box('<img src="https://emoncms.example./feed/delete.json?id=1" alt="a">'
    . '<a href="https://emoncms.example./feed/delete?id=1">b</a>'
    . '<img src="https://example.org./diagram.png" alt="c">'));
check(
    'trailing dot same host dropped',
    widgets($result)[0]['html'],
    '<img alt="a"><a>b</a><img src="https://example.org./diagram.png" alt="c" referrerpolicy="no-referrer">'
);
check(
    'trailing dot same host reported',
    codes($result),
    ['url_dropped', 'url_dropped']
);

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
check(
    'internal link dropped',
    widgets($result)[0]['html'],
    '<a>a</a><a>b</a><a>c</a><a>d</a><a>e</a>'
);
check(
    'internal link reported',
    codes($result),
    ['url_dropped', 'url_dropped', 'url_dropped', 'url_dropped', 'url_dropped']
);

// A link to another site is left alone. mailto is dropped like every scheme
// that is not http or https: harmless, but no dashboard needs it.
$result = convert(box('<a href="https://openenergymonitor.org">out</a>'));
check('external link kept', codes($result), []);
$result = convert(box('<a href="mailto:a@b.c">mail</a>'));
check('mailto link dropped', widgets($result)[0]['html'], '<a>mail</a>');
check('mailto link reported', codes($result), ['url_dropped']);

// A link opening in another tab is told not to hand that tab a handle to this
// one, on the way in and on the way out
$result = convert(box('<a href="https://example.org/" target="_blank">x</a>'));
check(
    'target carries noopener',
    widgets($result)[0]['html'],
    '<a href="https://example.org/" target="_blank" rel="noopener noreferrer">x</a>'
);
check('target is quiet', codes($result), []);
check(
    'noopener survives the round trip',
    widgets(convert(render($result)))[0]['html'],
    widgets($result)[0]['html']
);

// Styling authors write, from the style property counts in the census
$styling = 'font: bold 22px / 60px Helvetica; border-bottom: 2px solid #333; '
    . 'border-color: #9b9b9b; border-radius: 25px; background: black; opacity: 0.8; '
    . 'display: flex; justify-content: center; align-items: center; overflow: hidden; '
    . 'max-width: 100%; table-layout: fixed; white-space: nowrap; padding-bottom: 0';

$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . "left:0px; width:10px; height:10px; $styling;\">x</div>");
check('box styling kept', codes($result), []);
check('box styling complete', count(widgets($result)[0]['style']), 14);

// A value cannot fetch or run whatever the property is
$result = convert(box('<span style="background:url(//example.com/x.png); '
    . 'border-color:expression(alert(1)); box-shadow:0 0 0 red">x</span>'));
check(
    'style values still filtered',
    widgets($result)[0]['html'],
    '<span style="box-shadow: 0 0 0 red">x</span>'
);

// A fetch does not need url(). Every function in a value has to be one of the
// few that only compute one, see dashboard_convert_allowed_style_functions.
foreach (
    ['url(//example.com/x.png)', 'image-set("//example.com/x.png" 1x)',
        '-webkit-image-set("//example.com/x.png" 1x)', 'element(#page)',
        'expression(alert(1))', 'cross-fade(red, blue)'
    ] as $value
) {
    check(
        "style value $value refused",
        dashboard_convert_style_value_allowed($value),
        false
    );
}
foreach (
    ['rgb(255, 221, 221)', 'rgba(0, 0, 0, .5)', 'calc(100% - 10px)',
        'bold 22px / 60px Helvetica', '1px solid rgb(0, 0, 0)'
    ] as $value
) {
    check(
        "style value $value kept",
        dashboard_convert_style_value_allowed($value),
        true
    );
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
check(
    'box may rotate',
    widgets($result)[0]['style'],
    ['transform' => 'rotate(-90deg)']
);
check('rotation is not a warning', codes($result), []);

foreach (
    ['rotate(90deg)', 'rotate(-90deg)', 'rotate(.25turn)',
        'rotate(+1.5rad)', 'ROTATE( 90DEG )'
    ] as $value
) {
    check("angle $value kept", dashboard_convert_style_rotate_only($value), true);
}
foreach (
    ['rotate(90deg) rotate(90deg)', 'rotate3d(0,0,1,90deg)',
        'rotate(90deg) translate(1px)', 'rotate()', 'rotate(90deg', 'none'
    ] as $value
) {
    check("angle $value refused", dashboard_convert_style_rotate_only($value), false);
}

foreach (
    ['translate(100px, 0)', 'scale(40)', 'matrix(1,0,0,1,80,80)',
        'rotate(90deg) translate(100px)', 'translate(10px) rotate(90deg)',
        'rotate(90deg) scale(4)'
    ] as $value
) {
    $result = convert('<div id="1" class="paragraph" style="' . $box
        . 'transform:' . $value . ';">x</div>');
    check("transform $value dropped", isset(widgets($result)[0]['style']), false);
    check(
        "transform $value warned",
        in_array('style_value_dropped', codes($result)),
        true
    );
}

// The prefixed copies say the same thing as the property now kept, so they go
// without a warning
$result = convert('<div id="1" class="paragraph" style="' . $box
    . '-webkit-transform:rotate(90deg); -moz-transform:rotate(90deg); '
    . 'transform:rotate(90deg);">x</div>');
check('prefixed transform quiet', codes($result), []);
check(
    'prefixed transform not kept',
    widgets($result)[0]['style'],
    ['transform' => 'rotate(90deg)']
);

// The margin longhands go the way of the shorthand on a box, because the
// renderer writes margin: 0 and a later margin-top would win
$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px; margin-top:-500px; color:red;">x</div>');
check('box margin longhand dropped', widgets($result)[0]['style'], ['color' => 'red']);
$result = convert(box('<p style="margin-top:8px">x</p>'));
check(
    'margin longhand kept in html',
    widgets($result)[0]['html'],
    '<p style="margin-top: 8px">x</p>'
);

// Opacity is floored, so a widget cannot be left invisible and still clickable
$result = convert(box('<span style="opacity:0">x</span>'));
check(
    'opacity floored',
    widgets($result)[0]['html'],
    '<span style="opacity: 0.2">x</span>'
);
check('opacity floor reported', codes($result), ['opacity_raised']);
$result = convert(box('<span style="opacity:0.05%">x</span>'));
check(
    'opacity per cent floored',
    widgets($result)[0]['html'],
    '<span style="opacity: 20%">x</span>'
);
$result = convert(box('<span style="opacity:0.8">x</span>'));
check(
    'opacity above the floor kept',
    widgets($result)[0]['html'],
    '<span style="opacity: 0.8">x</span>'
);
check('opacity above the floor quiet', codes($result), []);
$result = convert(box('<span style="opacity:calc(0.1)">x</span>'));
check(
    'opacity that cannot be read is dropped',
    widgets($result)[0]['html'],
    '<span>x</span>'
);
$result = convert('<div id="1" class="paragraph" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px; opacity:0;">x</div>');
check('box opacity floored', widgets($result)[0]['style'], ['opacity' => '0.2']);

// A negative margin inside the html lifts content out of the widget box and
// over the emoncms menu bar, so it goes the way of a negative top
$result = convert(box('<p style="margin-top:-500px">x</p>'));
check('negative margin dropped', widgets($result)[0]['html'], '<p>x</p>');
check('negative margin reported', codes($result), ['style_value_dropped']);
$result = convert(box('<p style="margin:0 -20px">x</p>'));
check('negative margin shorthand dropped', widgets($result)[0]['html'], '<p>x</p>');
$result = convert(box('<p style="margin-left:-2px">x</p>'));
check(
    'negative margin dropped on the way out',
    render($result),
    '<div id="1" class="paragraph" style="position:absolute; margin: 0; '
    . 'top:0px; left:0px; width:200px; height:60px;"><p>x</p></div>'
);

// A negative top is clamped to the top of the page, a negative left is left
// alone, there is nothing to sit on top of off the side of the page
$result = convert('<div id="1" class="paragraph" style="position:absolute; '
    . 'top:-500px; left:-20px; width:10px; height:10px;">x</div>');
check('negative top stored as written', widgets($result)[0]['y'], -500);
check(
    'negative top clamped on the way out',
    strpos(render($result), 'top:0px; left:-20px;') !== false,
    true
);

// Extension styling is dropped without telling the author, there is nothing
// for them to act on
$result = convert(box('<span style="font-variant-caps:normal; font-stretch:100%; '
    . 'word-break:normal; pointer-events:auto; font-kerning:auto; '
    . '--darkreader-inline-color:red; user-select:none; font-width:100%; '
    . 'font-size-adjust:none; font-feature-settings:normal; '
    . 'font-optical-sizing:auto; font-variation-settings:normal">x</span>'));
check('extension styling dropped', widgets($result)[0]['html'], '<span>x</span>');
check('extension styling quiet', codes($result), []);
$result = convert(box('<span style="mix-blend-mode:multiply">x</span>'));
check(
    'unknown property still reported',
    codes($result),
    ['style_property_dropped']
);

// A container holding a hand built table
$html = '<div id="11" class="Container-White" style="position:absolute; top:0px; '
    . 'left:0px; width:200px; height:200px;"><table border="1" cellpadding="2">'
    . '<tbody><tr><th colspan="2">Head</th></tr><tr><td>a</td><td>b</td></tr></tbody>'
    . '</table></div>';

$result = convert($html);
check(
    'container table kept',
    widgets($result)[0]['html'],
    '<table border="1" cellpadding="2"><tbody><tr><th colspan="2">Head</th></tr>'
    . '<tr><td>a</td><td>b</td></tr></tbody></table>'
);
check('container table is clean', codes($result), []);

// ---------------------------------------------------------------------------
// The cases decided against keeping
// ---------------------------------------------------------------------------

$html = '<div id="12" class="paragraph" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:60px;">text<div id="13" class="feedvalue" feedid="2">1.2</div></div>';

$result = convert($html);
check('nested widget dropped', codes($result), ['nested_widget_dropped']);
check('nested widget text kept', widgets($result)[0]['html'], 'text');

$html = '<div id="14" class="paragraph" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:60px;"><iframe src="https://youtube.com/embed/a"></iframe></div>';

$result = convert($html);
check('hand added iframe dropped', codes($result), ['iframe_dropped']);
check(
    'hand added iframe src reported',
    $result['warnings'][0]['detail'],
    'https://youtube.com/embed/a'
);

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
check('unknown widget options dropped', $widget['options'], []);
check('unknown widget keeps its box style', $widget['style'], ['color' => 'red']);
check('unknown widget warned', codes($result), [
    'unknown_widget_option_dropped', 'unknown_widget_option_dropped',
    'unknown_widget_option_dropped', 'widget_type_unknown'
]);

// An event handler is shaped like an option name, which is why none of them
// are kept.
$html = '<div id="1" class="notawidget" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;" onmouseover="alert(1)"></div>';

$result = convert($html);
check('unknown widget handler dropped', widgets($result)[0]['options'], []);

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
check(
    'multi token class warned',
    in_array('widget_type_not_one_token', codes($result)),
    true
);
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
check(
    'wrapped box keeps its geometry',
    [widgets($result)[0]['x'], widgets($result)[0]['y']],
    [5, 5]
);
check(
    'wrapped box keeps its options',
    widgets($result)[0]['options'],
    ['feedid' => '7']
);

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
check(
    'and says only that it has no type',
    codes($result),
    ['widget_without_type']
);

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
check('empty content is clean', codes($result), []);

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

attack(
    'script tag',
    'ok<script>alert(1)</script>',
    ['<script', 'alert(1)'],
    'ok'
);

attack(
    'event handler',
    '<span onclick="alert(1)" onmouseover="alert(2)">hover</span>',
    ['onclick', 'onmouseover', 'alert'],
    'hover'
);

attack(
    'unquoted event handler',
    '<img src=x onerror=alert(1)>',
    ['onerror', 'alert']
);

attack(
    'javascript url',
    '<a href="javascript:alert(1)">go</a>',
    ['javascript', 'alert'],
    'go'
);

attack(
    'javascript url with whitespace',
    '<a href="  java&#9;script:alert(1)">go</a>',
    ['javascript', 'java	script', 'alert'],
    'go'
);

attack(
    'javascript url in mixed case',
    '<a href="JaVaScRiPt:alert(1)">go</a>',
    ['alert'],
    'go'
);

attack(
    'data url in an image',
    '<img src="data:text/html;base64,PHNjcmlwdD4=" alt="x">',
    ['data:', 'base64']
);

attack(
    'vbscript url',
    '<a href="vbscript:msgbox(1)">go</a>',
    ['vbscript', 'msgbox'],
    'go'
);

attack(
    'svg with an animation handler',
    '<svg><animate onbegin="alert(1)"></svg>text',
    ['<svg', 'onbegin', 'alert'],
    'text'
);

attack(
    'iframe',
    '<iframe src="https://evil.example/x"></iframe>text',
    ['<iframe', 'evil.example'],
    'text'
);

attack(
    'object and embed',
    '<object data="x.swf"></object><embed src="y.swf">',
    ['<object', '<embed', 'x.swf', 'y.swf']
);

attack(
    'form controls',
    '<form action="/x"><input name="a"><button>go</button></form>',
    ['<form', '<input', '<button']
);

attack(
    'style tag',
    '<style>body{background:url(//evil.example/x)}</style>text',
    ['<style>', 'evil.example'],
    'text'
);

attack(
    'url in an inline style',
    '<span style="background-image:url(//evil.example/x)">text</span>',
    ['url(', 'evil.example'],
    'text'
);

attack(
    'expression in an inline style',
    '<span style="width:expression(alert(1))">text</span>',
    ['expression', 'alert'],
    'text'
);

attack(
    'meta refresh',
    '<meta http-equiv="refresh" content="0;url=//evil.example">text',
    ['<meta', 'evil.example'],
    'text'
);

attack(
    'base tag',
    '<base href="//evil.example/">text',
    ['<base', 'evil.example'],
    'text'
);

attack(
    'link stylesheet',
    '<link rel="stylesheet" href="//evil.example/x.css">text',
    ['<link', 'evil.example'],
    'text'
);

attack(
    'noscript wrapper',
    '<noscript><p title="</noscript><img src=x onerror=alert(1)>">',
    ['onerror', 'alert']
);

attack(
    'broken nesting',
    '<b><i>text</b></i><script>alert(1)</script>',
    ['<script', 'alert'],
    'text'
);

attack(
    'comment hiding markup',
    'a<!-- <script>alert(1)</script> -->b',
    ['<script', 'alert', '<!--']
);

attack(
    'null byte in a url',
    "<a href=\"java\0script:alert(1)\">go</a>",
    ['alert'],
    'go'
);

// libxml before 2.14 drops the rest of the document after a null byte in an
// attribute value.
$result = convert(box("<a href=\"x\0y\">go</a> after")
    . '<div id="2" class="heading" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:6px;">second</div>');
check('null byte keeps following widgets', count(widgets($result)), 2);
check('null byte keeps following text', strpos(render($result), 'after') !== false, true);

attack(
    'colon dressed up as a path',
    '<a href="java&#xfffd;script:alert(1)">go</a>',
    ['alert'],
    'go'
);

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
$planted = ['version' => 1, 'widgets' => [[
    'type' => 'paragraph', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10,
    'options' => [],
    'html' => '<script>alert(1)</script><a href="javascript:alert(2)">go</a>'
]
]
];
$html = dashboard_render($planted)['html'];
check(
    'planted document is filtered on output',
    stripos($html, 'script') !== false || stripos($html, 'javascript') !== false,
    false
);

// So is a document holding the options an earlier converter carried through
// for an undeclared widget
$planted = ['version' => 1, 'widgets' => [[
    'type' => 'jgauge3', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'unknown' => true,
    'options' => ['onmouseover' => 'alert(1)', 'feedid' => '4']
]
]
];
$rendered = dashboard_render($planted);
check(
    'planted unknown widget writes no attributes',
    preg_match('/\son\w+\s*=|feedid/i', $rendered['html']) > 0,
    false
);
check(
    'planted unknown widget is reported',
    in_array('unknown_widget_option_dropped', array_column($rendered['errors'], 'code')),
    true
);
check(
    'planted unknown widget is drawn',
    strpos($rendered['html'], 'dashboard-placeholder') !== false,
    true
);

// No widget of any kind puts an event handler on the page
$every = ['version' => 1, 'widgets' => []];
foreach (['feedvalue', 'paragraph', 'Container-White', 'jgauge3', 'notawidget'] as $type) {
    $every['widgets'][] = [
        'type' => $type, 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10,
        'options' => ['onclick' => 'alert(1)', 'units' => '" onload="alert(2)'],
        'html' => '<b onmouseover="alert(3)">t</b>',
        'style' => ['color' => 'red']
    ];
}
// Read back as markup rather than as a string: an option value may legitimately
// contain the text of a handler, escaped, and that is not a handler.
$handlers = [];
foreach (dashboard_test_attributes(dashboard_render($every)['html']) as $name) {
    if (preg_match('/^on/i', $name)) {
        $handlers[] = $name;
    }
}
check('no event handler reaches the page', $handlers, []);

// A widget that may not hold html does not get to
$planted = ['version' => 1, 'widgets' => [[
    'type' => 'paragraph', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'options' => []
]
]
];
$planted['widgets'][0]['type'] = 'feedvalue';
$planted['widgets'][0]['html'] = '<b>text</b>';
$rendered = dashboard_render($planted);
check('html on a data widget is refused', strpos($rendered['html'], '<b>') !== false, false);
check(
    'html on a data widget is reported',
    $rendered['errors'][0]['code'],
    'html_not_allowed_on_type'
);

// ---------------------------------------------------------------------------
// Raw text elements.
//
// libxml parses the content of xmp, noembed, noframes and plaintext into a
// CDATA section, and saveHTML writes a CDATA section out verbatim wherever it
// ends up. Unwrapping the element carried that live markup into the page, and
// one more level of nesting carried it past the second pass the renderer runs.
// ---------------------------------------------------------------------------

attack(
    'raw text element',
    '<xmp><img src=x onerror=alert(1)></xmp>',
    ['onerror', 'alert']
);

attack(
    'nested raw text element',
    '<xmp><xmp><img src=x onerror=alert(1)></xmp></xmp>',
    ['onerror', 'alert', '<xmp']
);

attack(
    'raw text element holding a script',
    '<xmp><script>alert(1)</script></xmp>',
    ['<script', 'alert']
);

attack(
    'noembed',
    '<noembed><noembed><img src=x onerror=alert(1)></noembed></noembed>',
    ['onerror', 'alert']
);

attack(
    'noframes',
    '<noframes><noframes><svg onload=alert(1)></svg></noframes></noframes>',
    ['onload', 'alert', '<svg']
);

attack(
    'plaintext',
    '<plaintext><plaintext><img src=x onerror=alert(1)>',
    ['onerror', 'alert']
);

// The sanitiser is run again by the renderer, so its output has to be its own
// fixed point. Anything that survives one pass and not the next is markup it
// emitted live, which is what the raw text elements above did.
$idempotent = [
    '<xmp><img src=x onerror=alert(1)></xmp>',
    '<noembed><b>t</b></noembed>',
    '<p style="color:red">t</p><a href="https://example.com/x" target="_blank">l</a>',
    '<img src="https://example.com/pv.png" alt="pv">',
    '<table><tr><td align="left">c</td></tr></table>',
];
foreach ($idempotent as $i => $html) {
    $w = [];
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

$planted = ['version' => 1, 'widgets' => [[
    'type' => 'paragraph', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'options' => [],
    'style' => ['background' => 'red;position:fixed;top:0;z-index:9999']
]
]
];
$html = dashboard_render($planted)['html'];
check('style value may not start a declaration', stripos($html, 'z-index') !== false, false);
check('style value may not carry position', stripos($html, 'position:fixed') !== false, false);

// A value that is not a string used to reach strlen and stop the page with a
// TypeError rather than being dropped.
$planted['widgets'][0]['style'] = ['color' => ['red']];
$rendered = dashboard_render($planted);
check(
    'a style value that is not a string is dropped',
    strpos($rendered['html'], 'color') !== false,
    false
);
check(
    'a style value that is not a string is reported',
    $rendered['errors'][0]['code'],
    'style_declaration_unreadable'
);

// An image keeps the referer of its fetch off the site it is fetched from. The
// dashboard url carries an apikey or a readkey when the page was opened with
// one, see set_referrer_policy in core.php.
$w = [];
$marked = dashboard_convert_sanitise_html(
    '<img src="https://example.com/pv.png" alt="pv">',
    $w,
    0
);
check(
    'a remote image sends no referer',
    strpos($marked, 'referrerpolicy="no-referrer"') !== false,
    true
);

// An author cannot ask for a weaker policy than that.
$w = [];
$marked = dashboard_convert_sanitise_html(
    '<img src="https://example.com/pv.png" referrerpolicy="unsafe-url">',
    $w,
    0
);
check(
    'a weaker referer policy is overwritten',
    strpos($marked, 'unsafe-url') !== false,
    false
);

// ---------------------------------------------------------------------------
// Attributes in the reserved xml and xmlns namespaces.
//
// removeAttribute cannot remove one. libxml holds them apart from the rest and
// the call returns having done nothing, so each was reported as dropped and
// stayed on the element. Inert in an html page, and off the allowlist all the
// same, see dashboard_convert_attributes.
// ---------------------------------------------------------------------------

$reserved = [
    '<a xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="javascript:alert(1)">x</a>',
    '<p xmlns="urn:x">t</p>',
    '<p xmlns:onclick="alert(1)">t</p>',
    '<p xml:lang="en">t</p>',
    '<p xml:base="//example.com/">t</p>',
];
foreach ($reserved as $i => $html) {
    $w = [];
    $out = dashboard_convert_sanitise_html($html, $w, 0);
    check("reserved namespace attribute is gone $i", strpos($out, 'xml') === false, true);
}

// What the element carries otherwise is untouched
$w = [];
check(
    'an allowed attribute survives a reserved one',
    dashboard_convert_sanitise_html('<a xml:lang="en" href="https://example.com/x">x</a>', $w, 0),
    '<a href="https://example.com/x">x</a>'
);

// ---------------------------------------------------------------------------
// Inline config
// ---------------------------------------------------------------------------

// A graph widget may hold its chart in the document rather than pointing at a
// saved graph. It travels on the page as one json attribute.
function config_box($config)
{
    return '<div id="1" class="graph" style="position:absolute; top:0px; left:0px; '
        . 'width:400px; height:300px;" config=\'' . $config . '\'></div>';
}

$good = '{"state":{"mode":"daily","showlegend":1},"feedlist":[{"id":"12","plottype":"bars","yaxis":"2"}]}';
$result = convert(config_box($good));
$widget = widgets($result)[0];
check('config is stored as its own field', isset($widget['config']), true);
check(
    'config state is kept',
    $widget['config']['state'],
    ['mode' => 'daily', 'showlegend' => '1']
);
check(
    'config feedlist is kept',
    $widget['config']['feedlist'],
    [['id' => '12', 'plottype' => 'bars', 'yaxis' => '2']]
);
check('config is not an option', isset($widget['options']['config']), false);
check('a valid config warns about nothing', codes($result), []);
check('config is written back', strpos(render($result), 'config=') !== false, true);

// The round trip is what the editor depends on: the designer leaves the
// attribute alone, so a save has to read back what the renderer wrote.
$again = convert(render($result));
check('config survives a round trip', widgets($again)[0]['config'], $widget['config']);

// Values are strings, whatever json says they are.
$result = convert(config_box('{"state":{"showlegend":true,"interval":60}}'));
check('a json true is stored as 1', widgets($result)[0]['config']['state']['showlegend'], '1');
check('a json number is stored as a string', widgets($result)[0]['config']['state']['interval'], '60');

// Anything the declaration does not name is dropped.
$result = convert(config_box('{"state":{"mode":"daily","onload":"alert(1)"},"secrets":{"a":"b"}}'));
check(
    'an undeclared entry is dropped',
    isset(widgets($result)[0]['config']['state']['onload']),
    false
);
check(
    'an undeclared block is dropped',
    isset(widgets($result)[0]['config']['secrets']),
    false
);
check(
    'the declared entry beside it is kept',
    widgets($result)[0]['config']['state']['mode'],
    'daily'
);
check(
    'both are reported',
    codes($result),
    ['config_block_unknown', 'config_entry_unknown']
);

// Values are held to the type the declaration gives them.
$result = convert(config_box('{"state":{"mode":"hourly"},"feedlist":[{"id":"1","plottype":"pie"}]}'));
check(
    'a value outside a dropbox list is dropped',
    isset(widgets($result)[0]['config']['state']),
    false
);
check(
    'a bad feed value is dropped',
    widgets($result)[0]['config']['feedlist'],
    [['id' => '1']]
);

$result = convert(config_box('{"feedlist":[{"id":"1","color":"#ff0000"},{"id":"2","color":"javascript:x"}]}'));
check('a colour is kept', widgets($result)[0]['config']['feedlist'][0]['color'], '#ff0000');
check(
    'a colour that is not one is dropped',
    isset(widgets($result)[0]['config']['feedlist'][1]['color']),
    false
);

// Unreadable json is reported rather than stored.
$result = convert(config_box('{"state":'));
check('unreadable config is dropped', isset(widgets($result)[0]['config']), false);
check('unreadable config is reported', codes($result), ['config_unreadable']);

// A type that declares no config has no business carrying one.
$html = '<div id="1" class="dial" style="position:absolute; top:0px; left:0px; '
    . 'width:100px; height:100px;" config=\'{"state":{"mode":"daily"}}\'></div>';
$result = convert($html);
check(
    'config on a type that takes none is dropped',
    isset(widgets($result)[0]['config']),
    false
);
check(
    'config on a type that takes none is reported',
    codes($result),
    ['option_unknown_dropped']
);

// The way out is checked as well as the way in, so a document that reached the
// column some other way cannot put anything on the page.
$planted = ['version' => 1, 'widgets' => [[
    'type' => 'graph', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
    'options' => [],
    'config' => ['state' => ['mode' => 'daily', 'onload' => 'alert(1)']]
]
]
];
$rendered = dashboard_render($planted);
check(
    'a planted config entry is filtered on output',
    strpos($rendered['html'], 'onload') !== false,
    false
);
check(
    'a planted config entry is reported',
    $rendered['errors'][0]['code'],
    'config_entry_unknown'
);
check(
    'the rest of the planted config is drawn',
    strpos($rendered['html'], 'daily') !== false,
    true
);

$planted['widgets'][0]['type'] = 'dial';
$rendered = dashboard_render($planted);
check(
    'a planted config on the wrong type is refused',
    strpos($rendered['html'], 'config=') !== false,
    false
);
check(
    'a planted config on the wrong type is reported',
    $rendered['errors'][0]['code'],
    'config_not_allowed_on_type'
);

// A config cannot break out of the attribute it is written into.
$planted = ['version' => 1, 'widgets' => [[
    'type' => 'graph', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
    'options' => [],
    'config' => ['feedlist' => [['name' => '" onload="alert(1)']]]
]
]
];
$rendered = dashboard_render($planted);
$handlers = [];
foreach (dashboard_test_attributes($rendered['html']) as $name) {
    if (strpos(strtolower($name), 'on') === 0) {
        $handlers[] = $name;
    }
}
check('a config value cannot break out of the attribute', $handlers, []);

// ---------------------------------------------------------------------------
// Multigraph conversion
// ---------------------------------------------------------------------------

require_once dirname(__FILE__) . "/migrate.php";

// The shape a multigraph stores, taken from the vis editor: graph level
// settings hidden on the first entry, per feed settings on every entry.
$feedlist = json_encode([
    ['id' => '12', 'name' => 'use', 'tag' => 'house', 'left' => true, 'right' => false,
        'fill' => true, 'lineColour' => 'ff0000', 'graphtype' => 'bars', 'stacked' => true,
        'barwidth' => 0.8, 'backgroundColour' => 'ffffff', 'autorefresh' => 10,
        'intervaltype' => 'daily', 'showtag' => false, 'showlegend' => false,
        'skipmissing' => true, 'ymin' => 0, 'ymax' => 3000, 'y2min' => null,
        'timeWindow' => 2461582.442627, 'end' => 0
    ],
    ['id' => '13', 'name' => 'solar', 'tag' => 'house', 'left' => false, 'right' => true,
        'graphtype' => 'lineswithsteps', 'lineColour' => '#00ff00', 'delta' => true,
        'average' => true
    ],
]);

// A fixed moment, so the floating windows below are checked rather than the
// clock this ran at.
$now = 1700000000000;

$converted = dashboard_convert_multigraph_to_graph($feedlist, ' Solar and use ', $now);
$graph = $converted['data'];

check('multigraph name is trimmed', $graph['name'], 'Solar and use');
check('intervaltype becomes mode', $graph['mode'], 'daily');
check('showtag comes from the first entry', $graph['showtag'], 0);
check('showlegend comes from the first entry', $graph['showlegend'], 0);
check('skipmissing becomes showmissing', $graph['showmissing'], 0);
check('an axis bound carries over', $graph['yaxismin'], '0');
check('an axis bound that is not set is auto', $graph['yaxismin2'], 'auto');
// The window is stored on the first entry too. An end of zero means it ends
// now, which is what floatingtime is in a graph.
check(
    'an end of zero becomes a floating window',
    [$graph['floatingtime'], $graph['end']],
    [1, $now]
);
check(
    'timeWindow becomes the length of it',
    $graph['end'] - $graph['start'],
    2461582
);

// A multigraph worked its interval out from a fixed 2400 datapoints every time
// it drew. Nothing recalculates the interval of a saved graph when it is
// loaded, so the conversion works out the same number once.
check('the interval comes from the window', $graph['interval'], 1);
check(
    'a longer window gets a longer interval',
    dashboard_convert_multigraph_to_graph(json_encode([
        ['id' => '1', 'timeWindow' => 7 * 86400000, 'end' => 0]
    ]), 'Week', $now)['data']['interval'],
    252
);
check(
    'the interval never reaches zero',
    dashboard_convert_multigraph_to_graph(json_encode([
        ['id' => '1', 'timeWindow' => 1000, 'end' => 0]
    ]), 'Tiny', $now)['data']['interval'],
    1
);

$fixed = dashboard_convert_multigraph_to_graph(json_encode([
    ['id' => '1', 'timeWindow' => 86400000, 'end' => 1690000000000]
]), 'Fixed', $now);
check(
    'an end in the past is a fixed window',
    [$fixed['data']['floatingtime'], $fixed['data']['start'], $fixed['data']['end']],
    [0, 1690000000000 - 86400000, 1690000000000]
);

$ahead = dashboard_convert_multigraph_to_graph(json_encode([
    ['id' => '1', 'timeWindow' => 86400000, 'end' => $now + 86400000]
]), 'Ahead', $now);
check(
    'an end in the future floats instead',
    [$ahead['data']['floatingtime'], $ahead['data']['end']],
    [1, $now]
);

$noWindow = dashboard_convert_multigraph_to_graph('[{"id":"1"}]', 'No window', $now);
check(
    'a multigraph with no window stored opens on the last week',
    [$noWindow['data']['floatingtime'], $noWindow['data']['end'] - $noWindow['data']['start']],
    [1, 7 * 86400000]
);

check(
    'a feed keeps its name and tag',
    [$graph['feedlist'][0]['name'], $graph['feedlist'][0]['tag']],
    ['use', 'house']
);
check('left is axis one', $graph['feedlist'][0]['yaxis'], 1);
check('right is axis two', $graph['feedlist'][1]['yaxis'], 2);
check('bars carry over', $graph['feedlist'][0]['plottype'], 'bars');
check('lineswithsteps becomes steps', $graph['feedlist'][1]['plottype'], 'steps');
check('a colour gains its hash', $graph['feedlist'][0]['color'], '#ff0000');
check('a colour that has one keeps it', $graph['feedlist'][1]['color'], '#00ff00');
check('fill carries over', $graph['feedlist'][0]['fill'], 1);
check('stacked becomes stack', $graph['feedlist'][0]['stack'], 1);
check('delta carries over', $graph['feedlist'][1]['delta'], 1);
check('average carries over', $graph['feedlist'][1]['average'], 1);
check(
    'the fields with no equivalent are reported',
    array_keys($converted['dropped']),
    ['barwidth', 'autorefresh']
);

// A multigraph drew in an iframe and coloured the body of it, so the colour
// belongs to the widget box rather than to the graph.
check('the background is carried for the widget box', $converted['background'], 'ffffff');
check(
    'a colour written with a hash is carried without one',
    dashboard_convert_multigraph_to_graph('[{"id":"1","backgroundColour":"#E8E8E8"}]', 'Grey')['background'],
    'e8e8e8'
);
check(
    'a multigraph that never set one drew white',
    dashboard_convert_multigraph_to_graph('[{"id":"1"}]', 'Bare')['background'],
    'ffffff'
);
check(
    'a colour that is not one falls back to white',
    dashboard_convert_multigraph_to_graph('[{"id":"1","backgroundColour":"nonsense"}]', 'Bad')['background'],
    'ffffff'
);

// Defaults, for a multigraph saved before a field existed.
$converted = dashboard_convert_multigraph_to_graph('[{"id":"4"}]', 'Bare');
$graph = $converted['data'];
check('standard becomes mode interval', $graph['mode'], 'interval');
check('showtag defaults on', $graph['showtag'], 1);
check('showlegend defaults on', $graph['showlegend'], 1);
check('showmissing defaults on', $graph['showmissing'], 1);
check('a feed with neither axis set goes left', $graph['feedlist'][0]['yaxis'], 1);
check('a feed with no graphtype is lines', $graph['feedlist'][0]['plottype'], 'lines');
check('a feed with no colour has none', $graph['feedlist'][0]['color'], '');

// An unfinished multigraph is an empty column, which is not damage.
$converted = dashboard_convert_multigraph_to_graph('', 'Never filled in');
check('an empty feedlist is not reported as damage', $converted['dropped'], []);
check(
    'an empty feedlist converts to an empty graph',
    $converted['data']['feedlist'],
    []
);

// Damaged rows are reported rather than guessed at.
$converted = dashboard_convert_multigraph_to_graph('not json', 'Broken');
check(
    'an unreadable feedlist is reported',
    isset($converted['dropped']['feedlist_unreadable']),
    true
);
check(
    'an unreadable feedlist converts to an empty graph',
    $converted['data']['feedlist'],
    []
);

$converted = dashboard_convert_multigraph_to_graph('[{"name":"no id"},{"id":"5"}]', 'Partial');
check('a feed with no id is dropped', count($converted['data']['feedlist']), 1);
check(
    'a feed with no id is reported',
    $converted['dropped']['feed_without_id'],
    1
);

$converted = dashboard_convert_multigraph_to_graph('[{"id":"5","lineColour":"javascript:x"}]', 'Bad colour');
check(
    'a colour that is not one is dropped',
    $converted['data']['feedlist'][0]['color'],
    ''
);

// What is written has to be a config the dashboard keeps whole.
$converted = dashboard_convert_multigraph_to_graph($feedlist, 'Solar and use', $now);
$config = dashboard_convert_multigraph_config($converted['data']);
check(
    'a config holds the state and the feeds',
    array_keys($config),
    ['state', 'feedlist']
);
check(
    'the state is strings',
    [$config['state']['mode'], $config['state']['floatingtime'], $config['state']['showlegend']],
    ['daily', '1', '0']
);
check('the name is not part of the state', isset($config['state']['name']), false);
check(
    'a feed is strings',
    [$config['feedlist'][0]['id'], $config['feedlist'][0]['yaxis'], $config['feedlist'][0]['stack']],
    ['12', '1', '1']
);
check('an empty colour is kept empty', $config['feedlist'][1]['color'], '#00ff00');
$warnings = [];
$valid = dashboard_convert_config_valid($config, 'graph', 0, $warnings);
check('a converted multigraph is a config the dashboard keeps', $warnings, []);
check('nothing is lost from it', $valid, $config);

// Rewriting the widgets.
$held = dashboard_convert_multigraph_config(dashboard_convert_multigraph_to_graph('[{"id":"1"}]', 'One', $now)['data']);
$document = ['version' => 1, 'widgets' => [
    ['type' => 'multigraph', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
        'options' => ['mid' => '3']
    ],
    ['type' => 'multigraph', 'x' => 0, 'y' => 300, 'w' => 400, 'h' => 300,
        'options' => ['mid' => '']
    ],
    ['type' => 'multigraph', 'x' => 0, 'y' => 600, 'w' => 400, 'h' => 300,
        'options' => ['mid' => '99']
    ],
    ['type' => 'dial', 'x' => 0, 'y' => 900, 'w' => 100, 'h' => 100,
        'options' => ['feedid' => '1']
    ],
]
];
$counts = [];
$rewritten = multigraph_rewrite($document, ['3' => $held], $counts);

check(
    'a multigraph widget becomes a graph widget holding its chart',
    $rewritten['widgets'][0],
    ['type' => 'graph', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
        'options' => [],
        'config' => $held
    ]
);
check(
    'a widget with no mid becomes an empty graph',
    [$rewritten['widgets'][1]['options'], isset($rewritten['widgets'][1]['config'])],
    [[], false]
);
check(
    'a widget naming a multigraph that is gone becomes an empty graph',
    [$rewritten['widgets'][2]['options'], isset($rewritten['widgets'][2]['config'])],
    [[], false]
);
check('another widget is left alone', $rewritten['widgets'][3], $document['widgets'][3]);
check(
    'the three cases are counted',
    $counts,
    ['widget_converted' => 1, 'widget_without_mid' => 1, 'widget_mid_not_found' => 1]
);

// The colour a multigraph drew on goes on the box of the widget it converts to.
$counts = [];
$coloured = multigraph_rewrite($document, ['3' => $held], $counts, false, ['3' => 'e8e8e8']);
check(
    'the widget box carries the colour the multigraph drew on',
    $coloured['widgets'][0]['options'],
    ['colourbg' => 'e8e8e8']
);
check(
    'a widget naming no multigraph is left to show the dashboard behind it',
    $coloured['widgets'][1]['options'],
    []
);
$painted = dashboard_render($coloured);
check('a coloured box is a widget the dashboard keeps', $painted['errors'], []);
check(
    'the colour reaches the page',
    strpos($painted['html'], 'colourbg="e8e8e8"') !== false,
    true
);
check(
    'the chart reaches the page',
    strpos($painted['html'], 'config="') !== false,
    true
);

// A run naming one multigraph must not empty the widgets it does not cover.
$counts = [];
$partial = multigraph_rewrite($document, ['3' => $held], $counts, true);
check(
    'a partial run converts the widget it covers',
    $partial['widgets'][0]['config'],
    $held
);
check(
    'a partial run leaves the others as they are',
    [$partial['widgets'][1], $partial['widgets'][2]],
    [$document['widgets'][1], $document['widgets'][2]]
);
check(
    'what it left alone is counted',
    $counts,
    ['widget_converted' => 1, 'widget_left_alone' => 2]
);

// A graph widget pointing at a saved graph takes the chart of the saved
// graph and loses the pointer. One pointing at a saved graph that is gone
// loses the pointer and holds no chart.
$saved = $held['state'] + ['name' => 'One', 'feedlist' => $held['feedlist']];
$reader = function ($graphid) use ($saved) {
    return $graphid === '41' ? $saved : null;
};
$pointing = ['version' => 1, 'widgets' => [
    ['type' => 'graph', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
        'options' => ['graphid' => '41', 'colourbg' => 'e8e8e8']
    ],
    ['type' => 'graph', 'x' => 0, 'y' => 300, 'w' => 400, 'h' => 300,
        'options' => ['graphid' => '42']
    ],
]
];
$counts = [];
$folded = multigraph_rewrite($pointing, [], $counts, false, [], $reader);
check(
    'a widget pointing at a saved graph holds its chart',
    $folded['widgets'][0],
    ['type' => 'graph', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
        'options' => ['colourbg' => 'e8e8e8'],
        'config' => $held
    ]
);
check(
    'a widget pointing at a saved graph that is gone holds no chart',
    $folded['widgets'][1],
    ['type' => 'graph', 'x' => 0, 'y' => 300, 'w' => 400, 'h' => 300,
        'options' => []
    ]
);
check('what was folded in is counted', $counts, ['widget_folded' => 1, 'widget_graph_gone' => 1]);
$counts = [];
check(
    'a partial run folds nothing in',
    multigraph_rewrite($pointing, [], $counts, true, [], $reader),
    null
);

// The fold on its own, as a load runs it.
$mixed = ['version' => 2, 'next_id' => 6, 'widgets' => [
    ['id' => 1, 'type' => 'graph', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
        'options' => ['graphid' => '41']
    ],
    ['id' => 2, 'type' => 'graph', 'x' => 0, 'y' => 300, 'w' => 400, 'h' => 300,
        'options' => ['graphid' => '41'], 'config' => $held
    ],
    ['id' => 3, 'type' => 'graph', 'x' => 0, 'y' => 600, 'w' => 400, 'h' => 300,
        'options' => ['graphid' => '']
    ],
    ['id' => 4, 'type' => 'graph', 'x' => 0, 'y' => 900, 'w' => 400, 'h' => 300,
        'options' => ['graphid' => '43']
    ],
    ['id' => 5, 'type' => 'graph', 'x' => 0, 'y' => 1200, 'w' => 400, 'h' => 300,
        'options' => ['toolbar' => '0'], 'config' => $held
    ],
]
];
$failing = function ($graphid) use ($saved) {
    if ($graphid === '43') {
        return false;
    }
    return $graphid === '41' ? $saved : null;
};
$document = $mixed;
$changed = dashboard_migrate_graph_pointers($document, ['graph' => $failing]);
check(
    'a pointer with no chart loads the saved graph',
    [$document['widgets'][0]['options'], $document['widgets'][0]['config']],
    [[], $held]
);
check(
    'a pointer beside a chart is removed and the chart kept',
    [$document['widgets'][1]['options'], $document['widgets'][1]['config']],
    [[], $held]
);
check(
    'an empty pointer is removed',
    [$document['widgets'][2]['options'], isset($document['widgets'][2]['config'])],
    [[], false]
);
check(
    'a pointer whose read failed is left for the next load',
    $document['widgets'][3],
    $mixed['widgets'][3]
);
check('a widget with no pointer is left alone', $document['widgets'][4], $mixed['widgets'][4]);
check(
    'what changed is reported',
    $changed,
    [
        ['index' => 0, 'graphid' => '41', 'result' => 'loaded'],
        ['index' => 1, 'graphid' => '41', 'result' => 'cleared'],
        ['index' => 2, 'graphid' => '', 'result' => 'cleared'],
    ]
);
check(
    'the log line names what was loaded',
    dashboard_migrate_graph_summary($changed),
    'widget 0 loaded saved graph 41'
);
$document = $mixed;
$changed = dashboard_migrate_graph_pointers($document, []);
check(
    'with no reader a pointer with no chart is left alone',
    [$document['widgets'][0], count($changed)],
    [$mixed['widgets'][0], 2]
);
$errors = [];
$cleaned = dashboard_render_clean($mixed, $errors);
check('a stored pointer is kept by the document validation', $cleaned['widgets'][0]['options'], ['graphid' => '41']);
check('and does not warn', $errors, []);

$rendered = dashboard_render($rewritten);
check('the rewritten document draws', $rendered['errors'], []);
check(
    'it draws graph widgets',
    substr_count($rendered['html'], 'class="graph"'),
    3
);

$counts = [];
check(
    'a document with no multigraph is left alone',
    multigraph_rewrite(
        ['version' => 1, 'widgets' => [
            ['type' => 'dial', 'x' => 0, 'y' => 0, 'w' => 1, 'h' => 1, 'options' => []]
        ]
        ],
        [],
        $counts
    ),
    null
);

// ---------------------------------------------------------------------------
// The vis chart widgets that convert to a graph config
// ---------------------------------------------------------------------------

require_once dirname(__FILE__) . "/migrate.php";

$now = 1700000000000;
$day = 86400000;

// rawdata is one feed drawn as lines over the zoom it opened on.
$raw = dashboard_convert_preset_to_graph('rawdata', [
    'feedid' => '12', 'colour' => '0099ff', 'colourbg' => 'E8E8E8',
    'units' => 'W', 'dp' => '2', 'scale' => '0.001', 'fill' => '1', 'initzoom' => '30',
], $now);
$state = $raw['config']['state'];
$feed = $raw['config']['feedlist'][0];
check('rawdata draws lines', $feed['plottype'], 'lines');
check('rawdata carries the feed', $feed['id'], '12');
check('the colour gets its hash back', $feed['color'], '#0099ff');
check('the units go on the feed', $feed['unit'], 'W');
check('the decimal points carry over', $feed['dp'], '2');
check('the scale carries over', $feed['scale'], '0.001');
check('the fill carries over', $feed['fill'], '1');
check('rawdata picks its own step', $state['mode'], 'interval');
check('rawdata left the gaps out', $state['showmissing'], '0');
check(
    'the window is the zoom it opened on',
    (int) $state['end'] - (int) $state['start'],
    30 * $day
);
check('the window floats, so it shows recent data', $state['floatingtime'], '1');
check(
    'the colour behind the chart goes on the box',
    $raw['options'],
    ['colourbg' => 'e8e8e8']
);
check('these drew no legend', $state['showlegend'], '0');

// Defaults, for a widget saved with only a feed on it.
$bare = dashboard_convert_preset_to_graph('rawdata', ['feedid' => '4'], $now);
check(
    'a bare rawdata draws the colour it drew before',
    $bare['config']['feedlist'][0]['color'],
    '#EDC240'
);
check(
    'a bare rawdata opens on the last week',
    (int) $bare['config']['state']['end'] - (int) $bare['config']['state']['start'],
    7 * $day
);
check('a bare rawdata drew on white', $bare['options'], ['colourbg' => 'ffffff']);
check(
    'a zoom under a day is one nobody set',
    (int) dashboard_convert_preset_to_graph('rawdata', ['feedid' => '4', 'initzoom' => '0'], $now)
        ['config']['state']['end']
    - (int) dashboard_convert_preset_to_graph('rawdata', ['feedid' => '4', 'initzoom' => '0'], $now)
        ['config']['state']['start'],
    7 * $day
);

// A widget nobody finished draws an empty chart and is counted.
$empty = dashboard_convert_preset_to_graph('rawdata', [], $now);
check(
    'a rawdata with no feed converts to an empty chart',
    $empty['config']['feedlist'],
    []
);
check('and says so', $empty['dropped'], ['widget_without_feed' => 1]);

// bargraph says its step with the interval option, in seconds or as one of
// d, m and y. A day, a month or a year is a zoom widget, which draws all three
// and steps between them.
$bars = dashboard_convert_preset_to_graph('bargraph', ['feedid' => '7', 'delta' => '1', 'colour' => 'ff0000'], $now);
check('a bargraph with no interval becomes a zoom widget', $bars['type'], 'zoom');
check(
    'the feed is the daily feed and there is no power feed',
    [$bars['options']['kwhd'], $bars['options']['power']],
    ['7', '']
);
check('the delta carries over', $bars['options']['delta'], '1');
check('so does the colour, without the hash', $bars['options']['colour'], 'ff0000');
check('it drew on white', $bars['options']['colourbg'], 'ffffff');
check('a day opens on the days', $bars['options']['view'], 'days');
check('no config block', isset($bars['config']), false);
check('a scale of one is not written', isset($bars['options']['scale']), false);

check(
    'an interval of a day in seconds is a zoom widget too',
    dashboard_convert_preset_to_graph('bargraph', ['feedid' => '7', 'interval' => '86400'], $now)['type'],
    'zoom'
);
check(
    'an interval of zero is a day',
    dashboard_convert_preset_to_graph('bargraph', ['feedid' => '7', 'interval' => '0'], $now)['options']['view'],
    'days'
);
check(
    'd opens on the days',
    dashboard_convert_preset_to_graph('bargraph', ['feedid' => '7', 'interval' => 'd'], $now)['options']['view'],
    'days'
);
check(
    'm opens on the months',
    dashboard_convert_preset_to_graph('bargraph', ['feedid' => '7', 'interval' => 'm'], $now)['options']['view'],
    'months'
);
check(
    'y opens on the years',
    dashboard_convert_preset_to_graph('bargraph', ['feedid' => '7', 'interval' => 'y'], $now)['options']['view'],
    'years'
);

$scaled = dashboard_convert_preset_to_graph(
    'bargraph',
    ['feedid' => '7', 'scale' => '0.001', 'units' => 'kWh', 'dp' => '2', 'initzoom' => '30'],
    $now
);
check('the scale carries over', $scaled['options']['scale'], '0.001');
check(
    'units, dp and initzoom do not, and are counted',
    $scaled['dropped'],
    ['units' => 1, 'dp' => 1, 'initzoom' => 1]
);
check(
    'the colour it drew in when none was set',
    dashboard_convert_preset_to_graph('bargraph', ['feedid' => '7'], $now)['options']['colour'],
    '0096ff'
);
check(
    'a feed given as an association is passed through',
    dashboard_convert_preset_to_graph('bargraph', ['feedid' => 'house:use_kwh'], $now)['options']['kwhd'],
    'house:use_kwh'
);
$nofeed = dashboard_convert_preset_to_graph('bargraph', [], $now);
check(
    'a bargraph with no feed is a zoom widget with none, and is counted',
    [$nofeed['options']['kwhd'], $nofeed['dropped']],
    ['', ['widget_without_feed' => 1]]
);

// A fixed step in seconds is not a chart of days, so it stays a graph config.
$hourly = dashboard_convert_preset_to_graph('bargraph', ['feedid' => '7', 'interval' => '3600', 'delta' => '1'], $now);
check('any other interval is a graph config', $hourly['type'], 'graph');
check('at a fixed step', $hourly['config']['state']['mode'], 'interval');
check('at the seconds it was given', $hourly['config']['state']['interval'], '3600');
check('and the step is held there', $hourly['config']['state']['fixinterval'], '1');
check(
    'drawn as filled bars',
    [$hourly['config']['feedlist'][0]['plottype'], $hourly['config']['feedlist'][0]['fill']],
    ['bars', '1']
);
check('with the delta', $hourly['config']['feedlist'][0]['delta'], '1');
check('keeping the gaps', $hourly['config']['state']['showmissing'], '1');

// The mode option says daily. bargraph.php never read it, so the interval is
// what decides when the two disagree.
check(
    'mode daily with no interval is a zoom widget',
    dashboard_convert_preset_to_graph('bargraph', ['feedid' => '7', 'mode' => 'daily'], $now)['type'],
    'zoom'
);
$clash = dashboard_convert_preset_to_graph(
    'bargraph',
    ['feedid' => '7', 'mode' => 'daily', 'interval' => '3600'],
    $now
);
check(
    'mode daily against an hourly interval draws hourly',
    $clash['config']['state']['mode'],
    'interval'
);
check(
    'and is counted so it can be looked at',
    $clash['dropped'],
    ['mode_overruled_by_interval' => 1]
);

// stacked is two feeds of daily kWh, added into months and drawn on top of
// each other.
$stack = dashboard_convert_preset_to_graph('stacked', [
    'bottom' => '3', 'top' => '4', 'colourb' => 'aabbcc', 'colourt' => '112233', 'delta' => '1',
], $now);
check('stacked carries both feeds', count($stack['config']['feedlist']), 2);
check('the bottom feed is first', $stack['config']['feedlist'][0]['id'], '3');
check('with the bottom colour', $stack['config']['feedlist'][0]['color'], '#aabbcc');
check('the top feed is second', $stack['config']['feedlist'][1]['id'], '4');
check('with the top colour', $stack['config']['feedlist'][1]['color'], '#112233');
check(
    'both are stacked',
    [$stack['config']['feedlist'][0]['stack'], $stack['config']['feedlist'][1]['stack']],
    ['1', '1']
);
check(
    'both take the delta',
    [$stack['config']['feedlist'][0]['delta'], $stack['config']['feedlist'][1]['delta']],
    ['1', '1']
);
check('a month at a time', $stack['config']['state']['mode'], 'monthly');
check(
    'over five years',
    (int) $stack['config']['state']['end'] - (int) $stack['config']['state']['start'],
    5 * 365 * $day
);
$bare_stack = dashboard_convert_preset_to_graph('stacked', ['bottom' => '3', 'top' => '4'], $now);
check(
    'the colours it drew when neither was set',
    [$bare_stack['config']['feedlist'][0]['color'], $bare_stack['config']['feedlist'][1]['color']],
    ['#0096ff', '#7cc9ff']
);

// simplezoom was the zoom pair without the cost, and takes the same three
// options on the zoom widget.
$simple = dashboard_convert_preset_to_graph(
    'simplezoom',
    ['power' => '1', 'kwhd' => 'house:use_kwh', 'delta' => '1'],
    $now
);
check('a simplezoom becomes a zoom widget', $simple['type'], 'zoom');
check(
    'with its feeds and delta',
    $simple['options'],
    ['power' => '1', 'kwhd' => 'house:use_kwh', 'delta' => '1']
);
check('and nothing else set', isset($simple['config']), false);
check(
    'one with no daily feed is counted',
    dashboard_convert_preset_to_graph('simplezoom', ['power' => '1'], $now)['dropped'],
    ['widget_without_feed' => 1]
);

// smoothie was a scrolling live window of one feed, which realtime draws.
$smooth = dashboard_convert_preset_to_graph('smoothie', ['feedid' => '9', 'ufac' => '0.5'], $now);
check('a smoothie becomes a realtime widget', $smooth['type'], 'realtime');
check(
    'on the fifteen minute window, green on black as it drew, with white axes',
    $smooth['options'],
    ['feedid' => '9', 'initzoom' => '15',
        'colour' => '00ff00',
        'colourbg' => '000000',
        'colouraxis' => 'ffffff'
    ]
);
check('ufac is dropped and counted', $smooth['dropped'], ['ufac' => 1]);
check(
    'one with no feed is counted',
    dashboard_convert_preset_to_graph('smoothie', [], $now)['dropped'],
    ['widget_without_feed' => 1]
);

// stackedsolar works out import and export from the two feeds it is given,
// which a graph config has no way to say, so it is not one of these.
check(
    'stackedsolar is not converted here',
    dashboard_convert_preset_to_graph('stackedsolar', ['solar' => '1', 'consumption' => '2'], $now),
    null
);
check(
    'nor is a widget that is not a chart',
    dashboard_convert_preset_to_graph('dial', ['feedid' => '1'], $now),
    null
);

// A converted widget has to survive the validation the dashboard does on the
// way out, otherwise the chart is dropped before it reaches the page.
$document = ['version' => 1, 'widgets' => [
    ['type' => 'rawdata', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
        'wunit' => 'px', 'hunit' => 'px', 'options' => ['feedid' => '12', 'units' => 'W']
    ],
    ['type' => 'bargraph', 'x' => 0, 'y' => 300, 'w' => 400, 'h' => 300,
        'wunit' => 'px', 'hunit' => 'px', 'options' => ['feedid' => '7', 'interval' => 'y']
    ],
    ['type' => 'stacked', 'x' => 0, 'y' => 600, 'w' => 400, 'h' => 300,
        'wunit' => 'px', 'hunit' => 'px', 'options' => ['bottom' => '3', 'top' => '4']
    ],
    ['type' => 'dial', 'x' => 0, 'y' => 900, 'w' => 100, 'h' => 100,
        'wunit' => 'px', 'hunit' => 'px', 'options' => ['feedid' => '1']
    ],
]
];

$counts = [];
$rewritten = preset_rewrite($document, $counts, $now);
check(
    'every chart widget becomes the widget it maps to',
    [$rewritten['widgets'][0]['type'], $rewritten['widgets'][1]['type'],
        $rewritten['widgets'][2]['type']
    ],
    ['graph', 'zoom', 'graph']
);
check(
    'a zoom widget carries no config',
    isset($rewritten['widgets'][1]['config']),
    false
);
check('a widget that is not one is left alone', $rewritten['widgets'][3]['type'], 'dial');
check(
    'each type is counted',
    [$counts['converted_rawdata'], $counts['converted_bargraph'], $counts['converted_stacked']],
    [1, 1, 1]
);
check(
    'the chart goes in the config block',
    $rewritten['widgets'][0]['config']['feedlist'][0]['id'],
    '12'
);
check(
    'and the colour behind it in the options',
    $rewritten['widgets'][0]['options'],
    ['colourbg' => 'ffffff']
);

$rendered = dashboard_render($rewritten);
check('the rewritten document draws', $rendered['errors'], []);
check(
    'it draws two graph widgets and a zoom widget',
    [substr_count($rendered['html'], 'class="graph"'), substr_count($rendered['html'], 'class="zoom"')],
    [2, 1]
);
check(
    'the annual bargraph opens the zoom widget on the years',
    substr_count($rendered['html'], 'view="years"'),
    1
);
check(
    'the feed lists reach the page',
    substr_count($rendered['html'], '&quot;feedlist&quot;'),
    2
);

$counts = [];
check(
    'a document with none of these is left alone',
    preset_rewrite(
        ['version' => 1, 'widgets' => [
            ['type' => 'dial', 'x' => 0, 'y' => 0, 'w' => 1, 'h' => 1, 'options' => []]
        ]
        ],
        $counts
    ),
    null
);

// ---------------------------------------------------------------------------
// The text widget, which takes options instead of html
// ---------------------------------------------------------------------------

// The wrapper is drawn by the render script, so the body is read through it
// and never stored. See text_wrapper in widget/text/text_render.js.
$html = '<div id="1" class="text" style="position:absolute; margin: 0; top:20px; '
    . 'left:40px; width:120px; height:40px;" size="18" colour="ff0000" weight="bold" '
    . 'font="Arial" align="center" valign="bottom" rotate="-90">'
    . '<div class="text-content">Power in m<sub>3</sub></div></div>';

$result = convert($html);
$widget = widgets($result)[0];

check('text type', $widget['type'], 'text');
check(
    'text geometry',
    [$widget['x'], $widget['y'], $widget['w'], $widget['h']],
    [40, 20, 120, 40]
);
check('text options', $widget['options'], ['size' => '18', 'colour' => 'ff0000',
    'weight' => 'bold', 'font' => 'Arial', 'align' => 'center', 'valign' => 'bottom',
    'rotate' => '-90'
]);
check('text body read through the wrapper', $widget['text'], 'Power in m<sub>3</sub>');
check('text keeps no html field', isset($widget['html']), false);
check('text keeps no box style', isset($widget['style']), false);
check('text is clean', codes($result), []);

// A widget saved before its render script drew the wrapper
$result = convert('<div id="1" class="text" style="position:absolute; top:0px; '
    . 'left:0px; width:10px; height:10px;">bare</div>');
check('text body without a wrapper', widgets($result)[0]['text'], 'bare');

// The element list is narrower than the html one. Block elements are
// unwrapped and their text is kept, the same as any other tag not in the list.
$result = convert('<div id="1" class="text" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;"><div class="text-content">See '
    . '<a href="https://example.com/x">this</a> <b>now</b><i>i</i><u>u</u><br>'
    . '<script>alert(1)</script><p>block</p><span style="font-size:99px">s</span>'
    . '<img src="https://example.com/a.png"></div></div>');
$widget = widgets($result)[0];

check(
    'text keeps the inline subset',
    $widget['text'],
    'See <a href="https://example.com/x">this</a> <b>now</b><i>i</i><u>u</u><br>blocks'
);
check(
    'text drops a script and unwraps the rest',
    codes($result),
    ['tag_dropped', 'tag_unwrapped', 'tag_unwrapped', 'tag_unwrapped']
);

// Styling is set with options, so a style attribute inside the text is
// dropped.
$result = convert('<div id="1" class="text" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;"><div class="text-content"><b style="color:red">x</b>'
    . '</div></div>');
check('style inside text is dropped', widgets($result)[0]['text'], '<b>x</b>');
check('style inside text is named', codes($result), ['attribute_dropped']);

// An option is refused when it is outside the range the declaration gives, or
// is not one of the values it lists.
$result = convert('<div id="1" class="text" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;" size="9999" rotate="abc" align="middle" valign="center" '
    . 'weight="heavy">x</div>');
check('text refuses options out of range', widgets($result)[0]['options'], []);
check('text names each refused option', count(codes($result)), 5);

foreach (['-180', '-90', '0', '180'] as $degrees) {
    $result = convert('<div id="1" class="text" style="position:absolute; top:0px; '
        . 'left:0px; width:10px; height:10px;" rotate="' . $degrees . '">x</div>');
    check(
        "rotate $degrees is accepted",
        widgets($result)[0]['options'],
        ['rotate' => $degrees]
    );
}
foreach (['181', '-181', '90.5', ' 90'] as $degrees) {
    $result = convert('<div id="1" class="text" style="position:absolute; top:0px; '
        . 'left:0px; width:10px; height:10px;" rotate="' . $degrees . '">x</div>');
    check("rotate $degrees is refused", widgets($result)[0]['options'], []);
}

// Only the body field the type declares is rendered.
$document = ['version' => 1, 'widgets' => [
    ['type' => 'text', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10,
        'options' => [], 'html' => '<p style="color:red">html</p>'
    ]
]
];
$rendered = dashboard_render($document);
check('html is refused on a text widget', strpos($rendered['html'], '<p') === false, true);
check('html on a text widget is named', $rendered['errors'][0]['code'], 'html_not_allowed_on_type');

$document = ['version' => 1, 'widgets' => [
    ['type' => 'paragraph', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10,
        'options' => [], 'text' => 'text'
    ]
]
];
$rendered = dashboard_render($document);
check(
    'text is refused on a widget that does not declare it',
    $rendered['errors'][0]['code'],
    'text_not_allowed_on_type'
);

// Text is checked on render as well as on conversion.
$document = ['version' => 1, 'widgets' => [
    ['type' => 'text', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10,
        'options' => [], 'text' => '<img src="x" onerror="alert(1)"><b>ok</b>'
    ]
]
];
$rendered = dashboard_render($document);
check(
    'text is held to the vocabulary on the way out',
    dashboard_test_attributes($rendered['html']),
    ['id', 'class', 'style']
);

// Converting what was rendered gives the same document back
$html = '<div id="1" class="text" style="position:absolute; margin: 0; top:20px; '
    . 'left:40px; width:120px; height:40px;" size="18" align="center">m<sub>3</sub></div>';
$once = convert($html);
$twice = convert(render($once));
check('text is a fixed point', widgets($twice), widgets($once));

// ---------------------------------------------------------------------------
// The image widget
// ---------------------------------------------------------------------------

// The img drawn by the render script is generated, the same as a canvas, so
// no warning is raised.
$html = '<div id="1" class="image" style="position:absolute; margin: 0; top:0px; '
    . 'left:0px; width:120px; height:120px;" src="https://example.com/a.png" '
    . 'alt="A diagram" fit="cover" link="https://example.com/">'
    . '<img src="https://example.com/a.png" alt="A diagram"></div>';

$result = convert($html);
$widget = widgets($result)[0];

check('image options', $widget['options'], ['src' => 'https://example.com/a.png',
    'alt' => 'A diagram', 'fit' => 'cover', 'link' => 'https://example.com/'
]);
check('image keeps no body', isset($widget['html']) || isset($widget['text']), false);
check('the drawn img is not a warning', codes($result), []);

$result = convert('<div id="1" class="image" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;" src="javascript:alert(1)" link="data:text/html,x" '
    . 'fit="wobble"></div>');
check('image refuses a url that is not http', widgets($result)[0]['options'], []);
check('image names each refused option', count(codes($result)), 3);

$result = convert('<div id="1" class="image" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;" alt="&lt;b&gt;x&lt;/b&gt;"></div>');
check(
    'image alt holding a tag keeps its words',
    widgets($result)[0]['options'],
    ['alt' => 'x']
);

$once = convert($html);
$twice = convert(render($once));
check('image is a fixed point', widgets($twice), widgets($once));

// ---------------------------------------------------------------------------
// The url rules an image widget is held to
//
// The editor mirrors these rules in url_problem in designer.js. A change here
// needs the same change there.
// ---------------------------------------------------------------------------

$was_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
$_SERVER['HTTP_HOST'] = 'dash.example.org';

$src_cases = [
    'https://example.com/a.png' => true,
    'http://example.com/a.png' => true,
    '//example.com/a.png' => true,
    'javascript:alert(1)' => false,
    'data:image/png;base64,AAA' => false,
    'images/logo.png' => false,
    'Modules/dashboard/Views/images/logo.png' => true,
    '/Modules/dashboard/Views/images/logo.png' => true,
    'Modules/dashboard/Views/images/logo.txt' => false,
    'Modules/dashboard/Views/images/../../x.png' => false,
    'Modules/dashboard/Views/images/logo.png?v=1' => false,
    'http://dash.example.org/Modules/dashboard/Views/images/logo.png' => true,
    'http://dash.example.org/feed/list.json' => false,
    'http://dash.example.org./feed/list.json' => false,
];
foreach ($src_cases as $url => $want) {
    check("image src $url", dashboard_convert_url_allowed($url, 'src'), $want);
}

$href_cases = [
    'https://example.com/' => true,
    '/dashboard/view?id=2' => false,
    'http://dash.example.org/x' => false,
];
foreach ($href_cases as $url => $want) {
    check("image link $url", dashboard_convert_url_allowed($url, 'href'), $want);
}

// The option types the widget declares are what carry those rules
$result = convert('<div id="1" class="image" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;" src="images/logo.png" link="/dashboard/view?id=2"></div>');
check(
    'image refuses a src and a link pointing back at this site',
    widgets($result)[0]['options'],
    []
);

if ($was_host === null) {
    unset($_SERVER['HTTP_HOST']);
} else {
    $_SERVER['HTTP_HOST'] = $was_host;
}

// ---------------------------------------------------------------------------
// Stage 4: the old text widgets converted to the new ones
//
// A conversion either keeps the same meaning or is refused, so each case
// checks one of the two.
// ---------------------------------------------------------------------------

// Converts an old widget written as html and returns the new widget, or the
// reason it was refused.
function convert_text($type, $html, $extra = [])
{
    $widget = $extra + ['type' => $type, 'x' => 10, 'y' => 20, 'w' => 100, 'h' => 60,
        'options' => []
    ];
    if ($html !== null) {
        $widget['html'] = $html;
    }

    $reason = '';
    $new = dashboard_convert_text_widget($widget, $reason);
    if ($new === null) {
        return 'refused ' . $reason;
    }

    unset($new['x'], $new['y'], $new['w'], $new['h']);
    return $new;
}

// A converted paragraph as a check expects it. A paragraph draws from the
// top, so every one carries valign top ahead of its other options.
function paragraph($options, $text = null)
{
    $widget = ['type' => 'text', 'options' => ['valign' => 'top'] + $options];
    if ($text !== null) {
        $widget['text'] = $text;
    }
    return $widget;
}

// The widget.css styling of an old widget is written out as options
check(
    'a paragraph carries only its top alignment',
    convert_text('paragraph', 'Some text'),
    paragraph([], 'Some text')
);

check(
    'a heading carries its size and weight',
    convert_text('heading', 'Title'),
    ['type' => 'text', 'options' => ['size' => '24', 'weight' => 'bold'],
        'text' => 'Title'
    ]
);

check(
    'a centred heading carries its alignment as well',
    convert_text('heading-center', 'Title'),
    ['type' => 'text',
        'options' => ['size' => '24', 'weight' => 'bold', 'align' => 'center'],
        'text' => 'Title'
    ]
);

check(
    'an empty widget converts to an empty text widget',
    convert_text('paragraph', ''),
    paragraph([])
);

check(
    'the geometry is the one the old widget had',
    dashboard_convert_text_widget(['type' => 'paragraph', 'x' => 5, 'y' => 6,
        'w' => 7, 'h' => 8, 'wunit' => 'pc', 'options' => [], 'html' => 'x'
    ]),
    ['type' => 'text', 'x' => 5, 'y' => 6, 'w' => 7, 'h' => 8, 'wunit' => 'pc',
        'options' => ['valign' => 'top'],
        'text' => 'x'
    ]
);

// A heading has 20px of top padding and the text widget centres, which draw
// the same only at the default box height.
check(
    'a heading at the default height converts',
    convert_text('heading', 'x', ['h' => 60]),
    ['type' => 'text', 'options' => ['size' => '24', 'weight' => 'bold'], 'text' => 'x']
);

check(
    'a heading at another height is refused',
    convert_text('heading', 'x', ['h' => 40]),
    'refused heading_height: 40px'
);

check(
    'a centred heading at another height is refused',
    convert_text('heading-center', 'x', ['h' => 100]),
    'refused heading_height: 100px'
);

check(
    'a heading with a per cent height is refused',
    convert_text('heading', 'x', ['h' => 60, 'hunit' => 'pc']),
    'refused heading_height: 60%'
);

check(
    'a paragraph converts at any height',
    convert_text('paragraph', 'x', ['h' => 300]),
    paragraph([], 'x')
);

// The styling is read from the elements inside the widget
check(
    'a span carries its size and colour',
    convert_text('paragraph', '<span style="font-size:20px;color:#ff0000">Hot</span>'),
    paragraph(['size' => '20', 'colour' => 'ff0000'], 'Hot')
);

check(
    'a font tag carries its three attributes',
    convert_text('paragraph', '<font size="5" color="red" face="Verdana">Big</font>'),
    paragraph(['size' => '24', 'colour' => 'ff0000', 'font' => 'Verdana'], 'Big')
);

check(
    'a centre tag is an alignment',
    convert_text('paragraph', '<center>Middle</center>'),
    paragraph(['align' => 'center'], 'Middle')
);

check(
    'a bold wrapping everything is a weight',
    convert_text('paragraph', '<b>All bold</b>'),
    paragraph(['weight' => 'bold'], 'All bold')
);

check(
    'the inside wins over what widget.css gave the box',
    convert_text('heading', '<span style="font-size:40px;font-weight:normal">Small</span>'),
    ['type' => 'text', 'options' => ['size' => '40', 'weight' => 'normal'],
        'text' => 'Small'
    ]
);

// The old widget turns an element the height of its text, the new one turns
// the box, so the text lands somewhere else.
check(
    'a rotation is refused',
    convert_text('paragraph', '<div style="transform:rotate(-90deg)">Side</div>'),
    'refused rotation_moves_text: rotate(-90deg)'
);

check(
    'pt converts to px',
    convert_text('paragraph', '<span style="font-size:18pt">x</span>'),
    paragraph(['size' => '24'], 'x')
);

check(
    'rgb converts to hex',
    convert_text('paragraph', '<span style="color:rgb(0, 128, 255)">x</span>'),
    paragraph(['colour' => '0080ff'], 'x')
);

check(
    'three hex digits become six',
    convert_text('paragraph', '<span style="color:#f00">x</span>'),
    paragraph(['colour' => 'ff0000'], 'x')
);

check(
    'a font family keeps the first name it offers',
    convert_text('paragraph', '<span style="font-family:Verdana, Geneva, sans-serif">x</span>'),
    paragraph(['font' => 'Verdana'], 'x')
);

// The editor and the browser wrote these, not the author
check(
    'vertical-align inherit is passed over',
    convert_text('paragraph', '<span style="vertical-align:inherit;user-select:text">x</span>'),
    paragraph([], 'x')
);

// The inline elements stay as markup
check(
    'the inline vocabulary is kept as text',
    convert_text('paragraph', 'P<sub>L1</sub>: <b>now</b><br>next'),
    paragraph([], 'P<sub>L1</sub>: <b>now</b><br>next')
);

check(
    'strong and em are spelled b and i',
    convert_text('paragraph', '<em>it</em> and <strong>bold</strong>'),
    paragraph([], '<i>it</i> and <b>bold</b>')
);

check(
    'a link inside the text keeps its href',
    convert_text('paragraph', 'see <a href="https://example.com/x">this</a>'),
    paragraph([], 'see <a href="https://example.com/x">this</a>')
);

check(
    'a link around the whole widget becomes one inside it',
    convert_text('paragraph', '<a href="https://example.com/x">all of it</a>'),
    paragraph([], '<a href="https://example.com/x">all of it</a>')
);

// An image and no text is the image widget
check(
    'an image with no text is an image widget',
    convert_text('paragraph', '<img src="https://example.com/a.png" alt="a">'),
    ['type' => 'image',
        'options' => ['src' => 'https://example.com/a.png', 'fit' => 'contain',
            'alt' => 'a'
        ]
    ]
);

check(
    'a linked image carries the link',
    convert_text(
        'paragraph',
        '<a href="https://example.com"><img src="https://example.com/a.png"></a>'
    ),
    ['type' => 'image',
        'options' => ['src' => 'https://example.com/a.png', 'fit' => 'contain',
            'link' => 'https://example.com'
        ]
    ]
);

// Refused when a value cannot be carried
$refusals = [
    'blocks' => ['paragraph', '<p>one</p><p>two</p>', 'tag_not_in_vocabulary: p'],
    'a table' => ['paragraph', '<table><tr><td>x</td></tr></table>',
        'tag_not_in_vocabulary: table'
    ],
    'a stylesheet' => ['paragraph', '<style>.x{}</style>hi',
        'tag_not_in_vocabulary: style'
    ],
    'an iframe' => ['paragraph', '<iframe src="https://example.com"></iframe>',
        'tag_not_in_vocabulary: iframe'
    ],
    'a heading tag' => ['paragraph', '<h2>Big</h2>', 'tag_not_in_vocabulary: h2'],
    'a superscript' => ['paragraph', 'm<sup>3</sup>', 'tag_not_in_vocabulary: sup'],
    'a line height' => ['paragraph', '<div style="line-height:2">x</div>',
        'style_not_carried: line-height'
    ],
    'a background' => ['paragraph', '<div style="background-color:#eee">x</div>',
        'style_not_carried: background-color'
    ],
    'padding' => ['paragraph', '<div style="padding-top:20px">x</div>',
        'style_not_carried: padding-top'
    ],
    'an em font size' => ['paragraph', '<span style="font-size:1.4em">x</span>',
        'font_size_not_carried: 1.4em'
    ],
    'a relative font size' => ['paragraph', '<font size="+2">x</font>',
        'font_size_not_carried: size=+2'
    ],
    'a colour it cannot name' => ['paragraph',
        '<span style="color:lightgoldenrodyellow">x</span>',
        'colour_not_carried: lightgoldenrodyellow'
    ],
    'a font it does not offer' => ['paragraph',
        '<span style="font-family:Papyrus">x</span>', 'font_not_carried: Papyrus'
    ],
    'a justified alignment' => ['paragraph',
        '<div style="text-align:justify">x</div>', 'align_not_carried: justify'
    ],
    'a fraction of a degree' => ['paragraph',
        '<div style="transform:rotate(-70.5deg)">x</div>',
        'rotation_moves_text: rotate(-70.5deg)'
    ],
    'a transform that is not a rotation' => ['paragraph',
        '<div style="transform:scale(2)">x</div>', 'transform_not_carried: scale(2)'
    ],
    'a link it would drop' => ['paragraph',
        'see <a href="javascript:x">this</a>', 'link_url_not_allowed: javascript:x'
    ],
    'an image it would drop' => ['paragraph', '<img src="javascript:x">',
        'image_url_not_allowed: javascript:x'
    ],
];
foreach ($refusals as $name => $case) {
    check("refuses $name", convert_text($case[0], $case[1]), 'refused ' . $case[2]);
}

// There is no font-style option, so an italic stays as markup.
check(
    'an italic is kept as markup',
    convert_text('paragraph', '<i>all italic</i>'),
    paragraph([], '<i>all italic</i>')
);

check(
    'refuses a widget with box styling, which has nowhere to go',
    convert_text('paragraph', 'x', ['style' => ['border' => '1px solid #000']]),
    'refused box_style'
);

check(
    'refuses a type that is not one of the three',
    convert_text('text', 'x'),
    'refused not_an_old_text_widget'
);

check(
    'refuses a size outside the range the option allows',
    convert_text('paragraph', '<span style="font-size:400px">x</span>'),
    'refused font_size_not_carried: 400px'
);

// The result must be storable and renderable
$new = convert_text('paragraph', '<span style="font-size:20px;color:#ff0000">Hot</span>');
$errors = [];
check(
    'what comes back renders with its options on the box',
    dashboard_render_widget(
        $new + ['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10],
        0,
        widget_registry(),
        $errors
    ),
    '<div id="1" class="text" style="position:absolute; margin: 0; top:0px; left:0px; '
    . 'width:10px; height:10px;" valign="top" size="20" colour="ff0000">Hot</div>'
);
check('and raises nothing on the way out', $errors, []);

// ---------------------------------------------------------------------------
// Stage 5. Converting an old container to a panel
// ---------------------------------------------------------------------------

function sorted($options)
{
    ksort($options);
    return $options;
}

function convert_panel($type, $style = [], $extra = [])
{
    $widget = ['type' => $type, 'x' => 10, 'y' => 20, 'w' => 100, 'h' => 60,
        'options' => []
    ] + $extra;
    if (count($style)) {
        $widget['style'] = $style;
    }

    $reason = '';
    $new = dashboard_convert_panel_widget($widget, $reason);
    if ($new === null) {
        return 'refused ' . $reason;
    }

    // Sorted so a test can name the options in any order
    ksort($new['options']);
    return $new['options'];
}

$white = ['bordercolour' => 'e5e5e5', 'borderwidth' => '1', 'colour' => 'ffffff',
    'opacity' => '100', 'radius' => '0', 'shadow' => 'drop'
];

// The widget.css styling of each container is written out as options
check('a white container carries its look', convert_panel('Container-White'), $white);
check(
    'a grey container carries its look',
    convert_panel('Container-Grey'),
    sorted(['colour' => 'dddddd', 'bordercolour' => 'cccccc'] + $white)
);
check(
    'a black container carries its look',
    convert_panel('Container-Black'),
    sorted(['colour' => '000000', 'bordercolour' => '888888'] + $white)
);
check(
    'a blue line container is clear with a glow',
    convert_panel('Container-BlueLine'),
    sorted(['colour' => 'ffffff', 'opacity' => '0', 'bordercolour' => '0d97f3',
        'borderwidth' => '3',
        'radius' => '0',
        'shadow' => 'glow'
    ])
);

$kept = dashboard_convert_panel_widget(['type' => 'Container-White', 'x' => 5, 'y' => 6,
    'w' => 50, 'h' => 40, 'wunit' => 'pc', 'options' => []
]);
ksort($kept['options']);
check(
    'the geometry is kept',
    $kept,
    ['type' => 'panel', 'x' => 5, 'y' => 6, 'w' => 50, 'h' => 40, 'wunit' => 'pc',
        'options' => $white
    ]
);

// Box styling an author put over the class
check(
    'a background colour is carried',
    convert_panel('Container-White', ['background-color' => '#ff0000']),
    sorted(['colour' => 'ff0000'] + $white)
);
check(
    'the widget.css background shorthand is read',
    convert_panel('Container-White', ['background' => 'none repeat scroll 0 0 #DDD']),
    sorted(['colour' => 'dddddd'] + $white)
);
check(
    'a clear background is an opacity of 0',
    convert_panel('Container-Grey', ['background' => 'transparent']),
    sorted(['colour' => 'dddddd', 'bordercolour' => 'cccccc', 'opacity' => '0'] + $white)
);
check(
    'a border shorthand is carried',
    convert_panel('Container-White', ['border' => '2px solid red']),
    sorted(['borderwidth' => '2', 'bordercolour' => 'ff0000'] + $white)
);
check(
    'a border with no style draws nothing',
    convert_panel('Container-White', ['border' => '2px #ff0000']),
    sorted(['borderwidth' => '0'] + $white)
);
check(
    'border none is a width of 0',
    convert_panel('Container-White', ['border' => 'none']),
    sorted(['borderwidth' => '0'] + $white)
);
check(
    'border longhands are carried',
    convert_panel('Container-White', ['border-width' => '4px', 'border-color' => '#123456',
        'border-style' => 'solid'
    ]),
    sorted(['borderwidth' => '4', 'bordercolour' => '123456'] + $white)
);
check(
    'a radius is carried',
    convert_panel('Container-White', ['border-radius' => '10px']),
    sorted(['radius' => '10'] + $white)
);
check(
    'the glow shadow is recognised',
    convert_panel('Container-White', ['box-shadow' => '0px 0px 2px 2px rgba(200, 200, 200, 0.7)']),
    sorted(['shadow' => 'glow'] + $white)
);
check(
    'no shadow is recognised',
    convert_panel('Container-White', ['box-shadow' => 'none']),
    sorted(['shadow' => 'none'] + $white)
);
check(
    'a zero padding is passed over',
    convert_panel('Container-White', ['padding' => '0px']),
    $white
);

// What cannot be carried
check(
    'refuses a container holding html',
    convert_panel('Container-White', [], ['html' => '<table><tr><td>x</td></tr></table>']),
    'refused holds_html'
);
check(
    'an empty html field is not content',
    convert_panel('Container-White', [], ['html' => ' ']),
    $white
);
check(
    'refuses a type that is not a container',
    convert_panel('panel'),
    'refused not_an_old_container'
);
check(
    'refuses a container someone made up',
    convert_panel('Container-red'),
    'refused not_an_old_container'
);
check(
    'refuses a background image',
    convert_panel('Container-White', ['background' => 'url(https://example.com/a.png)']),
    'refused background_not_carried: url(https://example.com/a.png)'
);
check(
    'refuses a dashed border',
    convert_panel('Container-White', ['border' => '1px dashed #000']),
    'refused border_not_carried: 1px dashed #000'
);
check(
    'refuses a border wider than the option allows',
    convert_panel('Container-White', ['border-width' => '30px']),
    'refused border_not_carried: 30px'
);
check(
    'refuses a radius in per cent',
    convert_panel('Container-White', ['border-radius' => '50%']),
    'refused radius_not_carried: 50%'
);
check(
    'refuses a shadow of the author\'s own',
    convert_panel('Container-White', ['box-shadow' => '2px 2px 4px #000']),
    'refused shadow_not_carried: 2px 2px 4px #000'
);
check(
    'refuses an opacity on the box',
    convert_panel('Container-White', ['opacity' => '0.5']),
    'refused style_not_carried: opacity'
);
check(
    'refuses a padding',
    convert_panel('Container-White', ['padding' => '10px']),
    'refused style_not_carried: padding'
);

// The result must be storable and renderable
$new = dashboard_convert_panel_widget(['type' => 'Container-BlueLine', 'x' => 0,
    'y' => 0, 'w' => 10, 'h' => 10, 'options' => []
]);
$errors = [];
check(
    'a panel renders with its options on the box',
    dashboard_render_widget($new, 0, widget_registry(), $errors),
    '<div id="1" class="panel" style="position:absolute; margin: 0; top:0px; left:0px; '
    . 'width:10px; height:10px;" colour="ffffff" opacity="0" bordercolour="0d97f3" '
    . 'borderwidth="3" radius="0" shadow="glow"></div>'
);
check('and raises nothing on the way out', $errors, []);

// A panel saved by the designer converts like any other option widget
$result = convert('<div id="1" class="panel" style="position:absolute; margin: 0; top:0px; '
    . 'left:0px; width:10px; height:10px; background-color: rgba(255, 255, 255, 1); '
    . 'border: 1px solid rgb(229, 229, 229);" colour="ffffff" opacity="100" '
    . 'bordercolour="e5e5e5" borderwidth="1" radius="8" shadow="drop"></div>');
$widget = widgets($result)[0];
check('a saved panel keeps its options', $widget['options'], ['colour' => 'ffffff',
    'opacity' => '100', 'bordercolour' => 'e5e5e5', 'borderwidth' => '1', 'radius' => '8',
    'shadow' => 'drop'
]);
check('a saved panel drops the drawn box style', isset($widget['style']), false);
check('a saved panel holds no html', isset($widget['html']), false);
check(
    'a panel refuses an option out of range',
    widgets(convert('<div id="1" class="panel" style="position:absolute; top:0px; left:0px; '
    . 'width:10px; height:10px;" radius="500" shadow="big"></div>'))[0]['options'],
    []
);

// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Replacing the old widgets of a document
// ---------------------------------------------------------------------------

$document = ['version' => 1, 'widgets' => [
    ['type' => 'paragraph', 'x' => 1, 'y' => 2, 'w' => 100, 'h' => 60,
        'options' => [], 'html' => 'plain'
    ],
    ['type' => 'dial', 'x' => 1, 'y' => 2, 'w' => 100, 'h' => 60,
        'options' => ['feedid' => '1']
    ],
    ['type' => 'heading', 'x' => 1, 'y' => 2, 'w' => 100, 'h' => 90,
        'options' => [], 'html' => 'tall'
    ],
    ['type' => 'Container-Grey', 'x' => 1, 'y' => 2, 'w' => 100, 'h' => 60,
        'options' => [], 'html' => ''
    ],
    ['type' => 'Container-White', 'x' => 1, 'y' => 2, 'w' => 100, 'h' => 60,
        'options' => [], 'html' => '<table><tr><td>x</td></tr></table>'
    ],
    ['type' => 'paragraph', 'x' => 1, 'y' => 2, 'w' => 100, 'h' => 60,
        'options' => [], 'html' => '<p>one</p><p style="color:red">two</p>'
    ]
]
];

$kept = [];
$swapped = dashboard_migrate_widgets($document, $kept);

check(
    'the widgets the converters accept are replaced',
    $swapped,
    [['index' => 0, 'from' => 'paragraph', 'to' => 'text'],
        ['index' => 3, 'from' => 'Container-Grey', 'to' => 'panel']
    ]
);

check(
    'the ones they refuse are kept with the reason',
    $kept,
    [['index' => 2, 'type' => 'heading', 'reason' => 'heading_height: 90px'],
        ['index' => 4, 'type' => 'Container-White', 'reason' => 'holds_html'],
        ['index' => 5, 'type' => 'paragraph', 'reason' => 'tag_not_in_vocabulary: p']
    ]
);

check(
    'the document is changed in place',
    array_map(function ($w) {
        return $w['type'];
    }, $document['widgets']),
    ['text', 'dial', 'heading', 'panel', 'Container-White', 'paragraph']
);

check(
    'a replaced widget keeps its place and geometry',
    $document['widgets'][0],
    ['type' => 'text', 'x' => 1, 'y' => 2, 'w' => 100, 'h' => 60,
        'options' => ['valign' => 'top'],
        'text' => 'plain'
    ]
);

check(
    'a widget that is not old is left alone',
    $document['widgets'][1],
    ['type' => 'dial', 'x' => 1, 'y' => 2, 'w' => 100, 'h' => 60,
        'options' => ['feedid' => '1']
    ]
);

check(
    'the summary counts by old and new type',
    dashboard_migrate_summary($swapped),
    'paragraph to text: 1, Container-Grey to panel: 1'
);

$nothing = ['version' => 1, 'widgets' => [
    ['type' => 'dial', 'options' => []]
]
];
check(
    'a document with no old widget is untouched',
    dashboard_migrate_widgets($nothing, $kept),
    []
);
check('and keeps nothing', $kept, []);

$broken = 'not a document';
check(
    'something that is not a document is passed over',
    dashboard_migrate_widgets($broken, $kept),
    []
);

// ---------------------------------------------------------------------------
// Colours
// ---------------------------------------------------------------------------

// The designer writes none for a colour picker left with no colour at all,
// which is a background that shows the dashboard through.
$html = '<div id="1" class="realtime" style="position:absolute; top:0px; left:0px; '
    . 'width:400px; height:300px;" feedid="7" colourbg="none" colouraxis="ddd"></div>';
$converted = dashboard_convert($html);
check(
    'none is kept as a colour option',
    [$converted['warnings'], $converted['document']['widgets'][0]['options']],
    [[], ['feedid' => '7', 'colourbg' => 'none', 'colouraxis' => 'ddd']]
);
$rendered = dashboard_render($converted['document']);
check(
    'and reaches the page',
    [$rendered['errors'], strpos($rendered['html'], 'colourbg="none"') !== false],
    [[], true]
);
check(
    'anything else that is not a colour is still dropped',
    codes(convert('<div id="1" class="realtime" style="position:absolute; top:0px; left:0px; '
        . 'width:400px; height:300px;" feedid="7" colourbg="nonsense"></div>')),
    ['option_value_dropped']
);

// ---------------------------------------------------------------------------
// Replacing the chart widgets of the vis module
// ---------------------------------------------------------------------------

// The multigraph table is read through a loader the caller passes in. Here it
// holds one row.
$rows = ['3' => dashboard_convert_multigraph_row(
    'Solar',
    '[{"id":"12","lineColour":"00ff00","backgroundColour":"eeeeee"},{"id":"13","right":true}]',
    $now
)
];
$loader = function ($mid) use ($rows) {
    return isset($rows[$mid]) ? $rows[$mid] : null;
};

$document = ['version' => 1, 'widgets' => [
    ['type' => 'multigraph', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
        'options' => ['mid' => '3']
    ],
    ['type' => 'multigraph', 'x' => 0, 'y' => 300, 'w' => 400, 'h' => 300,
        'options' => ['mid' => '99']
    ],
    ['type' => 'rawdata', 'x' => 0, 'y' => 600, 'w' => 400, 'h' => 300,
        'options' => ['feedid' => '12', 'colour' => 'ff0000']
    ],
    ['type' => 'paragraph', 'x' => 0, 'y' => 900, 'w' => 100, 'h' => 60,
        'options' => [], 'html' => 'plain'
    ],
    ['type' => 'dial', 'x' => 0, 'y' => 1000, 'w' => 100, 'h' => 100,
        'options' => ['feedid' => '1']
    ],
]
];

$kept = [];
$swapped = dashboard_migrate_widgets(
    $document,
    $kept,
    ['multigraph' => $loader, 'now' => $now],
    dashboard_convert_chart_types()
);
check(
    'the chart widgets are replaced and the rest left alone',
    dashboard_migrate_summary($swapped),
    'multigraph to graph: 2, rawdata to graph: 1'
);
check('nothing was refused', $kept, []);
check(
    'a multigraph widget holds the chart of its row',
    [$document['widgets'][0]['type'], $document['widgets'][0]['options'],
        $document['widgets'][0]['config']['feedlist'][1]['yaxis']
    ],
    ['graph', ['colourbg' => 'eeeeee'], '2']
);
check(
    'one naming a row that is gone is an empty graph',
    [$document['widgets'][1]['type'], $document['widgets'][1]['options'],
        isset($document['widgets'][1]['config'])
    ],
    ['graph', [], false]
);
check(
    'a rawdata widget holds its feed',
    [$document['widgets'][2]['type'], $document['widgets'][2]['config']['feedlist'][0]['color']],
    ['graph', '#ff0000']
);
check(
    'a text widget is not touched when only the chart types are asked for',
    $document['widgets'][3]['type'],
    'paragraph'
);
$rendered = dashboard_render($document);
check('the migrated document draws', $rendered['errors'], []);

// Without a loader a multigraph widget is refused rather than emptied, and so
// is one whose read failed. A widget with no mid has nothing to read.
$document = ['version' => 1, 'widgets' => [
    ['type' => 'multigraph', 'x' => 0, 'y' => 0, 'w' => 400, 'h' => 300,
        'options' => ['mid' => '3']
    ],
    ['type' => 'multigraph', 'x' => 0, 'y' => 300, 'w' => 400, 'h' => 300,
        'options' => ['mid' => '']
    ],
]
];
$kept = [];
$swapped = dashboard_migrate_widgets($document, $kept, [], dashboard_convert_chart_types());
check(
    'with no loader the widget naming a row is kept',
    $kept,
    [['index' => 0, 'type' => 'multigraph', 'reason' => 'no_multigraph_loader']]
);
check(
    'and the widget naming nothing becomes an empty graph',
    [$swapped, $document['widgets'][1]['type']],
    [[['index' => 1, 'from' => 'multigraph', 'to' => 'graph']], 'graph']
);

$failing = function ($mid) {
    return false;
};
$reason = '';
check(
    'a read that failed refuses the widget',
    [dashboard_convert_chart_widget(
        ['type' => 'multigraph', 'options' => ['mid' => '3']],
        $reason,
        ['multigraph' => $failing]
    ), $reason
    ],
    [null, 'dashboard_convert_multigraph_not_read']
);

$reason = '';
check(
    'a widget that is not a chart is refused',
    [dashboard_convert_chart_widget(['type' => 'dial', 'options' => []], $reason), $reason],
    [null, 'not_a_chart']
);

// The whole path a stored dashboard takes on load: html with the iframes the
// vis widgets built, to a document, to graph widgets.
$html = '<div id="1" class="multigraph" style="position:absolute; top:0px; left:0px; '
    . 'width:400px; height:300px;" mid="3"><iframe src="/vis/multigraph?embed=1&mid=3"></iframe></div>'
    . '<div id="2" class="bargraph" style="position:absolute; top:300px; left:0px; '
    . 'width:400px; height:300px;" feedid="7" interval="d"><iframe src="/vis/bargraph?embed=1"></iframe></div>';
$converted = dashboard_convert($html);
check(
    'the retired types are declared, so their options are kept',
    [$converted['warnings'], $converted['document']['widgets'][0]['options'],
        $converted['document']['widgets'][1]['options']['interval']
    ],
    [[], ['mid' => '3'], 'd']
);
$document = $converted['document'];
$kept = [];
dashboard_migrate_widgets($document, $kept, ['multigraph' => $loader], dashboard_convert_chart_types());
$html = dashboard_render($document)['html'];
check(
    'and they draw as a graph widget and a zoom widget',
    [$kept, substr_count($html, 'class="graph"'), substr_count($html, 'class="zoom"')],
    [[], 1, 1]
);
check(
    'the zoom widget keeps the view the step asked for',
    strpos($html, 'view="days"') !== false,
    true
);
check(
    'a retired type counts as one that drew an iframe',
    dashboard_convert_draws_iframe('multigraph', widget_registry()),
    true
);

// ---------------------------------------------------------------------------
// Widget ids, see the widget ids section of SCHEMA.md
// ---------------------------------------------------------------------------

function idbox($id, $type = 'feedvalue')
{
    $attr = $id === null ? '' : ' id="' . $id . '"';
    return '<div' . $attr . ' class="' . $type . '" style="position:absolute; margin:0; '
        . 'top:0px; left:0px; width:100px; height:60px;" feedid="1"></div>';
}

function ids($result)
{
    return array_column($result['document']['widgets'], 'id');
}

// Stored html carries the designer's counter, which collides, so the
// migration numbers from the index and reports nothing.
$result = convert(idbox(17) . idbox(17) . idbox('x'));
check('html conversion numbers ids from the index', ids($result), [1, 2, 3]);
check('html conversion writes the counter', $result['document']['next_id'], 4);
check('html conversion writes version 2', $result['document']['version'], DASHBOARD_DOCUMENT_VERSION);
check('html conversion raises nothing for the ids', codes($result), []);
check('id is the first field of a widget', array_keys($result['document']['widgets'][0])[0], 'id');
check('an empty page has a counter', convert('')['document']['next_id'], 1);

// The editor posts the document, which the clean step checks with the rules
// the renderer draws by, see notes/EDITOR.md.
function cleanbox($id, $extra = [])
{
    return $extra + ['id' => $id, 'type' => 'feedvalue', 'x' => 0, 'y' => 0,
        'w' => 100, 'h' => 60, 'options' => ['feedid' => '1']
    ];
}

function clean($widgets, $extra = [], &$errors = null)
{
    $errors = [];
    return dashboard_render_clean($extra + ['version' => 2, 'next_id' => 1,
        'widgets' => $widgets
    ], $errors);
}

$clean = clean([cleanbox(5), cleanbox(9)], [], $errors);
check('clean keeps the ids', array_column($clean['widgets'], 'id'), [5, 9]);
check('clean sets the counter above the largest id', $clean['next_id'], 10);
check(
    'clean holds version, counter and widgets only',
    array_keys($clean),
    ['version', 'next_id', 'widgets']
);
check('clean raises nothing for a good document', $errors, []);

$clean = clean([cleanbox(5), cleanbox(9)], ['next_id' => 20]);
check('a counter above the ids is kept', $clean['next_id'], 20);

$clean = clean([cleanbox(5), cleanbox(5)], [], $errors);
check('a duplicate id is replaced', array_column($clean['widgets'], 'id'), [5, 6]);
check('and reported', array_column($errors, 'code'), ['widget_id_invalid']);

$clean = clean([cleanbox(3, ['options' => ['feedid' => '1', 'bad' => 'x', 'units' => '<b>']])], [], $errors);
check('clean keeps only declared options with good values', $clean['widgets'][0]['options'], ['feedid' => '1']);
check('and reports each drop', array_column($errors, 'code'), ['option_unknown_dropped', 'option_value_dropped']);

$clean = clean([cleanbox(1, ['type' => 'text', 'text' => 'a <script>x</script> b'])]);
check('clean runs text through the allowlist', $clean['widgets'][0]['text'], 'a  b');

$clean = clean([cleanbox(1, ['html' => '<b>x</b>'])], [], $errors);
check('html on a data widget is dropped', isset($clean['widgets'][0]['html']), false);
check('and reported', array_column($errors, 'code'), ['html_not_allowed_on_type']);

$clean = clean([cleanbox(1, ['x' => '12.4', 'y' => -5, 'wunit' => 'pc', 'hunit' => 'em'])]);
check(
    'geometry is rounded and units settled',
    [$clean['widgets'][0]['x'], $clean['widgets'][0]['y'], $clean['widgets'][0]['wunit'], $clean['widgets'][0]['hunit']],
    [12, -5, 'pc', 'px']
);

$clean = clean([cleanbox(1, ['type' => 'no such']), cleanbox(2)], [], $errors);
check('a widget that cannot be drawn is left out', array_column($clean['widgets'], 'id'), [2]);

$clean = clean([cleanbox(1, ['type' => 'gone', 'options' => ['a' => '1']])], [], $errors);
check(
    'an undeclared type is kept as unknown without its options',
    [$clean['widgets'][0]['unknown'], $clean['widgets'][0]['options']],
    [true, []]
);

check('clean refuses a later version', clean([], ['version' => 3]), null);
check('clean refuses something that is not a document', dashboard_render_clean('x', $errors), null);

// A version 1 document gains ids from the index, the ids it was drawn with.
$old = ['version' => 1, 'widgets' => [
    ['type' => 'feedvalue', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'options' => []],
    ['type' => 'dial', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'options' => []]
], 'meta' => ['converter' => 1]
];
$document = $old;
check('a version 1 document is upgraded', dashboard_upgrade_document($document), true);
check('the upgrade gives ids from the index', array_column($document['widgets'], 'id'), [1, 2]);
check('the upgrade sets the counter', [$document['version'], $document['next_id']], [2, 3]);
check('the upgrade keeps the field order', array_keys($document), ['version', 'next_id', 'widgets', 'meta']);
check('the upgrade puts the id first', array_keys($document['widgets'][1])[0], 'id');
check('a version 2 document is left alone', dashboard_upgrade_document($document), false);
$unreadable = 'x';
check('something that is not a document is left alone', dashboard_upgrade_document($unreadable), false);

// The renderer draws the stored id.
$rendered = dashboard_render($old);
check('a version 1 document draws with index ids', substr_count($rendered['html'], ' id="1"')
    + substr_count($rendered['html'], ' id="2"'), 2);
check('and raises nothing', $rendered['errors'], []);

$new = $old;
$new['version'] = 2;
$new['next_id'] = 10;
$new['widgets'][0]['id'] = 7;
$new['widgets'][1]['id'] = 3;
$rendered = dashboard_render($new);
check(
    'a stored id is drawn',
    preg_match_all('/ id="(\d+)"/', $rendered['html'], $m) ? $m[1] : null,
    ['7', '3']
);
check('a stored id raises nothing', $rendered['errors'], []);

$new['widgets'][1]['id'] = 7;
$rendered = dashboard_render($new);
check(
    'a duplicate stored id is drawn above every other',
    preg_match_all('/ id="(\d+)"/', $rendered['html'], $m) ? $m[1] : null,
    ['7', '8']
);
check(
    'and is reported',
    $rendered['errors'],
    [['widget' => 1, 'code' => 'widget_id_invalid', 'detail' => '7']]
);

unset($new['widgets'][1]['id']);
$rendered = dashboard_render($new);
check('a missing stored id is reported', $rendered['errors'][0]['detail'], 'none');

$new['widgets'][1]['id'] = 'abc';
$rendered = dashboard_render($new);
check('a non numeric stored id is reported', $rendered['errors'][0]['detail'], 'abc');

$new['version'] = 3;
$rendered = dashboard_render($new);
check(
    'a later version is refused',
    [$rendered['html'], $rendered['errors'][0]['code']],
    ['', 'document_version_unknown']
);

// A widget the migration replaces keeps its id.
$document = ['version' => 2, 'next_id' => 5, 'widgets' => [
    ['id' => 4, 'type' => 'paragraph', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10,
        'options' => [], 'html' => 'Hello'
    ]
]
];
$kept = [];
dashboard_migrate_widgets($document, $kept);
check(
    'a migrated widget keeps its id',
    [$document['widgets'][0]['type'], $document['widgets'][0]['id'], array_keys($document['widgets'][0])[0]],
    ['text', 4, 'id']
);

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
