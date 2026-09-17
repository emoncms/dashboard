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
 tools/SCHEMA.md.

   require_once "Modules/dashboard/dashboard_render.php";
   $result = dashboard_render($dashboard['content_json']);
   echo $result['html'];

 The markup matches what designer.add_widget writes in Views/js/designer.js, so
 render.js, the designer and every widget script work on it unchanged.

 Everything is validated on the way out as well as on the way in. A document
 that reached the column some other way cannot put an attribute, a tag or a url
 into the page that the allowlist would have rejected. The cost is one parse of
 the html of each text widget, and only the dashboard being viewed is rendered.
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
    $errors = array();

    $document = is_array($json) ? $json : json_decode((string) $json, true);
    if (!is_array($document)) {
        return array('html' => '', 'errors' => array(
            array('widget' => null, 'code' => 'document_unreadable', 'detail' => '')
        ));
    }

    if (!isset($document['version']) || (int) $document['version'] !== 1) {
        // A document from a later release. Refusing to guess at it is better
        // than drawing half of it.
        return array('html' => '', 'errors' => array(
            array('widget' => null, 'code' => 'document_version_unknown',
                  'detail' => isset($document['version']) ? (string) $document['version'] : '')
        ));
    }

    if (!isset($document['widgets']) || !is_array($document['widgets'])) {
        return array('html' => '', 'errors' => array(
            array('widget' => null, 'code' => 'document_has_no_widgets', 'detail' => '')
        ));
    }

    $registry = widget_registry();
    $html = '';
    $index = 0;

    foreach ($document['widgets'] as $widget) {
        if (!is_array($widget)) {
            dashboard_convert_warn($errors, $index, 'widget_unreadable', '');
            $index++;
            continue;
        }
        $rendered = dashboard_render_widget($widget, $index, $registry, $errors);
        if ($rendered !== '') $html .= $rendered;
        $index++;
    }

    return array('html' => $html, 'errors' => $errors);
}

// Ids are assigned here rather than stored. The designer's counter can produce
// colliding ids within a dashboard, so the array index is used instead, see
// the widget section of SCHEMA.md.
// Counted from one, as the designer does, because its own counter starts there
// and nothing else has had to deal with a box numbered zero.
function dashboard_render_widget($widget, $index, $registry, &$errors)
{
    $type = isset($widget['type']) ? (string) $widget['type'] : '';
    if ($type === '' || preg_match('/[\s"\'<>\/\\\\]/', $type)) {
        dashboard_convert_warn($errors, $index, 'widget_type_invalid',
            dashboard_convert_snippet($type));
        return '';
    }

    $known = isset($registry[$type]);
    if (!$known) {
        // Either the declaration is not deployed or the document was written
        // against a different set of widgets. Drawn as a placeholder rather
        // than silently left out, so the author can see it is still there.
        dashboard_convert_warn($errors, $index, 'widget_type_unknown', $type);
    }

    $holds_html = dashboard_convert_holds_html($type, $known, $registry);
    $holds_text = dashboard_convert_holds_text($type, $known, $registry);
    $style = dashboard_render_box_style($widget, $index, $errors);

    $attributes = ' id="' . ($index + 1) . '"'
        . ' class="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"'
        . ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"';

    if ($known) {
        $attributes .= dashboard_render_options($widget, $type, $index, $errors);
    } else if (!empty($widget['options'])) {
        // Written by a converter that carried the attributes of an undeclared
        // widget through. Nothing says which of them are options, so none are
        // written, see dashboard_convert_options. A document holding any is
        // due to be converted again.
        dashboard_convert_warn($errors, $index, 'unknown_widget_option_dropped',
            implode(' ', array_keys((array) $widget['options'])));
    }

    $inner = '';
    if (isset($widget['html']) && is_string($widget['html'])) {
        if ($holds_html) {
            // Run through the allowlist again on the way out.
            $inner = dashboard_convert_sanitise_html($widget['html'], $errors, $index);
        } else {
            dashboard_convert_warn($errors, $index, 'html_not_allowed_on_type', $type);
        }
    }

    // A text widget keeps its body in its own field with the narrower element
    // list. Only the field the type declares is rendered.
    if (isset($widget['text']) && is_string($widget['text'])) {
        if ($holds_text) {
            $inner .= dashboard_convert_sanitise_text($widget['text'], $errors, $index);
        } else {
            dashboard_convert_warn($errors, $index, 'text_not_allowed_on_type', $type);
        }
    }

    // A container draws itself from its own html and box style, so it is left
    // alone. The rest of the undeclared types are drawn by a render script
    // that is not here, which would leave an empty box.
    if (!$known && !$holds_html) $inner = dashboard_render_placeholder($type);

    return '<div' . $attributes . '>' . $inner . '</div>';
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

// The box style the designer writes, plus any allowlisted styling the widget
// carries. Widgets are positioned absolutely inside #page with no margin, and
// width and height may be in px or per cent.
function dashboard_render_box_style($widget, $index, &$errors)
{
    $x = dashboard_render_number($widget, 'x', $index, $errors);
    $y = dashboard_render_number($widget, 'y', $index, $errors);
    $w = dashboard_render_number($widget, 'w', $index, $errors);
    $h = dashboard_render_number($widget, 'h', $index, $errors);

    if ($w < 0) $w = 0;
    if ($h < 0) $h = 0;

    // A negative top lifts the box out of the page and over the emoncms menu
    // bar, which on a public dashboard puts author content on top of the site
    // chrome. Clamped to the top of the page. A negative left is left alone,
    // it only moves the box off the side of the page where there is nothing
    // to sit on top of.
    if ($y < 0) $y = 0;

    $wunit = (isset($widget['wunit']) && $widget['wunit'] === 'pc') ? '%' : 'px';
    $hunit = (isset($widget['hunit']) && $widget['hunit'] === 'pc') ? '%' : 'px';

    $style = "position:absolute; margin: 0; top:{$y}px; left:{$x}px; "
        . "width:{$w}{$wunit}; height:{$h}{$hunit};";

    if (isset($widget['style']) && is_array($widget['style'])) {
        $kept = dashboard_convert_styles($widget['style'], $index, $errors, true);
        if (count($kept)) $style .= ' ' . dashboard_convert_write_style($kept) . ';';
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

// Only ever called for a widget the registry declares. An option is written
// when the declaration names it and the value passes the check for its type,
// so the attributes on the page are the ones a widget said it accepts. See
// dashboard_render_widget for what happens to the rest.
function dashboard_render_options($widget, $type, $index, &$errors)
{
    if (!isset($widget['options'])) return '';

    $options = $widget['options'];
    if (is_object($options)) $options = (array) $options;
    if (!is_array($options)) {
        dashboard_convert_warn($errors, $index, 'options_unreadable', '');
        return '';
    }

    $html = '';
    foreach ($options as $name => $value) {
        if (!is_string($name) || !preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $name)) {
            dashboard_convert_warn($errors, $index, 'option_name_invalid',
                dashboard_convert_snippet($name));
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
        // An empty option is written back, see dashboard_convert_options.
        if ($value !== '' && !dashboard_convert_option_valid($option, $value)) {
            dashboard_convert_warn($errors, $index, 'option_value_dropped',
                $name . '=' . dashboard_convert_snippet($value));
            continue;
        }
        $name = $option['name'];

        $html .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }

    return $html;
}
