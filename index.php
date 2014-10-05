<?php
require_once("Fetcher.class.php");

$fetcher = Fetcher::user($argv[1]);
echo serialize($fetcher->getFollowers());
?>
