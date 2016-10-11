<?php

  // you have filled out and submitted the form
  if ( !empty( $_POST['in'] ) ) {

    // load submitted username:password list into array
    $inContents = $_POST['in'];
    $inList = explode( "\n", $inContents );

    // set up output
    header( 'Content-Type: text/plain' );
    $output = NULL;

    // for each submitted line...
    foreach ( $inList as $line ) {
      $line = trim( $line );

      // keep empty lines and comments, but don't process them
      if ( empty( $line ) || substr( $line, 0, 1 ) === '#' ) {
        $output .= "$line\r\n";
        continue;
      }

      // split into name and password
      list( $name, $passwd ) = explode( ':', $line );

      // use crypt() to encrypt the password
      $passwd = crypt( $passwd );

      // add username:encrypted password pair to output
      $output .= "$name:$passwd\r\n";
    }

    // display password file output for subsequent saving
    exit( $output );
  }

// form is presented first time through
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>htpasswd.php</title>
  </head>
  <body onload="document.getElementById('in').focus()" >
    <form action="<?= $_SERVER['SCRIPT_NAME'] ?>" method="post" >
      <p>Paste passwords.txt below, one username:password pair per line.</p>
      <textarea name="in" id="in" rows="8" cols="40"></textarea><br />
      <input type="submit" value="create passwords.crypt" />
    </form>
  </body>
</html>