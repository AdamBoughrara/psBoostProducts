<?php

namespace Module\BoostProducts\Controller\Admin;

use Module\BoostProducts\BoostOperation;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class BoostOperationAdminController extends FrameworkBundleAdminController
{
    public function viewAction($id_boost)
    {
        // Load the boost operation object from the database
        $boost_operation = new BoostOperation($id_boost);

        // Check if the boost operation object exists
        if (!Validate::isLoadedObject($boost_operation)) {
            $this->addFlash('error', $this->trans('Boost operation not found', [], 'Modules.BoostProducts.Admin'));
            return $this->redirectToRoute('admin_boost_products_boost_operation_index');
        }

        // Check if the current employee has access to the boost operation
        if (!$this->isGranted('BOOST_PRODUCTS_BOOST_OPERATION_VIEW', $boost_operation)) {
            $this->addFlash('error', $this->trans('You do not have permission to view this boost operation', [], 'Modules.BoostProducts.Admin'));
            return $this->redirectToRoute('admin_boost_products_boost_operation_index');
        }

        // Load the product and vendor objects from the database
        $product = new Product($boost_operation->id_product);
        $vendor = new Vendor($boost_operation->id_vendor);

        // Pass necessary variables to the template
        return $this->render('@Modules/boostproducts/views/templates/admin/boost_operation/view.html.twig', [
            'boost_operation' => $boost_operation,
            'product' => $product,
            'vendor' => $vendor,
        ]);
    }

    public function deleteAction($id_boost)
    {
        // Load the boost operation object from the database
        $boost_operation = new BoostOperation($id_boost);

        // Check if the boost operation object exists
        if (!Validate::isLoadedObject($boost_operation)) {
            $this->addFlash('error', $this->trans('Boost operation not found', [], 'Modules.BoostProducts.Admin'));
            return $this->redirectToRoute('admin_boost_products_boost_operation_index');
        }

        // Check if the current employee has access to delete the boost operation
        if (!$this->isGranted('BOOST_PRODUCTS_BOOST_OPERATION_DELETE', $boost_operation)) {
            $this->addFlash('error', $this->trans('You do not have permission to delete this boost operation', [], 'Modules.BoostProducts.Admin'));
            return $this->redirectToRoute('admin_boost_products_boost_operation_index');
        }

        // Delete the boost operation object from the database
        $boost_operation->delete();

        // Display success message
        $this->addFlash('success', $this->trans('Boost operation successfully deleted', [], 'Modules.BoostProducts.Admin'));

        return $this->redirectToRoute('admin_boost_products_boost_operation_index');
    }
}
