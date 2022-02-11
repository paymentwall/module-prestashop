<?php
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../header.php');
include(dirname(__FILE__) . '/paymentwall.php');

$cart = Context::getContext()->cart;
if (empty($cart->id)) {
    Tools::redirect('history.php');
} else {
    $paymentwall = new Paymentwall();
    $paymentwall->createOrder($cart);
    
    $currency = new Currency(intval($cart->id_currency));
    $currencyCode = $currency->iso_code;
    $totalOrder = $cart->getOrderTotal();

    $smarty->assign(array(
        'orderId' => $paymentwall->currentOrder,
        'totalOrder' => $totalOrder,
        'currencyCode' => $currencyCode,
        'HOOK_PW_LOCAL' => $paymentwall->getWidget($cart, $totalOrder, $currencyCode)
            ->getHtmlCode(array('width' => $paymentwall::WIDGET_WIDTH, 'height' => $paymentwall::WIDGET_HEIGHT)),
        'payment_success' => Configuration::get('PAYMENTWALL_ORDER_STATUS')
    ));
}

$smarty->display(dirname(__FILE__) . '/views/order_summary.tpl');
include(dirname(__FILE__) . '/../../footer.php');
?>