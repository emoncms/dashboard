<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Migrates every stored dashboard in one run, and reports what it finds.
//
// The same conversions run on their own the first time a dashboard is loaded,
// so nothing here is needed to migrate an install. This is for an admin who
// wants the whole table moved at once, with counts to read off, or wants to
// see what a conversion would do before it does it.
//
// Three stages, run in this order when none is named:
//
//   --html      html in the content column to a document in content_json
//   --widgets   paragraph, heading, heading-center and Container-* to the
//               text, image and panel widgets, where the converters accept them
//   --charts    multigraph, rawdata, bargraph, stacked, simplezoom and
//               smoothie to graph, zoom and realtime widgets
//
// Run from the emoncms root. It reports and changes nothing without --write:
//
//   php Modules/dashboard/tools/migrate.php
//   php Modules/dashboard/tools/migrate.php --write
//   php Modules/dashboard/tools/migrate.php --charts --id=42
//
// Without --write the widgets and charts stages read a dashboard still on
// html by converting it in memory, so a report covers every dashboard from
// either starting point. With --write each stage sees what the one before
// it stored. Only content_json is written. The content, multigraph and graph
// tables are not touched.
//
// A graph widget once pointed at a saved graph with graphid. A widget still
// pointing at one is folded in here, the chart of the saved graph goes in
// the widget, as a load does, see dashboard_migrate_graph_pointers.

defined('EMONCMS_EXEC') or define('EMONCMS_EXEC', 1);

require_once dirname(__FILE__) . "/cli.php";
require_once dirname(__FILE__) . "/../dashboard_render.php";

// ---------------------------------------------------------------------------
// Chart rewrites
//
// Kept as functions of a document so the tests can drive them.
// ---------------------------------------------------------------------------

// Rewrites every multigraph widget in a document as a graph widget holding
// its chart.
//
// A widget with no mid, or one naming a multigraph that is not there, becomes
// a graph widget with no chart, and is counted. A graph widget pointing at a
// saved graph is folded in: the chart of the saved graph goes in the widget
// and the pointer is removed. A run covering one multigraph leaves every
// other multigraph widget alone and folds nothing in.
//
// @param array $document decoded content_json
// @param array $configs multigraph id => config
// @param array $counts added to in place
// @param bool $partial true when the run covers only some of the multigraphs
// @param array $backgrounds multigraph id => the colour its box is given
// @param callable|null $graph reads a saved graph by id, see
//                             dashboard_migrate_graph_pointers
// @return array|null the changed document, or null if there was nothing to do
function multigraph_rewrite(
    $document,
    $configs,
    &$counts,
    $partial = false,
    $backgrounds = [],
    $graph = null
) {
    if (!isset($document['widgets']) || !is_array($document['widgets'])) {
        return null;
    }

    $context = ['multigraph' => function ($mid) use ($configs, $backgrounds) {
        if (!isset($configs[$mid])) {
            return null;
        }
        $row = ['config' => $configs[$mid]];
        if (isset($backgrounds[$mid])) {
            $row['background'] = $backgrounds[$mid];
        }
        return $row;
    }
    ];

    $changed = false;
    foreach ($document['widgets'] as $i => $widget) {
        if (!is_array($widget) || !isset($widget['type'])) {
            continue;
        }
        $options = (isset($widget['options']) && is_array($widget['options']))
            ? $widget['options'] : [];

        if ($widget['type'] !== 'multigraph') {
            continue;
        }

        $mid = isset($options['mid']) ? trim((string) $options['mid']) : '';
        if ($partial && !isset($configs[$mid])) {
            dashboard_convert_multigraph_count($counts, 'widget_left_alone');
            continue;
        }

        $reason = '';
        $converted = dashboard_convert_chart_widget($widget, $reason, $context, $counts);
        if ($converted === null) {
            continue;
        }

        $document['widgets'][$i] = $converted;
        $changed = true;
    }

    if (!$partial) {
        $context = $graph !== null ? ['graph' => $graph] : [];
        foreach (dashboard_migrate_graph_pointers($document, $context) as $folded) {
            $key = $folded['result'] === 'loaded' ? 'widget_folded'
                : ($folded['result'] === 'gone' ? 'widget_graph_gone' : 'widget_pointer_cleared');
            dashboard_convert_multigraph_count($counts, $key);
            $changed = true;
        }
    }

    return $changed ? $document : null;
}

