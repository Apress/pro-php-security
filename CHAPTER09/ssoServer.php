<?php

// use session to track state
session_start();

// config
$expect = 'Authorize';
$clientURI = 'http://www.example.org/ssoClient.php';
$serverURI = 'https://ssl.example.org/ssoServer.php';
$clientCert = 'www.example.org-80.crt';
$serverCert = 'ssl.example.org-443.crt';
$serverKey = 'ssl.example.org-443.key';
$serverKeyPassphrase = '1234';

// main
require_once( 'singleSignOn.php' );
$localCertificate =  file_get_contents( $serverCert );
$localKey =  file_get_contents( $serverKey );
$server = new singleSignOn( $localCertificate, $localKey );

// form processor, skip on first pass
if ( !empty( $_POST['submit'] ) ) {

  // get original return address from session
  $request = $_SESSION['ssoRequest'];
  $to = $request[1]; // original request['return']

  //
  // authenticate username and password here... unshown
  //

  // build command
  $command = "Authorize $_POST[username]";

  // send command to return address
  print '<h3>Authenticated, redirecting back to insecure
         with authorize token</h3>';
  $server->makeRequest( $command, $clientURI, $serverURI,
                        $serverKeyPassphrase );
  exit();
}

// discover command
$request = $server->discoverRequest( $clientURI, $serverKeyPassphrase );
$command = $server->getRequestCommand();
if ( !$command ) exit( 'No command found.' );

// put request in session
$_SESSION['ssoRequest'] = $request;

// command login:
?>
<h1>This is a secure server...</h1>
<p>...acting on behalf of
<a href="<?=$server->getRequestReturn()?>">
  <?=$server->getRequestReturn()?></a>.</p>
<p>Please <?=$server->getRequestCommand()?> below.</p>
<form action="" method="post">
  username: <input type="text" name="username" /><br>
  password: <input type="password" name="password" /><br>
  <input type="submit" name="submit" value="login" />
</form>
