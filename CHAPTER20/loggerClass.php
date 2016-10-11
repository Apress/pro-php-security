<?php

class logger {
  // infrastructure
  private $path;              // path of log file
  private $buffer = array();  // array to hold log entries
  private $debug = FALSE;     // capture debug-level messages if desired
  private $alertTo;           // email to send alerts to

  // constructor requires a writable path (file or directory)
   public function __construct( $path, $debug=FALSE, $alertTo=NULL ) {
    // start timer
    $this->start = microtime( TRUE );

    // determine log file path
    $this->path = $path;

    // check to make sure path exists and is writable
    if ( !is_writable( $this->path ) ) {
      throw new Exception( "Log creation failed, unwritable path." );
    }

    // if path is directory, use timestamp-based filename
    if ( is_dir( $this->path ) ) {
      $this->path = $this->path . date('Y-m').'.log';
    }

    // set debug flag
    $this->debug = $debug;

    // set alertTo address
    $this->alertTo = $alertTo;

    // end of constructor
  }

  // returns precise time elapsed since start
  public function elapsed() {
    $time = microtime( TRUE );
    $elapsed = $time - $this->start;
    return $elapsed;
  }



  // main log interface
  private $time;        // request time
  private $session;     // session ID
  private $action;      // requested action
  private $location;    // requested location

  // call activate() to discover metadata
  public function activate( $action = NULL, $location = NULL ) {
    // get timestamp
    $this->time = date('Y-m-d H:i:s', time() );

    // get session ID
    $this->session = session_id();

    // discover action
    if ( !empty( $action ) ) {
      $this->action = $action;
    }
    else {
      if ( !empty( $_GET['action'] ) ) {
        $this->action = $_GET['action'];
      }
      else {
        $this->action = $_SERVER['REQUEST_METHOD'];
      }
    }

    // discover location
    if ( !empty( $location ) ) {
      $this->location = $location;
    }
    else {
      $this->location = $_SERVER['REQUEST_URI'];
    }

    // make the first log entry
    $this->log( "$this->action $this->location" );
  }


  // basic write function for use with three log levels
  private function write( $message ) {
    $written = FALSE;

    // encode newlines in message
    $message = str_replace( array( "\n","\r" ), array( '\n','\r' ), $message );

    // check for repeated message prefix on last line
    $current = count( $this->buffer );
    $last = $this->buffer[ $current - 1 ];
    $rprefix = 'Last line repeated ';
    $repeats = 0;
    $rsuffix = ' times.';
    if ( substr( $last, 0, strlen( $rprefix ) ) === $rprefix ) {
      // check last line but one for duplicate message
      if ( $this->buffer[ $current - 2 ] === $message ) {
        list( $repeats ) = explode( ' ', substr( $last, strlen( $rprefix ) ) );
        $repeats++;
      }
    }
    // check for first repeat of last line
    elseif ( $last === $message ) {
      $repeats = 1;
    }

    if ( $repeats == 0 ) {
      // append new message
      $this->buffer[ $current ] = $message;
      $written = TRUE;
    }
    elseif ( $repeats == 1 ) {
      // append duplicate message
      $this->buffer[ $current ] = $rprefix . $repeats . $rsuffix;
    }
    else {
      // rewrite duplicate message
      $this->buffer[ $current - 1 ] = $rprefix . $repeats . $rsuffix;
    }

    return $written;
  } // end of write() method



  // three log levels: 1) debug
  public function debug( $message ) {
    if ( $this->debug ) {
      $this->write( $message );
    }
  }

  // three log levels: 2) log
  public function log( $message ) {
    $this->write( $message );
  }

  // three log levels: 3) alert
  public function alert( $message ) {
    // if message was written (not repeated) send alert
    if ( $this->write( $message ) ) {
      if ( !empty( $this->alertTo ) ) {
        $subject = "Alert from $this->action at $this->location";
        $sent = mail( $this->alertTo, $subject, $message );
      }
    }
  }



  // serializes buffer and writes it to file,
  //   returns int size of written log in bytes
  public function commit() {

    // prefix each line with time/session stamp
    $prefix = "$this->time $this->session";

    // convert each entry in buffer into new line of log
    foreach ( $this->buffer AS $line ) {
      if( empty( $line ) ) continue;
      $output .= "$prefix $line\r\n";
    }

    // write to disk
    $size = file_put_contents( $this->path, $output, FILE_APPEND );

    // reset buffer
    $this->buffer = array();

    // for debugging, helpful to know when log was committed
    if ( $this->debug ) {
      $elapsed = round( $this->elapsed(), 4 );
      $this->debug( "Committed previous buffer at " . $elapsed . " seconds." );
    }

    // return size of output
    return $size;
  }

// end of logger class
}

?>