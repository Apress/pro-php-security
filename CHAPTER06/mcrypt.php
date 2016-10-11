<?php

  class mcrypt {
    private $mcrypt;     // the resource for the object
    public $algo;        // the active encryption algorithm
    private $iv;         // the value of the initialization vector
    private $key;        // the key for the encryption
    public $ivsize;      // the size of the initialization vector
    private $maxKeysize; // the ideal size of the key
    public $keysize;     // the actual size of the key in use

    public function __construct( $algo='aes' ) {
      // seed the random number generator
      srand( (double) time() );

      // algorithm must be one of aes, tripledes, or blowfish
      switch ( $algo ) {
      case 'aes':
        $algorithm = MCRYPT_RIJNDAEL_256;
        break;
      case 'tripledes':
        $algorithm = MCRYPT_TRIPLEDES;
        break;
      case 'blowfish':
        $algorithm = MCRYPT_BLOWFISH;
        break;
      default:
        // disallow any other algorithm
        exit( "Fatal error. This implementation does not support $algo.
               Please use one of 'aes','tripledes', or 'blowfish'." );
      }
      $this->algo = $algorithm;

      // get a new mcrypt resource
      $this->mcrypt = mcrypt_module_open( $algorithm, '',  MCRYPT_MODE_CBC, '' );

      // determine size of initialization vector
      $this->ivsize = mcrypt_enc_get_iv_size( $this->mcrypt );

      // determine key length
      $this->maxKeysize = mcrypt_enc_get_key_size( $this->mcrypt );

     // end of _construct() method
    }

    public function setKey( $secret ) {
      // initialize key
      $key = NULL;

      // determine number of 32-character key blocks we need to use
      $keyblocks = ceil( ( $this->maxKeysize * 2 ) / 32 );

      // for each keyblock, generate a different md5 digest and append to key
      for ( $ix = 0; $ix < $keyblocks; $ix++ ) {
        $key .= md5( $ix . $secret );
      }

      // then pack the hexadecimal key to binary (2:1 ratio)
      $key = pack( 'H*', $key );

      // cut key to proper length
      $this->key = substr( $key, 0, $this->maxKeysize );
      $this->keysize = strlen( $this->key );
    }

    public function encrypt( $data ) {
      // generate a new initialization vector
      $this->iv = mcrypt_create_iv( $this->ivsize, MCRYPT_RAND );

      // init
      if (mcrypt_generic_init( $this->mcrypt, $this->key, $this->iv ) === -1) {
        $this->__destruct();
        exit( 'Fatal error, could not initialize encryption routine.' );
      }

      // encrypt
      $ciphertext = mcrypt_generic( $this->mcrypt, $data );

      // deinit
      mcrypt_generic_deinit( $this->mcrypt );

      // prepend initialization vector
      $out = $this->iv.$ciphertext;

      // encode encrypted value as base64
      // split into 64 character lines for transmission
      $out = chunk_split( base64_encode( $out ), 64 );

      return $out;
    }

    public function decrypt( $data ) {
      // expect base64
      $input = base64_decode( $data );

      // learn initialization vector from $input
      $this->iv = substr( $input, 0, $this->ivsize );
      $ciphertext = substr( $input, $this->ivsize );

      // init
      if ( mcrypt_generic_init( $this->mcrypt, $this->key, $this->iv ) === -1) {
        $this->__destruct();
        exit( 'Fatal error, could not initialize encryption routine.' );
      }

      // decrypt
      $out = mdecrypt_generic( $this->mcrypt, $ciphertext );

      // deinit
      mcrypt_generic_deinit( $this->mcrypt );

      // return decrypted data
      return $out;
    }

  public function __destruct() {
      // write over key in memory
      $this->key = str_repeat( 'X', strlen( $this->key ) );

      // free the mcrypt resource
      mcrypt_module_close( $this->mcrypt );
    }

    // end of mcrypt class
  }

?>