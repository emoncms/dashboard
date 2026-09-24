<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

---------------------------------------------------------------------
Converts the chart widgets of the retired vis module into graph widgets
holding their chart.

rawdata is one feed drawn as lines, stacked is two feeds drawn as bars on top
of each other, and a multigraph is a saved feed list. All three are what the
graph engine draws, so each widget is rewritten in place: the type becomes
graph, the colour it drew on goes on the box as colourbg, and the chart goes
in the config block beside it. Nothing is written to another table, so a
dashboard carries its own charts afterwards and the chart is edited in the
designer.

bargraph is one feed drawn as bars a day, a month or a year at a time, and
the graph engine draws one of those at a time where the visualisation let a
reader step between them. The zoom widget does, so a bargraph on a daily,
monthly or annual step becomes a zoom widget. One on a fixed number of
seconds is not a chart of days, and becomes a graph config.

simplezoom was the zoom pair of feeds without the cost, and was ported as a
widget of its own before the zoom widget grew the buttons that made it the
better one. It becomes a zoom widget with the same feeds.

smoothie was a scrolling live window of one feed, the same thing realtime
draws. It becomes a realtime widget on the fifteen minute window.

Called by dashboard_migrate.php when a dashboard is loaded or saved, and by
tools/migrate.php to do the same for a whole install
and report on it. Nothing here touches the database. A multigraph is read
through a loader the caller passes in, see dashboard_convert_chart_widget.

The declarations for the four types are in widget/retired/retired_widgets.json,
so the html converter keeps their options until this runs.

See notes/CHARTS.md.
*/

defined('EMONCMS_EXEC') or die('Restricted access');

define('DASHBOARD_CONVERT_DAY_MS', 86400000);

// The widget types this converts.
function dashboard_convert_chart_types()
{
    return ['rawdata', 'bargraph', 'stacked', 'simplezoom', 'smoothie', 'multigraph'];
}

/**
 * Converts one vis chart widget into a graph widget holding its chart.
 *
 * A multigraph widget names a row of the multigraph table by mid, and the
 * caller supplies the read as $context['multigraph'], a callable taking the
 * mid and returning what dashboard_convert_multigraph_row returns, null when
 * there is no such row, or false when the read failed. A widget with no mid,
 * or naming a row that is gone, becomes a graph widget with no chart. It
 * draws an empty box and says so, which is what the multigraph widget did.
 * With no loader, or a failed read, a multigraph widget is refused rather
 * than emptied.
 *
 * @param array $widget a widget of a document, one of the four types
 * @param string $reason set to why it was refused, when it was
 * @param array $context now (milliseconds) and multigraph (callable)
 * @param array $counts what was converted and what was dropped, name => count
 * @return array|null the new widget, or null
 */
function dashboard_convert_chart_widget($widget, &$reason = null, $context = [], &$counts = [])
{
    $reason = '';
    if (!is_array($widget) || !isset($widget['type'])) {
        $reason = 'not_a_widget';
        return null;
    }
    $type = (string) $widget['type'];
    if (!in_array($type, dashboard_convert_chart_types(), true)) {
        $reason = 'not_a_chart';
        return null;
    }
    if (!is_array($context)) {
        $context = [];
    }
    if (!is_array($counts)) {
        $counts = [];
    }

    $options = (isset($widget['options']) && is_array($widget['options']))
        ? $widget['options'] : [];
    $now = isset($context['now']) ? (int) $context['now'] : null;

    if ($type === 'multigraph') {
        $mid = isset($options['mid']) ? trim((string) $options['mid']) : '';
        $loaded = null;
        if ($mid === '') {
            dashboard_convert_preset_count($counts, 'widget_without_mid');
        } else {
            if (!isset($context['multigraph']) || !is_callable($context['multigraph'])) {
                $reason = 'no_multigraph_loader';
                return null;
            }
            $loaded = call_user_func($context['multigraph'], $mid);
            if ($loaded === false) {
                // Read failed, which is not the same as the row being gone.
                // Left for the next load rather than emptied.
                $reason = 'dashboard_convert_multigraph_not_read';
                return null;
            }
            if (!is_array($loaded) || !isset($loaded['config'])) {
                $loaded = null;
                dashboard_convert_preset_count($counts, 'widget_mid_not_found');
            } else {
                dashboard_convert_preset_count($counts, 'widget_converted');
                if (isset($loaded['dropped']) && is_array($loaded['dropped'])) {
                    foreach ($loaded['dropped'] as $key => $n) {
                        if (!isset($counts[$key])) {
                            $counts[$key] = 0;
                        }
                        $counts[$key] += $n;
                    }
                }
            }
        }

        // A multigraph was opaque, so the box it converts to is given the
        // colour it drew on. A widget that names no multigraph has no colour
        // to carry and is left to show the dashboard behind it.
        $widget['options'] = [];
        if ($loaded !== null && isset($loaded['background'])) {
            $widget['options']['colourbg'] = $loaded['background'];
        }
        if ($loaded !== null) {
            $widget['config'] = $loaded['config'];
        } else {
            unset($widget['config']);
        }
    } else {
        $converted = dashboard_convert_preset_to_graph($type, $options, $now);
        foreach ($converted['dropped'] as $key => $n) {
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
            }
            $counts[$key] += $n;
        }
        dashboard_convert_preset_count($counts, 'converted_' . $type);
        $widget['options'] = $converted['options'];
        if (isset($converted['config'])) {
            $widget['config'] = $converted['config'];
        } else {
            unset($widget['config']);
        }
    }

    $widget['type'] = isset($converted['type']) ? $converted['type'] : 'graph';
    unset($widget['unknown']);
    return $widget;
}