// Rewrites every rawdata, bargraph, stacked, simplezoom and smoothie widget
// in a document as the widget it becomes.
//
// @param array $document decoded content_json
// @param array $counts added to in place
// @param int $now milliseconds, for the windows to be anchored to
// @return array|null the changed document, or null if there was nothing to do
function preset_rewrite($document, &$counts, $now = null)
{
    if (!isset($document['widgets']) || !is_array($document['widgets'])) {
        return null;
    }

    $context = [];
    if ($now !== null) {
        $context['now'] = $now;
    }

    $changed = false;
    foreach ($document['widgets'] as $i => $widget) {
        if (!is_array($widget) || !isset($widget['type'])) {
            continue;
        }
        if (!in_array($widget['type'], dashboard_convert_preset_types(), true)) {
            continue;
        }

        $reason = '';
        $converted = dashboard_convert_chart_widget($widget, $reason, $context, $counts);
        if ($converted === null) {
            continue;
        }

        $document['widgets'][$i] = $converted;
        $changed = true;
    }

    return $changed ? $document : null;
}

// ---------------------------------------------------------------------------
// Shared by the stages
// ---------------------------------------------------------------------------

// Warnings from the html conversion that mean something an author wrote did
// not survive. The rest are generated markup, artefacts and debris.
function migrate_authored_codes()
{
    return [
        'unparsable', 'widget_without_type', 'widget_type_not_one_token',
        'nested_widget_dropped', 'iframe_dropped', 'tag_dropped', 'tag_unwrapped',
        'url_dropped', 'attribute_dropped', 'option_unknown_dropped',
        'option_value_dropped', 'text_outside_widget', 'position_fixed_dropped',
        'style_property_dropped', 'style_value_dropped', 'geometry_missing'
    ];
}

// The stored document of a row, or the html converted in memory where there
// is none. Returns null for a row with nothing in it or a document that
// cannot be read, and says which in $state.
function migrate_document($row, &$state)
{
    if (trim($row['content_json']) !== '') {
        $document = json_decode($row['content_json'], true);
        if (is_array($document) && isset($document['widgets']) && is_array($document['widgets'])) {
            $state = 'stored';
            return $document;
        }
        $state = 'unreadable';
        return null;
    }
    if (trim($row['content']) === '') {
        $state = 'empty';
        return null;
    }
    $document = dashboard_convert($row['content'])['document'];
    $state = $document === null ? 'unreadable' : 'from html';
    return $document;
}

// Stores a document, after checking it still draws. A type nothing declares
// draws as a placeholder, as it did before, so that is not a fault.
function migrate_store($mysqli, $update, $id, $document, &$failed)
{
    foreach (dashboard_render($document)['errors'] as $error) {
        if ($error['code'] === 'widget_type_unknown') {
            continue;
        }
        echo "  dashboard $id would not draw after the rewrite: " . $error['code'] . "\n";
        $failed++;
        return false;
    }
    $encoded = dashboard_convert_encode($document);
    if ($encoded === false) {
        echo "  dashboard $id could not be encoded: " . json_last_error_msg() . "\n";
        $failed++;
        return false;
    }
    $update->bind_param("si", $encoded, $id);
    if (!$update->execute()) {
        echo "  dashboard $id could not be written: " . $update->error . "\n";
        $failed++;
        return false;
    }
    return true;
}

function migrate_footer($write, $written, $changed)
{
    if ($write) {
        echo "\nWritten to content_json: $written\n";
    } elseif ($changed) {
        echo "\nNothing was changed. Add --write to store the documents.\n";
    } else {
        echo "\nNothing to change.\n";
    }
}

// ---------------------------------------------------------------------------
// Stage: html
// ---------------------------------------------------------------------------

