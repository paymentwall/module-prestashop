<?php

/*
 * Plugin Name: Paymentwall for Prestashop 1.6
 * Plugin URI: https://docs.paymentwall.com/modules/prestashop
 * Description: Official Paymentwall module for Prestashop.
 * Version: 1.6.2
 * Author: The Paymentwall Team
 * Author URI: http://www.paymentwall.com/
 * License: The MIT License (MIT)
 *
 */

require_once('lib/paymentwall-php/lib/paymentwall.php');

class Paymentwall extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();
    const ORDER_PROCESSING = 3;
    const PWLOCAL_METHOD = 'Paymentwall';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_ORDER_PLACED = 'order_placed';
    const STATUS_ORDER_SHIPPED = 'order_shipped';
    const STATUS_ORDER_CANCELED = 'order_cancelled';
    const STATUS_DELIVERING = 'delivering';

    public function __construct()
    {
        $this->name = 'paymentwall';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Paymentwall Team';
        /* The parent construct is required for translations */
        parent::__construct();

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Paymentwall');
        $this->description = $this->l('Accept payments from all over the world using many local and global credit cards, bank transfers, ewallets, sms, prepaid cards such as Paysafecard, Ukash and more via Paymentwall.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall, and remove all information regarding this module?');

        $this->initPaymentwallConfig();
        $this->context->controller->addCSS($this->_path . 'css/paymentwall.css');
    }

    public function install()
    {
        if (!parent::install())
            return false;

        Configuration::updateValue('PAYMENTWALL_APP_KEY', '');
        Configuration::updateValue('PAYMENTWALL_SECRET_KEY', '');
        Configuration::updateValue('PAYMENTWALL_WIDGET_TYPE', '');
        Configuration::updateValue('PAYMENTWALL_TEST_MODE', 0);
        Configuration::updateValue('PAYMENTWALL_ORDER_STATUS', self::ORDER_PROCESSING);
        Configuration::updateValue('PAYMENTWALL_ORDER_AWAITING', (int)$this->createOrderStatus());
        if (!$this->registerHook('payment')) {
            return false;
        }

        if (!$this->registerHook('actionOrderHistoryAddAfter')) {
            return false;
        }
        if (!$this->registerHook('actionAdminOrdersTrackingNumberUpdate')) {
            return false;
        }
    }

    public function uninstall()
    {
        Configuration::deleteByName('PAYMENTWALL_APP_KEY', $this->l('Application key'));
        Configuration::deleteByName('PAYMENTWALL_SECRET_KEY', $this->l('Secret key'));
        Configuration::deleteByName('PAYMENTWALL_WIDGET_TYPE', $this->l('Widget code'));
        Configuration::deleteByName('PAYMENTWALL_TEST_MODE', $this->l('Test mode'));
        Configuration::deleteByName('PAYMENTWALL_ORDER_STATUS', $this->l('Order status'));
        $this->removeOrderStatus();
        Configuration::deleteByName('PAYMENTWALL_ORDER_AWAITING', $this->l('Order awaiting'));
        if (!parent::uninstall())
            return false;
        return true;
    }

    public function getContent()
    {
        $this->_html = '<h2><img src="' . $this->_path . 'images/logo.gif"> Paymentwall</h2>';

        if (Tools::getValue('submitAddconfiguration')) {
            if (!Tools::getValue('PAYMENTWALL_APP_KEY'))
                $this->_postErrors[] = $this->l('Application key is required.');
            if (!Tools::getValue('PAYMENTWALL_SECRET_KEY'))
                $this->_postErrors[] = $this->l('Secret key is required.');
            if (!Tools::getValue('PAYMENTWALL_WIDGET_TYPE'))
                $this->_postErrors[] = $this->l('Widget type is required.');

            if (!sizeof($this->_postErrors)) {
                Configuration::updateValue('PAYMENTWALL_APP_KEY', Tools::getValue('PAYMENTWALL_APP_KEY'));
                Configuration::updateValue('PAYMENTWALL_SECRET_KEY', Tools::getValue('PAYMENTWALL_SECRET_KEY'));
                Configuration::updateValue('PAYMENTWALL_WIDGET_TYPE', Tools::getValue('PAYMENTWALL_WIDGET_TYPE'));
                Configuration::updateValue('PAYMENTWALL_TEST_MODE', Tools::getValue('PAYMENTWALL_TEST_MODE'));
                Configuration::updateValue('PAYMENTWALL_ORDER_STATUS', Tools::getValue('PAYMENTWALL_ORDER_STATUS'));
                $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                foreach ($this->_postErrors AS $error) {
                    $this->_html .= $this->displayError($error);
                }
            }
        }

        $this->displayFormSettings();
        return $this->_html;
    }

    public function displayFormSettings()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings Paymentwall'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Application key'),
                        'name' => 'PAYMENTWALL_APP_KEY',
                        'suffix' => 'Application key for payment method: Paymentwall',
                        'class' => 'w220',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Secret key'),
                        'name' => 'PAYMENTWALL_SECRET_KEY',
                        'suffix' => 'Secret key for payment method: Paymentwall',
                        'class' => 'w220',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Order status'),
                        'name' => 'PAYMENTWALL_ORDER_STATUS',
                        'class' => 'w220',
                        'options' => $this->getOrderStates()
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Widget code'),
                        'name' => 'PAYMENTWALL_WIDGET_TYPE',
                        'suffix' => 'Type of widget for payment method: Paymentwall',
                        'class' => 'w50',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Test mode'),
                        'name' => 'PAYMENTWALL_TEST_MODE',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );

        $helper = new HelperForm();
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        $this->_html .= $helper->generateForm(array($fields_form));
        $this->_html .= $this->display(__FILE__, '/views/information.tpl');
    }

    public function getConfigFieldsValues()
    {
        $id_shop_group = Shop::getContextShopGroupID();
        $id_shop = Shop::getContextShopID();

        return array(
            'PAYMENTWALL_APP_KEY' => Tools::getValue('PAYMENTWALL_APP_KEY', Configuration::get('PAYMENTWALL_APP_KEY', null, $id_shop_group, $id_shop)),
            'PAYMENTWALL_SECRET_KEY' => Tools::getValue('PAYMENTWALL_SECRET_KEY', Configuration::get('PAYMENTWALL_SECRET_KEY', null, $id_shop_group, $id_shop)),
            'PAYMENTWALL_WIDGET_TYPE' => Tools::getValue('PAYMENTWALL_WIDGET_TYPE', Configuration::get('PAYMENTWALL_WIDGET_TYPE', null, $id_shop_group, $id_shop)),
            'PAYMENTWALL_TEST_MODE' => Tools::getValue('PAYMENTWALL_TEST_MODE', Configuration::get('PAYMENTWALL_TEST_MODE', null, $id_shop_group, $id_shop)),
            'PAYMENTWALL_ORDER_STATUS' => Tools::getValue('PAYMENTWALL_ORDER_STATUS', Configuration::get('PAYMENTWALL_ORDER_STATUS', null, $id_shop_group, $id_shop)),
        );
    }

    public function hookPayment($params)
    {
        $this->smarty->assign('path', $this->_path);
        return $this->display(__FILE__, '/views/payment.tpl');
    }

    public function getWidget($cart, $totalOrder, $currencyCode)
    {
        $widget = new Paymentwall_Widget(
            $cart->id_customer, // id of the end-user who's making the payment
            Configuration::get('PAYMENTWALL_WIDGET_TYPE'), // widget code, e.g. p1; can be picked inside of your merchant account
            array( // product details for Flexible Widget Call. To let users select the product on Paymentwall's end, leave this array empty
                new Paymentwall_Product(
                    $this->currentOrder, // id of the product in your system
                    $totalOrder,               // price
                    $currencyCode, // currency code
                    'Order #' . $this->currentOrder, // product name
                    Paymentwall_Product::TYPE_FIXED // this is a time-based product;
                )
            ),
            array_merge(
                array(
                    'integration_module' => 'prestashop',
                    'test_mode' => Configuration::get('PAYMENTWALL_TEST_MODE'),
                ),
                $this->getUserProfileData($cart)
            )
        );
        return $widget->getHtmlCode(array('width' => '100%', 'height' => '380px'));
    }

    private function initPaymentwallConfig()
    {
        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => Configuration::get('PAYMENTWALL_APP_KEY'), // available in your Paymentwall merchant area
            'private_key' => Configuration::get('PAYMENTWALL_SECRET_KEY') // available in your Paymentwall merchant area
        ));
    }

    private function getUserProfileData($cart)
    {
        $customer = new Customer((int)$cart->id_customer);
        $address = $customer->getAddresses((int)$this->context->language->id);
        $address = $address[0];
        return array(
            'customer[city]' => $address['city'],
            'customer[state]' => $address['state'],
            'customer[address]' => $address['address1'],
            'customer[country]' => $address['country'],
            'customer[zip]' => $address['postcode'],
            'customer[firstname]' => $customer->firstname,
            'customer[lastname]' => $customer->lastname,
            'email' => $customer->email
        );
    }

    public function createOrder($cart)
    {
        $this->validateOrder(
            $cart->id,
            Configuration::get('PAYMENTWALL_ORDER_AWAITING'),
            $cart->getOrderTotal(),
            $this->displayName,
            NULL,
            array(),
            (int)$cart->id_currency,
            false,
            $this->context->customer->secure_key
        );
        $this->displayHeader();
    }

    private function displayHeader()
    {
        if (isset(Context::getContext()->controller)) {
            $controller = Context::getContext()->controller;
        } else {
            $controller = new FrontController();
            $controller->init();
            $controller->setMedia();
        }
        Tools::displayFileAsDeprecated();
        $controller->displayHeader();
    }

    private function getOrderStates()
    {
        $states = OrderState::getOrderStates($this->context->language->id);
        $orderStatus = array();

        if (!empty($states)) {
            foreach ($states AS $state) {
                $orderStatus[] = array(
                    'id' => $state['id_order_state'],
                    'name' => $state['name'],
                );
            }
        }
        $options = array(
            'id' => 'id',
            'name' => 'name',
            'query' => $orderStatus,
        );
        return $options;
    }

    private function createOrderStatus()
    {
        $orderState = new OrderState();
        $orderState->name = array_fill(0, 10, "Awaiting Paymentwall payment");
        $orderState->template = array_fill(0, 10, "paymentwall");
        $orderState->send_email = 0;
        $orderState->invoice = 1;
        $orderState->color = "#4169E1";
        $orderState->unremovable = false;
        $orderState->logable = 0;
        $orderState->add();
        return $orderState->id;

    }

    private function removeOrderStatus()
    {
        $orderState = new OrderState(Configuration::get('PAYMENTWALL_ORDER_AWAITING'));
        $orderState->delete();
    }

    public function pingBack($getData)
    {
        unset($getData['controller']);
        $pingback = new Paymentwall_Pingback($getData, $_SERVER['REMOTE_ADDR']);
        $orderId = $pingback->getProductId();

        if ($pingback->validate(true)) {
            $referenceId = $pingback->getReferenceId();
            $history = new OrderHistory();
            $history->id_order = $orderId;

            if ($pingback->isDeliverable()) {
                $newState = Configuration::get('PAYMENTWALL_ORDER_STATUS');
            } elseif ($pingback->isCancelable()) {
                $newState = Configuration::get('PS_OS_CANCELED');
            }

            if (!empty($newState)) {
                $history->changeIdOrderState($newState, $orderId);
                $this->updateTransactionId($orderId, $referenceId);
                $history->addWithemail(true, array());
            }

            $result = "OK";
        } else {
            $result = $pingback->getErrorSummary();
        }
        return $result;
    }

    public function hookActionOrderHistoryAddAfter($params)
    {
        if (!empty($params)) {
            $order = new Order(intval($params['order_history']->id_order));
            $payments = $order->getOrderPayments();
            $orderPayment = new OrderPayment($payments[0]->id);

            if (
                $order->payment == self::PWLOCAL_METHOD
                && (
                    $params['order_history']->id_order_state == Configuration::get('PS_OS_DELIVERED')
                    || $params['order_history']->id_order_state == Configuration::get('PS_OS_SHIPPING')
                    || $params['order_history']->id_order_state == Configuration::get('PAYMENTWALL_ORDER_STATUS')
                    || $params['order_history']->id_order_state == Configuration::get('PS_OS_CANCELED')
                )
            ) {

                $deliveryStatus = $this->prepareDeliveryStatus($params['order_history']->id_order_state );
                $data =  $this->prepareDeliveryData($order, $orderPayment->transaction_id, $deliveryStatus);

                $this->initPaymentwallConfig();
                $delivery = new Paymentwall_GenerericApiObject('delivery');

                return $delivery->post($data);
            }
        }
    }

    public function prepareDeliveryData($order, $ref, $status = '', $trackingData = array()) {
        $isVirtual = $this->hasVirtualProduct($order);
        $addressDelivery = new Address(intval($order->id_address_delivery));

        $customer = new Customer((int)$order->id_customer);

        $data = array(
            'payment_id' => $ref,
            'merchant_reference_id' => $order->id,
            'estimated_delivery_datetime' => $order->delivery_date,
            'estimated_update_datetime' => $order->date_upd,
            'refundable' => true,
            'status' => $status,
            'details' => 'Order status has been changed on ' . $order->date_upd,
            'shipping_address[email]' => $customer->email ? $customer->email : Context::getContext()->customer->email,
            'shipping_address[firstname]' => $addressDelivery->firstname,
            'shipping_address[lastname]' => $addressDelivery->lastname,
            'shipping_address[country]' => $addressDelivery->country,
            'shipping_address[street]' => $addressDelivery->address1,
            'shipping_address[phone]' => !empty($addressDelivery->phone) ? $addressDelivery->phone : (!empty($addressDelivery->phone_mobile) ? $addressDelivery->phone_mobile : 'NA'),
            'shipping_address[state]' => State::getNameById($addressDelivery->id_state) ? State::getNameById($addressDelivery->id_state) : 'NA',
            'shipping_address[zip]' => $addressDelivery->postcode ? $addressDelivery->postcode : 'NA',
            'shipping_address[city]' => $addressDelivery->city ? $addressDelivery->city : 'NA',
            'reason' => 'none',
            'is_test' => Configuration::get('PAYMENTWALL_TEST_MODE') ? 1 : 0,
        );

        if ($isVirtual) {
            $data['type'] = 'digital';
        } else {
            $data['type'] = 'physical';
        }

        if (!empty($trackingData['carrier_tracking_id']) && !empty($trackingData['carrier_type'])) {
            $data = array_merge($data, $trackingData);
        }

        return $data;

    }

    public function hasVirtualProduct($order) {
        if (!empty($order)) {
            $producs = $order->getProducts();
            foreach ($producs as $item) {
                if ($item['is_virtual']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function prepareTrackingData($params) {
        $trackingData = [];
        if (!empty($params['order']) && !empty($params['carrier'])) {
            $trackingData['carrier_tracking_id'] = $params['order']->getWsShippingNumber();
            $trackingData['carrier_type'] = $params['carrier']->name;
        }
        return $trackingData;

    }

    public function hookActionAdminOrdersTrackingNumberUpdate($params) {
        if (!empty($params['order']->getWsShippingNumber()) &&  $params['order']->payment == self::PWLOCAL_METHOD) {
            $payments = $params['order']->getOrderPayments();
            $trackingData = $this->prepareTrackingData($params);
            $dataDelivery = $this->prepareDeliveryData($params['order'], $payments[0]->transaction_id, self::STATUS_ORDER_SHIPPED, $trackingData);

            $this->initPaymentwallConfig();
            $delivery = new Paymentwall_GenerericApiObject('delivery');

            return $delivery->post($dataDelivery);
        }
    }

    public function prepareDeliveryStatus($orderState) {
        $status = null;
        switch ($orderState) {
            case  Configuration::get('PS_OS_DELIVERED') :
                $status = self::STATUS_DELIVERED;
                break;
            case Configuration::get('PS_OS_SHIPPING'):
                $status = self::STATUS_ORDER_SHIPPED;
                break;
            case Configuration::get('PAYMENTWALL_ORDER_STATUS'):
                $status = self::STATUS_ORDER_PLACED;
                break;
            case Configuration::get('PS_OS_CANCELED'):
                $status = self::STATUS_ORDER_CANCELED;
        }

        return $status;
    }

    public function updateTransactionId ($orderId, $referenceId) {
        $order = new Order(intval($orderId));
        if (!Validate::isLoadedObject($order)) {
            return;
        }
        $payments = $order->getOrderPayments();
        $orderPayment = new OrderPayment($payments[0]->id);
        if ($orderPayment) {
            $orderPayment->transaction_id = $referenceId;
            $orderPayment->update();
        }
    }
}
