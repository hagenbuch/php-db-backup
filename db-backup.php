<?php

/**
 *    Name: db-backup.php
 * Version: 20161019
 * Purpose: Make a daily backup of a database whenever called with the right token
 *          Keep daily backups for a certain number of days
 *          Keep weekly backups for a number of weeks
 *          Keep monthly backups for a number of months
 *   Usage: Called from a cronjob (like wget http://domain.tld/db-backup.php?secrettoken=xyz )
 *          or included from / appended to another script
 * License: Public domain
 *  Author: Andreas Delleske
 * Depends: SQL (MySQL, MariaDB)
 *          PHP > 5.6 on Apache webserver
 *          Permission to use the exec() function
 *          Permission to use commands mysqldump, gzip in the shell 
 *          Webserver is permitted to write to the filesystem
 *          Works on file structure of all-inkl.com
 *    Bugs: Does not work on Microsoft Windows
 */

// we do not want others to find or guess our backup file names, so we 
// store them outside our web folder. This assumes you have write permission there!
$pathelements = explode('/', $_SERVER['DOCUMENT_ROOT']);
// array_splice() changes its arguments!
$backupfolderbasepath = implode('/', array_splice($pathelements, 0, 4)) . '/';

// configure here:
$tokenhash = 'f75778f7425be4db0369d09af37a6c2b9a83dea0e53e7b997412e4b060e607f7';
$dbname = '<dbname>'; // database name
$dbuser = '<dbuser>'; // database username
$dbpasswd = '<dbpassword>';
$dbserver = '<dbserver>'; // database server, in most cases localhost
$backupfoldername = $dbname; // name of the backup folder - could as well be the database name!
$filenamepart = $pathelements[0] . '-' . $dbname; // The first part is the domain name
$keepdays = 14; // in days, 0: no deletion
$keepweeks = 90; // in days, 0: no deletion
$keepmonths = 730; // in days, 0: no deletion
$keepyears = 0; // in years, 0: no deletion

// uncomment this to find the hash for your secret token, don't use mine!
// echo hash('sha256', $secrettoken, false);

// don't edit anything below here unless you know what you're doing!
$output = '';
$send = false;
$r = chr(13);
$n = chr(10);
$rn = $r . $n;
$eol = $rn;
if(!isset($secrettoken)) {
	if(!isset($_GET['secrettoken'])) {
		exit;
	}
	$secrettoken = $_GET['secrettoken'];
	// Only someone accessing via browser may see output:
	$send = true;
}

// Even if an attacker gets to read this script, they will not be able to 
// know the password:
if(hash('sha256', $secrettoken, false) != $tokenhash) {
	exit;   	
}


$backupfolder = $backupfolderbasepath . $backupfoldername;
$sqlfile = $backupfolder . $sqlfilename;
if(!file_exists($backupfolder)) {
	mkdir($backupfolder);
	$output .= 'Directory "' . $backupfolder . '" created.' . $eol;
}
$now = time();
$datestring = strftime('%Y%m%dw%W', $now);
$sqlfilename = $datestring . '-' . $filenamepart . '.sql';
$sqlfile = $backupfolder . '/' . $sqlfilename;
if(file_exists($sqlfile . '.gz')) {
	$output .= 'File "' . $sqlfile . '.gz" exists already' . $eol;
//	exit;
} else {
	// save the database:
	$t0 = microtime(true);
	$outarr = array();
	$cmd = 'mysqldump -u ' . $dbuser . ' -p' . $dbpasswd . ' --allow-keywords --add-drop-table --complete-insert --quote-names ';
	$cmd .= $dbname . ' > ' . $sqlfile;
	exec($cmd, $outarr);
	$t1 = microtime(true);
	exec('gzip ' . $sqlfile . ' 2>&1', $outarr);
	$t2 = microtime(true);
	$output .= implode($eol, $outarr) . $eol;
	$dumpms = intval(($t1 - $t0) * 1000);
	$gzipms = intval(($t2 - $t1) * 1000);
	$output .= 'File "' . $sqlfile . '.gz" dumped in ' . $dumpms . ', gzipped in ' . $gzipms . ' ms' . $eol;
}

