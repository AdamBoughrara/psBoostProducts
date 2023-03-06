<?php

namespace PrestaShop\Module\BoostProducts\Controller\Admin;

use PrestaShop\Module\BoostProducts\Entity\BoostProduct;
use PrestaShop\Module\BoostProducts\Form\BoostProductConfigurationType;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class BoostProductAdminController extends FrameworkBundleAdminController
{
    public function indexAction()
    {
        $boostProducts = BoostProduct::getList();

        return $this->render('@Modules/boostproducts/views/templates/admin/index.html.twig', [
            'boost_products' => $boostProducts,
        ]);
    }

    public function configureAction()
    {
        $form = $this->createForm(BoostProductConfigurationType::class);

        return $this->render('@Modules/boostproducts/views/templates/admin/configure.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function saveConfigurationAction()
    {
        $form = $this->createForm(BoostProductConfigurationType::class);
        $form->handleRequest($this->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Save configuration values
            Configuration::updateValue('BOOST_PRODUCTS_ENABLED', $data['enabled']);
            Configuration::updateValue('BOOST_PRODUCTS_PRICE', $data['price']);

            $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
        }

        return $this->redirectToRoute('boost_products_admin_configure');
    }
}