function migrate_html($mysqli, $o, &$failed)
{
    $update = $mysqli->prepare("UPDATE dashboard SET content_json=? WHERE id=?");
    if (!$update) {
        die("Cannot prepare update: " . $mysqli->error . "\n");
    }

    $authored = migrate_authored_codes();
    $total = 0;
    $empty = 0;
    $already = 0;
    $skipped = 0;
    $clean = 0;
    $warned = 0;
    $unreadable = 0;
    $nothing = 0;
    $written = 0;
    $widgets = 0;
    $html_bytes = 0;
    $json_bytes = 0;
    $codes = [];
    $examples = [];
    $worst = [];
    $nothing_ids = [];

    foreach (cli_dashboards($mysqli, ['id', 'userid', 'content', 'content_json'], $o['id']) as $row) {
        $id = (int) $row['id'];
        $total++;
        if ($o['limit'] > 0 && $total > $o['limit']) {
            $total--;
            break;
        }
        if (isset($o['skip'][$id])) {
            $skipped++;
            continue;
        }

        if (trim($row['content_json']) !== '' && !$o['force']) {
            $already++;
            continue;
        }
        if (trim($row['content']) === '') {
            $empty++;
            continue;
        }

        $result = dashboard_convert($row['content']);
        $document = $result['document'];

        if ($o['id']) {
            echo "dashboard $id\n";
            foreach ($result['warnings'] as $warning) {
                echo "  " . $warning['code'] . ": " . $warning['detail'] . "\n";
            }
            echo dashboard_convert_encode($document, JSON_PRETTY_PRINT) . "\n";
        }

        // Counted once per dashboard, so one dashboard repeating the same
        // problem does not look like many dashboards having it.
        $seen = [];
        $authored_here = 0;
        foreach ($result['warnings'] as $warning) {
            $code = $warning['code'];
            if (!isset($seen[$code])) {
                $seen[$code] = true;
                if (!isset($codes[$code])) {
                    $codes[$code] = ['dashboards' => 0, 'total' => 0];
                }
                $codes[$code]['dashboards']++;
            }
            $codes[$code]['total']++;
            if (in_array($code, $authored)) {
                $authored_here++;
            }
            if ($o['show'] === $code && count($examples) < $o['samples']) {
                $examples[] = "dashboard $id user " . $row['userid'] . ": " . $warning['detail'];
            }
        }

        if ($document === null) {
            echo "  dashboard $id could not be read\n";
            $unreadable++;
            continue;
        }
        if (!count($document['widgets'])) {
            // Content with no widget in it. Left alone, so the html stays the
            // only copy and can be looked at.
            $nothing++;
            $nothing_ids[] = $id;
            continue;
        }

        if ($authored_here) {
            $warned++;
            $worst[$id] = $authored_here;
        } else {
            $clean++;
        }

        $widgets += count($document['widgets']);
        $html_bytes += strlen($row['content']);
        $json_bytes += strlen(dashboard_convert_encode($document));

        if ($o['write'] && migrate_store($mysqli, $update, $id, $document, $failed)) {
            $written++;
        }
    }
    $update->close();

    print_examples($o['show'], $examples, 'dashboard raised');

    $converted = $clean + $warned;
    echo "Dashboards:        $total\n";
    echo "  empty:           $empty\n";
    if ($already) {
        echo "  already done:    $already\n";
    }
    if ($skipped) {
        echo "  left out:        $skipped\n";
    }
    if ($unreadable) {
        echo "  unreadable:      $unreadable\n";
    }
    echo "  no widgets:      $nothing" . ($nothing ? " (" . implode(' ', array_slice($nothing_ids, 0, 10))
        . (count($nothing_ids) > 10 ? ' ...' : '') . ")" : "") . "\n";
    printf("  clean:           %d%s\n", $clean, share($clean, $converted));
    printf("  warned:          %d%s\n", $warned, share($warned, $converted));
    echo "  widgets:         $widgets\n";
    if ($html_bytes) {
        printf(
            "  size:            %s of html to %s of json (%d%%)\n",
            bytes($html_bytes),
            bytes($json_bytes),
            round(100 * $json_bytes / $html_bytes)
        );
    }

    if (count($codes)) {
        echo "\nWarnings, by how many dashboards raised each\n";
        uasort($codes, function ($a, $b) {
            return $b['dashboards'] - $a['dashboards'];
        });
        foreach ($codes as $code => $count) {
            $mark = in_array($code, $authored) ? ' *' : '  ';
            printf(
                "%s %-32s %5d dashboards %8d in total\n",
                $mark,
                $code,
                $count['dashboards'],
                $count['total']
            );
        }
        echo "\n  * something an author wrote was dropped. Use --show=CODE for examples.\n";
    }
    if (count($worst)) {
        arsort($worst);
        echo "\nDashboards with the most dropped\n";
        foreach (array_slice($worst, 0, $o['samples'], true) as $id => $count) {
            printf("  dashboard %-8s %d\n", $id, $count);
        }
    }

    migrate_footer($o['write'], $written, $converted);
}

// ---------------------------------------------------------------------------
// Stage: widgets
// ---------------------------------------------------------------------------

