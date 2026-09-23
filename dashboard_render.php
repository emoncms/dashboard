<?php

/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
*/

/*
 Renders a JSON dashboard document as the markup the widget scripts expect, see
 notes/SCHEMA.md.

   require_once "Modules/dashboard/dashboard_render.php";
   $result = dashboard_render($dashboard['content_json']);
   echo $result['html'];

 The markup matches what designer.box_element writes in Views/js/designer.js,
 so render.js, the designer and every widget script work on it unchanged.

 Everything is validated on the way out as well as on the way in, by
 dashboard_render_clean, which is also what the editor's save runs before it
 stores a document, see notes/EDITOR.md. A document that reached the
 column some other way cannot put an attribute, a tag or a url into the page
 that the allowlist would have rejected. The cost is one parse of the html of
 each text widget, and only the dashboard being viewed is rendered.
*/

defined('EMONCMS_EXEC') or die('Restricted access');

require_once dirname(__FILE__) . "/dashboard_convert.php";

/**
 * Renders a stored document.
 *
 * @param string|array $json the content_json column, or a decoded document
 * @return array html and errors
 */
function dashboard_render($json)
{
    $errors = [];

    $document = dashboard_render_clean($json, $errors);
    if ($document === null) {
        return ['html' => '', 'errors' => $errors];
    }

    $html = '';
    foreach ($document['widgets'] as $widget) {
        $html .= dashboard_render_write($widget);
    }

    return ['html' => $html, 'errors' => $errors];
}

/**
 * Validates a document and returns what passes.
 *
 * Every rule the renderer draws by is applied here, so the editor's save
 * stores a document that has been through the same checks the page is drawn
 * with, see notes/EDITOR.md. A widget that cannot be drawn is left
 * out. The result holds version, next_id and widgets and nothing else.
 *
 * @param string|array $json the content_json column, or a decoded document
 * @param array $errors added to in place
 * @return array|null the cleaned document, or null if there is no document
 */
function dashboard_render_clean($json, &$errors)
{
    $document = is_array($json) ? $json : json_decode((string) $json, true);
    if (!is_array($document)) {
        $errors[] = ['widget' => null, 'code' => 'document_unreadable', 'detail' => ''];
        return null;
    }

    // A version 1 document, one with no widget ids, is upgraded here as well
    // as on load so it draws even where the store failed.
    dashboard_upgrade_document($document);

    if (!isset($document['version']) || (int) $document['version'] !== DASHBOARD_DOCUMENT_VERSION) {
        // A document from a later release. Refusing to guess at it is better
        // than drawing half of it.
        $errors[] = ['widget' => null, 'code' => 'document_version_unknown',
            'detail' => isset($document['version']) ? (string) $document['version'] : ''
        ];
        return null;
    }

    if (!isset($document['widgets']) || !is_array($document['widgets'])) {
        $errors[] = ['widget' => null, 'code' => 'document_has_no_widgets', 'detail' => ''];
        return null;
    }

    $registry = widget_registry();
    $ids = dashboard_render_ids($document['widgets'], $errors);
    $widgets = [];
    $index = 0;
    $next_id = isset($document['next_id']) && is_numeric($document['next_id'])
        ? (int) $document['next_id'] : 1;

    foreach ($document['widgets'] as $widget) {
        if (!is_array($widget)) {
            dashboard_convert_warn($errors, $index, 'widget_unreadable', '');
            $index++;
            continue;
        }
        $clean = dashboard_render_clean_widget($widget, $index, $registry, $errors, $ids[$index]);
        if ($clean !== null) {
            $widgets[] = $clean;
            if ($clean['id'] >= $next_id) {
                $next_id = $clean['id'] + 1;
            }
        }
        $index++;
    }

    return [
        'version' => DASHBOARD_DOCUMENT_VERSION,
        'next_id' => max(1, $next_id),
        'widgets' => $widgets
    ];
}

// Id each widget is drawn with, by index. A stored id is kept when it is
// a positive integer no earlier widget carries. Anything else raises
// widget_id_invalid and is drawn with a fresh id above every other, so the
// page still draws and the fault is logged. See the widget ids section of
// SCHEMA.md.
function dashboard_render_ids($widgets, &$errors)
{
    $ids = [];
    $seen = [];
    $fresh = [];
    $next = 1;

    foreach ($widgets as $index => $widget) {
        $raw = is_array($widget) && isset($widget['id']) ? $widget['id'] : null;
        $id = (is_int($raw) || (is_string($raw) && ctype_digit($raw))) ? (int) $raw : 0;
        if ($id >= 1 && !isset($seen[$id])) {
            $seen[$id] = true;
            $ids[$index] = $id;
            if ($id >= $next) {
                $next = $id + 1;
            }
            continue;
        }
        if (is_array($widget)) {
            dashboard_convert_warn(
                $errors,
                $index,
                'widget_id_invalid',
                $raw === null ? 'none' : dashboard_convert_snippet(is_scalar($raw) ? $raw : '')
            );
        }
        $fresh[] = $index;
    }
    foreach ($fresh as $index) {
        $ids[$index] = $next++;
    }
    return $ids;
}

