<?php

declare(strict_types=1);

namespace CoreShop\Bundle\OrderReturnManagementBundle\Controller;

use CoreShop\Bundle\ResourceBundle\Controller\PimcoreController;
use CoreShop\Bundle\ResourceBundle\Form\Helper\ErrorSerializer;
use CoreShop\Bundle\OrderReturnManagementBundle\Form\Type\OrderReturnCreationType;
use CoreShop\Bundle\WorkflowBundle\Manager\StateMachineManager;
use CoreShop\Bundle\WorkflowBundle\Manager\StateMachineManagerInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\OrderItemInterface;
use CoreShop\Component\Order\Processable\ProcessableInterface;
use CoreShop\Component\Order\Renderer\OrderDocumentRendererInterface;
use CoreShop\Component\Order\Repository\OrderRepositoryInterface;
use CoreShop\Component\Order\Transformer\OrderDocumentTransformerInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Resource\Repository\PimcoreRepositoryInterface;
use CoreShop\Component\OrderReturnManagement\Model\OrderReturnInterface;
use CoreShop\Component\OrderReturnManagement\OrderReturnTransitions;
use CoreShop\Component\OrderReturnManagement\Repository\OrderReturnRepositoryInterface;
use CoreShop\Component\OrderReturnManagement\ReturnStates;
use CoreShop\Component\OrderReturnManagement\Transformer\OrderToReturnTransformer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;

class OrderReturnController extends PimcoreController
{
    public function getReturnAbleItemsAction(Request $request): JsonResponse
    {
        $orderId = $this->getParameterFromRequest($request, 'id');
        $order = $this->getOrderRepository()->find($orderId);

        if (!$order instanceof OrderInterface) {
            return $this->viewHandler->handle(['success' => false, 'message' => 'Order with ID "' . $orderId . '" not found']);
        }

        if (!$this->getProcessableHelper()->isProcessable($order)) {
            return $this->viewHandler->handle(['success' => false, 'message' => 'The current order state does not allow to create returns']);
        }

        try {
            $items = $this->getProcessableHelper()->getProcessableItems($order);
        } catch (\Exception $e) {
            return $this->viewHandler->handle(['success' => false, 'message' => $e->getMessage()]);
        }

        $itemsToReturn = [];

        foreach ($items as $item) {
            $orderItem = $item['item'];
            if ($orderItem instanceof OrderItemInterface) {
                $itemToReturn = [
                    'orderItemId' => $orderItem->getId(),
                    'price' => $orderItem->getItemPrice(),
                    'maxToReturn' => $item['quantity'],
                    'quantity' => $orderItem->getQuantity(),
                    'quantityReturned' => $orderItem->getQuantity() - $item['quantity'],
                    'toReturn' => $item['quantity'],
                    'tax' => $orderItem->getTotalTax(),
                    'total' => $orderItem->getTotal(),
                    'name' => $orderItem->getName(),
                ];

                $event = new GenericEvent($orderItem, $itemToReturn);

                $this->container->get('event_dispatcher')->dispatch($event, 'coreshop.order.return.prepare_return_able');

                $itemsToReturn[] = $event->getArguments();
            }
        }

        return $this->viewHandler->handle(['success' => true, 'items' => $itemsToReturn]);
    }

    public function createReturnAction(Request $request): JsonResponse
    {
        $orderId = $this->getParameterFromRequest($request, 'id');

        $form = $this->container->get('form.factory')->createNamed('', OrderReturnCreationType::class);

        $handledForm = $form->handleRequest($request);

        if ($request->getMethod() === 'POST') {
            if (!$handledForm->isValid()) {
                return $this->viewHandler->handle(
                    [
                        'success' => false,
                        'message' => $this->container->get(ErrorSerializer::class)->serializeErrorFromHandledForm($form),
                    ],
                );
            }

            $resource = $handledForm->getData();

            $order = $this->getOrderRepository()->find($resource['id']);

            if (!$order instanceof OrderInterface) {
                return $this->viewHandler->handle(['success' => false, 'message' => "Order with ID '$orderId' not found"]);
            }

            try {
                $workflow = $this->getStateMachineManager()->get($order, 'coreshop_order_return');
                if ($workflow->can($order, OrderReturnTransitions::TRANSITION_REQUEST_RETURN)) {
                    $workflow->apply($order, OrderReturnTransitions::TRANSITION_REQUEST_RETURN);
                }

                $return = $this->getReturnFactory()->createNew();
                $return->setState(ReturnStates::STATE_NEW);

                foreach ($resource as $key => $value) {
                    if (in_array($key, ['items', 'id', 'state'])) {
                        continue;
                    }

                    $return->setValue($key, $value);
                }

                $items = $resource['items'];
                $return = $this->getOrderToReturnTransformer()->transform($order, $return, $items);

                return $this->viewHandler->handle(['success' => true, 'returnId' => $return->getId()]);
            } catch (\Exception $ex) {
                return $this->viewHandler->handle(['success' => false, 'message' => $ex->getMessage()]);
            }
        }

        return $this->viewHandler->handle(['success' => false, 'message' => 'Method not supported, use POST']);
    }