function migrate_widgets_stage($mysqli, $o, &$failed)
{
    if (
        widget_registry_option('text', 'text') === false
        || widget_registry_option('image', 'src') === false
        || widget_registry_option('panel', 'colour') === false
    ) {
        die("The text, image and panel widgets have no declaration. Generate it with:\n"
            . "  node Modules/dashboard/tools/extract_registry.js\n");
    }

    $update = $mysqli->prepare("UPDATE dashboard SET content_json=? WHERE id=?");
    if (!$update) {
        die("Cannot prepare update: " . $mysqli->error . "\n");
    }

    $text_types = array_keys(dashboard_convert_text_widget_defaults());
    $panel_types = array_keys(dashboard_convert_panel_defaults());
    $old_types = array_merge($text_types, $panel_types);

    $total = 0;
    $empty = 0;
    $from_html = 0;
    $skipped = 0;
    $unreadable = 0;
    $upgraded = 0;
    $changed = 0;
    $written = 0;
    $found = 0;
    $by_type = [];
    $by_tier = [];
    $by_holds = [];
    $reasons = [];
    $examples = [];
    $remaining = [];
    $heights = [];
    $height_only = 0;

    foreach (cli_dashboards($mysqli, ['id', 'userid', 'content', 'content_json'], $o['id']) as $row) {
        $id = (int) $row['id'];
        $total++;
        if ($o['limit'] > 0 && $total > $o['limit']) {
            $total--;
            break;
        }
        if (isset($o['skip'][$id])) {
            $skipped++;
            continue;
        }

        $state = '';
        $document = migrate_document($row, $state);
        if ($state === 'empty') {
            $empty++;
            continue;
        }
        if ($state === 'unreadable') {
            echo "  dashboard $id holds a document that cannot be read\n";
            $unreadable++;
            continue;
        }
        if ($state === 'from html') {
            $from_html++;
        }
        $version_before = isset($document['version']) ? $document['version'] : 0;
        if (dashboard_upgrade_document($document)) {
            $upgraded++;
        }

        // What each old widget holds, read before it is replaced.
        $before = [];
        foreach ($document['widgets'] as $index => $widget) {
            if (!isset($widget['type']) || !in_array($widget['type'], $old_types)) {
                continue;
            }
            $before[$index] = in_array($widget['type'], $text_types)
                ? text_tier(isset($widget['html']) ? $widget['html'] : '')
                : panel_holds($widget);
        }
        $old = $document['widgets'];

        $kept = [];
        $swapped = dashboard_migrate_widgets($document, $kept, [], $old_types);
        $found += count($swapped) + count($kept);

        // Counts by type and by what the widget held, text and containers apart.
        $count = function ($type, $holds, $outcome) use (&$by_type, &$by_tier, &$by_holds, $text_types) {
            bump($by_type, "$type\tfound");
            bump($by_type, "$type\t$outcome");
            $table = in_array($type, $text_types) ? 'by_tier' : 'by_holds';
            bump($$table, "$holds\tfound");
            bump($$table, "$holds\t$outcome");
        };

        foreach ($swapped as $swap) {
            $index = $swap['index'];
            $count($swap['from'], $before[$index], 'converted');
            if ($swap['from'] !== 'paragraph' && in_array($swap['from'], $text_types)) {
                heading_height($old[$index], $document['widgets'][$index], '', $heights, $height_only);
            }
            if ($o['id']) {
                echo str_pad($swap['from'], 20) . "to " . $swap['to'] . "  "
                    . dashboard_convert_encode($document['widgets'][$index]) . "\n";
            }
        }
        foreach ($kept as $keep) {
            $index = $keep['index'];
            $code = explode(':', $keep['reason'])[0];
            $count($keep['type'], $before[$index], 'kept');
            bump($reasons, $code);
            if ($keep['type'] !== 'paragraph' && in_array($keep['type'], $text_types)) {
                heading_height($old[$index], null, $keep['reason'], $heights, $height_only);
            }
            if ($o['show'] === $code && count($examples) < $o['samples']) {
                $examples[] = "dashboard $id: " . $keep['reason'] . "\n      " . describe($old[$index]);
            }
            if ($o['id']) {
                echo str_pad($keep['type'], 20) . "kept, " . $keep['reason'] . "\n";
            }
        }
        if (count($kept)) {
            $codes = [];
            foreach ($kept as $keep) {
                bump($codes, $keep['type'] . ' ' . explode(':', $keep['reason'])[0]);
            }
            $remaining[] = ['id' => $id, 'userid' => (int) $row['userid'], 'codes' => $codes];
        }

        if (!count($swapped) && $document['version'] === $version_before) {
            continue;
        }
        $changed++;
        if ($o['write'] && migrate_store($mysqli, $update, $id, $document, $failed)) {
            $written++;
        }
    }
    $update->close();

    print_examples($o['show'], $examples, 'widget was kept');

    echo "Dashboards:          $total\n";
    echo "  empty:             $empty\n";
    if ($from_html) {
        echo "  still on html:     $from_html" . ($o['write'] ? "" : " (converted in memory)") . "\n";
    }
    if ($skipped) {
        echo "  left out:          $skipped\n";
    }
    if ($unreadable) {
        echo "  unreadable:        $unreadable\n";
    }
    if ($upgraded) {
        echo "  version upgraded:  $upgraded\n";
    }
    echo "  changed:           $changed\n";

    $converted = 0;
    $kept_total = 0;
    foreach ($by_type as $key => $count) {
        if (substr($key, -10) === "\tconverted") {
            $converted += $count;
        }
        if (substr($key, -5) === "\tkept") {
            $kept_total += $count;
        }
    }
    echo "\nOld widgets:         $found\n";
    printf("  converted:         %d%s\n", $converted, share($converted, $found));
    printf("  kept:              %d%s\n", $kept_total, share($kept_total, $found));

    if (count($by_type)) {
        echo "\nBy widget type\n";
        column_table($by_type, ['found', 'converted', 'kept'], $found);
    }
    if (count($by_tier)) {
        echo "\nBy what the text widget holds\n";
        column_table($by_tier, ['found', 'converted', 'kept'], $found);
    }
    if (count($by_holds)) {
        echo "\nBy what the container holds\n";
        column_table($by_holds, ['found', 'converted', 'kept'], $found);
    }
    if (count($reasons)) {
        echo "\nWhy a widget was kept\n";
        arsort($reasons);
        foreach ($reasons as $code => $count) {
            printf("  %-32s %6d%s\n", $code, $count, share($count, $found));
        }
        echo "\n  Use --show=CODE for examples.\n";
    }
    if (count($heights)) {
        print_heading_heights($heights, $height_only);
    }


    echo "\nDashboards still holding an old widget: " . count($remaining) . "\n";
    if ($o['list'] && count($remaining)) {
        echo "\n  id      userid  widgets kept\n";
        foreach ($remaining as $entry) {
            $parts = [];
            foreach ($entry['codes'] as $code => $count) {
                $parts[] = "$code($count)";
            }
            printf("  %-7d %-7d %s\n", $entry['id'], $entry['userid'], implode(' ', $parts));
        }
    } elseif (count($remaining)) {
        echo "  Add --list to see them.\n";
    }

    migrate_footer($o['write'], $written, $changed);
}

