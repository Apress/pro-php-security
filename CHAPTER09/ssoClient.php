<?php

// use session to keep state
session_start();

// config
$expect = 'Authorize';
$clientURI = 'http://www.example.org/ssoClient.php';
$serverURI = 'https://ssl.example.org/ssoServer.php';
$clientCert = 'www.example.org-80.crt';
$clientKey = 'www.example.org-80.key';
$clientKeyPassphrase = '1234';
$serverCert = 'ssl.example.org-443.crt';

// main

// handle logout
if ( !empty( $_GET['logout'] ) ) {
  unset( $_SESSION['username'] );
 }

// not logged in yet?
if ( empty( $_SESSION['username'] ) ) {
  require_once( 'singleSignOn.php' );

  // load local certificate and key
  $localCertificate = file_get_contents( $clientCert );
  $localKey = file_get_contents( $clientKey );

  // create new singleSignOn instance
  $client = new singleSignOn( $localCertificate, $localKey );

  // no token from the server, redirect
  if ( empty( $_GET['sso'] ) ) {
    print '<h3>Please log in using our secure server:</h3>';

    // make request
    // makeRequest( command, to, from, keypassphrase )
    $client->makeRequest( 'login', $serverURI, $clientURI,
                            $clientKeyPassphrase  );
    exit();
  }

  else {
    // decode request
    // discoverRequest( from, keypassphrase )
    $client->discoverRequest( $serverURI, $clientKeyPassphrase );
    $command = $client->getRequestCommand();

    // check for expected command
    if ( substr( $command, 0, strlen($expect) ) != $expect ) {
      exit( 'Invalid sso token.' );
    }
    else {
      // log in using provided username
      $_SESSION['username'] = trim( substr( $command, strlen($expect) ) );
    }
  }

}

?>
<h3>Hello <?=$_SESSION['username']?>. <a href="?logout=1">logout</a></h3>
