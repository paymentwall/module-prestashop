<?php
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../header.php');

$smarty->assign(array(
    'orderId' => $_GET['orderId']
));

$smarty->display(dirname(__FILE__) . '/views/order_confirm.tpl');

include(dirname(__FILE__) . '/../../footer.php');
?>