// Now delete the old stuff:
$weeklist = array();
$thisweekid = (date('Y') * 52) + date('W');
$monthlist = array();
$thismonthid = (date('Y') * 12) + (date('m') - 1);
$yearlist = array();
$thisyearid = date('Y');
$filelist = scandir($backupfolder, SCANDIR_SORT_ASCENDING);
foreach($filelist as $file) {
	if($file == '.' or $file == '..') {
		// Directory handles: Ignore:
		continue;
	}
	if(substr($file, -7) != '.sql.gz') {
		// Not a .sql.gz file: Ignore:
		$output .= 'Unknown file "' . $file . '" - keep it' . $eol;
		continue;
	}
	$matches = array();
	$rc = preg_match('/^(\d{4})(\d{2})(\d{2})w(\d{1,2}.*$)/', $file, $matches);
	if(count($matches) != 5) {
		// Not a properly formatted file!
		$output .= 'Unknown file "' . $file . '" - keep it' . $eol;
		continue;
	}
	$year = $matches[1];
	$month = $matches[2];
	$day = $matches[3];
	$week = $matches[4];
	// 12:00 so we get around daylight savings time problems:
	$timestamp = mktime(12, 0, 0, $month, $day, $year);
	$todayts = mktime(12, 0, 0, date('m'), date('d'), date('Y'));
	$daydifference = round(($todayts - $timestamp) / (24 * 3600));
	$weekid = ($year * 52) + $week;
	$monthid = ($year * 12) + ($month - 1);
	$size = intval(filesize($backupfolder . '/' . $file) / 1024);
	$output .= '"' . $file . '": ' . $size . ' kBytes, ' . $daydifference . ' days old: ';

	if(($daydifference > $keepyears) and ($keepyears > 0)) {
		// File is older than the longest storage span, 
		$output .= 'is older than ' . $keepyears . ' days, delete it.' . $eol;
		unlink($backupfolder . '/' . $file);
		continue;
	}
	if(!isset($yearlist[$yearid])) {
		// If we don't have that month, for sure we want to 
		// keep it:
		$output .= 'keep it (yearly backups)' . $eol;
		$yearlist[$yearid] = $file;
		continue;
	}
	if(($daydifference > $keepmonths) and ($keepmonths > 0)) {
		// File is older than the longest storage span, 
		$output .= 'is older than ' . $keepmonths . ' days, delete it.' . $eol;
		unlink($backupfolder . '/' . $file);
		continue;
	}
	if(!isset($monthlist[$monthid])) {
		// If we don't have that month, for sure we want to 
		// keep it:
		$output .= 'keep it (monthly backups)' . $eol;
		$monthlist[$monthid] = $file;
		continue;
	}
	if(($daydifference > $keepweeks) and ($keepweeks > 0)) {
		// we're here when we have found an additional week in a
		// month
		$output .= 'is older than ' . $keepweeks . ' days, delete it' . $eol;
		unlink($backupfolder . '/' . $file);
		continue;
	}	
	if(!isset($weeklist[$weekid])) {
		// That's the oldest week: We keep it!
		$output .= 'keep it (weekly backups)' . $eol;
		$weeklist[$weekid] = $file;
		continue;
	}	
	if(($daydifference > $keepdays) and ($keepdays > 0)) {
		$output .= 'is older than ' . $keepdays . ' days, delete it' . $eol;
		unlink($backupfolder . '/' . $file);
		continue;
	}
	// We're here when the file is not old enough for any deletion!
	$output .= 'keep it (daily backups)' . $eol;
}

$output .= $eol . 'Script terminated normally' . $eol;

if($send) {
    echo '<pre>' . $output . '</pre>' . $eol;
}
exit;


function DBBackupForm($config = array()) {
	$header = '<html>
<head>
	<title>Set configuration for database backup
	<style>
body {
   font: 95% Verdana, sans-serif;
   line-height: 1.1;
   height: 100%;
   background-color: white;
}
table { padding: 5px; margin: 0;}
h3 { color: green; }
    </style>
</head>
<body>';
	$footer = '</body>
</html>';
	
	// Try to access database
	if(count($config) > 0) {
		$rc = DBBackupTest($config);
		if($rc) {
			return;
		}
		echo $header;
		echo '<h3>Wrong database settings, please configure:</h3>';
	}
	
	if(isset($_POST)) {
		$config = array();
		$config['dbserver'] = $_POST['dbserver'];
		$config['dbuser'] = $_POST['dbuser'];
		$config['dbname'] = $_POST['dbname'];
		$config['dbpass'] = $_POST['dbpass'];
		$config['keepmonthly'] = intval($_POST['keepmonthly']);
		$config['keepweekly'] = intval($_POST['keepweekly']);
		$config['keepdaily'] = intval($_POST['keepdaily']);
		if($rc = DBBackupTest($config)) {
			// all is fine, modify db-backup.php
			$contents = file_get_contents('db-backup.php');
			$contents = preg_replace('#// <config>(.*)// </config>#m', $content, $configtext);
			$filehandle = fopen('db-backup.bak.php', 'w');
			fwrite($filehandle, $contents);
			fclose($filehandle);
			// copy to old script
			// unlink('db-backup.php');
			// rename('db-backup.bak.php', 'db-backup.php');
			echo 'all done!';
			return;
		}
		echo '<h3>Database connection was not successful, please correct: ';		
	}
	
	if(!isset($config['dbserver'])) {
		$config['dbserver'] = 'localhost';
	}
	echo '

<h3>Please enter the database configuration</h3>

<form action="/db-bakup.php" method="post"> 
<table>
<tr>
	<td style="text-align: right;">Database-Servername:</td>
	<td><input type="text" name="dbserver" value="' . $config['dbserver'] . '"></td>
</tr>
<tr>
	<td style="text-align: right;">Database-Name:</td>
	<td><input type="text" name="dbname" value="' . $config['dbname'] . '"></td>
</tr>
<tr>
	<td style="text-align: right;">Database-Username:</td>
	<td><input type="text" name="dbuser" value="' . $config['dbuser'] . '"><br />Often, the database name is equal to the database username</td>
</tr>
<tr>
	<td style="text-align: right;">Database-Password:</td>
	<td><input type="password" name="dbpasswd"></td>
</tr>
<tr>
	<td style="text-align: right;">Foldername where to put the backup files:</td>
	<td><input name="foldername" value="' . $config['foldername'] . '"></td>
</tr>
<tr>
	<td style="text-align: right;">For many days should we keep a monthly backup?</td>
	<td><input name="keepmonthly" value="' . $config['keepmonthly'] . '"><br />empty for "forever"</td>
</tr>
<tr>
	<td style="text-align: right;">For many days should we keep a weekly backup?</td>
	<td><input name="keepweekly" value="' . $config['keepweekly'] . '"><br />empty for "forever"</td>
</tr>
<tr>
	<td style="text-align: right;">For many days should we keep daily backups?</td>
	<td><input name="keepdaily" value="' . $config['keepdaily'] . '"><br />empty for "forever"</td>
</tr>
</table>
</form>

';
	return;
}

?>
