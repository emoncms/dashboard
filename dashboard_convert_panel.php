<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

---------------------------------------------------------------------
Converts a Container-White, Container-Grey, Container-Black or
Container-BlueLine widget to a panel widget. Nothing is written to the
database. It is called by dashboard_migrate.php on load and on save, and
by tools/migrate.php in bulk.

A conversion either keeps the same drawing or is refused. When a value cannot
be carried, the widget is refused and the reason is recorded.

See notes/TEXT-IMAGE-PANEL.md.
*/

defined('EMONCMS_EXEC') or die('Restricted access');

// The widget.css styling of each old container, written out as panel options.
// Every option is written so the converted widget does not depend on the
// panel defaults.
function dashboard_convert_panel_defaults()
{
    $box = ['opacity' => '100', 'borderwidth' => '1', 'radius' => '0', 'shadow' => 'drop'];
    return [
        'Container-White' => ['colour' => 'ffffff', 'bordercolour' => 'e5e5e5'] + $box,
        'Container-Grey' => ['colour' => 'dddddd', 'bordercolour' => 'cccccc'] + $box,
        'Container-Black' => ['colour' => '000000', 'bordercolour' => '888888'] + $box,
        'Container-BlueLine' => ['colour' => 'ffffff', 'opacity' => '0',
            'bordercolour' => '0d97f3', 'borderwidth' => '3', 'radius' => '0', 'shadow' => 'glow'
        ]
    ];
}

// The box-shadow of each shadow option, the same strings as panelShadows in
// panel_render.js with the spacing normalised.
function dashboard_convert_panel_shadows()
{
    return [
        'none' => 'none',
        'drop' => '0 4px 10px -1px rgba(200,200,200,0.7)',
        'glow' => '0 0 2px 2px rgba(200,200,200,0.7)'
    ];
}

/**
 * Converts an old container widget into a panel widget.
 *
 * @param array $widget a widget of a converted document, type Container-*
 * @param string $reason set to why it was refused, when it was
 * @return array|null the new widget, or null
 */
function dashboard_convert_panel_widget($widget, &$reason = null)
{
    $reason = '';
    $defaults = dashboard_convert_panel_defaults();

    $type = isset($widget['type']) ? (string) $widget['type'] : '';
    if (!isset($defaults[$type])) {
        return dashboard_convert_panel_refuse($reason, 'not_an_old_container');
    }

    // The containers declare no options, so any option is unexpected.
    if (!empty($widget['options'])) {
        return dashboard_convert_panel_refuse($reason, 'widget_has_options');
    }

    // A panel holds nothing. A container with html in it is a text box of
    // sorts and stays as it is.
    if (isset($widget['html']) && trim((string) $widget['html']) !== '') {
        return dashboard_convert_panel_refuse($reason, 'holds_html');
    }

    $values = $defaults[$type];
    if (!empty($widget['style'])) {
        if (!is_array($widget['style'])) {
            return dashboard_convert_panel_refuse($reason, 'style_unreadable');
        }
        if (!dashboard_convert_panel_styles($widget['style'], $values, $reason)) {
            return null;
        }
    }

    return dashboard_convert_panel_build($widget, $values, $reason);
}

function dashboard_convert_panel_refuse(&$reason, $code, $detail = '')
{
    $reason = $detail === '' ? $code : $code . ': ' . $detail;
    return null;
}

// Reads the box style an author put on the container over the class
// styling. A property with no matching option refuses the widget.
function dashboard_convert_panel_styles($declarations, &$values, &$reason)
{
    foreach ($declarations as $property => $value) {
        if (!is_string($property) || !is_string($value)) {
            return dashboard_convert_panel_refuse($reason, 'style_unreadable');
        }
        $property = strtolower(trim($property));
        $value = trim($value);
        if ($value === '') {
            continue;
        }

        switch ($property) {
            case 'background':
            case 'background-color':
                if (!dashboard_convert_panel_background($value, $values)) {
                    return dashboard_convert_panel_refuse($reason, 'background_not_carried', $value);
                }
                break;

            case 'border':
                if (!dashboard_convert_panel_border($value, $values)) {
                    return dashboard_convert_panel_refuse($reason, 'border_not_carried', $value);
                }
                break;

            case 'border-color':
                $colour = dashboard_convert_text_colour($value);
                if ($colour === null) {
                    return dashboard_convert_panel_refuse($reason, 'border_not_carried', $value);
                }
                $values['bordercolour'] = $colour;
                break;

            case 'border-width':
                $px = dashboard_convert_panel_px($value, 0, 20);
                if ($px === null) {
                    return dashboard_convert_panel_refuse($reason, 'border_not_carried', $value);
                }
                $values['borderwidth'] = (string) $px;
                break;

            case 'border-style':
                if (!dashboard_convert_panel_border_style($value, $values)) {
                    return dashboard_convert_panel_refuse($reason, 'border_not_carried', $value);
                }
                break;

            case 'border-radius':
                $px = dashboard_convert_panel_px($value, 0, 100);
                if ($px === null) {
                    return dashboard_convert_panel_refuse($reason, 'radius_not_carried', $value);
                }
                $values['radius'] = (string) $px;
                break;

            case 'box-shadow':
                $shadow = dashboard_convert_panel_shadow($value);
                if ($shadow === null) {
                    return dashboard_convert_panel_refuse($reason, 'shadow_not_carried', $value);
                }
                $values['shadow'] = $shadow;
                break;

            case 'padding':
                // A panel holds nothing, so padding changes nothing. A non
                // zero padding is still refused because the box may have held
                // something once and the author may put it back.
                if (!preg_match('/^0(px)?(\s+0(px)?){0,3}$/', $value)) {
                    return dashboard_convert_panel_refuse($reason, 'style_not_carried', $property);
                }
                break;

            default:
                return dashboard_convert_panel_refuse($reason, 'style_not_carried', $property);
        }
    }
    return true;
}

