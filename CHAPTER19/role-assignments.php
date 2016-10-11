<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>role assignments</title>

  </head>
  <body>
  <form method="post">
    <h3>Role Administration for /articles</h3>
<?php

$actions = array( 'add','view','edit','delete','publish','addComment','moderateComment' );
$roles = array( 'writer'=>array('add','view','addComment'),
                'editor'=>array('add','view','edit','delete','publish','addComment','moderateComment'),
                'owner'=>array('add','view','edit'),
                'member'=>array('view','addComment'),
                'moderator'=>array('view','addComment','moderateComment') );
$rx = 1;
?>
    <table cellpadding="5">
      <tr>
        <th>Role Name</th>
        <th>Permissions Allowed</th>
      </tr>
      <?php
      foreach( $roles AS $name=>$perms ) {
        $rx++;
        ?>
        <tr style="background-color: <?if($rx%2==0){?>#eee<?}else{?>#eef<?}?>;">
          <td><?=$name?></td>
          <td>
            <?php foreach( $actions AS $action ) {?>
              <input type="checkbox" value="1" <?php if ( in_array( $action, $perms ) ){?>checked="checked"<? } ?> /><?=$action?> &nbsp;
            <? } ?>
          </td>
        </tr>
      <? } ?>
      <tr>
        <td><input type="text" size="8" /><input type="submit" name="addrole" value="add" /></td>
        <td>
            <?php foreach( $actions AS $action ) {?>
              <input type="checkbox" value="1" /><?=$action?> &nbsp;
            <? } ?>
          </td>
      </tr>
    </table>
  </form>
  </body>
</html>