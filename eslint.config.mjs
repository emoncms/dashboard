// ESLint configuration for the module's JavaScript.
//
//     npm run lint:js
//     npm run lint:js:fix
//
// Browser scripts are classic scripts in one global scope, so every top
// level name is a global. Each file lists the names it declares below and
// no-implicit-globals reports any other, including a variable assigned
// without var. Names that other files or the PHP views read are listed
// under shared as well, readonly, so a file that reads one is not reported
// by no-undef.

import globals from "globals";
import stylistic from "@stylistic/eslint-plugin";

// Set by emoncms core, its Lib scripts or the dashboard views before the
// module scripts run.
const emoncms = {
    _Tr: "readonly",
    _SI: "readonly",
    path: "readonly",
    apikey: "readonly",
    public_userid: "readonly",
    feedlist: "readonly",
    dashboard_owner: "readonly",
    offsetofTime: "readonly",
    feed: "readonly",
    Flot: "readonly",
};

// Names read outside the file that declares them
const shared = [
    "dashboard_v2", "designer", "widgets",
    "disabled_widgets_init", "render_widgets_init", "render_widgets_start",
    "render_feeds", "render_curve", "addOption",
    "widget_font_options", "widget_style_options", "widget_weight_options",
    "widget_size_options", "widget_align_options", "widget_decimals_options",
    "widget_unitend_options", "widget_yesno_options", "widget_tooltips",
    "widget_font", "widget_decimals", "widget_colour", "widget_timeout",
    "CHART_DAY", "CHART_BAR_WIDTH", "ChartView", "chart_hex", "chart_background",
    "chart_axis", "chart_points", "chart_feed_data", "chart_fetch", "chart_abort",
    "chart_refresh", "chart_destroy", "chart_stats", "chart_range",
    "chart_days_month", "chart_months", "chart_years", "chart_date", "chart_frame",
    "chart_button", "chart_group_of", "chart_place", "chart_plot_options",
    "chart_readout", "chart_fullscreen", "chart_tooltip", "chart_tooltip_hide",
    "chart_message", "chart_event_item", "chart_event_ranges",
    // Widgets that draw with another widget's functions
    "dewPoint", "draw_dewpoint", "dewpoint_reading", "draw_index_text", "humidex",
    "draw_status_shape", "jgauge_image", "jgauge_size", "jgauge_decimals",
    "jgauge_position", "jgauge_ticks", "jgauge_needle", "StyleOptions",
    // Read by the PHP views
    "panelShadows", "text_wrapper", "text_widget",
];

