<?php

// schedule signals
declare( ticks = 1 );

// set up signal handlers
pcntl_signal( SIGTERM, "sig_handler" );
pcntl_signal( SIGHUP, "sig_handler" );

// log file
$log = 'changeOwnershipDaemon.log';

// make sure we're root
if ( posix_getuid() != 0 ) {
  exit( "changeOwnershipDaemon must be run as root.\r\n" );
}

// limit paths
$allowedPaths = array( '/tmp', '/home/csnyder/uploads' );

// db config
$dbuser = 'username';
$dbpass = 'password';
$dbhost = 'localhost';
$dbname = 'pps';




// import job queue manager class
include_once( 'jobManagerClass.php' );

// create daemon
$fork = pcntl_fork();
if ( $fork === -1 ) {
  exit("Could not fork.\r\n");
}
elseif ( $fork ) {
  // script exits, leaving daemon running
  exit("Started background permissionsDaemon with PID $fork.\r\n");
}

// daemon gets its own process ID
$dpid = posix_getpid();

// daemon detaches from the controlling terminal
if ( !posix_setsid() ) {
  dlog( "Daemon could not detach." );
  exit();
}
dlog( "Daemon running." );

// loop forever watching for queued jobs
while ( TRUE ) {
  // sleep for 5 seconds each loop
  sleep( 5 );




  // db connection
  $db = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );

  // create a new job queue manager object
  try {
    $job = new jobManager( $db );
  }
  catch ( Exception $e ) {
    exception_handler( $e );
  }

  // fetch next outstanding job in queue
  while ( $job->next() ) {
    try {
      $job->start();
    }
    catch ( Exception $e ) {
      exception_handler( $e );
    }

    // parse request
    list( $command, $owner, $path ) = explode( ' ', $job->request );
    dlog( "Starting job $job->id: $command $owner $path ... " );

    // check for complete changeOwnership request
    if ( $command != 'changeOwnership' || empty( $owner ) || empty( $path ) ) {
      $job->result = "Invalid command";
      $job->status = 'error';
    }

    // check for violations in allowed path
    $allowed = FALSE;
    foreach( $allowedPaths AS $pathpart ) {
      // look for match against allowed paths
      if ( substr( $path, 0, strlen( $pathpart ) ) == $pathpart ) {
        $allowed = TRUE;
        break;
      }
    }
    // check also for double-dots in path
    if ( !$allowed || strpos( $path, '..' ) ) {
      $job->result = "Invalid path";
      $job->status = 'error';
    }

    // if no errors, then carry out request
    if ( $job->status != 'error' ) {
      $success = @chown( $path, $owner );
      if ( !$success ) {
        $job->result = "Could not chown( $path, $owner )";
        $job->status = 'error';
      }
      else {
        $job->result = "OK";
        $job->status = 'done';
      }
    }

    // save job
    try {
      $job->finish( $job->status );
    }
    catch ( Exception $e ) {
      exception_handler( $e );
    }
    dlog( "Finished job $job->id: $job->status" );
  }

  // close db connection
  $db->close();

  // unset db and job
  unset( $db, $job );

  // log every hour
  if ( !isset( $lastLog ) || ( date( 'i' ) == '00'
       && date( 'h' ) != $lastLog ) ) {
    dlog( date( 'r' ) );
    $lastLog = date( 'h' );
  }

  // end while( TRUE ) loop
}




// lib
function dlog( $message ) {
  global $log, $dpid;
  $fp = fopen( $log, 'a' );
  fwrite( $fp, "$dpid $message\r\n" );
  fclose( $fp );
}

function exception_handler( $e ) {
  global $dpid;

  // log exception
  dlog( 'Caught exception: ' . $e->getMessage() );

  // send TERM signal to self
  posix_kill( $dpid, SIGTERM );
}

function sig_handler( $signo ) {
  global $child, $children;
  dlog( "Received signal $signo." );

  switch ($signo) {
    case SIGTERM:
      // handle shutdown tasks
      dlog( "Terminating." );
      exit();
      break;

    case SIGHUP:
      // handle restart tasks
      dlog( "Restarting." );
      shell_exec( 'php permissionsDaemon.php > /dev/null 2>&1 &' );
      exit();
      break;

    default:
      // handle any other signals
      dlog( "Unhandled signal." );
  }
}

?>