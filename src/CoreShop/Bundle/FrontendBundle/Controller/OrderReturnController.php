<?php

declare(strict_types=1);

namespace CoreShop\Bundle\FrontendBundle\Controller;

use CoreShop\Bundle\FrontendBundle\Form\Type\OrderReturnType;
use CoreShop\Bundle\WorkflowBundle\Manager\StateMachineManagerInterface;
use CoreShop\Bundle\WorkflowBundle\StateManager\WorkflowStateInfoManagerInterface;
use CoreShop\Component\Core\Context\ShopperContextInterface;
use CoreShop\Component\Core\Model\CustomerInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\OrderItemInterface;
use CoreShop\Component\Order\Processable\ProcessableInterface;
use CoreShop\Component\Order\Repository\OrderRepositoryInterface;
use CoreShop\Component\Order\Transformer\OrderDocumentTransformerInterface;
use CoreShop\Component\OrderReturnManagement\Model\OrderReturnInterface;
use CoreShop\Component\OrderReturnManagement\OrderReturnTransitions;
use CoreShop\Component\OrderReturnManagement\Repository\OrderReturnRepositoryInterface;
use CoreShop\Component\OrderReturnManagement\ReturnStates;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\SubscribedService;

class OrderReturnController extends FrontendController
{
    public function returnsAction(): Response
    {
        $this->denyAccessUnlessGranted('CORESHOP_CUSTOMER_PROFILE_RETURNS');

        $customer = $this->getCustomer();

        if (!$customer instanceof CustomerInterface) {
            return $this->redirectToRoute('coreshop_index');
        }

        $orders = $this->container->get('coreshop.repository.order')->findOrdersByCustomer($customer);
        $returns = [];

        foreach ($orders as $order) {
            if (!$order instanceof OrderInterface) {
                continue;
            }

            $orderReturns = $this->container->get('coreshop.repository.order_return')->getDocuments($order);

            foreach ($orderReturns as $return) {
                if (!$return instanceof OrderReturnInterface) {
                    continue;
                }

                $returns[] = [
                    'return' => $return,
                    'order' => $order,
                    'stateInfo' => $this->container->get(WorkflowStateInfoManagerInterface::class)->getStateInfo(
                        'coreshop_return',
                        $return->getState() ?? ReturnStates::STATE_NEW,
                        true,
                    ),
                ];
            }
        }

        return $this->render($this->getTemplateConfigurator()->findTemplate('Customer/returns.html'), [
            'customer' => $customer,
            'returns' => $returns,
        ]);
    }

    public function returnDetailAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CORESHOP_CUSTOMER_PROFILE_RETURN_DETAIL');

        $customer = $this->getCustomer();

        if (!$customer instanceof CustomerInterface) {
            return $this->redirectToRoute('coreshop_index');
        }

        $returnId = $this->getParameterFromRequest($request, 'return');
        $return = $this->container->get('coreshop.repository.order_return')->find($returnId);

        if (!$return instanceof OrderReturnInterface) {
            return $this->redirectToRoute('coreshop_customer_returns');
        }

        $order = $return->getOrder();

        if (!$order instanceof OrderInterface) {
            return $this->redirectToRoute('coreshop_customer_returns');
        }

        if (!$order->getCustomer() instanceof CustomerInterface || $order->getCustomer()->getId() !== $customer->getId()) {
            return $this->redirectToRoute('coreshop_customer_returns');
        }

        $stateInfo = $this->container->get(WorkflowStateInfoManagerInterface::class)->getStateInfo(
            'coreshop_return',
            $return->getState() ?? ReturnStates::STATE_NEW,
            true,
        );

