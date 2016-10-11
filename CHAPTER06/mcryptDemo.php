<?php

  // specify which algorithm to use:
  //  aes, blowfish, or tripledes
  $algorithm = 'aes';

  // create a new mcrypt object
  include_once( 'mcrypt.php' );
  $mcrypt = new mcrypt( $algorithm );

  // specify the encryption key
  $secret = 'Lions, tigers and bears, oh my.';
  $mcrypt->setKey( $secret );

  // settings report
  print "<p>The encryption algorithm is $mcrypt->algo,
         which has a key size of $mcrypt->keysize bytes (" .
         ( $mcrypt->keysize * 8 ) . " bits).</p>";

  // specify some text, and encrypt it
  $text = 'The goat is in the red barn.';
  $encrypted = $mcrypt->encrypt( $text );
  print "<p>The plain text is:<br />$text</p>";
  print "<p>The encrypted, base64-encoded text is:<br />$encrypted</p>";

  // decrypt the encrypted text
  $decrypted = $mcrypt->decrypt( $encrypted );
  print "<p>The decrypted text is:<br />$decrypted</p>";

?>