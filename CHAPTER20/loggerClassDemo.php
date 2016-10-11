<?php

// use sessions
session_start();

// write debug messages to log?
$debug = TRUE;

// load logger class
include_once( 'loggerClass.php' );

// instantiate logger
$log = new logger( '/home/csnyder/log/', $debug, 'csnyder@example.com' );

// register $log->commit() as shutdown function
register_shutdown_function( array( $log, 'commit' ) );

// set action and location
$action = 'test';
$location = $_SERVER['PHP_SELF'];

// activate logging
$log->activate( $action, $location );

// three repeated debug messages
$log->debug( "Testing debug." );
$log->debug( "Testing debug." );
$log->debug( "Testing debug." );

// one regular log message
$log->log( "Testing log." );

// one alert
$log->alert( "Testing alert." );

// dump logger object for demo
print "<pre>".print_r( $log, 1 )."</pre>";

// log will be committed at shutdown

?>