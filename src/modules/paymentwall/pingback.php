<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/paymentwall.php');

$paymentwall = new Paymentwall();

echo $paymentwall->pingBack($_GET);
die;
?>