/**
 * One row of the multigraph table as the config and box colour a widget takes.
 *
 * @param string $name the name column
 * @param string $feedlist the feedlist column
 * @param int $now milliseconds, for a floating window to be anchored to
 * @return array config, background, dropped field name => count, and feeds,
 *         the number of feeds the chart holds
 */
function dashboard_convert_multigraph_row($name, $feedlist, $now = null)
{
    $converted = dashboard_convert_multigraph_to_graph($feedlist, $name, $now);
    return [
        'config'     => dashboard_convert_multigraph_config($converted['data']),
        'background' => $converted['background'],
        'dropped'    => $converted['dropped'],
        'feeds'      => count($converted['data']['feedlist']),
    ];
}

// ---------------------------------------------------------------------------
// rawdata, bargraph, stacked, simplezoom and smoothie
// ---------------------------------------------------------------------------

// The types that were never a saved row.
function dashboard_convert_preset_types()
{
    return ['rawdata', 'bargraph', 'stacked', 'simplezoom', 'smoothie'];
}

// One stored widget as the widget it becomes.
//
// @param string $type the widget type
// @param array $options its stored options
// @param int $now milliseconds, for the window to be anchored to
// @return array|null type, options, dropped field name => count, and config
//         for a graph widget, or null if the type is not one of these
function dashboard_convert_preset_to_graph($type, $options, $now = null)
{
    if ($now === null) {
        $now = (int) round(microtime(true) * 1000);
    }
    if (!is_array($options)) {
        $options = [];
    }

    if ($type === 'rawdata') {
        return dashboard_convert_preset_rawdata($options, $now);
    }
    if ($type === 'bargraph') {
        return dashboard_convert_preset_bargraph($options, $now);
    }
    if ($type === 'stacked') {
        return dashboard_convert_preset_stacked($options, $now);
    }
    if ($type === 'simplezoom') {
        return dashboard_convert_preset_simplezoom($options);
    }
    if ($type === 'smoothie') {
        return dashboard_convert_preset_smoothie($options);
    }
    return null;
}

// A scrolling live window of one feed, which is what realtime draws. It opens
// on fifteen minutes, the realtime default, and keeps the look it had, a
// green line on a black box, which were the Smoothie Charts defaults, with
// white axes so they read against the black. ufac, how far behind now the
// line was drawn, has no equivalent and is counted.
function dashboard_convert_preset_smoothie($options)
{
    $dropped = [];
    $feedid = dashboard_convert_preset_text($options, 'feedid');
    if ($feedid === '') {
        dashboard_convert_preset_count($dropped, 'widget_without_feed');
    }
    if (dashboard_convert_preset_text($options, 'ufac') !== '') {
        dashboard_convert_preset_count($dropped, 'ufac');
    }

    return [
        'type'    => 'realtime',
        'options' => [
            'feedid'   => $feedid,
            'initzoom' => '15',
            'colour'     => '00ff00',
            'colourbg'   => '000000',
            'colouraxis' => 'ffffff',
        ],
        'dropped' => $dropped,
    ];
}

// The zoom pair of feeds without the cost. The three options it had are the
// same three on the zoom widget, and nothing else is set, so the widget draws
// as it did with the cost readouts showing a price of nothing.
function dashboard_convert_preset_simplezoom($options)
{
    $dropped = [];
    $kwhd = dashboard_convert_preset_text($options, 'kwhd');
    if ($kwhd === '') {
        dashboard_convert_preset_count($dropped, 'widget_without_feed');
    }

    return [
        'type'    => 'zoom',
        'options' => [
            'power' => dashboard_convert_preset_text($options, 'power'),
            'kwhd'  => $kwhd,
            'delta' => dashboard_convert_preset_flag($options, 'delta') ? '1' : '0',
        ],
        'dropped' => $dropped,
    ];
}

