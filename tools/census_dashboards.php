<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Builds four dashboards for the test user that hold one of everything the
// census found: every chart type, every text tier, every container, the
// data widgets, the made up classes and the cases SCHEMA.md decided
// against keeping. Written through the dashboard model of whatever commit
// is checked out, so run with the module at daa3a2b and core at 56366f2 to
// get html shaped by AntiXSS and htmlspecialchars_decode, which is
// snapshot B in notes/RELEASE.md.
//
// The feed ids are those of the test account on the development machine.
//
//   php Modules/dashboard/tools/census_dashboards.php
//
// Creates new dashboards each run. Delete the old ones first.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') {
    die("cli only\n");
}

require_once dirname(__FILE__) . "/cli.php";

chdir(cli_root());
$mysqli = cli_connect();
require "Modules/dashboard/dashboard_model.php";
$dashboard = new Dashboard($mysqli);
$userid = 2;

$n = 0;
// A widget box the way the old designer wrote it. Options are attributes.
function box($class, $top, $left, $w, $h, $attrs = [], $inner = '', $extra_style = '')
{
    global $n;
    $n++;
    $style = "position: absolute; margin: 0px; top: {$top}px; left: {$left}px; width: $w; height: $h;" . $extra_style;
    $a = '';
    foreach ($attrs as $k => $v) {
        $a .= ' ' . $k . '="' . $v . '"';
    }
    return "<div id=\"$n\" class=\"$class\" style=\"$style\"$a>$inner</div>\n";
}
function px($v)
{
    return $v . 'px';
}
function iframe($src, $w, $h)
{
    return "<iframe frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"$src\" style=\"width: {$w}px; height: {$h}px;\"></iframe>";
}
function canvas($w, $h)
{
    global $n;
    return "<canvas id=\"can-" . ($n + 1) . "\" width=\"$w\" height=\"$h\"></canvas>";
}

function save($dashboard, $mysqli, $userid, $name, $html, $height, $fields = [])
{
    $id = $dashboard->create($userid);
    $fields['name'] = $name;
    $dashboard->set($userid, $id, json_encode($fields));
    $r = $dashboard->set_content($userid, $id, $html, $height);
    $stored = $mysqli->query("SELECT LENGTH(content) l FROM dashboard WHERE id=$id")->fetch_assoc();
    echo "dashboard $id $name: " . $r['message'] . ", stored " . $stored['l'] . " bytes, posted " . strlen($html) . "\n";
    return $id;
}

