<?php
class PaymentwallPingbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $paymentwall = new Paymentwall();
        echo $paymentwall->pingBack($_GET);
        die;
    }
}