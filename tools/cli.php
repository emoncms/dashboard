<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// What the command line tools share: option parsing, the database connection,
// reading the dashboard table, the registry check and the report helpers.
// Functions only, nothing runs on include.

defined('EMONCMS_EXEC') or die('Restricted access');

// Emoncms root, from the location of this file.
function cli_root()
{
    return realpath(dirname(__FILE__) . "/../../..");
}

// Parses --name and --name=value into an array. Anything else goes in $args.
// Parsed by hand rather than with getopt, which stops at the first argument
// that is not an option.
function cli_options($argv, &$args = [])
{
    $opts = [];
    $args = [];
    foreach (array_slice($argv, 1) as $a) {
        if (substr($a, 0, 2) !== '--') {
            $args[] = $a;
            continue;
        }
        $a = substr($a, 2);
        $eq = strpos($a, '=');
        if ($eq === false) {
            $opts[$a] = true;
        } else {
            $opts[substr($a, 0, $eq)] = substr($a, $eq + 1);
        }
    }
    return $opts;
}

// An option that names one row, as a positive integer, or 0 when not given.
function cli_id($opts, $name)
{
    if (!isset($opts[$name])) {
        return 0;
    }
    if ($opts[$name] === true || !ctype_digit((string) $opts[$name]) || (int) $opts[$name] < 1) {
        die("--$name takes the number of one row, for example --$name=3\n");
    }
    return (int) $opts[$name];
}

// Reads the settings and connects. $settings is left global, as the model
// and the logger expect.
function cli_connect()
{
    global $settings;

    $cwd = getcwd();
    chdir(cli_root());
    require_once "process_settings.php";
    chdir($cwd);

    try {
        $mysqli = @new mysqli(
            $settings["sql"]["server"],
            $settings["sql"]["username"],
            $settings["sql"]["password"],
            $settings["sql"]["database"],
            $settings["sql"]["port"]
        );
    } catch (Exception $e) {
        die("Cannot connect to database: " . $e->getMessage() . "\n");
    }
    if ($mysqli->connect_error) {
        die("Cannot connect to database: " . $mysqli->connect_error . "\n");
    }
    $mysqli->set_charset("utf8mb4");
    return $mysqli;
}

// Redis connection as index.php makes it, or false when redis is disabled.
// Call after cli_connect. Feed model on emoncms.org reads feed metadata from
// redis only, so a tool that reads saved graphs passes this rather than false.
function cli_redis()
{
    global $settings;

    if (empty($settings['redis']['enabled'])) {
        return false;
    }
    if (!extension_loaded('redis')) {
        die("Redis is enabled in settings but the php redis extension is not loaded\n");
    }
    $redis = new Redis();
    if (!@$redis->connect($settings['redis']['host'], $settings['redis']['port'])) {
        die("Cannot connect to redis at " . $settings['redis']['host'] . ":" . $settings['redis']['port'] . "\n");
    }
    if (!empty($settings['redis']['prefix'])) {
        $redis->setOption(Redis::OPT_PREFIX, $settings['redis']['prefix']);
    }
    if (!empty($settings['redis']['auth']) && !$redis->auth($settings['redis']['auth'])) {
        die("Redis authentication failed\n");
    }
    if (!empty($settings['redis']['dbnum'])) {
        $redis->select($settings['redis']['dbnum']);
    }
    return $redis;
}

// Sets the host a page view would have, so a url back at this site is judged
// as it is on a page, see dashboard_convert_url_allowed. From --host, else the
// domain setting. Call after cli_connect. Returns the host, or '' when there
// is none and every absolute url counts as another site.
function cli_host($opts)
{
    global $settings;

    $host = '';
    if (isset($opts['host']) && $opts['host'] !== true) {
        $host = $opts['host'];
    } elseif (!empty($settings['domain'])) {
        $host = $settings['domain'];
    }
    if ($host !== '') {
        $_SERVER['HTTP_HOST'] = $host;
    }
    return $host;
}

function cli_has_table($mysqli, $table)
{
    $check = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    return $check && $check->num_rows > 0;
}

function cli_has_column($mysqli, $table, $column)
{
    $check = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return $check && $check->num_rows > 0;
}

