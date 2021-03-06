<?php
define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

require_once("../pdo.php");
require_once("../lib/lms_lib.php");

?>
<html>
<head>
<?php echo($OUTPUT->togglePreScript()); ?>
</head>
<body>
<?php

$p = $CFG->dbprefix;
echo("Checking plugins table...<br/>\n");
$plugins = "{$p}lms_plugins";
$table_fields = $PDOX->metadata($plugins);

if ( $table_fields === false ) {
    echo("Creating plugins table...<br/>\n");
    $sql = "
create table {$plugins} (
    plugin_id        INTEGER NOT NULL AUTO_INCREMENT,
    plugin_path      VARCHAR(255) NOT NULL,

    version          BIGINT NOT NULL,

    title            VARCHAR(2048) NULL,

    json             TEXT NULL,
    created_at       DATETIME NOT NULL,
    updated_at       DATETIME NOT NULL,

    UNIQUE(plugin_path),
    PRIMARY KEY (plugin_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;";
    $q = $PDOX->queryReturnError($sql);
    if ( ! $q->success ) die("Unable to create lms_plugins table: ".implode(":", $q->errorInfo) );
    echo("Created plugins table...<br/>\n");
}

echo("Checking for any needed upgrades...<br/>\n");

// Scan the tools folders
$tools = findFiles("database.php","../");
if ( count($tools) < 1 ) {
    echo("No database.php files found...<br/>\n");
    return;
}

// A simple precedence order..   Will have to improve this.
foreach($tools as $k => $tool ) {
    if ( strpos($tool,"core/lti/database.php") && $k != 0 ) {
        $tmp = $tools[0];
        $tools[0] = $tools[$k];
        $tools[$k] = $tmp;
        break;
    }
}

$maxversion = 0;
$maxpath = '';
foreach($tools as $tool ) {
    $path = str_replace("../","",$tool);
    echo("Checking $path ...<br/>\n");
    unset($DATABASE_INSTALL);
    unset($DATABASE_POST_CREATE);
    unset($DATABASE_UNINSTALL);
    unset($DATABASE_UPGRADE);
    require($tool);
    require('migrate-run.php');
}

echo("\n<br/>Highest database version=$maxversion in $maxpath<br/>\n");

if ( $maxversion != $CFG->dbversion ) {
   echo("-- WARNING: You should set \$CFG->dbversion=$maxversion in setup.php
        before distributing this version of the code.<br/>\n");
}

?>
</body>
</html>

