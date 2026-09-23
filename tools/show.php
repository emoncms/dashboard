<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Prints the stored content of one or more dashboards, so a finding in the
// census can be checked against the html it came from. Read only.
//
// Reads the dashboard table:
//
//   php Modules/dashboard/tools/show.php 42694 44855
//   php Modules/dashboard/tools/show.php --grep=text-align:
//
// Or an export written by export_content.php:
//
//   php Modules/dashboard/tools/show.php dashboards.jsonl 42694
//
// This prints the content column, which after the switch over holds what was
// there before the dashboard was converted. Use migrate.php --html --id=N to
// see the document it converts to.
//
// Add --raw for the content on one line, unwrapped.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') {
    die("cli only\n");
}

require_once dirname(__FILE__) . "/cli.php";

$ids = [];
$opts = [];
$infile = null;
for ($i = 1; $i < $argc; $i++) {
    if (substr($argv[$i], 0, 2) === '--') {
        $parts = explode('=', substr($argv[$i], 2), 2);
        $opts[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
    } elseif ($infile === null && !ctype_digit($argv[$i])) {
        $infile = $argv[$i];
    } else {
        $ids[] = (int) $argv[$i];
    }
}

if (isset($opts['help']) || (!count($ids) && !isset($opts['grep']) && $infile === null)) {
    echo "usage: php show.php [ID ...] [--grep=STRING] [--raw] [--max=N]\n";
    echo "       php show.php EXPORT.jsonl [ID ...] [--grep=STRING]\n\n";
    echo "Reads the dashboard table unless an export file is named.\n";
    exit(0);
}
if ($infile !== null && !is_readable($infile)) {
    die("Cannot read $infile\n");
}

$grep = isset($opts['grep']) ? $opts['grep'] : null;
$max = isset($opts['max']) ? (int) $opts['max'] : 5;
$raw = isset($opts['raw']);

$shown = 0;

foreach (rows($infile, $ids) as $row) {
    if (!is_array($row) || !isset($row['content'])) {
        continue;
    }

    $wanted = count($ids) && in_array((int) $row['id'], $ids);
    if ($grep !== null && strpos($row['content'], $grep) !== false) {
        $wanted = true;
    }
    if (!$wanted) {
        continue;
    }

    echo "===== dashboard " . $row['id'] . "  (" . strlen($row['content']) . " bytes) =====\n";
    if ($raw) {
        echo $row['content'] . "\n\n";
    } else {
        // One element per line, easier to read than a single long line
        echo trim(preg_replace('/></', ">\n<", $row['content'])) . "\n\n";
    }

    if (++$shown >= $max && $grep !== null) {
        echo "(stopping after $max matches, raise with --max)\n";
        break;
    }
}

if (!$shown) {
    echo "No matching dashboards found\n";
}

// Either the export, or the dashboard table when no export was named.
function rows($infile, $ids)
{
    if ($infile !== null) {
        $fh = fopen($infile, 'r');
        while (($line = fgets($fh)) !== false) {
            yield json_decode(trim($line), true);
        }
        fclose($fh);
        return;
    }

    $mysqli = cli_connect();

    $where = count($ids) ? " WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")" : "";
    $result = $mysqli->query(
        "SELECT id, content FROM dashboard" . $where . " ORDER BY id",
        MYSQLI_USE_RESULT
    );
    if (!$result) {
        die("Query failed: " . $mysqli->error . "\n");
    }

    while ($row = $result->fetch_assoc()) {
        yield ['id' => $row['id'], 'content' => $row['content'] === null ? '' : $row['content']];
    }
    $result->free();
    $mysqli->close();
}