// One feed drawn as lines over the last few days. The window is the zoom the
// widget opened on, and it floats, so the chart shows recent data whenever the
// dashboard is loaded.
function dashboard_convert_preset_rawdata($options, $now)
{
    $dropped = [];
    $length = dashboard_convert_preset_zoom($options) * DASHBOARD_CONVERT_DAY_MS;

    $feed = dashboard_convert_preset_feed($options, 'feedid', $dropped);
    if ($feed !== null) {
        $feed['plottype'] = 'lines';
        $feed['color']    = dashboard_convert_preset_colour($options, 'colour', '#EDC240');
        $feed['unit']     = dashboard_convert_preset_text($options, 'units');
        $feed['dp']       = dashboard_convert_preset_dp($options);
        $feed['scale']    = dashboard_convert_preset_scale($options);
        $feed['fill']     = dashboard_convert_preset_flag($options, 'fill') ? '1' : '0';
    }

    return dashboard_convert_preset_result([
        'mode'          => 'interval',
        'limitinterval' => '1',
        // rawdata asked for the gaps to be left out, see the skipmissing
        // argument it passed to feed.getdata.
        'showmissing'   => '0',
    ] + dashboard_convert_preset_window($length, $now), dashboard_convert_preset_one($feed), $options, $dropped);
}

// One feed drawn as bars. The step comes from the interval option, which is a
// number of seconds or one of d, m and y for a day, a month and a year.
//
// A day, a month or a year is a zoom widget, which draws all three and steps
// between them. Anything else is a fixed step the zoom widget does not draw,
// so it becomes a graph config at that step.
function dashboard_convert_preset_bargraph($options, $now)
{
    $dropped = [];
    $window = dashboard_convert_preset_bargraph_window($options, $dropped);

    if ($window['mode'] !== 'interval') {
        return dashboard_convert_preset_bargraph_zoom($options, $window, $dropped);
    }

    $feed = dashboard_convert_preset_feed($options, 'feedid', $dropped);
    if ($feed !== null) {
        $feed['plottype'] = 'bars';
        $feed['color']    = dashboard_convert_preset_colour($options, 'colour', '#EDC240');
        $feed['unit']     = dashboard_convert_preset_text($options, 'units');
        $feed['dp']       = dashboard_convert_preset_dp($options);
        $feed['scale']    = dashboard_convert_preset_scale($options);
        $feed['delta']    = dashboard_convert_preset_flag($options, 'delta') ? '1' : '0';
        // A bar the engine draws unfilled is an outline, so bars are filled.
        $feed['fill']     = '1';
    }

    $state = [
        'mode'          => $window['mode'],
        'limitinterval' => '0',
        'showmissing'   => '1',
    ] + dashboard_convert_preset_window($window['length'], $now);

    if ($window['mode'] === 'interval') {
        // A bargraph drew at the step it was given rather than at one worked
        // out for the width of the box, so the step is fixed here too.
        $state['fixinterval'] = '1';
        $state['interval'] = (string) $window['interval'];
    }

    return dashboard_convert_preset_result($state, dashboard_convert_preset_one($feed), $options, $dropped);
}

// A bargraph as a zoom widget: the feed goes in as the daily feed, with no
// power feed, and the widget opens on the view the step asked for. The
// colour, background, delta, scale and units carry over. dp and initzoom do
// not, the zoom widget chooses its own decimal places and window, and they
// are counted.
function dashboard_convert_preset_bargraph_zoom($options, $window, $dropped)
{
    $views = ['daily' => 'days', 'monthly' => 'months', 'annual' => 'years'];

    $feedid = dashboard_convert_preset_text($options, 'feedid');
    if ($feedid === '') {
        dashboard_convert_preset_count($dropped, 'widget_without_feed');
    }

    foreach (['dp', 'initzoom'] as $key) {
        if (dashboard_convert_preset_text($options, $key) !== '') {
            dashboard_convert_preset_count($dropped, $key);
        }
    }
    $units = dashboard_convert_preset_zoom_units($options, $dropped);

    $zoom = [
        'power'    => '',
        'kwhd'     => $feedid,
        'delta'    => dashboard_convert_preset_flag($options, 'delta') ? '1' : '0',
        'colour'   => ltrim(dashboard_convert_preset_colour($options, 'colour', '#0096ff'), '#'),
        'colourbg' => dashboard_convert_preset_background($options),
        'view'     => $views[$window['mode']],
    ];
    $scale = dashboard_convert_preset_scale($options);
    if ($scale !== '1') {
        $zoom['scale'] = $scale;
    }
    if ($units !== '') {
        $zoom['units'] = $units;
    }

    return ['type' => 'zoom', 'options' => $zoom, 'dropped' => $dropped];
}