// Counts a heading by its box height, and whether it was kept for that alone.
function heading_height($widget, $new, $reason, &$heights, &$height_only)
{
    $unit = isset($widget['hunit']) && $widget['hunit'] === 'pc' ? '%' : 'px';
    $height = (isset($widget['h']) ? $widget['h'] : '?') . $unit;
    if (!isset($heights[$height])) {
        $heights[$height] = ['found' => 0, 'kept' => 0];
    }
    $heights[$height]['found']++;

    if ($new !== null || strpos($reason, 'heading_height') !== 0) {
        return;
    }
    $heights[$height]['kept']++;

    // Would it have converted at the default height
    $retry = $widget;
    $retry['h'] = DASHBOARD_CONVERT_TEXT_HEADING_HEIGHT;
    unset($retry['hunit']);
    $again = '';
    if (dashboard_convert_text_widget($retry, $again) !== null) {
        $height_only++;
    }
}

function print_heading_heights($heights, $height_only)
{
    echo "\nHeading heights\n";
    echo "  A heading converts only at " . DASHBOARD_CONVERT_TEXT_HEADING_HEIGHT . "px, where its top padding\n";
    echo "  and the centred text widget draw the same. At another height the\n";
    echo "  text would move by half the difference.\n";
    echo "  kept only for their height:  $height_only\n\n";
    printf("  %-12s %8s %8s   %s\n", 'height', 'headings', 'kept', 'text would move by');
    uksort($heights, function ($a, $b) {
        // px before per cent, then by size
        $pa = substr($a, -2) === 'px';
        $pb = substr($b, -2) === 'px';
        if ($pa !== $pb) {
            return $pa ? -1 : 1;
        }
        return (int) $a - (int) $b;
    });
    $shown = 0;
    foreach ($heights as $height => $counts) {
        if ($shown++ >= 25) {
            echo "  ...\n";
            break;
        }
        $move = '';
        if (substr($height, -2) === 'px') {
            $delta = ((int) $height - DASHBOARD_CONVERT_TEXT_HEADING_HEIGHT) / 2;
            if ($delta != 0) {
                $move = sprintf('%+dpx', $delta);
            }
        }
        printf("  %-12s %8d %8d   %s\n", $height, $counts['found'], $counts['kept'], $move);
    }
}

