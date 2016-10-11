#!/usr/local/bin/php

<?php

// check for correct invocation
if ( empty( $argv[2] ) ) {
  exit( "Missing argument.\r\nUsage: $argv[0] <log file> <date>\r\n" );
}

// load log file into array by line
$loglines = file( $argv[1] );
if ( empty( $loglines ) ) exit( "Empty or missing log file at $argv[1].\r\n" );

// set date for which to count requests
$countDate = $argv[2];

// initialize tracking arrays
$requests = array();
$actions = array();
$locations = array();
$actionsAtLocations = array();

// start processing log
foreach( $loglines AS $num=>$line ) {

  // strip newline
  $line = trim( $line );

  // ignore blank lines
  if ( empty( $line ) ) continue;

  // ignore repeat statements
  $rep = 'Last line repeated';
  if ( substr( $line, 0, strlen( $rep ) ) === $rep ) continue;

  // log format is date time sessionId message
  list( $date, $time, $sessionId, $message ) = explode( " ", trim( $line ), 4 );

  // ignore lines from other dates
  if ( $date != $countDate ) continue;

  // generate unix timestamp from date and time
  $timestamp = strtotime( "$date $time" );

  if ( $timestamp < 1 ) {
    exit( "Parse error at line $num.\r\n" );
  }

  // use timestamp + sessionId as request signature
  $signature = md5( $timestamp . $sessionId );

// parseLoggerFile.php continues
In the first part of this script, you check that it has been invoked correctly, and set various initialization values. You then begin stepping through the log file, line by line, ignoring lines until you get to one that contains an actual message. When you find such a line, you generate a timestamp that will be used as an index to the parsed log file lines, which you are saving in the $request array.
// continues parseLoggerFile.php

  // is this a request we haven't seen yet?
  if ( empty( $requests[ $signature ] ) ) {

    // add to requests array
    $requests[ $signature ] = $line;

    // explode message to get action and location
    list( $action, $location ) = explode( " ", $message, 2 );

    // increment action counter
    if ( empty( $actions[ $action ] ) ) {
      $actions[ $action ] = 1;
    }
    else {
      $actions[ $action ]++;
    }

    // increment location counter
    if ( empty( $locations[ $location ] ) ) {
      $locations[ $location ] = 1;
    }
    else {
      $locations[ $location ]++;
    }

    // increment actionsAtLocation counter
    if ( empty( $actionsAtLocations[ "$action:$location" ] ) ) {
      $actionsAtLocations[ "$action:$location" ] = 1;
    }
    else {
      $actionsAtLocations[ "$action:$location" ]++;
    }

    // end if unseen request
  }
}

print "Found " . count( $requests ) . " requests for $countDate,
         details follow.\r\n
       Actions: " . print_r( $actions, 1 ) . "\r\n
       Locations: " . print_r( $locations, 1 ) . "\r\n
       Actions At Locations: " . print_r( $actionsAtLocations, 1 ) . "\r\n";

?>