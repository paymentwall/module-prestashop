<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/paymentwall.php');

$orderStatus = OrderHistory::getLastOrderState($_GET['orderId']);
if ($orderStatus) {
	echo $orderStatus->id;
} else {
	echo 0;
}
?>
