<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>PHP_SELF</title>

  </head>
  <body>
<h3>PHP_SELF is <?=$_SERVER['PHP_SELF']?></h3>
<pre>
With nl2br(): <?=htmlentities( htmlentities( nl2br( $_SERVER['PHP_SELF'] ) ) )?>

In urlencode(): <?=urlencode( $_SERVER['PHP_SELF'] )?>

In rawurlencode(): <?=rawurlencode( $_SERVER['PHP_SELF'] )?>
</pre>
  </body>
</html>