// Units of a bargraph as the zoom widget takes them. Blank means kWh, so any
// spelling of kWh is left blank: kwh, KWH, kWh/d and kWh per day would
// otherwise read "kWh/d/d" in the per day average. A bare number is a
// mistyped option and is left blank too. Anything else is kept as written,
// or counted as units when it holds an angle bracket, which a value option
// may not.
function dashboard_convert_preset_zoom_units($options, &$dropped)
{
    $units = dashboard_convert_preset_text($options, 'units');
    if ($units === '' || preg_match('/^[\d.]+$/', $units)) {
        return '';
    }
    if (preg_match('#kwh|kw\s*/\s*h#i', $units)) {
        return '';
    }
    if (strpbrk($units, '<>') !== false || mb_strlen($units, 'UTF-8') > 64) {
        dashboard_convert_preset_count($dropped, 'units');
        return '';
    }
    return $units;
}

// The step and the window a bargraph opened on.
//
// d, m and y each carry their own window, the rest open on the zoom option. The
// mode option says daily, which bargraph.php never read, so it decides only
// when the interval option is not already a step of its own.
function dashboard_convert_preset_bargraph_window($options, &$dropped)
{
    $raw = dashboard_convert_preset_text($options, 'interval');
    $mode = strtolower(dashboard_convert_preset_text($options, 'mode'));
    $zoom = dashboard_convert_preset_zoom($options) * DASHBOARD_CONVERT_DAY_MS;

    if ($raw === 'y') {
        return ['mode' => 'annual',  'length' => 5 * 365 * DASHBOARD_CONVERT_DAY_MS, 'interval' => 0];
    }
    if ($raw === 'm') {
        return ['mode' => 'monthly', 'length' => 365 * DASHBOARD_CONVERT_DAY_MS,     'interval' => 0];
    }
    if ($raw === 'd') {
        return ['mode' => 'daily',   'length' => 10 * DASHBOARD_CONVERT_DAY_MS,      'interval' => 0];
    }

    $seconds = is_numeric($raw) ? (int) round((float) $raw) : 86400;
    // An interval of nothing at all is a day, which is both the default the
    // visualisation used and what it fell back to for an interval of zero.
    if ($seconds <= 0) {
        $seconds = 86400;
    }

    if ($seconds === 86400) {
        return ['mode' => 'daily', 'length' => $zoom, 'interval' => 0];
    }

    if ($mode === 'daily') {
        // The widget says daily and the interval says otherwise. The interval
        // is what drew, so it is what carries over.
        dashboard_convert_preset_count($dropped, 'mode_overruled_by_interval');
    }
    return ['mode' => 'interval', 'length' => $zoom, 'interval' => $seconds];
}

// Two feeds drawn as bars on top of each other, a month at a time over the last
// five years. The visualisation read days and added them into months itself,
// which the monthly mode does in the request.
function dashboard_convert_preset_stacked($options, $now)
{
    $dropped = [];
    $delta = dashboard_convert_preset_flag($options, 'delta') ? '1' : '0';

    $feeds = [];
    // The editor names these bottom and top, and colours them the other way
    // round: colourb is the bottom one and colourt the top one.
    foreach ([['bottom', 'colourb', '#0096ff'], ['top', 'colourt', '#7cc9ff']] as $pair) {
        $feed = dashboard_convert_preset_feed($options, $pair[0], $dropped);
        if ($feed === null) {
            continue;
        }
        $feed['plottype'] = 'bars';
        $feed['color']    = dashboard_convert_preset_colour($options, $pair[1], $pair[2]);
        $feed['stack']    = '1';
        $feed['fill']     = '1';
        $feed['delta']    = $delta;
        $feeds[] = $feed;
    }

    $state = [
        'mode'          => 'monthly',
        'limitinterval' => '0',
        'showmissing'   => '1',
    ] + dashboard_convert_preset_window(5 * 365 * DASHBOARD_CONVERT_DAY_MS, $now);

    return dashboard_convert_preset_result($state, $feeds, $options, $dropped);
}

// The config and the options of one converted widget.
//
// Every one of these drew no legend and no tag, so neither is turned on. The
// colour behind the chart moves to the box, the same as it does for a
// multigraph.
function dashboard_convert_preset_result($state, $feeds, $options, $dropped)
{
    $config = [
        'state' => ['showlegend' => '0', 'showtag' => '0'] + $state,
        'feedlist' => $feeds,
    ];

    return [
        'type'    => 'graph',
        'config'  => $config,
        'options' => ['colourbg' => dashboard_convert_preset_background($options)],
        'dropped' => $dropped,
    ];
}

// A feed list holding the one feed a widget names, or none when it names
// nothing this can read.
function dashboard_convert_preset_one($feed)
{
    return $feed === null ? [] : [$feed];
}

// A floating window of a given length, stored as a start and an end. The
// renderer moves it up to now on load, so what is stored is the length.
function dashboard_convert_preset_window($length, $now)
{
    $length = (int) $length;
    if ($length < 1) {
        $length = 7 * DASHBOARD_CONVERT_DAY_MS;
    }
    return [
        'floatingtime' => '1',
        'start'        => (string) ($now - $length),
        'end'          => (string) $now,
    ];
}

