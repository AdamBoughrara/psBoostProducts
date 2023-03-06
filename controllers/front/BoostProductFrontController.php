<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BoostProductFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        // Check if boost feature is enabled
        if (!Configuration::get('BOOST_PRODUCTS_ENABLED')) {
            Tools::redirect('index.php');
        }

        // Get product ID from URL
        $id_product = (int)Tools::getValue('id_product');

        // Check if product exists
        if (!Validate::isLoadedObject(new Product($id_product))) {
            Tools::redirect('index.php');
        }

        // Get boost price per day per product from configuration
        $boost_price = (float)Configuration::get('BOOST_PRODUCTS_PRICE');

        // Load template file
        $this->setTemplate('module:boostproducts/views/templates/front/boost_product.tpl');

        // Pass necessary variables to the template
        $this->context->smarty->assign([
            'id_product' => $id_product,
            'boost_price' => $boost_price,
        ]);
    }

    public function postProcess()
    {
        // Check if boost form is submitted
        if (Tools::isSubmit('boost_product')) {

            // Get product ID and boost duration from form submission
            $id_product = (int)Tools::getValue('id_product');
            $boost_duration = (int)Tools::getValue('boost_duration');

            // Validate boost duration
            if (!Validate::isInt($boost_duration) || $boost_duration < 1) {
                $this->errors[] = $this->module->l('Invalid boost duration');
                return;
            }

            // Get boost price per day per product from configuration
            $boost_price = (float)Configuration::get('BOOST_PRICE');

            // Calculate total boost price
            $total_price = $boost_price * $boost_duration;

            // Check if the vendor has sufficient balance to boost the product
            if ($this->context->vendor->id && $this->context->vendor->getBalance() >= $total_price) {

                // Deduct the total boost price from the vendor's balance
                $this->context->vendor->updateBalance(-$total_price, 1, 'Product Boost: #' . $id_product);

                // Add a new boost record to the database
                $boost = new BoostProduct();
                $boost->id_product = $id_product;
                $boost->id_vendor = $this->context->vendor->id;
                $boost->boost_price = $boost_price;
                $boost->boost_duration = $boost_duration;
                $boost->total_price = $total_price;
                $boost->date_add = date('Y-m-d H:i:s');
                $boost->save();

                // Display success message
                $this->context->smarty->assign([
                    'boost_success' => true,
                    'boost_duration' => $boost_duration,
                    'boost_price' => $boost_price,
                    'total_price' => $total_price,
                ]);
            } else {
                $this->errors[] = $this->module->l('Insufficient balance to boost this product');
            }
        }
    }
}
