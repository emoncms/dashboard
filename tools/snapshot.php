<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Saves the dashboard and multigraph tables to a file, and puts them back.
// Used to hold a table at a known migration starting point so that the
// migration can be run against it again and again, see
// notes/RELEASE.md.
//
// Run from the emoncms root:
//
//   php Modules/dashboard/tools/snapshot.php --out=FILE
//   php Modules/dashboard/tools/snapshot.php --restore=FILE
//   php Modules/dashboard/tools/snapshot.php --restore=FILE --strip-json
//
// A restore replaces every row of both tables with the rows in the file.
// --strip-json clears content_json on the way in, which turns any snapshot
// into the state before the move to JSON, since content is never rewritten.
// A column in the file that the table does not have is passed over.
//
// The file holds user content in full. Keep it outside the repository.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') {
    die("cli only\n");
}

require_once dirname(__FILE__) . "/cli.php";

$opts = cli_options($argv);

if (isset($opts['help']) || (!isset($opts['out']) && !isset($opts['restore']))) {
    echo "usage: php snapshot.php --out=FILE | --restore=FILE [--strip-json]\n";
    echo "  --out=FILE      write the dashboard and multigraph tables to FILE\n";
    echo "  --restore=FILE  replace both tables with the rows in FILE\n";
    echo "  --strip-json    with --restore, clear content_json on every row\n";
    exit(0);
}

$mysqli = cli_connect();

$tables = ['dashboard', 'multigraph'];

function table_columns($mysqli, $table)
{
    $columns = [];
    $result = @$mysqli->query("SHOW COLUMNS FROM `$table`");
    if (!$result) {
        return $columns;
    }
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

if (isset($opts['out'])) {
    $fh = fopen($opts['out'], 'w');
    if (!$fh) {
        die("Cannot write " . $opts['out'] . "\n");
    }
    foreach ($tables as $table) {
        if (!count(table_columns($mysqli, $table))) {
            echo "$table: no table, skipped\n";
            continue;
        }
        $result = $mysqli->query("SELECT * FROM `$table` ORDER BY id");
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            fwrite($fh, json_encode(['table' => $table, 'row' => $row]) . "\n");
            $count++;
        }
        $result->free();
        echo "$table: $count rows\n";
    }
    fclose($fh);
    exit(0);
}

$lines = @file($opts['restore'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    die("Cannot read " . $opts['restore'] . "\n");
}
$strip = isset($opts['strip-json']);

$rows = [];
foreach ($lines as $line) {
    $entry = json_decode($line, true);
    if (!is_array($entry) || !isset($entry['table'], $entry['row'])) {
        die("Bad line in snapshot\n");
    }
    $rows[$entry['table']][] = $entry['row'];
}

foreach ($tables as $table) {
    $columns = table_columns($mysqli, $table);
    if (!count($columns)) {
        echo "$table: no table, skipped\n";
        continue;
    }
    if (!isset($rows[$table])) {
        echo "$table: not in snapshot, left as it is\n";
        continue;
    }
    $mysqli->query("DELETE FROM `$table`");
    $count = 0;
    $passed = [];
    foreach ($rows[$table] as $row) {
        if ($strip && array_key_exists('content_json', $row)) {
            $row['content_json'] = null;
        }
        $names = [];
        $values = [];
        foreach ($row as $name => $value) {
            if (!in_array($name, $columns)) {
                $passed[$name] = true;
                continue;
            }
            $names[] = "`$name`";
            $values[] = $value === null ? "NULL" : "'" . $mysqli->real_escape_string($value) . "'";
        }
        $sql = "INSERT INTO `$table` (" . implode(',', $names) . ") VALUES (" . implode(',', $values) . ")";
        if (!$mysqli->query($sql)) {
            die("$table: insert failed: " . $mysqli->error . "\n");
        }
        $count++;
    }
    echo "$table: $count rows restored";
    if (count($passed)) {
        echo ", column not in table: " . implode(', ', array_keys($passed));
    }
    if ($strip && $table === 'dashboard') {
        echo ", content_json cleared";
    }
    echo "\n";
}
