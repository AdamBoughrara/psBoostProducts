<?php


use App\Entity\BoostedProduct;

class BoostProductAdminController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product';
        $this->className = 'Product';
        $this->identifier = 'id_product';
        $this->lang = false;
        $this->deleted = false;
        $this->explicitSelect = true;

        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign(array(
            'module_dir' => $this->module->getLocalPath(),
            'products' => $this->getProducts(),
            'token' => Tools::getAdminTokenLite('BoostProductAdmin')
        ));

        $this->setTemplate('admin.tpl');
    }

    public function getProducts()
    {
        $products = Db::getInstance()->executeS('
            SELECT p.`id_product`, p.`name`, p.`description_short`, p.`price`, pl.`link_rewrite`
            FROM `' . _DB_PREFIX_ . 'product` p
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON p.`id_product` = pl.`id_product`
            WHERE pl.`id_lang` = ' . (int)$this->context->language->id . '
        ');

        $boostedProducts = Db::getInstance()->executeS('
            SELECT `id_product`
            FROM `' . _DB_PREFIX_ . 'product_boost`
        ');

        foreach ($products as &$product) {
            $product['boosted'] = false;

            foreach ($boostedProducts as $boostedProduct) {
                if ($boostedProduct['id_product'] == $product['id_product']) {
                    $product['boosted'] = true;
                    break;
                }
            }
        }

        return $products;
    }

    public function processToggleBoostedProduct()
    {
        if (!$this->isTokenValid()) {
            die(Tools::jsonEncode(array(
                'success' => false,
                'message' => $this->l('Invalid token')
            )));
        }

        $productId = (int)Tools::getValue('id_product');
        $isBoosted = (int)Tools::getValue('is_boosted');

        if ($isBoosted) {
            // Check if product is already boosted
            $boostedProduct = BoostedProduct::loadByProductId($productId);
            if ($boostedProduct) {
                die(Tools::jsonEncode(array(
                    'success' => false,
                    'message' => $this->l('Product is already boosted')
                )));
            }

            // Create a new BoostedProduct object
            $boostedProduct = new BoostedProduct();
            $boostedProduct->id_product = $productId;
            $boostedProduct->boost_start_date = date('Y-m-d H:i:s');
            $boostedProduct->boost_end_date = date('Y-m-d H:i:s', strtotime('+1 day'));

            // Save the BoostedProduct object
            if ($boostedProduct->save()) {
                die(Tools::jsonEncode(array(
                    'success' => true,
                    'message' => $this->l('Product has been boosted')
                )));
            } else {
                die(Tools::jsonEncode(array(
                    'success' => false,
                    'message' => $this->l('Error while boosting product')
                )));
            }
        } else {
            // Load the BoostedProduct object
            $boostedProduct = BoostedProduct::loadByProductId($productId);
            if (!$boostedProduct) {
                die(Tools::jsonEncode(array(
                    'success' => false,
                    'message' => $this->l('Product is not boosted')
                )));
            }

            // Delete the BoostedProduct object
            if ($boostedProduct->delete()) {
                die(Tools::jsonEncode(array(
                    'success' => true,
                    'message' => $this->l('Product has been un-boosted')
                )));
            } else {
                die(Tools::jsonEncode(array(
                    'success' => false,
                    'message' => $this->l('Error while un-boosting product')
                )));
            }
        }
    }

}
