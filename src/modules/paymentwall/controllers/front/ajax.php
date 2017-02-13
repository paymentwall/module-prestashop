<?php
class PaymentwallAjaxModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $orderId = Tools::getValue('orderId');
        if (!$orderId || !$orderStatus = OrderHistory::getLastOrderState($orderId)) {
            echo 0;
        } else {
            echo $orderStatus->id;
        }
    }
}