// ---------------------------------------------------------------- charts
// Every vis type, the ported types, and the multigraph cases: a row with
// feeds, a row with an empty feedlist, and a mid that names no row.
$h = '';
$h .= box('heading', 20, 20, px(300), px(60), [], 'Heat pump');
$h .= box(
    'rawdata',
    80,
    20,
    px(460),
    px(260),
    ['feedid' => '625', 'colour' => 'AA0000', 'colourbg' => 'ffffff', 'units' => 'W', 'dp' => '0', 'scale' => '1', 'fill' => '1', 'initzoom' => '7'],
    iframe('/emoncms/vis/rawdata?embed=1&feedid=625&colour=AA0000&colourbg=ffffff&units=W&dp=0&scale=1&fill=1&initzoom=7', 460, 260)
);
$h .= box(
    'bargraph',
    80,
    500,
    px(460),
    px(260),
    ['feedid' => '1071', 'colour' => '0044AA', 'colourbg' => 'ffffff', 'interval' => '86400', 'units' => 'kWh', 'dp' => '1', 'scale' => '1', 'delta' => '1', 'mode' => 'daily', 'initzoom' => '30'],
    iframe('/emoncms/vis/bargraph?embed=1&feedid=1071', 460, 260)
);
$h .= box(
    'bargraph',
    360,
    20,
    px(460),
    px(260),
    ['feedid' => '1072', 'colour' => '', 'colourbg' => '', 'interval' => '3600', 'units' => '', 'dp' => '', 'scale' => '', 'delta' => '0', 'mode' => '', 'initzoom' => ''],
    iframe('/emoncms/vis/bargraph?embed=1&feedid=1072', 460, 260)
);
$h .= box(
    'stacked',
    360,
    500,
    px(460),
    px(260),
    ['bottom' => '1071', 'top' => '1072', 'colourt' => '00AA00', 'colourb' => 'AAAAAA', 'delta' => '1'],
    iframe('/emoncms/vis/stacked?embed=1&bottom=1071&top=1072', 460, 260)
);
$h .= box(
    'simplezoom',
    640,
    20,
    px(460),
    px(260),
    ['power' => '635', 'kwhd' => '1169', 'delta' => '0'],
    iframe('/emoncms/vis/simplezoom?embed=1&power=635&kwhd=1169', 460, 260)
);
$h .= box(
    'smoothie',
    640,
    500,
    px(460),
    px(260),
    ['feedid' => '1127', 'ufac' => '1'],
    iframe('/emoncms/vis/smoothie?embed=1&feedid=1127', 460, 260)
);
$h .= box(
    'multigraph',
    920,
    20,
    px(940),
    px(360),
    ['mid' => '1'],
    iframe('/emoncms/vis/multigraph?embed=1&mid=1', 940, 360)
);
$h .= box(
    'multigraph',
    1300,
    20,
    px(460),
    px(260),
    ['mid' => '2'],
    iframe('/emoncms/vis/multigraph?embed=1&mid=2', 460, 260)
);
$h .= box(
    'multigraph',
    1300,
    500,
    px(460),
    px(260),
    ['mid' => '99'],
    iframe('/emoncms/vis/multigraph?embed=1&mid=99', 460, 260)
);
$h .= box(
    'graph',
    1580,
    20,
    px(940),
    px(360),
    ['graphid' => '3'],
    iframe('/emoncms/graph/embed&graphid=3', 940, 360)
);
$h .= box(
    'realtime',
    1960,
    20,
    px(460),
    px(260),
    ['feedid' => '1114', 'colour' => 'EDC240', 'colourbg' => 'ffffff', 'initzoom' => '', 'kw' => '0'],
    iframe('/emoncms/vis/realtime?embed=1&feedid=1114', 460, 260)
);
$h .= box(
    'zoom',
    1960,
    500,
    px(460),
    px(260),
    ['power' => '1127', 'kwhd' => '1169', 'currency' => '£', 'currency_after_val' => '0', 'pricekwh' => '0.28', 'delta' => '0'],
    iframe('/emoncms/vis/zoom?embed=1&power=1127&kwhd=1169', 460, 260)
);
$h .= box(
    'stackedsolar',
    2240,
    20,
    px(460),
    px(260),
    ['solar' => '1114', 'consumption' => '1127', 'delta' => '0'],
    iframe('/emoncms/vis/stackedsolar?embed=1', 460, 260)
);
$h .= box(
    'orderbars',
    2240,
    500,
    px(460),
    px(260),
    ['feedid' => '1169', 'delta' => '1'],
    iframe('/emoncms/vis/orderbars?embed=1', 460, 260)
);
$h .= box(
    'timecompare',
    2520,
    20,
    px(940),
    px(360),
    ['feedid' => '1127', 'fill' => '1', 'depth' => '3', 'npoints' => '800', 'initzoom' => '1'],
    iframe('/emoncms/vis/timecompare?embed=1', 940, 360)
);
$h .= box(
    'histgraph',
    2900,
    20,
    px(460),
    px(260),
    ['feedid' => '1127'],
    iframe('/emoncms/vis/histgraph?embed=1&feedid=1127', 460, 260)
);
save($dashboard, $mysqli, $userid, 'Census charts', $h, 3200, ['public' => 1, 'alias' => 'census-charts', 'description' => 'every chart type']);