// The feed an option names, with the fields a graph feed holds. Null when the
// option is not a feed id, which is a widget nobody finished.
function dashboard_convert_preset_feed($options, $key, &$dropped)
{
    $id = dashboard_convert_preset_text($options, $key);
    if ($id === '' || !ctype_digit($id) || (int) $id < 1) {
        dashboard_convert_preset_count($dropped, 'widget_without_feed');
        return null;
    }
    return [
        'id'       => $id,
        'name'     => '',
        'tag'      => '',
        'unit'     => '',
        'yaxis'    => '1',
        'plottype' => 'lines',
        'color'    => '',
        'fill'     => '0',
        'stack'    => '0',
        'delta'    => '0',
        // None of these averaged, they read the feed as it was stored.
        'average'  => '0',
        'scale'    => '1',
        'offset'   => '0',
        'dp'       => '1',
    ];
}

// The zoom option, in days. Under a day is what the visualisations treated as
// unset, and they opened on a week.
function dashboard_convert_preset_zoom($options)
{
    $zoom = dashboard_convert_preset_text($options, 'initzoom');
    if (!is_numeric($zoom)) {
        return 7;
    }
    $days = (float) $zoom;
    return $days < 1 ? 7 : $days;
}

// A colour as a hex string with a hash, or '' when it is not one. The old
// chart editors and saved graphs took a CSS colour name as well as hex, and
// the graph engine, its editor and the colour option only read hex, so a name
// is turned into its hex value here.
function dashboard_convert_hex_colour($colour)
{
    $colour = trim((string) $colour);
    $name = strtolower($colour);
    if (isset(DASHBOARD_CONVERT_CSS_COLOURS[$name])) {
        return '#' . DASHBOARD_CONVERT_CSS_COLOURS[$name];
    }
    if (!preg_match('/^#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $colour)) {
        return '';
    }
    return $colour[0] === '#' ? $colour : '#' . $colour;
}

// The CSS colour names and their hex values.
const DASHBOARD_CONVERT_CSS_COLOURS = [
    'aliceblue' => 'f0f8ff', 'antiquewhite' => 'faebd7', 'aqua' => '00ffff',
    'aquamarine' => '7fffd4', 'azure' => 'f0ffff', 'beige' => 'f5f5dc',
    'bisque' => 'ffe4c4', 'black' => '000000', 'blanchedalmond' => 'ffebcd',
    'blue' => '0000ff', 'blueviolet' => '8a2be2', 'brown' => 'a52a2a',
    'burlywood' => 'deb887', 'cadetblue' => '5f9ea0',
    'chartreuse' => '7fff00', 'chocolate' => 'd2691e', 'coral' => 'ff7f50',
    'cornflowerblue' => '6495ed', 'cornsilk' => 'fff8dc',
    'crimson' => 'dc143c', 'cyan' => '00ffff', 'darkblue' => '00008b',
    'darkcyan' => '008b8b', 'darkgoldenrod' => 'b8860b',
    'darkgray' => 'a9a9a9', 'darkgreen' => '006400', 'darkgrey' => 'a9a9a9',
    'darkkhaki' => 'bdb76b', 'darkmagenta' => '8b008b',
    'darkolivegreen' => '556b2f', 'darkorange' => 'ff8c00',
    'darkorchid' => '9932cc', 'darkred' => '8b0000', 'darksalmon' => 'e9967a',
    'darkseagreen' => '8fbc8f', 'darkslateblue' => '483d8b',
    'darkslategray' => '2f4f4f', 'darkslategrey' => '2f4f4f',
    'darkturquoise' => '00ced1', 'darkviolet' => '9400d3',
    'deeppink' => 'ff1493', 'deepskyblue' => '00bfff', 'dimgray' => '696969',
    'dimgrey' => '696969', 'dodgerblue' => '1e90ff', 'firebrick' => 'b22222',
    'floralwhite' => 'fffaf0', 'forestgreen' => '228b22',
    'fuchsia' => 'ff00ff', 'gainsboro' => 'dcdcdc', 'ghostwhite' => 'f8f8ff',
    'gold' => 'ffd700', 'goldenrod' => 'daa520', 'gray' => '808080',
    'green' => '008000', 'greenyellow' => 'adff2f', 'grey' => '808080',
    'honeydew' => 'f0fff0', 'hotpink' => 'ff69b4', 'indianred' => 'cd5c5c',
    'indigo' => '4b0082', 'ivory' => 'fffff0', 'khaki' => 'f0e68c',
    'lavender' => 'e6e6fa', 'lavenderblush' => 'fff0f5',
    'lawngreen' => '7cfc00', 'lemonchiffon' => 'fffacd',
    'lightblue' => 'add8e6', 'lightcoral' => 'f08080',
    'lightcyan' => 'e0ffff', 'lightgoldenrodyellow' => 'fafad2',
    'lightgray' => 'd3d3d3', 'lightgreen' => '90ee90',
    'lightgrey' => 'd3d3d3', 'lightpink' => 'ffb6c1',
    'lightsalmon' => 'ffa07a', 'lightseagreen' => '20b2aa',
    'lightskyblue' => '87cefa', 'lightslategray' => '778899',
    'lightslategrey' => '778899', 'lightsteelblue' => 'b0c4de',
    'lightyellow' => 'ffffe0', 'lime' => '00ff00', 'limegreen' => '32cd32',
    'linen' => 'faf0e6', 'magenta' => 'ff00ff', 'maroon' => '800000',
    'mediumaquamarine' => '66cdaa', 'mediumblue' => '0000cd',
    'mediumorchid' => 'ba55d3', 'mediumpurple' => '9370db',
    'mediumseagreen' => '3cb371', 'mediumslateblue' => '7b68ee',
    'mediumspringgreen' => '00fa9a', 'mediumturquoise' => '48d1cc',
    'mediumvioletred' => 'c71585', 'midnightblue' => '191970',
    'mintcream' => 'f5fffa', 'mistyrose' => 'ffe4e1', 'moccasin' => 'ffe4b5',
    'navajowhite' => 'ffdead', 'navy' => '000080', 'oldlace' => 'fdf5e6',
    'olive' => '808000', 'olivedrab' => '6b8e23', 'orange' => 'ffa500',
    'orangered' => 'ff4500', 'orchid' => 'da70d6',
    'palegoldenrod' => 'eee8aa', 'palegreen' => '98fb98',
    'paleturquoise' => 'afeeee', 'palevioletred' => 'db7093',
    'papayawhip' => 'ffefd5', 'peachpuff' => 'ffdab9', 'peru' => 'cd853f',
    'pink' => 'ffc0cb', 'plum' => 'dda0dd', 'powderblue' => 'b0e0e6',
    'purple' => '800080', 'rebeccapurple' => '663399', 'red' => 'ff0000',
    'rosybrown' => 'bc8f8f', 'royalblue' => '4169e1',
    'saddlebrown' => '8b4513', 'salmon' => 'fa8072', 'sandybrown' => 'f4a460',
    'seagreen' => '2e8b57', 'seashell' => 'fff5ee', 'sienna' => 'a0522d',
    'silver' => 'c0c0c0', 'skyblue' => '87ceeb', 'slateblue' => '6a5acd',
    'slategray' => '708090', 'slategrey' => '708090', 'snow' => 'fffafa',
    'springgreen' => '00ff7f', 'steelblue' => '4682b4', 'tan' => 'd2b48c',
    'teal' => '008080', 'thistle' => 'd8bfd8', 'tomato' => 'ff6347',
    'turquoise' => '40e0d0', 'violet' => 'ee82ee', 'wheat' => 'f5deb3',
    'white' => 'ffffff', 'whitesmoke' => 'f5f5f5', 'yellow' => 'ffff00',
    'yellowgreen' => '9acd32',
];

// A colour option as the graph engine wants it, with the hash the designer
// strips put back. The default is what the visualisation drew when the option
// was never set.
function dashboard_convert_preset_colour($options, $key, $default)
{
    $colour = dashboard_convert_hex_colour(dashboard_convert_preset_text($options, $key));
    return $colour === '' ? $default : $colour;
}

// What the widget painted behind its chart. These drew in an iframe and
// coloured the body of it, white when the option was never set, so the box the
// widget becomes is given the colour it drew on.
function dashboard_convert_preset_background($options)
{
    $colour = ltrim(dashboard_convert_preset_text($options, 'colourbg'), '#');
    if (!preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $colour)) {
        return 'ffffff';
    }
    return strtolower($colour);
}