// A background is carried when it is one colour, or none. An image or a
// gradient has no option.
function dashboard_convert_panel_background($value, &$values)
{
    $value = strtolower(trim($value));

    if ($value === 'none' || $value === 'transparent') {
        $values['opacity'] = '0';
        return true;
    }

    // The shorthand widget.css writes: none repeat scroll 0 0 #FFFFFF
    if (preg_match('/^none\s+repeat\s+scroll\s+0\s+0\s+(\S+)$/', $value, $match)) {
        $value = $match[1];
    }

    $colour = dashboard_convert_text_colour($value);
    if ($colour === null) {
        return false;
    }

    $values['colour'] = $colour;
    $values['opacity'] = '100';
    return true;
}

// A border shorthand, any order of width, style and colour.
function dashboard_convert_panel_border($value, &$values)
{
    $value = strtolower(trim($value));
    if ($value === 'none' || $value === '0' || $value === '0px') {
        $values['borderwidth'] = '0';
        return true;
    }

    $parts = preg_split('/\s+/', $value);
    if (count($parts) > 3) {
        return false;
    }

    $width = null;
    $style = null;
    $colour = null;
    foreach ($parts as $part) {
        if (in_array($part, ['solid', 'none', 'hidden'])) {
            if ($style !== null) {
                return false;
            }
            $style = $part;
            continue;
        }
        $px = dashboard_convert_panel_px($part, 0, 20);
        if ($px !== null) {
            if ($width !== null) {
                return false;
            }
            $width = $px;
            continue;
        }
        $hex = dashboard_convert_text_colour($part);
        if ($hex !== null) {
            if ($colour !== null) {
                return false;
            }
            $colour = $hex;
            continue;
        }
        return false;
    }

    if ($style === 'none' || $style === 'hidden') {
        $values['borderwidth'] = '0';
        return true;
    }
    // The initial value of border-style is none, so a border shorthand with no
    // style draws nothing.
    if ($style === null) {
        $values['borderwidth'] = '0';
        return true;
    }
    // The initial width is medium, which is 3px in every browser.
    $values['borderwidth'] = (string) ($width === null ? 3 : $width);
    if ($colour !== null) {
        $values['bordercolour'] = $colour;
    }
    return true;
}

function dashboard_convert_panel_border_style($value, &$values)
{
    $value = strtolower(trim($value));
    if ($value === 'solid') {
        return true;
    }
    if ($value === 'none' || $value === 'hidden') {
        $values['borderwidth'] = '0';
        return true;
    }
    return false;
}

// The two shadows of widget.css and none. Anything else is an author's own
// shadow, which the option list does not offer.
function dashboard_convert_panel_shadow($value)
{
    $normal = strtolower(preg_replace('/\s*,\s*/', ',', preg_replace('/\s+/', ' ', trim($value))));
    // A zero length may be written with or without a unit
    $normal = preg_replace('/\b0px\b/', '0', $normal);
    if ($normal === 'none') {
        return 'none';
    }

    foreach (dashboard_convert_panel_shadows() as $name => $shadow) {
        if ($normal === $shadow) {
            return $name;
        }
    }
    return null;
}

// A whole number of px within a range. A length in another unit is relative
// to something that is not known here.
function dashboard_convert_panel_px($value, $min, $max)
{
    $value = strtolower(trim($value));
    if ($value === 'thin') {
        return 1;
    }
    if ($value === 'medium') {
        return 3;
    }
    if ($value === 'thick') {
        return 5;
    }
    if (!preg_match('/^(\d+(\.\d+)?)(px)?$/', $value, $match)) {
        return null;
    }
    if ($match[1] !== '0' && !isset($match[3])) {
        return null;
    }
    $px = (int) round((float) $match[1]);
    if ($px < $min || $px > $max) {
        return null;
    }
    return $px;
}

/**
 * Builds the panel with the geometry of the old widget and checks every value
 * against the registry, so the result can always be stored and drawn.
 *
 * @return array|null
 */
function dashboard_convert_panel_build($widget, $values, &$reason)
{
    $new = ['type' => 'panel'];
    foreach (['x', 'y', 'w', 'h', 'wunit', 'hunit'] as $key) {
        if (isset($widget[$key])) {
            $new[$key] = $widget[$key];
        }
    }

    $options = [];
    foreach ($values as $name => $value) {
        $option = widget_registry_option('panel', $name);
        if ($option === false) {
            return dashboard_convert_panel_refuse($reason, 'option_unknown', $name);
        }
        if (!dashboard_convert_option_valid($option, (string) $value)) {
            return dashboard_convert_panel_refuse(
                $reason,
                'option_value_refused',
                $name . '=' . dashboard_convert_snippet($value)
            );
        }
        $options[$option['name']] = (string) $value;
    }

    $new['options'] = $options;
    return $new;
}
