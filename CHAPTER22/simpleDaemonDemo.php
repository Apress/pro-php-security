#!/usr/local/bin/php
<?php

// functions
// dlog function, writes a message to a log file with current PID
function dlog( $message ) {

  global $log,    // location of log file
         $dpid,   // parent PID
         $cpid;   // child PID

  if ( !empty( $cpid ) ) {
    // current process is a child
    $cpid = "  $cpid";
    $prefix = $cpid;
  }
  else {
    // current process is a parent
    $prefix = $dpid;
  }

  // get file handle to append to log file
  $cfp = fopen( $log, 'a' );

  // wait for an exclusive lock on the log
  flock( $cfp, LOCK_EX );

  // write the message
  fwrite( $cfp, "$prefix $message\r\n" );

  // release the lock
  flock( $cfp, LOCK_UN );

  // close the log file handle
  fclose( $cfp );

  // end of dlog function
}

// sig_handler function, catches and handles signals
function sig_handler( $signo ) {
  global $child, $children;
  dlog( "Received signal $signo." );

  switch ($signo) {
    case SIGTERM:
      // handle shutdown tasks
      if ( !$child ) {
        // kill all child processes
        foreach( $children AS $cpid ) {
          posix_kill( $cpid, SIGTERM );
          pcntl_wait( $status );
        }
        dlog( "Terminating. ".print_r( $children, 1 ) );
      }
      else {
        dlog( "Terminating." );
      }
      exit();
      break;

    case SIGHUP:
      // handle restart requests
      if ( !$child ) {
        // kill all child processes
        foreach( $children AS $cpid ) {
          posix_kill( $cpid, SIGTERM );
          pcntl_wait( $status );
        }

        // now launch a new simpleDaemonDemo.php
        dlog( "Restarting. " . print_r( $children, 1 ) );
        shell_exec( 'php simpleDaemon.php > /dev/null 2>&1 &' );
      }
      else {
        dlog( "Caught restart, waiting for TERM." );
      }
      exit();
      break;

    case SIGCHLD:
      // child status change - use pcntl_wait() to clean up zombie
      $cpid = pcntl_wait( $status );
      dlog( "Caught SIGCHLD from $cpid, status was $status." );
      break;

    default:
      // handle all other signals
      dlog( " ... which is an unhandled signal." );
  }

  // end of sig_handler function
}

// schedule signal checking
declare( ticks = 1 );

// set up signal handlers
pcntl_signal( SIGTERM, "sig_handler" );
pcntl_signal( SIGHUP, "sig_handler" );
pcntl_signal( SIGCHLD, "sig_handler" );

// open a logfile resource
$log = 'daemon.log';
$fp = fopen( $log, 'w' );



print "Forking into the background now...\r\n";

// create the daemon
$fork = pcntl_fork();

// the daemon now exists alongside the script; for it, $fork = 0
if ( $fork === -1 ) {
  exit( "Could not fork.\r\n" );
}
elseif ( $fork ) {
  // the script exits
  exit( "Started background daemon with PID $fork.\r\n" );
}

// the daemon gets its own PID
$dpid = posix_getpid();

// the daemon detaches from the controlling terminal
if ( !posix_setsid() ) {
  dlog( "Daemon could not detach." );
  exit();
}
sleep( 1 );

// prove that file descriptor is inherited by the daemon
fwrite( $fp, "File descriptor was inherited from original process.\r\n" );
fclose( $fp );
dlog( "I am up and running as a daemon." );

// intialize
$children = array();
$child = FALSE;




// loop forever until SIGTERM is received
while ( TRUE ) {
  // sleep for 5 seconds each loop
  sleep( 5 );

  if ( !$child ) {
    // kill oldest child
    if ( count( $children ) > 2 ) {
      $killpid = array_shift( $children );
      dlog( "Killing $killpid now" );
      posix_kill( $killpid, SIGTERM );
      sleep( 1 );
    }

    // create a child process
    $fork = pcntl_fork();
    // child process now exists
    if ( $fork === -1 ) {
      dlog( "Could not fork." );
    }
    elseif ( $fork ) {
      $children[] = $fork;
      sleep( 2 );
      dlog( "Added $fork to children." );
    }
    else {  // $fork = 0; new child process executes here
      $child = TRUE;
      sleep( 1 );
      $cpid = posix_getpid();
      dlog( "Starting up as child." );

      // set nice value to 20 for lowest priority
      proc_nice( 20 );
    }
  }
  else {
    // existing children sleep for some random time
    $randomDelay = rand( 300, 3000 ) * 1000;
    usleep( $randomDelay );
    dlog( "Checking in after $randomDelay microseconds." );
  }

  // end while loop
}

?>
