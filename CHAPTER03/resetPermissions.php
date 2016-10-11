#!/usr/local/bin/php
<?php

// (sample) presets
$presets = array( 'production-www'=>'root:www-0750',
                  'shared-dev'=>':www-2770',
                  'all-mine'=>'-0700'
                  );

// construct usage reminder notice
ob_start();
?>
resetPermissions.php
Changes file ownership and permissions in some location according
to a preset scheme.

Usage: <?=$argv[0]?> $location $preset

  $location -
    Path or filename. Shell wildcards allowed

  $preset -
    Ownership / group / permissions scheme, one of the following:
    <?php
       foreach( $presets AS $name=>$scheme ) {
         print $name . '<br />';
      }

$usage = ob_get_contents();
ob_end_clean();

// provide usage reminder if script was invoked incorrectly
if ( count($argv) < 2 ) {
  exit( $usage );
}

// import arguments
$location = $argv[1];
$preset = $argv[2];
if ( !array_key_exists( $preset, $presets ) ) {
  print 'Invalid preset.\n\n';
  exit( $usage );
}

// parse preset [[$owner]:$group][-$octalMod]
// first into properties
$properties = explode( '-', $presets[$preset] );

// determine whether chown or chgrp was requested
$ownership = FALSE;
$owner = FALSE;
$group = FALSE;
if ( !empty($properties[0]) ) {
  $ownership = explode( ':', $properties[0] );
  if ( count( $ownership ) > 0 ) {
    $owner = $ownership[0];
    $group = $ownership[1];
  }
  else {
    $group = $ownership[0];
  }
}

// determine whether chmod was requested
$octalMod = FALSE;
if ( !empty( $properties[1] ) ) {
  $octalMod = $properties[1];
}

// carry out commands
$result = NULL;
if ( $owner ) {
  print "Changing ownership to $owner.\n";
  $result .= shell_exec( "chown -R $owner $location 2>&1" );
}

if ( $group ) {
  print "Changing groupership to $group.\n";
  $result .= shell_exec( "chgrp -R $group $location 2>&1" );
}

if ( $octalMod ) {
  print "Changing permissions to $octalMod.\n";
  $result .= shell_exec( "chmod -R $octalMod $location 2>&1" );
}

// display errors if any
if ( !empty( $result ) ) {
  print "\nOperation complete, with errors:\n$result\n";
}
else {
  print 'Done.\n';
}

?>
