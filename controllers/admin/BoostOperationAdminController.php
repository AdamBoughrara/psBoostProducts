<?php

namespace PrestaShop\Module\BoostProducts\Controller\Admin;

use PrestaShop\Module\BoostProducts\Entity\BoostOperation;
use PrestaShop\PrestaShop\Core\Controller\AdminController;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BoostOperationAdminController extends AdminController
{
    use PrestaShopException;

    public function indexAction()
    {
        $boostOperations = $this->getDoctrine()->getRepository(BoostOperation::class)->findAll();
        return $this->render('@Modules/boostproducts/views/templates/admin/boostoperation_index.html.twig', [
            'boostOperations' => $boostOperations,
        ]);
    }

    public function newAction(Request $request)
    {
        $boostOperation = new BoostOperation();
        $form = $this->createFormBuilder($boostOperation)
            ->add('productId')
            ->add('vendorId')
            ->add('totalBoostPrice')
            ->add('boostStartDate')
            ->add('boostEndDate')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $boostOperation = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($boostOperation);
            $entityManager->flush();
            $this->addFlash('success', $this->trans('Operation created successfully', [], 'Modules.BoostProducts.Admin'));
            return $this->redirectToRoute('boost_operation_index');
        }

        return $this->render('@Modules/boostproducts/views/templates/admin/boostoperation_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function editAction(Request $request, $id)
    {
        $boostOperation = $this->getDoctrine()->getRepository(BoostOperation::class)->find($id);
        $form = $this->createFormBuilder($boostOperation)
            ->add('productId')
            ->add('vendorId')
            ->add('totalBoostPrice')
            ->add('boostStartDate')
            ->add('boostEndDate')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($boostOperation);
            $entityManager->flush();
            $this->addFlash('success', $this->trans('Operation updated successfully', [], 'Modules.BoostProducts.Admin'));
            return $this->redirectToRoute('boost_operation_index');
        }

        return $this->render('@Modules/boostproducts/views/templates/admin/boostoperation_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function deleteAction(Request $request, $id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $boostOperation = $entityManager->getRepository(BoostOperation::class)->find($id);

        if (!$boostOperation) {
            throw $this->createNotFoundException($this->trans('Operation not found', [], 'Modules.BoostProducts.Admin'));
        }

        $entityManager->remove($boostOperation);
        $entityManager->flush();
        $this->addFlash('success', $this->trans('Operation deleted successfully', [], 'Modules.BoostProducts.Admin'));

        return $this->redirectToRoute('boost_operation_index');
    }

    public function stopAction(Request $request, $id)
    {
        // Load the boost operation with the given ID
        $boostOperation = $this->getDoctrine()->getRepository(BoostOperation::class)->find($id);
        if (!Validate::isLoadedObject($boostOperation)) {
            throw new PrestaShopException('Invalid boost operation ID');
        }

        // Stop the boost operation
        $boostOperation->stop();

        // Redirect to the boost operation list with a success message
        return $this->redirectToRoute(
            $this->generateUrl('admin_boost_operation_index'),
            sprintf(
                $this->trans('Boost operation #%d has been stopped', [], 'Admin.Notifications.Success'),
                $id
            )
        );
    }

    public function resumeAction(Request $request, $id)
    {
        // code to resume a stopped boost operation
    }
}