    public function updateStateAction(Request $request): JsonResponse
    {
        $return = $this->getOrderReturnRepository()->find($this->getParameterFromRequest($request, 'id'));
        $transition = $this->getParameterFromRequest($request, 'transition');

        if (!$return instanceof OrderReturnInterface) {
            return $this->viewHandler->handle(['success' => false, 'message' => 'invalid return']);
        }

        $workflow = $this->getStateMachineManager()->get($return, 'coreshop_return');
        if (!$workflow->can($return, $transition)) {
            return $this->viewHandler->handle(['success' => false, 'message' => 'this transition is not allowed.']);
        }

        $workflow->apply($return, $transition);

        return $this->viewHandler->handle(['success' => true]);
    }

    public function renderAction(Request $request): Response
    {
        $returnId = (int) $this->getParameterFromRequest($request, 'id');
        $return = $this->getOrderReturnRepository()->find($returnId);

        if ($return instanceof OrderReturnInterface) {
            try {
                $responseData = $this->getOrderDocumentRenderer()->renderDocumentPdf($return);
                $header = [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="return-' . $return->getId() . '.pdf"',
                ];
            } catch (\Exception $e) {
                return new Response('An error occurred while rendering the return.', 500, ['Content-Type' => 'text/html']);
            }

            return new Response($responseData, 200, $header);
        }

        throw new NotFoundHttpException(sprintf('Return with Id %s not found', $returnId));
    }

    protected function getOrderDocumentRenderer(): OrderDocumentRendererInterface
    {
        return $this->container->get(OrderDocumentRendererInterface::class);
    }

    protected function getOrderReturnRepository(): OrderReturnRepositoryInterface
    {
        return $this->container->get('coreshop.repository.order_return');
    }

    protected function getProcessableHelper(): ProcessableInterface
    {
        return $this->container->get('coreshop.order.return.processable');
    }

    protected function getOrderRepository(): PimcoreRepositoryInterface
    {
        return $this->container->get('coreshop.repository.order');
    }

    protected function getReturnFactory(): FactoryInterface
    {
        return $this->container->get('coreshop.factory.order_return');
    }

    protected function getOrderToReturnTransformer(): OrderDocumentTransformerInterface
    {
        return $this->container->get(OrderToReturnTransformer::class);
    }

    protected function getStateMachineManager(): StateMachineManager
    {
        return $this->container->get('coreshop.state_machine_manager');
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
                new SubscribedService('coreshop.state_machine_manager', StateMachineManagerInterface::class),
                new SubscribedService('coreshop.repository.order', OrderRepositoryInterface::class),
                new SubscribedService(OrderDocumentRendererInterface::class, OrderDocumentRendererInterface::class),
                new SubscribedService('coreshop.repository.order_return', OrderReturnRepositoryInterface::class, attributes: new Autowire(service: 'coreshop.repository.order_return')),
                new SubscribedService('coreshop.order.return.processable', ProcessableInterface::class, attributes: new Autowire(service: 'coreshop.order.return.processable')),
                new SubscribedService('coreshop.factory.order_return', FactoryInterface::class, attributes: new Autowire(service: 'coreshop.factory.order_return')),
                new SubscribedService('event_dispatcher', EventDispatcherInterface::class),
                new SubscribedService(OrderToReturnTransformer::class, OrderToReturnTransformer::class),
                new SubscribedService(ErrorSerializer::class, ErrorSerializer::class),
            ]);
    }
}