// Top level names each file declares
const declares = {
    "dashboard.js": [
        "dashboard_v2",
    ],
    "Views/js/chart.helper.js": [
        "CHART_DAY", "CHART_AXIS_COLOUR", "CHART_GRID_BORDER", "chart_hex", "chart_background",
        "chart_axis", "chart_axis_options", "CHART_POINTS_PER_PIXEL", "CHART_POINTS_MIN",
        "CHART_POINTS_MAX", "CHART_POINTS_DEFAULT", "chart_points", "CHART_INTERVALS",
        "ChartView", "chart_feed_data", "chart_fetch", "chart_abort", "chart_refresh",
        "chart_destroy", "chart_stats", "chart_range", "chart_days_month", "chart_months",
        "chart_years", "chart_group", "CHART_MONTHS", "CHART_DAYS", "chart_pad", "chart_date",
        "chart_frame", "chart_button", "chart_group_of", "CHART_BAR_INSET", "chart_place",
        "CHART_BAR_WIDTH", "chart_plot_options", "chart_readout", "chart_fullscreen",
        "chart_tooltip_element", "chart_tooltip", "chart_tooltip_hide", "chart_message",
        "chart_event_item", "chart_event_ranges",
    ],
    "Views/js/designer.js": [
        "selected_edges", "designer_escape", "designer",
    ],
    "Views/js/disabledwidgets.js": [
        "disabled_widgets_mark", "disabled_widgets_init",
    ],
    "Views/js/render.js": [
        "render_live", "render_assoc", "render_instances", "render_observer",
        "render_mutations", "RENDER_POLL_INTERVAL", "RENDER_FRAME_RATE", "RENDER_RESIZE_DELAY",
        "RENDER_CURVE_RATE", "render_feeds", "render_ctx", "render_canvas", "render_curve",
        "render_widgets_init", "addOption", "render_widgets_start", "render_mount",
        "render_unmount", "render_config", "render_poll", "render_update", "render_resized",
        "render_resize_one", "render_frames",
    ],
    "Views/js/widget.helper.js": [
        "WIDGET_FONTS", "WIDGET_FONTS_SHORT", "WIDGET_SIZES", "widget_font_options",
        "widget_style_options", "widget_weight_options", "widget_size_options",
        "widget_align_options", "widget_decimals_options", "widget_unitend_options",
        "widget_yesno_options", "widget_tooltips", "widget_font", "widget_decimals",
        "widget_colour", "widget_timeout",
    ],
    "Views/js/widgetlist.js": [
        "widgets",
    ],
    "widget/bar/bar_render.js": [
        "bar_widgetlist", "draw_bar", "bar_define_tooltips", "bar_tooltip", "bar_widget",
    ],
    "widget/battery/battery_render.js": [
        "battery_widgetlist", "battery_widget",
    ],
    "widget/button/button_render.js": [
        "button_widgetlist", "button_widget", "draw_button",
    ],
    "widget/curl/curl_render.js": [
        "curl_widgetlist", "curl_widget", "draw_curl",
    ],
    "widget/cylinder/cylinder_render.js": [
        "cylinder_widgetlist", "get_color", "drawCylinder", "cylinder_widget",
    ],
    "widget/dewpoint/dewpoint_render.js": [
        "dewPoint", "dewpoint_widgetlist", "draw_dewpoint", "dewpoint_reading",
        "dewpoint_widget",
    ],
    "widget/dial/dial_render.js": [
        "dial_widgetlist", "deg_to_radians", "polar_to_cart", "round1decimal", "draw_gauge",
        "dial_widget",
    ],
    "widget/feedtime/feedtime_render.js": [
        "feedtime_widgetlist", "draw_feedtime", "feedtime_widget",
    ],
    "widget/feedtimestamp/feedtimestamp_render.js": [
        "feedtimestamp_widgetlist", "draw_feedtimestamp", "feedtimestamp_widget",
    ],
    "widget/feedvalue/feedvalue_render.js": [
        "feedvalue_widgetlist", "draw_feedvalue", "feedvalue_affixes", "feedvalue_widget",
    ],
    "widget/frostpoint/frostpoint_render.js": [
        "frostPoint", "frostpoint_widgetlist", "frostpoint_widget",
    ],
    "widget/heatindex/heatindex_render.js": [
        "heatindex", "heatindex_widgetlist", "draw_index_text", "draw_heatindex",
        "heatindex_widget",
    ],
    "widget/humidex/humidex_render.js": [
        "humidex", "humidex_widgetlist", "draw_humidex", "humidex_widget",
    ],
    "widget/image/image_render.js": [
        "imageFitOptions", "image_widgetlist", "image_warning", "image_widget",
    ],
    "widget/isactivefeed/isactivefeed_render.js": [
        "shapeOptionsIsActive", "isactivefeed_widgetlist", "draw_status_star",
        "draw_status_shape", "isactivefeed_widget",
    ],
    "widget/jgauge/jgauge_render.js": [
        "jgauge_widgetlist", "jgauge_images", "jgauge_image", "jgauge_size", "jgauge_decimals",
        "jgauge_position", "jgauge_ticks", "jgauge_needle", "draw_jgauge", "jgauge_widget",
    ],
    "widget/jgauge2/jgauge2_render.js": [
        "jgauge2_widgetlist", "draw_jgauge2", "jgauge2_widget",
    ],
    "widget/kwhperiod/kwhperiod_render.js": [
        "msToDayConversion", "kwhperiod_widgetlist", "draw_kwhperiod", "kwhperiod_period",
        "kwhperiod_value", "kwhperiod_affixes", "kwhperiod_widget",
    ],
    "widget/led/led_render.js": [
        "StyleOptions", "led_widgetlist", "draw_led", "led_widget",
    ],
    "widget/orderbars/orderbars_render.js": [
        "ORDERBARS_DAYS", "ORDERBARS_REFRESH", "ORDERBARS_COLOUR", "orderbars_widgetlist",
        "orderbars_widget", "orderbars_build", "orderbars_start", "orderbars_fetch",
        "orderbars_sort", "orderbars_plot", "orderbars_refresh",
    ],
    "widget/panel/panel_render.js": [
        "panelShadowOptions", "panelShadows", "panelDefaults", "panel_widgetlist",
        "panel_option", "panel_rgba", "panel_widget",
    ],
    "widget/realtime/realtime_render.js": [
        "realtime_windows", "REALTIME_FPS", "REALTIME_LINE", "REALTIME_BACKGROUND",
        "realtime_widgetlist", "realtime_widget", "realtime_build", "realtime_start",
        "realtime_fetch", "realtime_read", "realtime_value", "realtime_plot",
        "realtime_scroll", "realtime_window", "realtime_series", "realtime_append",
        "realtime_tick",
    ],
    "widget/signal/signal_render.js": [
        "signal_widgetlist", "signal_widget",
    ],
    "widget/stackedsolar/stackedsolar_render.js": [
        "STACKEDSOLAR_DAYS", "STACKEDSOLAR_REFRESH", "STACKEDSOLAR_COLOURS",
        "stackedsolar_widgetlist", "stackedsolar_widget", "stackedsolar_build",
        "stackedsolar_start", "stackedsolar_fetch", "stackedsolar_derive",
        "stackedsolar_months_view", "stackedsolar_days_view", "stackedsolar_plot",
        "stackedsolar_bind", "stackedsolar_refresh",
    ],
    "widget/sun/sun_render.js": [
        "sun_widgetlist", "sun_widget",
    ],
    "widget/text/text_render.js": [
        "textFontOptions", "textWeightOptions", "textAlignOptions", "textValignOptions",
        "text_widgetlist", "text_wrapper", "text_widget",
    ],
    "widget/thermometer/thermometer_render.js": [
        "thermometer_widgetlist", "draw_thermometer", "thermometer_hover",
        "thermometer_widget",
    ],
    "widget/thresholds/thresholds_render.js": [
        "shapeOptions", "thresholds_widgetlist", "thresholds_widget",
    ],
    "widget/timecompare/timecompare_render.js": [
        "TIMECOMPARE_ZOOM", "TIMECOMPARE_DEPTH", "TIMECOMPARE_REFRESH",
        "timecompare_widgetlist", "timecompare_widget", "timecompare_build",
        "timecompare_start", "TIMECOMPARE_LENGTHS", "timecompare_toolbar",
        "timecompare_length", "timecompare_moved", "timecompare_fetch", "timecompare_label",
        "timecompare_unit", "timecompare_plot", "timecompare_bind", "timecompare_refresh",
    ],
    "widget/windrose/windrose_render.js": [
        "windrose_needle", "windrose_windrose", "windrose_widgetlist", "draw_windrose",
        "windrose_load_images", "windrose_widget",
    ],
    "widget/zoom/zoom_render.js": [
        "ZOOM_DAYS", "ZOOM_REFRESH", "ZOOM_COLOUR", "zoom_widgetlist", "zoom_widget",
        "zoom_build", "zoom_start", "ZOOM_LENGTHS", "zoom_toolbar", "zoom_length", "zoom_nav",
        "zoom_buttons", "zoom_step", "zoom_periods", "zoom_floor", "zoom_period_length",
        "zoom_fetch", "zoom_totals", "zoom_has_price", "zoom_priced", "zoom_cost",
        "zoom_bars_view", "zoom_years_view", "zoom_months_view", "zoom_days_view",
        "zoom_power", "zoom_open", "zoom_back", "zoom_plot", "zoom_bind", "zoom_title",
        "zoom_reading", "zoom_refresh",
    ],
};

