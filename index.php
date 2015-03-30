<?php

include_once 'scrapeweibo.php';

$username = "username";
$password = "password";
$ssoversion = "1.4.18";
$server = "http://localhost:1337/?";
$weiboLink = 'weiboprofile/pagelink';

$scrape = new scrapeWeibo($username, $password, $ssoversion, $server, $weiboLink);
$datas = $scrape -> getScrape();

?>