function dashboard_convert_preset_dp($options)
{
    $dp = dashboard_convert_preset_text($options, 'dp');
    if (!is_numeric($dp)) {
        return '1';
    }
    return (string) max(0, (int) round((float) $dp));
}

function dashboard_convert_preset_scale($options)
{
    $scale = dashboard_convert_preset_text($options, 'scale');
    if (!is_numeric($scale) || (float) $scale == 0) {
        return '1';
    }
    return $scale;
}

function dashboard_convert_preset_text($options, $key)
{
    if (!isset($options[$key]) || is_array($options[$key])) {
        return '';
    }
    return trim((string) $options[$key]);
}

function dashboard_convert_preset_flag($options, $key)
{
    $value = dashboard_convert_preset_text($options, $key);
    return $value !== '' && $value !== '0' && $value !== 'false';
}

function dashboard_convert_preset_count(&$counts, $key)
{
    if (!isset($counts[$key])) {
        $counts[$key] = 0;
    }
    $counts[$key]++;
}

// ---------------------------------------------------------------------------
// multigraph
// ---------------------------------------------------------------------------

// A multigraph feed list as a graph, in the shape a saved graph stores.
// dashboard_convert_multigraph_config turns it into the config a widget holds.
//
// The graph level settings are read from the first entry, which is where the
// multigraph editor hides them. Two fields have no equivalent and are dropped,
// see the multigraph section of notes/CHARTS.md: barwidth, which the graph
// engine draws at a fixed width, and autorefresh, which the dashboard update
// cycle replaces.
//
// backgroundColour is not part of a graph. It coloured the body of the iframe
// a multigraph drew in, so it is returned on its own for the widget box, which
// is what paints behind a chart now.
//
// @param string $feedlist_raw the feedlist column
// @param string $name the name column
// @param int $now milliseconds, for a floating window to be anchored to
// @return array data, the background for the widget box, and dropped field
//         name => count
function dashboard_convert_multigraph_to_graph($feedlist_raw, $name, $now = null)
{
    if ($now === null) {
        $now = (int) round(microtime(true) * 1000);
    }

    $dropped = [];
    $raw = trim((string) $feedlist_raw);
    $feeds = $raw === '' ? [] : json_decode($raw, true);
    if (!is_array($feeds)) {
        // An empty column is a multigraph nobody finished, which converts to a
        // graph with no feeds. Anything else in there is damaged.
        $feeds = [];
        $dropped['feedlist_unreadable'] = 1;
    }

    $first = (isset($feeds[0]) && is_array($feeds[0])) ? $feeds[0] : [];
    $window = dashboard_convert_multigraph_window($first, $now);

    $graph = [
        'name'          => trim((string) $name),
        'start'         => $window['start'],
        'end'           => $window['end'],
        'floatingtime'  => $window['floating'],
        'mode'          => dashboard_convert_multigraph_mode($first),
        'interval'      => dashboard_convert_multigraph_interval($window),
        'fixinterval'   => false,
        'limitinterval' => 1,
        'showtag'       => dashboard_convert_multigraph_flag($first, 'showtag', true) ? 1 : 0,
        'showlegend'    => dashboard_convert_multigraph_flag($first, 'showlegend', true) ? 1 : 0,
        // skipmissing is per feed in a multigraph and graph level in a graph.
        // The first entry is the one that decides, the same entry the rest of
        // the graph level settings come from.
        'showmissing'   => dashboard_convert_multigraph_flag($first, 'skipmissing', false) ? 0 : 1,
        'yaxismin'      => dashboard_convert_multigraph_bound($first, 'ymin'),
        'yaxismax'      => dashboard_convert_multigraph_bound($first, 'ymax'),
        'yaxismin2'     => dashboard_convert_multigraph_bound($first, 'y2min'),
        'yaxismax2'     => dashboard_convert_multigraph_bound($first, 'y2max'),
        'feedlist'      => [],
    ];

    foreach ($feeds as $entry) {
        if (!is_array($entry)) {
            dashboard_convert_multigraph_count($dropped, 'entry_unreadable');
            continue;
        }
        if (!isset($entry['id']) || (string) $entry['id'] === '') {
            dashboard_convert_multigraph_count($dropped, 'feed_without_id');
            continue;
        }

        foreach (['barwidth', 'autorefresh'] as $field) {
            if (isset($entry[$field])) {
                dashboard_convert_multigraph_count($dropped, $field);
            }
        }

        $graph['feedlist'][] = [
            'id'       => (string) $entry['id'],
            'name'     => isset($entry['name']) ? (string) $entry['name'] : '',
            'tag'      => isset($entry['tag'])  ? (string) $entry['tag']  : '',
            'unit'     => isset($entry['unit']) ? (string) $entry['unit'] : '',
            // The editor offers left and right as one pair of radio buttons,
            // and defaults to left when neither is set.
            'yaxis'    => (isset($entry['right']) && $entry['right']) ? 2 : 1,
            'plottype' => dashboard_convert_multigraph_plottype($entry),
            'color'    => dashboard_convert_multigraph_colour($entry),
            'fill'     => dashboard_convert_multigraph_flag($entry, 'fill', false)    ? 1 : 0,
            'stack'    => dashboard_convert_multigraph_flag($entry, 'stacked', false) ? 1 : 0,
            'delta'    => dashboard_convert_multigraph_flag($entry, 'delta', false)   ? 1 : 0,
            'average'  => dashboard_convert_multigraph_flag($entry, 'average', false) ? 1 : 0,
            'scale'    => '1',
            'offset'   => '0',
            'dp'       => 1,
        ];
    }

    return [
        'data'       => $graph,
        'background' => dashboard_convert_multigraph_background($first),
        'dropped'    => $dropped,
    ];
}