// ------------------------------------------------------------------ text
// One widget per census tier, and the cases SCHEMA.md decided against.
$h = '';
$h .= box('heading', 20, 20, px(300), px(60), [], 'Plain heading');
$h .= box('heading', 20, 340, px(300), px(90), [], 'Tall heading');
$h .= box('heading-center', 20, 660, px(300), px(60), [], 'Centred heading');
$h .= box('paragraph', 100, 20, px(300), px(60), [], 'Plain paragraph');
// one style throughout, the font tag
$h .= box('paragraph', 100, 340, px(300), px(60), [], '<font size="4" color="red">Red size four</font>');
$h .= box('heading-center', 100, 660, px(300), px(60), [], '<font face="comic sans ms" color="#4444cc">Comic</font>');
$h .= box('paragraph', 180, 20, px(300), px(60), [], '<p style="font-size: 18px; color: maroon;">Styled block</p>');
// one style throughout on the box itself
$h .= box('paragraph', 180, 340, px(300), px(60), [], 'Box styled', ' font-size: 18px; color: maroon;');
// mixed, across blocks
$h .= box(
    'paragraph',
    180,
    660,
    px(300),
    px(120),
    [],
    '<p><b>Bold line</b></p><p><u>Underlined line</u></p><p><font size="2">Small line</font></p><center>Centred</center>'
);
// mixed, inline
$h .= box('paragraph', 320, 20, px(300), px(60), [], 'CO<sub>2</sub> now <b>412</b> ppm<br>Flow m<sup>3</sup>/h');
// mixed, inline with a link
$h .= box(
    'paragraph',
    320,
    340,
    px(300),
    px(60),
    [],
    'P<sub>L1</sub>: <a href="https://openenergymonitor.org/" target="_blank">OEM</a>'
);
$h .= box(
    'paragraph',
    320,
    660,
    px(300),
    px(60),
    [],
    '<a href="/emoncms/feed/list">local link</a> and <a href="feed/list">relative</a> and <a href="mailto:x@example.com">mail</a>'
);
// image, no text
$h .= box(
    'paragraph',
    400,
    20,
    px(300),
    px(200),
    [],
    '<img src="https://openenergymonitor.org/wp-content/uploads/2017/01/OpenEnergyMonitor-logo.png" alt="logo" width="280">'
);
$h .= box(
    'paragraph',
    400,
    340,
    px(300),
    px(200),
    [],
    '<a href="http://openenergymonitor.org/"><img src="http://openenergymonitor.org/logo.png"></a>'
);
$h .= box(
    'paragraph',
    400,
    660,
    px(300),
    px(200),
    [],
    '<img src="/emoncms/Modules/dashboard/dashboard.png" alt="local image">'
);
$h .= box(
    'heading',
    620,
    20,
    px(300),
    px(60),
    [],
    '<img src="https://openenergymonitor.org/logo.png?v=3">'
);
// table or list
$h .= box(
    'paragraph',
    620,
    340,
    px(300),
    px(120),
    [],
    '<ul><li>one</li><li><a href="https://example.com/">two</a></li></ul>'
);
$h .= box(
    'paragraph',
    620,
    660,
    px(300),
    px(120),
    [],
    '<table border="1" cellpadding="2"><tr><th>Room</th><th>Temp</th></tr><tr><td>Hall</td><td align="right">21.2</td></tr></table>'
);
// empty
$h .= box('paragraph', 760, 20, px(300), px(60), [], '');
$h .= box('heading', 760, 340, px(300), px(60), [], '');
// embed or nested widget
$h .= box(
    'paragraph',
    760,
    660,
    px(300),
    px(200),
    [],
    'Inside: ' . box('dial', 30, 10, px(160), px(160), ['feedid' => '626', 'max' => '60', 'units' => 'C'], canvas(160, 160))
);
$h .= box(
    'paragraph',
    980,
    20,
    px(300),
    px(200),
    [],
    '<iframe src="https://www.openstreetmap.org/export/embed.html" width="280" height="180"></iframe>'
);
$h .= box(
    'paragraph',
    980,
    340,
    px(300),
    px(200),
    [],
    '<meta http-equiv="content-type" content="text/html; charset=utf-8"><title>Pasted</title><span style="user-select: text; color: teal;">Pasted from a page</span>'
);
$h .= box(
    'paragraph',
    980,
    660,
    px(300),
    px(200),
    [],
    '<style>.rot { transform: rotate(-90deg); }</style><div class="rot">Rotated by a stylesheet</div>'
);
// rotation on an inner element
$h .= box(
    'paragraph',
    1200,
    20,
    px(60),
    px(300),
    [],
    '<div style="transform: rotate(-90deg); white-space: nowrap;">Rotated label</div>'
);
$h .= box(
    'paragraph',
    1200,
    100,
    px(60),
    px(300),
    [],
    '<div style="transform: rotate(70deg);">Seventy</div>'
);
// the Arial Black case, quotes in a style attribute
$h .= box(
    'paragraph',
    1200,
    340,
    px(300),
    px(60),
    [],
    '<span style="font-family: &quot;Arial Black&quot;; font-size: 18px;">Arial Black</span>'
);
$h .= box(
    'heading',
    1200,
    660,
    px(300),
    px(60),
    [],
    '<span style="font-family: &quot;Comic Sans MS&quot;;">Comic heading</span>'
);
// browser extension debris and designer artefacts
$h .= box(
    'paragraph',
    1280,
    20,
    px(300),
    px(60),
    ['bis_skin_checked' => '1', '_msttexthash' => '123', 'units_dropdown' => ''],
    '<span data-darkreader-inline-color="" style="--darkreader-inline-color: #fff; color: black;">Debris</span>'
);
// deeply nested, whitespace and nbsp
$h .= box(
    'paragraph',
    1280,
    340,
    px(300),
    px(60),
    [],
    '<div><div><div><span><b>Deep</b>&nbsp;text</span></div></div></div>'
);
// long content
$h .= box('paragraph', 1280, 660, px(300), px(120), [], str_repeat('Long text. ', 80));
$h .= box('paragraph', 1420, 660, px(300), px(60), [], '<h4>Heading four</h4><h2>Heading two</h2>');
// percentage width
$h .= box('paragraph', 1500, 20, '50%', px(60), [], 'Half width');
$text_id = save($dashboard, $mysqli, $userid, 'Census text', $h, 1600, ['alias' => 'census-text']);
// AntiXSS refuses a script tag and an onclick, and the census holds two
// script tags and a handful of event attributes that got in some other way.
// Written straight to the column, past AntiXSS, so the load path meets them.
$past = box('paragraph', 1420, 20, px(300), px(60), [], 'Before<script>alert(1)</script>After')
    . box('paragraph', 1420, 340, px(300), px(60), [], '<span onclick="alert(1)">Click</span><a href="javascript:alert(1)">js</a>');