function writable(names) {
    return Object.fromEntries(names.map((name) => [name, "writable"]));
}

function readonly(names) {
    return Object.fromEntries(names.map((name) => [name, "readonly"]));
}

export default [
    {
        ignores: ["node_modules/", "vendor/", "widget/retired/"],
    },
    {
        files: ["**/*.js"],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: "script",
            globals: {
                ...globals.browser,
                ...globals.jquery,
                ...emoncms,
                ...readonly(shared),
            },
        },
        plugins: {
            "@stylistic": stylistic,
        },
        rules: {
            // Kept at warn until each comparison has been checked by hand.
            // Widget ids and option values are compared across number and
            // string, so the change to === is not mechanical.
            "eqeqeq": ["warn", "always", { null: "ignore" }],
            "no-undef": "error",
            "no-implicit-globals": "error",
            "prefer-const": "error",
            "@stylistic/quotes": ["error", "double", { avoidEscape: true }],
            "@stylistic/semi": ["error", "always"],
        },
    },
    ...Object.entries(declares).map(([file, names]) => ({
        files: [file],
        languageOptions: { globals: writable(names) },
    })),
    {
        files: ["tools/**/*.js"],
        languageOptions: {
            sourceType: "commonjs",
            globals: { ...globals.node },
        },
    },
];