// A graph as the widget holds it. Only what the widget declares is kept, in
// strings, so the config that is written is one the document validation keeps
// whole. Follows graph_config_from_saved in the graph widget.
//
// @param array $data a graph in the shape a saved graph stores
// @return array config with a state block and a feedlist block
function dashboard_convert_multigraph_config($data)
{
    $declared = widget_registry_config('graph');
    $config = ['state' => [], 'feedlist' => []];

    foreach ($declared['state'] as $name => $entry) {
        if (!isset($data[$name]) || is_array($data[$name])) {
            continue;
        }
        $value = $data[$name];
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }
        $config['state'][$name] = (string) $value;
    }

    $feeds = isset($data['feedlist']) && is_array($data['feedlist']) ? $data['feedlist'] : [];
    foreach ($feeds as $feed) {
        if (!is_array($feed)) {
            continue;
        }
        $kept = [];
        foreach ($declared['feedlist'] as $name => $entry) {
            if (!isset($feed[$name]) || is_array($feed[$name])) {
                continue;
            }
            $value = $feed[$name];
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            // A saved graph could hold a colour name such as red.
            if ($name === 'color' && $value !== '') {
                $value = dashboard_convert_hex_colour($value);
            }
            $kept[$name] = (string) $value;
        }
        if (count($kept)) {
            $config['feedlist'][] = $kept;
        }
    }

    return $config;
}

