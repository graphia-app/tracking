<?php
header("Content-Type: text/plain");

if(array_key_exists("ip", $_GET))
{
  $ip = $_GET["ip"];
  $hostname = gethostbyaddr($ip);

  echo $hostname;
}
?>
