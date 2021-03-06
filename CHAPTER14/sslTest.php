<?php
  // allow by default...
  $allow = TRUE;
  $reason = "";
  // require SSL
  if ( !isset( $_SERVER['HTTPS'] ) ) {
    $allow = FALSE;
    $reason = "You must use an SSL connection for this request.";
  }

  // if SSL and there is a client certificate,
  //   require certificate to be verified
  elseif ( isset( $_SERVER['SSL_CLIENT_VERIFY'] ) AND              $_SERVER['SSL_CLIENT_VERIFY'] == 'FAILED:(null)' ) {
    $allow = FALSE;
    $reason = "The server could not verify your client SSL certificate.";
  }


  if ( !$allow ) {
    header( 'HTTP/1.1 403 Forbidden' );    
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
      <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <title>Connection Not Secure</title>
      </head>
      <body>
        <h1>Connection Not Secure</h1>
        <p><?=$reason?></p>
      </body>
    </html>
    <?
    exit();
  }

  // connection secure, continue with script

?>