        return $this->render($this->getTemplateConfigurator()->findTemplate('Customer/return_detail.html'), [
            'customer' => $customer,
            'orderReturn' => $return,
            'order' => $order,
            'stateInfo' => $stateInfo,
        ]);
    }

    public function createReturnAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CORESHOP_CUSTOMER_PROFILE_RETURN_CREATE');

        $customer = $this->getCustomer();

        if (!$customer instanceof CustomerInterface) {
            return $this->redirectToRoute('coreshop_index');
        }

        $orderId = $this->getParameterFromRequest($request, 'order');
        $order = $this->container->get('coreshop.repository.order')->find($orderId);

        if (!$order instanceof OrderInterface) {
            return $this->redirectToRoute('coreshop_customer_orders');
        }

        if (!$order->getCustomer() instanceof CustomerInterface || $order->getCustomer()->getId() !== $customer->getId()) {
            return $this->redirectToRoute('coreshop_customer_orders');
        }

        $processable = $this->container->get('coreshop.order.return.processable');

        if (!$processable->isProcessable($order)) {
            $this->addFlash('error', $this->container->get('translator')->trans('coreshop.ui.return_not_possible'));

            return $this->redirect($this->generateUrl('coreshop_customer_order_detail', ['order' => $order->getId()]));
        }

        $processableItems = $processable->getProcessableItems($order);
        $returnableItems = [];

        foreach ($processableItems as $item) {
            $orderItem = $item['item'];
            if ($orderItem instanceof OrderItemInterface) {
                $returnableItems[] = [
                    'orderItem' => $orderItem,
                    'maxQuantity' => $item['quantity'],
                ];
            }
        }

        $form = $this->container->get('form.factory')->createNamed('coreshop_order_return', OrderReturnType::class);

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            $handledForm = $form->handleRequest($request);

            if ($handledForm->isSubmitted() && $handledForm->isValid()) {
                $formData = $handledForm->getData();

                $itemsToReturn = [];
                foreach ($formData['items'] ?? [] as $item) {
                    $quantity = (int) ($item['quantity'] ?? 0);
                    if ($quantity > 0) {
                        $itemsToReturn[] = [
                            'orderItemId' => $item['orderItemId'],
                            'quantity' => $quantity,
                        ];
                    }
                }

                if (empty($itemsToReturn)) {
                    $this->addFlash('error', $this->container->get('translator')->trans('coreshop.ui.select_items_to_return'));

                    return $this->render($this->getTemplateConfigurator()->findTemplate('Customer/return_create.html'), [
                        'customer' => $customer,
                        'order' => $order,
                        'returnableItems' => $returnableItems,
                        'form' => $form->createView(),
                    ]);
                }

                try {
                    $stateMachineManager = $this->container->get('coreshop.state_machine_manager');
                    $workflow = $stateMachineManager->get($order, 'coreshop_order_return');

                    if ($workflow->can($order, OrderReturnTransitions::TRANSITION_REQUEST_RETURN)) {
                        $workflow->apply($order, OrderReturnTransitions::TRANSITION_REQUEST_RETURN);
                    }

                    $returnObject = $this->container->get('coreshop.factory.order_return')->createNew();
                    $returnObject->setState(ReturnStates::STATE_NEW);

                    $returnObject = $this->container->get(OrderDocumentTransformerInterface::class)->transform(
                        $order,
                        $returnObject,
                        $itemsToReturn,
                    );

                    $this->addFlash('success', $this->container->get('translator')->trans('coreshop.ui.return_created_successfully'));

                    return $this->redirect($this->generateUrl('coreshop_customer_return_detail', ['return' => $returnObject->getId()]));
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->container->get('translator')->trans('coreshop.ui.return_creation_failed'));
                }
            }
        }

        return $this->render($this->getTemplateConfigurator()->findTemplate('Customer/return_create.html'), [
            'customer' => $customer,
            'order' => $order,
            'returnableItems' => $returnableItems,
            'form' => $form->createView(),
        ]);
    }

    protected function getCustomer(): ?CustomerInterface
    {
        $shopperContext = $this->container->get(ShopperContextInterface::class);

        if (!$shopperContext->hasCustomer()) {
            return null;
        }

        $customer = $shopperContext->getCustomer();

        if (!$customer instanceof CustomerInterface) {
            return null;
        }

        return $customer;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            ShopperContextInterface::class,
            WorkflowStateInfoManagerInterface::class,
            new SubscribedService('coreshop.repository.order', OrderRepositoryInterface::class),
            new SubscribedService('coreshop.repository.order_return', OrderReturnRepositoryInterface::class, attributes: new Autowire(service: 'coreshop.repository.order_return')),
            new SubscribedService('coreshop.order.return.processable', ProcessableInterface::class, attributes: new Autowire(service: 'coreshop.order.return.processable')),
            new SubscribedService('coreshop.factory.order_return', FactoryInterface::class, attributes: new Autowire(service: 'coreshop.factory.order_return')),
            new SubscribedService('coreshop.state_machine_manager', StateMachineManagerInterface::class),
            new SubscribedService(OrderDocumentTransformerInterface::class, OrderDocumentTransformerInterface::class, attributes: new Autowire(service: 'coreshop.order.transformer.order_to_return.state_applier')),
        ]);
    }
}