// Rows of the dashboard table in id order, as a generator. Null columns come
// back as empty strings, and content_json as an empty string where the table
// has no such column.
//
// Buffered by default, so the same connection can be used to write while
// the rows are read. Pass $stream true to read a large table unbuffered
// when nothing is written.
function cli_dashboards($mysqli, $columns, $only = 0, $stream = false)
{
    $has_json = cli_has_column($mysqli, 'dashboard', 'content_json');
    $select = [];
    foreach ($columns as $column) {
        if ($column === 'content_json' && !$has_json) {
            $select[] = "'' AS content_json";
        } else {
            $select[] = "`$column`";
        }
    }
    $sql = "SELECT " . implode(', ', $select) . " FROM dashboard";
    if ($only) {
        $sql .= " WHERE id=" . (int) $only;
    }
    $sql .= " ORDER BY id";

    $result = $mysqli->query($sql, $stream ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT);
    if (!$result) {
        die("Query failed: " . $mysqli->error . "\n");
    }

    while ($row = $result->fetch_assoc()) {
        foreach ($row as $name => $value) {
            if ($value === null && $name !== 'userid' && $name !== 'name') {
                $row[$name] = '';
            }
        }
        yield $row;
    }
    $result->free();
}

// Dashboard ids listed one per line in a file, as id => true.
function cli_skip_ids($file)
{
    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        die("Cannot read $file\n");
    }
    $skip = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '' && ctype_digit($line)) {
            $skip[(int) $line] = true;
        }
    }
    return $skip;
}

// Says on stderr which widgets have no declaration. With $refuse the script
// stops, since a converted dashboard would treat those types as unknown.
function cli_registry_check($refuse = false)
{
    $missing = widget_registry_missing();
    if (!count($missing)) {
        return;
    }
    fwrite(STDERR, ($refuse ? "Refusing to run. " : "Warning: ")
        . "These widgets have no declaration, so their types would\n"
        . "convert as unknown and their options would not be checked:\n");
    foreach ($missing as $script => $declaration) {
        fwrite(STDERR, "  $script\n");
    }
    fwrite(STDERR, "Generate them with: node Modules/dashboard/tools/extract_registry.js\n\n");
    if ($refuse) {
        exit(1);
    }
}

// ---------------------------------------------------------------------------
// Html
// ---------------------------------------------------------------------------

function to_entities($html)
{
    return mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
}

// Parses stored html as a fragment and returns the wrapping element, or null.
// Stored content complains loudly and parses fine, so the parse errors are
// handed back for counting and not acted on.
function parse_fragment($html, &$nerrors = null, &$messages = null)
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    libxml_clear_errors();
    $ok = $doc->loadHTML(
        '<div>' . to_entities($html) . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    $errs = libxml_get_errors();
    $nerrors = count($errs);
    $messages = [];
    foreach ($errs as $e) {
        $messages[] = trim($e->message);
    }
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    if (!$ok || !$doc->documentElement) {
        return null;
    }
    return $doc->documentElement;
}

// ---------------------------------------------------------------------------
// Reporting
// ---------------------------------------------------------------------------

function bump(&$arr, $key, $n = 1)
{
    if (!isset($arr[$key])) {
        $arr[$key] = 0;
    }
    $arr[$key] += $n;
}

function share($count, $total)
{
    if (!$total) {
        return '';
    }
    return sprintf("  %.1f%%", 100 * $count / $total);
}

function bytes($n)
{
    if ($n < 1024 * 1024) {
        return round($n / 1024) . 'KB';
    }
    return round($n / 1024 / 1024, 1) . 'MB';
}

// Counts keyed "row\tcolumn" printed as a table, rows in order of how many
// each holds under the first column.
function column_table($counts, $columns, $total)
{
    $rows = [];
    foreach ($counts as $key => $count) {
        list($row, $column) = explode("\t", $key, 2);
        if (!isset($rows[$row])) {
            $rows[$row] = [];
        }
        $rows[$row][$column] = $count;
    }
    $first = $columns[0];
    uasort($rows, function ($a, $b) use ($first) {
        return (isset($b[$first]) ? $b[$first] : 0) - (isset($a[$first]) ? $a[$first] : 0);
    });

    printf("  %-28s", '');
    foreach ($columns as $column) {
        printf("%12s", $column);
    }
    echo "\n";
    foreach ($rows as $name => $row) {
        printf("  %-28s", substr($name, 0, 28));
        foreach ($columns as $column) {
            printf("%12d", isset($row[$column]) ? $row[$column] : 0);
        }
        $found = isset($row[$first]) ? $row[$first] : 0;
        printf("%s\n", share($found, $total));
    }
}

// Examples collected for --show=CODE, printed ahead of the report.
function print_examples($show, $examples, $what)
{
    if ($show === null) {
        return;
    }
    if (!count($examples)) {
        echo "No $what for $show\n\n";
    } else {
        echo "Examples of $show\n";
        foreach ($examples as $example) {
            echo "    $example\n";
        }
        echo "\n";
    }
}
