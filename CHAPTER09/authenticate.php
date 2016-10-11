<?php
// this script must be included in another rather than run by itself

// force headers
$auth = FALSE;

// user has entered something
if ( isset( $_SERVER['PHP_AUTH_USER'] ) &&
     isset( $_SERVER['PHP_AUTH_PW'] ) ) {

  // get stored usernames and passwords from above the docroot
  $userList = file( '../passwords.crypt' );

  // extract each username and password
  foreach ( $userList as $line ) {
    $line = trim( $line );

    // skip empty lines and comments
    if ( empty( $line ) || substr( $line, 0, 1 ) === '#' ) {
      continue;
    }
    list( $targetUserName, $targetPassword ) = explode( ":", $line );

    // compare submission to stored value
    if ($targetUserName === $_SERVER['PHP_AUTH_USER']) {
      $submittedPassword = crypt( $_SERVER['PHP_AUTH_PW'] );
      // does the user's password match?
      if ( $targetPassword === $submittedPassword ) {
        $auth = TRUE;
        break;
      }
    }
  }
}

// first time through, or user entered wrong data
if ($auth === FALSE) {
  header('WWW-Authenticate: Basic realm="Protected Website"');
  header('HTTP/1.0 401 Unauthorized');
  echo 'You are not authorized!  Goodbye!';
  exit;
}

?>