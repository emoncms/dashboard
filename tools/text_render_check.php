<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

---------------------------------------------------------------------
The roundtrip check for a converted text widget: the old widget and the new
one are drawn in a browser and the results are compared.

The stored content cannot be compared. The old widget takes its size, weight
and alignment from widget.css and the new one from its options. The drawing is
compared instead: the text of each line, its position in the box, and the
font, weight, colour and alignment.

Both sides go through dashboard_render.php and the real render scripts.

Images are not compared. The old widget draws an image at its natural size and
the new one fits it to the box, and the image is not fetched. They are counted
separately.

Needs google-chrome or chromium. Without one the check reports no_browser and
the caller continues with the other counts.
*/

defined('EMONCMS_EXEC') or die('Restricted access');

require_once dirname(__FILE__) . "/../dashboard_render.php";

// Page width for per cent geometry. Both sides use it, so the value does not
// affect the comparison.
define('TEXT_RENDER_CHECK_PAGE', 1000);

function text_render_check_browser()
{
    static $found = null;
    if ($found !== null) return $found;

    foreach (array('google-chrome', 'chromium', 'chromium-browser', 'google-chrome-stable') as $name) {
        $path = trim((string) shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null'));
        if ($path !== '') return $found = $path;
    }
    return $found = '';
}

/**
 * Compares a batch of conversions in one browser run.
 *
 * @param array $pairs each array('old' => widget, 'new' => widget)
 * @param string $error set when the browser could not be run
 * @return array one result per pair, in the same order, each
 *               array('verdict' => identical|differs|not_compared,
 *                     'detail' => string)
 */
function text_render_check($pairs, &$error = null)
{
    $error = '';
    if (!count($pairs)) return array();

    $browser = text_render_check_browser();
    if ($browser === '') {
        $error = 'no browser: install google-chrome or chromium';
        return array_fill(0, count($pairs), array('verdict' => 'not_compared',
            'detail' => 'no_browser'));
    }

    $results = array();
    $drawn = array();
    foreach ($pairs as $i => $pair) {
        if (isset($pair['new']['type']) && $pair['new']['type'] === 'image') {
            $results[$i] = array('verdict' => 'not_compared', 'detail' => 'image_not_fetched');
            continue;
        }
        $results[$i] = array('verdict' => 'not_compared', 'detail' => 'not_drawn');
        $drawn[$i] = $pair;
    }
    if (!count($drawn)) return $results;

    $page = text_render_check_page($drawn);
    $dir = sys_get_temp_dir() . '/dashboard-text-check-' . getmypid();
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
        $error = "cannot write to $dir";
        return $results;
    }
    $file = $dir . '/check.html';
    file_put_contents($file, $page);

    $command = escapeshellarg($browser)
        . ' --headless --disable-gpu --no-sandbox --disable-extensions'
        . ' --hide-scrollbars --window-size=1200,800'
        . ' --user-data-dir=' . escapeshellarg($dir . '/profile')
        . ' --virtual-time-budget=5000 --dump-dom'
        . ' ' . escapeshellarg('file://' . $file) . ' 2>/dev/null';
    $dom = (string) shell_exec($command);

    @unlink($file);
    text_render_check_rmdir($dir . '/profile');
    @rmdir($dir);

    if (!preg_match('/<div id="result">([A-Za-z0-9+\/=]*)<\/div>/', $dom, $match)) {
        $error = 'the browser printed no result';
        return $results;
    }
    $report = json_decode(base64_decode($match[1]), true);
    if (!is_array($report)) {
        $error = 'the browser printed a result that could not be read';
        return $results;
    }

    foreach ($report as $key => $sides) {
        $i = (int) $key;
        if (!isset($results[$i])) continue;
        $results[$i] = text_render_check_compare($sides);
    }

    return $results;
}

// Compares the two sides and names the fields that differ.
function text_render_check_compare($sides)
{
    if (!isset($sides['old']) || !isset($sides['new'])) {
        return array('verdict' => 'not_compared', 'detail' => 'no_lines');
    }

    $old = $sides['old'];
    $new = $sides['new'];

    if (count($old) !== count($new)) {
        return array('verdict' => 'differs',
            'detail' => 'lines ' . count($old) . ' to ' . count($new));
    }

    $differences = array();
    foreach ($old as $n => $line) {
        foreach ($line as $field => $value) {
            $there = isset($new[$n][$field]) ? $new[$n][$field] : null;
            if ($there !== $value) $differences[$field] = true;
        }
    }

    if (!count($differences)) return array('verdict' => 'identical', 'detail' => '');
    return array('verdict' => 'differs', 'detail' => implode(' ', array_keys($differences)));
}