// The window a multigraph stores, on its first entry like the rest of its graph
// level settings. timeWindow is the length and end is where it stops, and an
// end of zero means it ends now, which is what floatingtime is in a graph. A
// multigraph saved before the editor wrote these opened on the last week, see
// multigraphInit in the vis module.
function dashboard_convert_multigraph_window($first, $now)
{
    $length = 0;
    if (isset($first['timeWindow']) && is_numeric($first['timeWindow'])) {
        $length = (int) round((float) $first['timeWindow']);
    }
    if ($length <= 0) {
        $length = 7 * 86400000;
    }

    $end = 0;
    if (isset($first['end']) && is_numeric($first['end'])) {
        $end = (int) round((float) $first['end']);
    }

    // A fixed end in the future is one a browser clock was ahead for. It is
    // treated as floating rather than stored as a window nobody can reach.
    if ($end > 0 && $end <= $now) {
        return ['start' => $end - $length, 'end' => $end, 'floating' => 0];
    }
    return ['start' => $now - $length, 'end' => $now, 'floating' => 1];
}

// The step to read the feeds at. A multigraph worked this out every time it
// drew, from a fixed 2400 datapoints across whatever window it was showing,
// and left limitinterval to raise it to the interval of the feed. A saved
// graph stores the step instead, and nothing recalculates it when one is
// loaded, so the conversion has to work out the same number once.
function dashboard_convert_multigraph_interval($window)
{
    $seconds = max(1, (int) round(($window['end'] - $window['start']) / 1000));
    return max(1, (int) round($seconds / 2400));
}

// standard means the graph picks an interval for the window it is showing,
// which is what mode interval does. The rest carry over by name.
function dashboard_convert_multigraph_mode($first)
{
    $type = isset($first['intervaltype']) ? (string) $first['intervaltype'] : 'standard';
    if ($type === 'daily' || $type === 'weekly' || $type === 'monthly') {
        return $type;
    }
    return 'interval';
}

function dashboard_convert_multigraph_plottype($entry)
{
    $type = isset($entry['graphtype']) ? (string) $entry['graphtype'] : 'lines';
    if ($type === 'bars') {
        return 'bars';
    }
    if ($type === 'lineswithsteps') {
        return 'steps';
    }
    return 'lines';
}

// A multigraph stores a colour with no hash, and the editor writes one with a
// hash. Both reach the graph engine as a hex colour.
function dashboard_convert_multigraph_colour($entry)
{
    return dashboard_convert_hex_colour(isset($entry['lineColour']) ? $entry['lineColour'] : '');
}

// What a multigraph painted behind its chart. It drew in an iframe and coloured
// the body of it, white when the field was never set, see convertToPlotlist in
// the vis module. A chart draws in the dashboard page now, so the colour goes
// on the widget box, in the hex the designer writes there.
function dashboard_convert_multigraph_background($first)
{
    $colour = isset($first['backgroundColour']) ? trim((string) $first['backgroundColour']) : '';
    $colour = ltrim($colour, '#');
    if (!preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $colour)) {
        return 'ffffff';
    }
    return strtolower($colour);
}

// An axis bound is a number or nothing at all. A multigraph writes null for an
// axis it leaves alone, a graph writes auto.
function dashboard_convert_multigraph_bound($first, $field)
{
    if (!isset($first[$field]) || $first[$field] === '' || $first[$field] === null) {
        return 'auto';
    }
    if (!is_numeric($first[$field])) {
        return 'auto';
    }
    return (string) $first[$field];
}

function dashboard_convert_multigraph_flag($entry, $field, $default)
{
    if (!isset($entry[$field])) {
        return $default;
    }
    $value = $entry[$field];
    if (is_string($value)) {
        return $value !== '' && $value !== '0' && $value !== 'false';
    }
    return (bool) $value;
}

function dashboard_convert_multigraph_count(&$counts, $key)
{
    dashboard_convert_preset_count($counts, $key);
}
