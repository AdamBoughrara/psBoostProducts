<?php
if (!defined('_PS_VERSION_')) {
    exit;
}


class BoostProducts extends Module
{
    public function __construct()
    {
        $this->name = 'boostproducts';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Innovtech Engineering';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Boost Products');
        $this->description = $this->l('Boost your products on the marketplace to increase visibility.');

        $this->bootstrap = true;
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('displayProductAdditionalInfo') ||
            !$this->registerHook('displayAdminProductsMainStepLeftColumnMiddle') ||
            !$this->registerHook('actionAdminControllerSetMedia') ||
            !$this->registerHook('displayAdminProductsExtra') ||
            !$this->registerHook('displayBackOfficeHeader') ||
            !$this->registerHook('displayHeader') ||
            !$this->installDb()
        ) {
            return false;
        }

        return true;
    }

    public function installDb()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "product_boost` (
            `id_product_boost` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT UNSIGNED NOT NULL,
            `id_vendor` INT UNSIGNED NOT NULL,
            `boost_price` DECIMAL(20,6) NOT NULL DEFAULT '0.000000',
            `boost_start_date` DATETIME NOT NULL,
            `boost_end_date` DATETIME NOT NULL,
            `isBoosted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_product_boost`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        if (Db::getInstance()->execute($sql)) {
            $sql = "SHOW COLUMNS FROM `"._DB_PREFIX_."product` LIKE 'is_boosted'";
            $result = Db::getInstance()->executeS($sql);
            if (empty($result)) {
                $sql = "ALTER TABLE `"._DB_PREFIX_."product` ADD `is_boosted` INT(1) NOT NULL DEFAULT '0'";
                return Db::getInstance()->execute($sql);
            }
            return true;
        }

        return false;
    }



    public function uninstall()
    {
        if (!parent::uninstall() ||
            !$this->unregisterHook('displayProductAdditionalInfo') ||
            !$this->unregisterHook('displayAdminProductsMainStepLeftColumnMiddle') ||
            !$this->unregisterHook('actionAdminControllerSetMedia') ||
            !$this->unregisterHook('displayAdminProductsExtra') ||
            !$this->unregisterHook('displayBackOfficeHeader') ||
            !$this->unregisterHook('displayHeader') ||
            !$this->uninstallDb()
        ) {
            return false;
        }

        return true;
    }

    public function uninstallDb()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "product_boost`";

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return true;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Boost Products Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Boost Price'),
                        'name' => 'boost_price',
                        'desc' => $this->l('Enter the price for product boosting'),
                        'required' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        $helper->fields_value['boost_price'] = Configuration::get('BOOSTPRODUCTS_BOOST_PRICE');

        return $helper->generateForm(array($fields_form));
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $boost_price = (float)(Tools::getValue('boost_price'));

            if (!$boost_price || empty($boost_price))
                $output .= $this->displayError($this->l('Invalid boost price'));
            else {
                Configuration::updateValue('BOOSTPRODUCTS_BOOST_PRICE', $boost_price);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output . $this->renderForm();
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $product = new Product($params['id_product']);
        $isBoosted = $product->boosted; // check if the product is already boosted
        $boost_price = Configuration::get('BOOST_PRICE'); // get the boost price from the module configuration

        // Set the boost start and end dates if the product is already boosted
        $boost_start_date = null;
        $boost_end_date = null;
        if ($isBoosted) {
            $boostedProduct = new BoostedProduct($params['id_product']);
            $boost_start_date = $boostedProduct->boost_start_date;
            $boost_end_date = $boostedProduct->boost_end_date;
        }

        $this->context->smarty->assign(array(
            'isBoosted' => $isBoosted,
            'boost_price' => $boost_price,
            'boost_start_date' => $boost_start_date,
            'boost_end_date' => $boost_end_date
        ));

        return $this->display(__FILE__, 'views/templates/admin/hook/displayAdminProductsExtra.tpl');
    }


    public function hookDisplayProductListReviews($params)
    {
        // Check if BoostedProduct entity exists
        if (!class_exists('\BoostProducts\src\Entity\BoostedProduct')) {
            return '';
        }

        $products = $params['products'];
        $boostedProducts = [];

        foreach ($products as $product) {
            $boostedProduct = \BoostProducts\src\Entity\BoostedProduct::findOneBy([
                'id_product' => $product['id_product'],
                'boosted' => true
            ]);

            if ($boostedProduct) {
                $boostedProducts[] = $boostedProduct;
            }
        }

        if (empty($boostedProducts)) {
            return '';
        }

        return $this->display(__FILE__, 'views/templates/hook/displayProductListReviews.tpl', [
            'boostedProducts' => $boostedProducts
        ]);
    }

    public function hookDisplayProductPriceBlock($params)
    {
        $product = $params['product'];
        $boostedProduct = BoostedProduct::getByProductId($product->id);

        if ($boostedProduct && $boostedProduct->isCurrentlyBoosted()) {
            $boostPrice = $boostedProduct->getBoostPrice();
            $boostDuration = $boostedProduct->getBoostDuration();
            $totalBoostPrice = $boostPrice * $boostDuration;
            $formattedBoostPrice = Tools::displayPrice($boostPrice);
            $formattedTotalBoostPrice = Tools::displayPrice($totalBoostPrice);
            $boostPriceLabel = $this->trans(
                'Boosted for %duration% day(s) for a cost of %price% per day. Total boost price: %total_price%',
                array(
                    '%duration%' => $boostDuration,
                    '%price%' => $formattedBoostPrice,
                    '%total_price%' => $formattedTotalBoostPrice,
                ),
                'Modules.BoostProducts.Admin'
            );

            return $this->display(__FILE__, 'views/templates/hook/product_boost.tpl', array(
                'boost_price' => $boostPriceLabel,
            ));
        }

        return '';
    }

    public function hookActionObjectProductDeleteBefore($params)
    {
        $product = $params['object'];

        // Remove any boost operations associated with the product
        $boostedProductRepo = $this->getDoctrine()->getRepository(BoostedProduct::class);
        $boostedProducts = $boostedProductRepo->findBy(['product' => $product]);

        $entityManager = $this->getDoctrine()->getManager();

        foreach ($boostedProducts as $boostedProduct) {
            $entityManager->remove($boostedProduct);
        }

        $entityManager->flush();
    }

}
