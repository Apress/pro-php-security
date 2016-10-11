<?php

  // requires openSSL and mcrypt classes from Chapter 6
require_once( 'openSSL.php' );
require_once( 'mcrypt.php' );

class singleSignOn {
  // certificate/key directory
  private $certDir = '.';

  // contents of local certificate
  public $certificate;

  // contents of local key
  private $privatekey;

  // stub for openSSL object
  protected $openSSL;

  // constructor
  // initializes local openSSL object
  public function __construct( $cert, $key ) {
    $this->certificate = $cert;
    $this->privatekey = $key;
    // throw error on bad key or cert?

    $this->openSSL = new openSSL();
    $this->openSSL->privatekey( $key );
    $this->openSSL->certificate( $cert );
  }

  // nonce() returns a relatively random number
  public function nonce() {
    $nonce = sha1( uniqid( rand(), TRUE ) );
    return $nonce;
  } // end of nonce() method

  // makeRequest() sends a command to a remote server via HTTP 401 redirect
  // $command is the command to encode
  // $to is the url to send the command to
  // $return is the return address for the response
  public function makeRequest( $command, $to, $return,
                               $keyPassphrase = NULL ) {
    // generate a one-time value to randomize request
    $randomizer = $this->nonce();

    // encode the request using the remote certificate
    $request = "$command::$return::$randomizer";
    $remoteCertificate = $this->getCertificate( $to );
    $encodedMessage = $this->encodeMessage( $request,
      $remoteCertificate, $keyPassphrase );

    // build full remote url with encoded message as GET var
    $remoteURI = $to . '?sso=' . rawurlencode( $encodedMessage );

    // redirect to secure server with message as $_GET['sso']
    $this->redirect( $remoteURI );
  } // end of makeRequest() method

  // discoverRequest() a command and return address sent by makeRequest()
  // $from is the URI to use for the sender; defaults to the referring page
  // returns associative array with command (command) and return address (return)
  public function discoverRequest( $from = FALSE,
                                   $keyPassphrase = NULL ) {
    // find the message
    if ( empty( $_GET['sso'] ) ) return FALSE;
    $encodedMessage = $_GET['sso'];

    // discover from
    if ( !$from ) {
      $from = $_SERVER['HTTP_REFERER'];  //sic
    }

    // decode the message
    $remoteCertificate = $this->getCertificate( $from );
    $request = $this->decodeMessage( $encodedMessage, $remoteCertificate,
                                     $keyPassphrase );

    // decode and save request
    $request = explode( '::', $request );
    $this->request = $request;

    // return the request array
    return $request;
  } // end of discoverRequest() method

  // encodeMessage() encodes a message using the certificate of the intended recipient
  // $message is any string
  // $remoteCertificate is the recipient's certificate (public key)
  public function encodeMessage ( $message, $remoteCertificate,
                                  $keyPassphrase ) {
    // sign message using local server's private key
    $signedMessage = $this->openSSL->sign( $message, $keyPassphrase );

    // generate another key to use for encryption
    $encryptionKey = $this->nonce();

    // encrypt the message using blowfish
    $blowfish = new mcrypt( 'blowfish' );
    $blowfish->setKey( $encryptionKey );
    $encryptedMessage = trim( $blowfish->encrypt( $signedMessage ) );

    // encrypt the encryption key using the secure server's certificate
    $this->openSSL->certificate( $remoteCertificate );
    $sslEncryptedKey = $this->openSSL->encrypt( $encryptionKey );

    // put it all together
    $encodedMessage = "$sslEncryptedKey::$encryptedMessage";
    return $encodedMessage;
  } // end of encodeMessage() method

  // decodeMessage() decodes a message encoded using encodeMessage(),  // using the recipient's private key  public function decodeMessage ( $message, $remoteCertificate,                                  $keyPassphrase ) {    // split fullMessage    list( $sslEncryptedKey, $encryptedMessage ) = explode( '::', $message );    // decrypt the encryptionKey    $encryptionKey = $this->openSSL->decrypt( $sslEncryptedKey,       $keyPassphrase );    // decrypt the blowfish-encrypted message    $blowfish = new mcrypt( 'blowfish' );    $blowfish->setKey( $encryptionKey );    $signedMessage = $blowfish->decrypt( $encryptedMessage );    // verify signature    $this->openSSL->certificate( $remoteCertificate );    $decodedMessage = $this->openSSL->verify( $signedMessage );    if ( !$decodedMessage ) {      exit( "ERROR - Invalid message, unverified." );    }    // return decoded, verified message    return $decodedMessage;  } // end of decodeMessage() method  public function getRequestCommand() {    return $this->request[0];  }  public function getRequestReturn() {    return $this->request[1];  }  // looks in the local certificate directory  // for a copy of the remote certificate  // certificate naming convention is $this->certDir/hostname-port.crt  public function getCertificate( $remoteURI ) {    $parsed = parse_url( $remoteURI );    if ( empty( $parsed['port'] ) ) {      if ( $parsed['scheme'] == 'https' ) {        $parsed['port'] = 443;      }      else {        $parsed['port'] = 80;      }    }    $certificate = $this->certDir . '/' . $parsed[host] .      '-' . $parsed[port] . '.crt';    if ( !is_readable( $certificate ) ) {      exit("Cannot read certificate file            for remote ($remoteURI) signon server: $certificate");    }    return file_get_contents( $certificate );  } // end of getCertificate() method  public function redirect( $url ) {    if ( !headers_sent() ) {      header('HTTP/1.1 401 Authorization Required');      header('Location: '.$url );    }    print '<a href="' . $url . '">Please click here to continue.</a>';    exit();  } // end of redirect() method} // end of singleSignOn class?>