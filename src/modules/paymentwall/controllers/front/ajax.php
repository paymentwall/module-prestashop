<?php
class PaymentwallAjaxModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if(!$_GET['orderId']) {
            echo 0;
            return;
        }
        $orderStatus = OrderHistory::getLastOrderState($_GET['orderId']);
        if ($orderStatus) {
            echo $orderStatus->id;
        } else {
            echo 0;
        }
    }
}