function text_render_check_rmdir($dir)
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $path = $dir . '/' . $entry;
        if (is_dir($path)) text_render_check_rmdir($path);
        else @unlink($path);
    }
    @rmdir($dir);
}

// ---------------------------------------------------------------------------
// The page
// ---------------------------------------------------------------------------

// Each pair gets two boxes of the same geometry side by side. The widget is
// drawn at the origin of its box, so its page position does not affect the
// comparison.
function text_render_check_page($pairs)
{
    $root = realpath(dirname(__FILE__) . '/../../..');
    $registry = widget_registry();

    $body = '';
    foreach ($pairs as $i => $pair) {
        $body .= '<div class="pair" data-i="' . (int) $i . '">';
        foreach (array('old', 'new') as $side) {
            $widget = $pair[$side];
            $widget['x'] = 0;
            $widget['y'] = 0;
            $errors = array();
            $body .= '<div class="side" data-side="' . $side . '" style="position:relative;width:'
                . TEXT_RENDER_CHECK_PAGE . 'px;height:400px;">'
                . dashboard_render_widget($widget, $i, $registry, $errors)
                . '</div>';
        }
        $body .= '</div>';
    }

    $asset = function ($path) use ($root) {
        return 'file://' . $root . '/' . $path;
    };

    $script = text_render_check_script();

    return '<!DOCTYPE html><html><head><meta charset="utf-8">'
        . '<link rel="stylesheet" href="' . $asset('Lib/bootstrap/css/bootstrap.min.css') . '">'
        . '<link rel="stylesheet" href="' . $asset('Lib/bootstrap/css/bootstrap-responsive.min.css') . '">'
        . '<link rel="stylesheet" href="' . $asset('Theme/css/emoncms-base.css') . '">'
        . '<link rel="stylesheet" href="' . $asset('Modules/dashboard/Views/js/widget.css') . '">'
        . '<style>.pair{overflow:hidden;}.side{float:left;}</style>'
        . '</head><body>' . $body
        . '<script src="' . $asset('Lib/js/jquery-3.6.0.min.js') . '"></script>'
        . '<script>function _Tr(s){return s;}function addOption(){}</script>'
        . '<script src="' . $asset('Modules/dashboard/widget/text/text_render.js') . '"></script>'
        . '<script src="' . $asset('Modules/dashboard/widget/image/image_render.js') . '"></script>'
        . '<script>' . $script . '</script>'
        . '</body></html>';
}

// Reads each line of text in a box: its text, position and computed style.
// Run on both sides of each pair.
function text_render_check_script()
{
    return <<<'JS'
text_init();
image_init();

function lines(box) {
    var out = [];
    var rect = box.getBoundingClientRect();
    var walker = document.createTreeWalker(box, NodeFilter.SHOW_TEXT);
    var node;
    while ((node = walker.nextNode())) {
        var text = node.nodeValue.replace(/\s+/g, ' ').trim();
        if (text === '') continue;
        var range = document.createRange();
        range.selectNodeContents(node);
        var r = range.getBoundingClientRect();
        var style = getComputedStyle(node.parentElement);
        out.push({
            text: text,
            x: Math.round(r.left - rect.left),
            y: Math.round(r.top - rect.top),
            w: Math.round(r.width),
            h: Math.round(r.height),
            size: style.fontSize,
            weight: style.fontWeight,
            family: style.fontFamily,
            colour: style.color,
            // A center element reports -webkit-center, which draws the same
            align: style.textAlign.replace('-webkit-', ''),
            style: style.fontStyle,
            decoration: style.textDecorationLine
        });
    }
    return out;
}

var report = {};
document.querySelectorAll('.pair').forEach(function (pair) {
    var entry = {};
    pair.querySelectorAll('.side').forEach(function (side) {
        var box = side.firstElementChild;
        if (box) entry[side.getAttribute('data-side')] = lines(box);
    });
    report[pair.getAttribute('data-i')] = entry;
});

var out = document.createElement('div');
out.id = 'result';
out.textContent = btoa(unescape(encodeURIComponent(JSON.stringify(report))));
document.body.appendChild(out);
JS;
}
