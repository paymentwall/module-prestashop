<?php
/**
 * @since 1.5.0
 */
class PaymentwallOrder_SummaryModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
    }

    public function postProcess()
    {
        $cart = Context::getContext()->cart;
        if (empty($cart->id)) {
            Tools::redirect('history.php');
        } else {
            $paymentwall = new Paymentwall();
            $paymentwall->createOrder($cart);

            $currency = new Currency(intval($cart->id_currency));
            $currencyCode = $currency->iso_code;
            $totalOrder = $cart->getOrderTotal();

            $widget = $paymentwall->getWidget($cart, $totalOrder, $currencyCode);
            if ($paymentwall->getConfigFieldsValues()['PAYMENTWALL_USE_PAYMENTWALL_HOSTED_PAGE']) {
                Tools::redirect($widget->getUrl());
                return;
            }

            $this->context->smarty->assign(array(
                'orderId' => $paymentwall->currentOrder,
                'totalOrder' => $totalOrder,
                'currencyCode' => $currencyCode,
                'HOOK_PW_LOCAL' => $widget->getHtmlCode(array('width' => $paymentwall::WIDGET_WIDTH, 'height' => $paymentwall::WIDGET_HEIGHT)),
                'payment_success' => Configuration::get('PAYMENTWALL_ORDER_STATUS'),
                'base_url' => __PS_BASE_URI__
            ));
            $this->setTemplate('module:paymentwall/views/templates/front/order_summary.tpl');
        }
    }
}
