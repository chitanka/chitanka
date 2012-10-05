<?php
$options = getopt('h:P:d:u:p:f:');

$dbhost = isset($options['h']) ? $options['h'] : 'localhost';
$dbport = isset($options['P']) ? $options['P'] : '';
$dbname = isset($options['d']) ? $options['d'] : null;
$dbuser = isset($options['u']) ? $options['u'] : 'root';
$dbpassword = isset($options['p']) ? $options['p'] : '';
$sqlFile = isset($options['f']) ? $options['f'] : null;

function exitWithUsage() {
	global $argv;
	echo <<<USAGE
php $argv[0] -f FILE -d DATABASE [-h HOST=localhost] [-P PORT=] [-u USER=root] [-p PASSWORD=]

USAGE;
	exit;
}

if (empty($sqlFile) || empty($dbname)) {
	exitWithUsage();
}

$dsn = "mysql:host=$dbhost;dbname=$dbname";
if ($dbport) {
	$dsn .= ";port=$dbport";
}

$sqlImporter = new SqlImporter($dsn, $dbuser, $dbpassword);
$sqlImporter->importFile($sqlFile);