// One widget cleaned and written. $id is the id to draw the box with, from
// dashboard_render_ids. Called without one, by the tools that render a single
// widget, it is the index counted from one.
function dashboard_render_widget($widget, $index, $registry, &$errors, $id = null)
{
    $clean = dashboard_render_clean_widget($widget, $index, $registry, $errors, $id);
    return $clean === null ? '' : dashboard_render_write($clean);
}

// Validates one widget and returns the fields that pass, or null for a
// widget that cannot be drawn. Every check the page is drawn by is here, and
// dashboard_render_write checks nothing.
function dashboard_render_clean_widget($widget, $index, $registry, &$errors, $id = null)
{
    if ($id === null) {
        $id = $index + 1;
    }
    $type = isset($widget['type']) ? (string) $widget['type'] : '';
    if ($type === '' || preg_match('/[\s"\'<>\/\\\\]/', $type)) {
        dashboard_convert_warn(
            $errors,
            $index,
            'widget_type_invalid',
            dashboard_convert_snippet($type)
        );
        return null;
    }

    $known = isset($registry[$type]);
    if (!$known) {
        // Either the declaration is not deployed or the document was written
        // against a different set of widgets. Kept as a placeholder rather
        // than silently left out, so the author can see it is still there.
        dashboard_convert_warn($errors, $index, 'widget_type_unknown', $type);
    }

    $holds_html = dashboard_convert_holds_html($type, $known, $registry);
    $holds_text = dashboard_convert_holds_text($type, $known, $registry);

    $clean = [
        'id' => (int) $id,
        'type' => $type,
        'x' => dashboard_render_number($widget, 'x', $index, $errors),
        'y' => dashboard_render_number($widget, 'y', $index, $errors),
        'w' => dashboard_render_number($widget, 'w', $index, $errors),
        'h' => dashboard_render_number($widget, 'h', $index, $errors),
        'wunit' => (isset($widget['wunit']) && $widget['wunit'] === 'pc') ? 'pc' : 'px',
        'hunit' => (isset($widget['hunit']) && $widget['hunit'] === 'pc') ? 'pc' : 'px',
        'options' => []
    ];

    if ($known) {
        $clean['options'] = dashboard_render_options($widget, $type, $index, $errors);
        $config = dashboard_render_config($widget, $type, $index, $errors);
        if ($config !== null) {
            $clean['config'] = $config;
        }
    } elseif (!empty($widget['options'])) {
        // Written by a converter that carried the attributes of an undeclared
        // widget through. Nothing says which of them are options, so none are
        // kept, see dashboard_convert_options. A document holding any is
        // due to be converted again.
        dashboard_convert_warn(
            $errors,
            $index,
            'unknown_widget_option_dropped',
            implode(' ', array_keys((array) $widget['options']))
        );
    }

    if (isset($widget['html']) && is_string($widget['html'])) {
        if ($holds_html) {
            // Run through the allowlist again on the way out.
            $html = dashboard_convert_sanitise_html($widget['html'], $errors, $index);
            if ($html !== '') {
                $clean['html'] = $html;
            }
        } else {
            dashboard_convert_warn($errors, $index, 'html_not_allowed_on_type', $type);
        }
    }

    // A text widget keeps its body in its own field with the narrower element
    // list. Only the field the type declares is kept.
    if (isset($widget['text']) && is_string($widget['text'])) {
        if ($holds_text) {
            $text = dashboard_convert_sanitise_text($widget['text'], $errors, $index);
            if ($text !== '') {
                $clean['text'] = $text;
            }
        } else {
            dashboard_convert_warn($errors, $index, 'text_not_allowed_on_type', $type);
        }
    }

    if (isset($widget['style']) && is_array($widget['style'])) {
        $kept = dashboard_convert_styles($widget['style'], $index, $errors, true);
        if (count($kept)) {
            $clean['style'] = $kept;
        }
    }

    if (!$known) {
        $clean['unknown'] = true;
    }

    return $clean;
}