$stmt = $mysqli->prepare("UPDATE dashboard SET content=CONCAT(content, ?) WHERE id=?");
$stmt->bind_param("si", $past, $text_id);
$stmt->execute();
echo "dashboard $text_id: script and onclick widgets written past AntiXSS\n";

// --------------------------------------------------- containers and data
$h = '';
$h .= box('Container-White', 20, 20, px(400), px(300), [], '');
$h .= box('Container-Grey', 20, 440, px(400), px(300), [], '');
$h .= box('Container-Black', 340, 20, px(400), px(300), [], '');
$h .= box('Container-BlueLine', 340, 440, px(400), px(300), [], '');
// a container with a table in it, a hand edited style, and a made up class
$h .= box(
    'Container-White',
    660,
    20,
    px(400),
    px(200),
    [],
    '<table><colgroup><col></colgroup><thead><tr><th>Room</th><th>Temp</th></tr></thead><tbody><tr><td>Hall</td><td>21.2</td></tr></tbody></table>'
);
$h .= box('Container-Grey', 660, 440, px(400), px(200), [], '', ' border-radius: 12px; opacity: 0.5;');
$h .= box('Container-red', 880, 20, px(400), px(200), [], '');
$h .= box('Container-333-Solid', 880, 440, px(400), px(200), [], '');
// data widgets with the option shapes the census counts
$h .= box(
    'dial',
    40,
    40,
    px(160),
    px(160),
    ['feedid' => '626', 'max' => '60', 'scale' => '', 'units' => 'C', 'units_dropdown' => '', 'decimals' => '1', 'offset' => '', 'type' => '10', 'graduations' => '1', 'unitend' => '0', 'displayminmax' => '1', 'minvaluefeed' => '629', 'maxvaluefeed' => '630', 'timeout' => '', 'errormessagedisplayed' => ''],
    canvas(160, 160) . '<div id="can-tooltip-1"></div><div id="can-tooltip-2"></div>'
);
$h .= box(
    'feedvalue',
    40,
    220,
    px(180),
    px(40),
    ['feedid' => '625', 'prepend' => 'Elec ', 'append' => ' W', 'decimals' => '0', 'colour' => '000000', 'font' => '0', 'fstyle' => '0', 'fweight' => '0', 'size' => '2', 'align' => '0', 'timeout' => '', 'errormessagedisplayed' => '', 'threshold1' => '', 'threshold2' => '', 'colour1' => '', 'colour2' => '', 'colour3' => '', 'scale' => ''],
    canvas(180, 40) . '1234 W'
);
$h .= box(
    'feedvalue',
    90,
    220,
    px(180),
    px(40),
    ['feedid' => '628', 'append' => ' kW', 'decimals' => '2', 'scale' => '0.001'],
    '<font size="4">2.95</font>'
);
$h .= box(
    'feedvalue',
    140,
    220,
    px(180),
    px(40),
    ['feedid' => '630', 'append' => '<sub>ret</sub>', 'decimals' => '1'],
    ''
);
$h .= box(
    'bar',
    460,
    40,
    px(100),
    px(200),
    ['title_bar' => 'Flow', 'colour_label' => '000000', 'font' => '0', 'fstyle' => '0', 'fweight' => '0', 'feedid' => '627', 'max' => '20', 'scale' => '', 'units' => 'l/m', 'unitend' => '0', 'decimals' => '1', 'offset' => '', 'colour' => '00AA00', 'graduations' => '1', 'displayminmax' => '0', 'minvaluefeed' => '', 'maxvaluefeed' => '', 'colour_minmax' => '', 'timeout' => '', 'errormessagedisplayed' => ''],
    '<div></div>' . canvas(100, 200)
);
$h .= box(
    'jgauge',
    460,
    160,
    px(200),
    px(200),
    ['feedid' => '635', 'scale' => '', 'max' => '5000', 'min' => '0', 'units' => 'W', 'timeout' => '', 'errormessagedisplayed' => ''],
    canvas(200, 200)
);
$h .= box('jgauge3', 460, 380, px(200), px(200), ['feedid' => '635', 'max' => '5000'], canvas(200, 200));
$h .= box(
    'thermometer',
    360,
    60,
    px(80),
    px(240),
    ['font' => '0', 'fstyle' => '0', 'fweight' => '0', 'feedid' => '629', 'min' => '-10', 'max' => '40', 'scale' => '', 'units' => 'C', 'unitend' => '0', 'decimals' => '1', 'offset' => '', 'graduations' => '1', 'displayminmax' => '0', 'minvaluefeed' => '', 'maxvaluefeed' => '', 'timeout' => '', 'errormessagedisplayed' => ''],
    '<div></div>' . canvas(80, 240)
);
$h .= box(
    'battery',
    360,
    160,
    px(200),
    px(120),
    ['feedid' => '1116', 'battery_title' => 'SoC', 'max' => '100', 'min' => '0', 'scale' => '', 'units' => '%', 'unitend' => '0', 'number_of_blocks' => '', 'decimals' => '0', 'offset' => '', 'colour' => '26a269', 'font' => '8', 'fstyle' => '2', 'fweight' => '0', 'battery_style' => '0', 'timeout' => '', 'errormessagedisplayed' => ''],
    canvas(200, 120)
);
$h .= box('led', 360, 380, px(40), px(40), ['feedid' => '1172', 'ledstyle' => '0'], canvas(40, 40));
$h .= box(
    'thresholds',
    360,
    440,
    px(40),
    px(40),
    ['feedid' => '629', 'threshold1' => '5', 'threshold2' => '15', 'colour1' => '0000ff', 'colour2' => '00ff00', 'colour3' => 'ff0000', 'shapetype' => '0'],
    canvas(40, 40)
);
$h .= box(
    'feedtime',
    40,
    460,
    px(200),
    px(40),
    ['feedid' => '1172', 'colour' => '000000', 'font' => '0', 'fstyle' => '0', 'fweight' => '0', 'units' => '', 'size' => '2', 'align' => '1', 'unitend' => '0'],
    '12:30'
);
$h .= box(
    'cylinder',
    40,
    520,
    px(120),
    px(200),
    ['topfeedid' => '624', 'botfeedid' => '1173', 'temptype' => '0', 'decimals' => '1', 'unitend' => '0'],
    canvas(120, 200)
);
$h .= box(
    'sun',
    40,
    740,
    px(160),
    px(160),
    ['feedid' => '1114', 'max' => '4000', 'scale' => '', 'units' => 'W', 'unitend' => '0', 'decimals' => '0', 'offset' => '', 'solar_title' => 'Solar', 'colour' => 'ffaa00', 'font' => '0', 'fstyle' => '0', 'fweight' => '0', 'timeout' => '', 'errormessagedisplayed' => ''],
    canvas(160, 160)
);
$h .= box('kwhperiod', 40, 920, px(200), px(60), ['feedid' => '1169', 'period' => 'd', 'units' => 'kWh', 'decimals' => '1'], '');
$h .= box('button', 460, 600, px(120), px(40), ['feedid' => '1172', 'value' => '1'], canvas(120, 40));
$h .= box(
    'curl',
    460,
    660,
    px(120),
    px(40),
    ['ip' => '192.168.1.50', 'port' => '80', 'url' => '/relay', 'payload' => '{"on":true}', 'method' => 'POST', 'https' => '0', 'timeout' => '5', 'colour' => '0000ff', 'caption' => 'Relay', 'confirm' => '1'],
    ''
);
$h .= box('dewpoint', 460, 720, px(160), px(60), ['feedid' => '629', 'feedid2' => '1170'], canvas(160, 60));
$h .= box('feedtimestamp', 460, 800, px(200), px(40), ['feedid' => '1172'], '');
$h .= box('isactivefeed', 460, 860, px(40), px(40), ['feedid' => '1172'], canvas(40, 40));
$h .= box('signal', 460, 920, px(40), px(40), ['feedid' => '1199'], canvas(40, 40));
$h .= box('windrose', 660, 660, px(200), px(200), ['feedid' => '629', 'feedid2' => '1170', 'scale' => '', 'units' => ''], canvas(200, 200));
$h .= box('timestoredaily', 880, 660, px(200), px(100), ['feedid' => '1169'], '');
// a box with no class, and one with a fixed position
$html_noclass = "<div id=\"999\" style=\"position: absolute; top: 1100px; left: 20px; width: 200px; height: 40px;\">No class</div>\n";
$h .= $html_noclass;
$h .= box('paragraph', 1100, 300, px(200), px(40), [], 'Fixed', ' position: fixed;');
$h .= "stray text at the top\n";
save($dashboard, $mysqli, $userid, 'Census containers', $h, 1200, ['alias' => 'census-containers', 'backgroundcolor' => 'eeeeee']);

// A dashboard that is a single stray line, the "no widgets" case.
save($dashboard, $mysqli, $userid, 'Census stray', "just a line of text", 400);
