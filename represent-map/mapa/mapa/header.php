<?php
include "./include/db.php";

// connect to db
mysql_connect('localhost', 'root', '') or die(mysql_error());
mysql_select_db('mapa') or die(mysql_error());

// if map is in Startup Genome mode, check for new data

// input parsing
function parseInput($value) {
  $value = htmlspecialchars($value, ENT_QUOTES);
  $value = str_replace("\r", "", $value);
  $value = str_replace("\n", "", $value);
  return $value;
}



?>