// One line saying what an old widget holds, for an example.
function describe($widget)
{
    $parts = [];
    foreach (['w', 'h'] as $key) {
        if (isset($widget[$key])) {
            $unit = isset($widget[$key . 'unit']) && $widget[$key . 'unit'] === 'pc' ? '%' : 'px';
            $parts[] = $key . '=' . $widget[$key] . $unit;
        }
    }
    if (!empty($widget['style'])) {
        $parts[] = 'style=' . json_encode($widget['style']);
    }
    if (isset($widget['html']) && trim((string) $widget['html']) !== '') {
        $parts[] = 'html=' . dashboard_convert_snippet($widget['html']);
    }
    return implode(' ', $parts);
}

// The most demanding thing a container holds: html, an author's box style,
// or nothing.
function panel_holds($widget)
{
    $html = isset($widget['html']) && trim((string) $widget['html']) !== '';
    $style = !empty($widget['style']);
    if ($html && $style) {
        return 'html and box style';
    }
    if ($html) {
        return 'html';
    }
    if ($style) {
        return 'box style';
    }
    return 'empty';
}

// The most demanding thing a text widget holds. The same tiers as census.php,
// so the numbers can be compared with the census table.
function text_tier($html)
{
    $inline = ['b', 'strong', 'i', 'em', 'u', 'sub', 'sup', 'font', 'span', 'small'];
    $block = ['p', 'div', 'center', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    $table = ['table', 'thead', 'tbody', 'tr', 'th', 'td', 'colgroup', 'col', 'ul', 'ol', 'li'];

    $root = dashboard_convert_parse($html);
    if ($root === null) {
        return 'unparsable';
    }

    $tags = [];
    $has_text = false;
    $stack = [$root];
    while (count($stack)) {
        $current = array_pop($stack);
        foreach ($current->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                if (trim($child->nodeValue) !== '') {
                    $has_text = true;
                }
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            bump($tags, strtolower($child->nodeName));
            $stack[] = $child;
        }
    }

    $names = array_keys($tags);
    $expected = array_merge($inline, $block, $table, ['a', 'img', 'br']);

    if (count(array_diff($names, $expected))) {
        return 'embed or nested widget';
    }
    if (count(array_intersect($names, $table))) {
        return 'table or list';
    }
    if (!count($tags)) {
        return $has_text ? 'plain text' : 'empty';
    }
    if (!$has_text && in_array('img', $names)) {
        return 'image, no text';
    }
    if (text_tier_one_style($root)) {
        return 'one style throughout';
    }
    if (count(array_intersect($names, $block))) {
        return 'mixed, across blocks';
    }
    if (in_array('a', $names)) {
        return 'mixed, inline with a link';
    }
    return 'mixed, inline';
}

// True when every element wraps all of the text, so one set of options can
// describe the whole box. A br is ignored.
function text_tier_one_style($node)
{
    while (true) {
        $elements = [];
        $text = false;
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                if (trim($child->nodeValue) !== '') {
                    $text = true;
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                if (strtolower($child->nodeName) !== 'br') {
                    $elements[] = $child;
                }
            }
        }
        if (!count($elements)) {
            return true;
        }
        if (count($elements) > 1) {
            return false;
        }
        if ($text) {
            return false;
        }
        $node = $elements[0];
    }
}

// ---------------------------------------------------------------------------
// Stage: charts
// ---------------------------------------------------------------------------

