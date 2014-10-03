<?php
require_once("Fetcher.class.php");

$fetcher = Fetcher::user($argv[1]);
print_r($fetcher->getFollowers());

?>
