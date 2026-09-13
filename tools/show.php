<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Stage 1 of the move to JSON dashboard content.
// Prints the stored content of one or more dashboards from the export, so a
// finding in the census can be checked against the html it came from.
//
//   php Modules/dashboard/tools/show.php dashboards.jsonl 42694 44855
//   php Modules/dashboard/tools/show.php dashboards.jsonl --grep=text-align:
//
// Add --raw for the content on one line, unwrapped.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') die("cli only\n");

$ids = array();
$opts = array();
$infile = null;
for ($i = 1; $i < $argc; $i++) {
    if (substr($argv[$i], 0, 2) === '--') {
        $parts = explode('=', substr($argv[$i], 2), 2);
        $opts[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
    } else if ($infile === null) {
        $infile = $argv[$i];
    } else {
        $ids[] = (int) $argv[$i];
    }
}

if ($infile === null || isset($opts['help'])) {
    echo "usage: php show.php EXPORT.jsonl [ID ...] [--grep=STRING] [--raw] [--max=N]\n";
    exit(0);
}
if (!is_readable($infile)) die("Cannot read $infile\n");

$grep = isset($opts['grep']) ? $opts['grep'] : null;
$max = isset($opts['max']) ? (int) $opts['max'] : 5;
$raw = isset($opts['raw']);

$fh = fopen($infile, 'r');
$shown = 0;

while (($line = fgets($fh)) !== false) {
    $row = json_decode(trim($line), true);
    if (!is_array($row) || !isset($row['content'])) continue;

    $wanted = count($ids) && in_array((int) $row['id'], $ids);
    if ($grep !== null && strpos($row['content'], $grep) !== false) $wanted = true;
    if (!$wanted) continue;

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

fclose($fh);
if (!$shown) echo "No matching dashboards found\n";