function migrate_charts($mysqli, $o, &$failed)
{
    // The multigraphs, read once. A dashboard names one by id.
    $configs = [];
    $backgrounds = [];
    $multigraphs = 0;
    $feeds_total = 0;
    $no_feeds = 0;
    if (cli_has_table($mysqli, 'multigraph')) {
        $result = $mysqli->query("SELECT id, userid, name, feedlist FROM multigraph ORDER BY id");
        if (!$result) {
            die("Query failed: " . $mysqli->error . "\n");
        }
        while ($row = $result->fetch_assoc()) {
            $mid = (string) (int) $row['id'];
            $converted = dashboard_convert_multigraph_row($row['name'], $row['feedlist']);
            if ($o['multigraph'] && (int) $row['id'] === $o['multigraph']) {
                echo "multigraph $mid, user " . $row['userid'] . "\n" . $row['feedlist'] . "\n\n";
                echo json_encode($converted['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                echo "\nwidget background: " . $converted['background'] . "\n";
                if (count($converted['dropped'])) {
                    echo "\ndropped: " . json_encode($converted['dropped']) . "\n";
                }
                exit(0);
            }
            $configs[$mid] = $converted['config'];
            $backgrounds[$mid] = $converted['background'];
            $multigraphs++;
            $feeds_total += $converted['feeds'];
            if (!$converted['feeds']) {
                $no_feeds++;
            }
        }
        $result->free();
    }
    if ($o['multigraph']) {
        die("No multigraph " . $o['multigraph'] . "\n");
    }

    // Saved graphs are read as the owner of each dashboard, so a widget
    // takes only what it could fetch when it drew the saved graph.
    global $settings;
    $graph_table = cli_has_table($mysqli, 'graph');
    $redis = $graph_table ? cli_redis() : false;

    $update = $mysqli->prepare("UPDATE dashboard SET content_json=? WHERE id=?");
    if (!$update) {
        die("Cannot prepare update: " . $mysqli->error . "\n");
    }

    $total = 0;
    $empty = 0;
    $from_html = 0;
    $skipped = 0;
    $unreadable = 0;
    $upgraded = 0;
    $changed = 0;
    $written = 0;
    $counts = [];

    foreach (cli_dashboards($mysqli, ['id', 'userid', 'content', 'content_json'], $o['id']) as $row) {
        $id = (int) $row['id'];
        $total++;
        if ($o['limit'] > 0 && $total > $o['limit']) {
            $total--;
            break;
        }
        if (isset($o['skip'][$id])) {
            $skipped++;
            continue;
        }

        $state = '';
        $document = migrate_document($row, $state);
        if ($state === 'empty') {
            $empty++;
            continue;
        }
        if ($state === 'unreadable') {
            echo "  dashboard $id holds a document that cannot be read\n";
            $unreadable++;
            continue;
        }
        if ($state === 'from html') {
            $from_html++;
        }
        $graph = $graph_table
            ? dashboard_migrate_graph_loader($mysqli, (int) $row['userid'], $redis, $settings) : null;
        $touched = dashboard_upgrade_document($document);
        if ($touched) {
            $upgraded++;
        }

        if ($o['id']) {
            foreach ($document['widgets'] as $widget) {
                if (!is_array($widget) || !isset($widget['type'])) {
                    continue;
                }
                $options = isset($widget['options']) && is_array($widget['options']) ? $widget['options'] : [];
                if (!in_array($widget['type'], dashboard_convert_preset_types(), true)) {
                    continue;
                }
                $converted = dashboard_convert_preset_to_graph($widget['type'], $options, null);
                if ($converted === null) {
                    continue;
                }
                echo $widget['type'] . " " . json_encode($options, JSON_UNESCAPED_SLASHES) . "\n";
                echo "becomes " . $converted['type'] . "\n";
                if (isset($converted['config'])) {
                    echo json_encode($converted['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                }
                echo "widget options: " . json_encode($converted['options']) . "\n";
                if (count($converted['dropped'])) {
                    echo "dropped: " . json_encode($converted['dropped']) . "\n";
                }
                echo "\n";
            }
        }

        $rewritten = multigraph_rewrite($document, $configs, $counts, false, $backgrounds, $graph);
        if ($rewritten !== null) {
            $document = $rewritten;
            $touched = true;
        }
        $rewritten = preset_rewrite($document, $counts);
        if ($rewritten !== null) {
            $document = $rewritten;
            $touched = true;
        }
        if (!$touched) {
            continue;
        }

        $changed++;
        if ($o['write'] && migrate_store($mysqli, $update, $id, $document, $failed)) {
            $written++;
        }
    }
    $update->close();

    $n = function ($key) use ($counts) {
        return isset($counts[$key]) ? $counts[$key] : 0;
    };

    echo "Multigraphs:       $multigraphs\n";
    echo "  feeds:           $feeds_total\n";
    if ($no_feeds) {
        echo "  with no feeds:   $no_feeds\n";
    }

    echo "\nDashboards:        $total\n";
    echo "  empty:           $empty\n";
    if ($from_html) {
        echo "  still on html:   $from_html" . ($o['write'] ? "" : " (converted in memory)") . "\n";
    }
    if ($skipped) {
        echo "  left out:        $skipped\n";
    }
    if ($unreadable) {
        echo "  unreadable:      $unreadable\n";
    }
    if ($upgraded) {
        echo "  version upgraded:$upgraded\n";
    }
    echo "  changed:         $changed\n";

    echo "\nChart widgets\n";
    echo "  multigraph:      " . $n('widget_converted') . " converted, "
        . $n('widget_without_mid') . " with no mid, "
        . $n('widget_mid_not_found') . " naming a multigraph that is gone\n";
    if ($n('widget_folded') || $n('widget_graph_gone')) {
        echo "  folded in:       " . $n('widget_folded') . " took the chart of the saved graph they pointed at, "
            . $n('widget_graph_gone') . " pointed at one that is gone\n";
    }
    if ($n('widget_pointer_cleared')) {
        echo "  pointers removed: " . $n('widget_pointer_cleared') . " empty or beside a chart\n";
    }
    foreach (dashboard_convert_preset_types() as $type) {
        printf("  %-16s %d\n", $type . ':', $n('converted_' . $type));
    }
    if ($n('widget_without_feed')) {
        echo "  with no feed:    " . $n('widget_without_feed') . " draw an empty chart\n";
    }
    if ($n('mode_overruled_by_interval')) {
        echo "  mode ignored:    " . $n('mode_overruled_by_interval')
            . " say daily and carry an interval that is not a day\n";
    }

    $dropped = [];
    foreach ($counts as $key => $count) {
        if (
            substr($key, 0, 7) === 'widget_' || substr($key, 0, 10) === 'converted_'
            || $key === 'mode_overruled_by_interval'
        ) {
            continue;
        }
        $dropped[] = "$key $count";
    }
    if (count($dropped)) {
        echo "\nFields with no equivalent: " . implode(', ', $dropped) . "\n";
    }

    migrate_footer($o['write'], $written, $changed);
}

// ---------------------------------------------------------------------------
// Command line
// ---------------------------------------------------------------------------

function migrate_main($argv)
{
    $opts = cli_options($argv);

    if (isset($opts['help'])) {
        echo "usage: php migrate.php [stage ...] [options]\n";
        echo "stages, all three in this order when none is given:\n";
        echo "  --html            content column to a document in content_json\n";
        echo "  --widgets         old text and container widgets to text, image and panel\n";
        echo "  --charts          retired chart widgets to graph, zoom and realtime\n";
        echo "options:\n";
        echo "  --write           store the documents, without this nothing changes\n";
        echo "  --id=N            one dashboard only, printing what each widget becomes\n";
        echo "  --skip-ids=FILE   leave out the ids in this file, one per line\n";
        echo "  --show=CODE       print examples of one warning or reason\n";
        echo "  --samples=N       how many examples to print, default 5\n";
        echo "  --limit=N         stop after N dashboards\n";
        echo "  --force           html stage: convert again where content_json is set\n";
        echo "  --list            widgets stage: list each dashboard still holding an old widget\n";
        echo "  --multigraph=N    charts stage: print what one multigraph row converts to\n";
        exit(0);
    }

    $stages = [];
    foreach (['html', 'widgets', 'charts'] as $stage) {
        if (isset($opts[$stage])) {
            $stages[] = $stage;
        }
    }
    if (!count($stages)) {
        $stages = ['html', 'widgets', 'charts'];
    }

    $o = [
        'write' => isset($opts['write']),
        'force' => isset($opts['force']),
        'list' => isset($opts['list']),
        'id' => cli_id($opts, 'id'),
        'multigraph' => cli_id($opts, 'multigraph'),
        'limit' => isset($opts['limit']) ? (int) $opts['limit'] : 0,
        'samples' => isset($opts['samples']) ? (int) $opts['samples'] : 5,
        'show' => isset($opts['show']) ? $opts['show'] : null,
        'skip' => []
    ];
    if ($o['multigraph'] && !in_array('charts', $stages)) {
        $stages = ['charts'];
    }
    if (isset($opts['skip-ids'])) {
        $o['skip'] = cli_skip_ids($opts['skip-ids']);
        echo "Leaving out " . count($o['skip']) . " dashboards listed in " . $opts['skip-ids'] . "\n\n";
    }

    cli_registry_check($o['write']);

    $mysqli = cli_connect();
    if (!cli_has_table($mysqli, 'dashboard')) {
        die("There is no dashboard table.\n");
    }
    if ($o['write'] && !cli_has_column($mysqli, 'dashboard', 'content_json')) {
        die("The dashboard table has no content_json column. Run the database update first.\n");
    }

    $failed = 0;
    foreach ($stages as $i => $stage) {
        if (count($stages) > 1) {
            echo ($i ? "\n" : "") . "==== $stage ====\n\n";
        }
        if ($stage === 'html') {
            migrate_html($mysqli, $o, $failed);
        }
        if ($stage === 'widgets') {
            migrate_widgets_stage($mysqli, $o, $failed);
        }
        if ($stage === 'charts') {
            migrate_charts($mysqli, $o, $failed);
        }
    }
    $mysqli->close();

    if ($failed) {
        echo "\nFailed: $failed\n";
    }
    exit($failed ? 1 : 0);
}

// Run when called directly, so the rewrites above can be included by a test.
if (
    php_sapi_name() === 'cli' && isset($argv) && isset($argv[0])
    && realpath($argv[0]) === realpath(__FILE__)
) {
    migrate_main($argv);
}
