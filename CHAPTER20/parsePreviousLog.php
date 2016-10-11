<?php

// log file location
$logpath = '/home/csnyder/logs/';

// log parser location
$logparser = '/home/csnyder/bin/parseLoggerFile.php';

// php cli path
$php = '/usr/local/bin/php';

// generate yesterday's date
$yesterday = date( "Y-m-d", time()-86400 );

// generate yesterday's month
$yestermonth = substr( $yesterday, 0, 7 );

// generate log file path
$logfile = $logpath . $yestermonth . '.log';

// call parser
$command = escapeshellcmd( "$php $logparser $logfile $yesterday" );
print shell_exec( $command );

?>