<?php
require_once('lib/paymentwall-php/lib/paymentwall.php');
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Paymentwall extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();
    const ORDER_CANCEL = 6;
    const ORDER_PROCESSING = 3;

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
//        ini_set('display_errors',1);
//        $this->context->controller->addCSS($this->_path . 'css/paymentwall.css');
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
        if (!$this->registerHook('paymentOptions')) {
            return false;
        }

    }

    public function uninstall()
    {
        Configuration::deleteByName('PAYMENTWALL_APP_KEY', $this->l('Project key'));
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
                $this->_postErrors[] = $this->l('Project key is required.');
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
                        'label' => $this->l('Project key'),
                        'name' => 'PAYMENTWALL_APP_KEY',
                        'suffix' => 'Project key for payment method: Paymentwall',
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

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(
            $this->getTemplateVarInfos()
        );

        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->trans('Pay by PaymentWall', array()))
            ->setAction($this->context->link->getModuleLink($this->name, 'order_summary', array(), true))
            ->setAdditionalInformation($this->fetch('module:paymentwall/views/templates/hook/payment_infos.tpl'));
        $payment_options = [
            $newOption,
        ];

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getTemplateVarInfos()
    {
        $cart = Context::getContext()->cart;
        $paymentwall = new Paymentwall();
//        $paymentwall->createOrder($cart);

        $currency = new Currency(intval($cart->id_currency));
        $currencyCode = $currency->iso_code;
        $totalOrder = $cart->getOrderTotal();

        return array(
            'orderId' => $paymentwall->currentOrder,
            'totalOrder' => $totalOrder,
            'currencyCode' => $currencyCode,
            'HOOK_PW_LOCAL' => $paymentwall->getWidget($cart, $totalOrder, $currencyCode),
            'payment_success' => Configuration::get('PAYMENTWALL_ORDER_STATUS')
        );
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
        unset($getData['module']);
        unset($getData['fc']);
        $pingback = new Paymentwall_Pingback($getData, $_SERVER['REMOTE_ADDR']);
        $orderId = $pingback->getProductId();

        if ($pingback->validate()) {
            $history = new OrderHistory();
            $history->id_order = $orderId;
            if ($pingback->isDeliverable()) {
                $history->changeIdOrderState(Configuration::get('PAYMENTWALL_ORDER_STATUS'), $orderId);
                $history->addWithemail(true, array());
            } elseif ($pingback->isCancelable()) {
                $history->changeIdOrderState(self::ORDER_CANCEL, $orderId);
            }
            $result = "OK";
        } else {
            $result = $pingback->getErrorSummary();
        }
        return $result;
    }
}

?>
