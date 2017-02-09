<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class PaymentwallOrder_SummaryModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();


    }
	/**
	 * @see FrontController::postProcess()
	 */

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

            $this->context->smarty->assign(array(
                'orderId' => $paymentwall->currentOrder,
                'totalOrder' => $totalOrder,
                'currencyCode' => $currencyCode,
                'HOOK_PW_LOCAL' => $paymentwall->getWidget($cart, $totalOrder, $currencyCode),
                'payment_success' => Configuration::get('PAYMENTWALL_ORDER_STATUS'),
                'base_url' => __PS_BASE_URI__
            ));
            $this->setTemplate('module:paymentwall/views/templates/front/order_summary.tpl');
        }

    }

}
