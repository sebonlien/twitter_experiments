<?php
require_once("Fetcher.class.php");

$fetcher = new Fetcher;
print_r($fetcher->lookupUser($argv[1]));
?>
