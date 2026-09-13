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
// Exports the dashboard content column to a JSONL file for the census script.
// Read only, it makes no changes to the database.
//
// Run from the emoncms root:
//   php Modules/dashboard/tools/export_content.php --out=/var/tmp/dashboards.jsonl
//
// Userids are replaced with a salted hash. The salt is random per run unless
// EXPORT_SALT is set, so exports from different runs cannot be linked.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') die("cli only\n");

chdir(dirname(__FILE__) . "/../../../");
require "process_settings.php";

$opts = getopt("", array("out::", "limit::", "help"));

if (isset($opts['help'])) {
    echo "usage: php export_content.php [--out=FILE] [--limit=N]\n";
    echo "  --out    output file, default dashboards.jsonl in the current directory\n";
    echo "  --limit  export at most N dashboards, for a quick look\n";
    exit(0);
}

$outfile = isset($opts['out']) ? $opts['out'] : "dashboards.jsonl";
$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;

$salt = getenv("EXPORT_SALT");
if ($salt === false || $salt === "") $salt = bin2hex(random_bytes(16));

$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
if ($mysqli->connect_error) die("Cannot connect to database: " . $mysqli->connect_error . "\n");
$mysqli->set_charset("utf8mb4");

$fh = fopen($outfile, "w");
if (!$fh) die("Cannot write to $outfile\n");

$sql = "SELECT id, userid, content, height, gridsize, public, published FROM dashboard ORDER BY id";
if ($limit > 0) $sql .= " LIMIT $limit";

// Unbuffered so a large table does not have to fit in memory
$result = $mysqli->query($sql, MYSQLI_USE_RESULT);
if (!$result) die("Query failed: " . $mysqli->error . "\n");

$count = 0;
$empty = 0;
$bytes = 0;

while ($row = $result->fetch_assoc()) {
    $content = $row['content'] === null ? "" : $row['content'];
    if (trim($content) === "") $empty++;
    $bytes += strlen($content);

    $record = array(
        'id' => (int) $row['id'],
        'user' => substr(hash('sha256', $salt . ':' . $row['userid']), 0, 12),
        'height' => (int) $row['height'],
        'gridsize' => (int) $row['gridsize'],
        'public' => (int) $row['public'],
        'published' => (int) $row['published'],
        'content' => $content
    );

    $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        fwrite(STDERR, "Skipped dashboard " . $row['id'] . ": " . json_last_error_msg() . "\n");
        continue;
    }
    fwrite($fh, $json . "\n");
    $count++;
}

$result->free();
fclose($fh);
$mysqli->close();

echo "Exported $count dashboards to $outfile\n";
echo "  empty content: $empty\n";
echo "  total content: " . round($bytes / 1024 / 1024, 1) . " MB\n";