// Writes the box of a cleaned widget. Nothing is checked here, see
// dashboard_render_clean_widget.
function dashboard_render_write($widget)
{
    $type = $widget['type'];
    $known = empty($widget['unknown']);

    $attributes = ' id="' . (int) $widget['id'] . '"'
        . ' class="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"'
        . ' style="' . htmlspecialchars(dashboard_render_box_style($widget), ENT_QUOTES, 'UTF-8') . '"';

    foreach ($widget['options'] as $name => $value) {
        $attributes .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }

    if (isset($widget['config'])) {
        $json = json_encode($widget['config'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            $attributes .= ' config="' . htmlspecialchars($json, ENT_QUOTES, 'UTF-8') . '"';
        }
    }

    $inner = '';
    if (isset($widget['html'])) {
        $inner .= $widget['html'];
    }
    if (isset($widget['text'])) {
        $inner .= $widget['text'];
    }

    // A container draws itself from its own html and box style, so it is left
    // alone. The rest of the undeclared types are drawn by a render script
    // that is not here, which would leave an empty box.
    if (
        !$known && !isset($widget['html'])
        && substr($type, 0, 10) !== 'Container-'
    ) {
        $inner = dashboard_render_placeholder($type);
    }

    return '<div' . $attributes . '>' . $inner . '</div>';
}

// The nested config of a widget that declares one, checked again on the way
// out so a config that reached the column some other way is held to the same
// declaration the converter would have held it to. Returns the config to
// keep, or null.
function dashboard_render_config($widget, $type, $index, &$errors)
{
    if (!isset($widget['config'])) {
        return null;
    }

    $config = $widget['config'];
    if (is_object($config)) {
        $config = (array) $config;
    }
    if (!is_array($config)) {
        dashboard_convert_warn($errors, $index, 'config_block_unreadable', $type);
        return null;
    }

    if (widget_registry_config($type) === false) {
        dashboard_convert_warn($errors, $index, 'config_not_allowed_on_type', $type);
        return null;
    }

    return dashboard_convert_config_valid($config, $type, $index, $errors);
}

// Stands in for a widget no module declares. Named so the author can see which
// widget it is and put the module back, or delete the box.
function dashboard_render_placeholder($type)
{
    $label = function_exists('tr') ? tr('widget not installed') : 'widget not installed';

    return '<div class="dashboard-placeholder">'
        . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '<br><small>'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</small></div>';
}

// The box style the designer writes, plus the allowlisted styling a cleaned
// widget carries. Widgets are positioned absolutely inside #page with no
// margin, and width and height may be in px or per cent.
function dashboard_render_box_style($widget)
{
    $x = $widget['x'];
    $y = $widget['y'];
    $w = $widget['w'];
    $h = $widget['h'];

    if ($w < 0) {
        $w = 0;
    }
    if ($h < 0) {
        $h = 0;
    }

    // A negative top lifts the box out of the page and over the emoncms menu
    // bar, which on a public dashboard puts author content on top of the site
    // chrome. Clamped to the top of the page. A negative left is left alone,
    // it only moves the box off the side of the page where there is nothing
    // to sit on top of.
    if ($y < 0) {
        $y = 0;
    }

    $wunit = $widget['wunit'] === 'pc' ? '%' : 'px';
    $hunit = $widget['hunit'] === 'pc' ? '%' : 'px';

    $style = "position:absolute; margin: 0; top:{$y}px; left:{$x}px; "
        . "width:{$w}{$wunit}; height:{$h}{$hunit};";

    if (isset($widget['style'])) {
        $style .= ' ' . dashboard_convert_write_style($widget['style']) . ';';
    }

    return $style;
}

function dashboard_render_number($widget, $key, $index, &$errors)
{
    if (!isset($widget[$key]) || !is_numeric($widget[$key])) {
        dashboard_convert_warn($errors, $index, 'geometry_invalid', $key);
        return 0;
    }
    return (int) round((float) $widget[$key]);
}

// Only ever called for a widget the registry declares. An option is kept
// when the declaration names it and the value passes the check for its type,
// so the attributes on the page are the ones a widget said it accepts. See
// dashboard_render_clean_widget for what happens to the rest. Returns the
// options to keep, under the names the registry declares.
function dashboard_render_options($widget, $type, $index, &$errors)
{
    if (!isset($widget['options'])) {
        return [];
    }

    $options = $widget['options'];
    if (is_object($options)) {
        $options = (array) $options;
    }
    if (!is_array($options)) {
        dashboard_convert_warn($errors, $index, 'options_unreadable', '');
        return [];
    }

    $kept = [];
    foreach ($options as $name => $value) {
        if (!is_string($name) || !preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $name)) {
            dashboard_convert_warn(
                $errors,
                $index,
                'option_name_invalid',
                dashboard_convert_snippet($name)
            );
            continue;
        }
        if (is_bool($value) || is_array($value) || $value === null) {
            dashboard_convert_warn($errors, $index, 'option_value_invalid', $name);
            continue;
        }
        $value = (string) $value;

        $option = widget_registry_option($type, $name);
        if ($option === false) {
            dashboard_convert_warn($errors, $index, 'option_unknown_dropped', $name);
            continue;
        }
        // An empty option is kept, see dashboard_convert_options.
        if ($value !== '' && !dashboard_convert_option_valid($option, $value)) {
            dashboard_convert_warn(
                $errors,
                $index,
                'option_value_dropped',
                $name . '=' . dashboard_convert_snippet($value)
            );
            continue;
        }
        $kept[$option['name']] = $value;
    }

    return $kept;
}
