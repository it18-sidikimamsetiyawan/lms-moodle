<?php
// Example Moodle configuration. Copy to config.php and fill in real values.
// config.php is gitignored - never commit real credentials.
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodleuser';
$CFG->dbpass    = 'CHANGE_ME';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = ['dbport' => 3307];

$CFG->wwwroot   = 'http://localhost:8081';
$CFG->dataroot  = '/path/to/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');
