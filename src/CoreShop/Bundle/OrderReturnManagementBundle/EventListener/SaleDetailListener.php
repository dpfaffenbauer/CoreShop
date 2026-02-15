<?php

declare(strict_types=1);

namespace CoreShop\Bundle\OrderReturnManagementBundle\EventListener;

use CoreShop\Bundle\WorkflowBundle\StateManager\WorkflowStateInfoManagerInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Processable\ProcessableInterface;
use CoreShop\Component\OrderReturnManagement\Model\OrderReturnInterface;
use CoreShop\Component\OrderReturnManagement\OrderReturnStates;
use CoreShop\Component\OrderReturnManagement\Repository\OrderReturnRepositoryInterface;
use CoreShop\Component\OrderReturnManagement\ReturnStates;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class SaleDetailListener
{
    public function __construct(
        private OrderReturnRepositoryInterface $orderReturnRepository,
        private ProcessableInterface $processable,
        private WorkflowStateInfoManagerInterface $workflowStateInfoManager,
        private SerializerInterface $serializer,
    ) {
    }

    public function onSaleDetailPrepare(GenericEvent $event): void
    {
        $order = $event->getSubject();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $event->setArgument('returns', $this->getReturns($order));
        $event->setArgument('returnCreationAllowed', $this->processable->isProcessable($order));
        $event->setArgument('orderReturnState', $this->workflowStateInfoManager->getStateInfo(
            'coreshop_order_return',
            $order->getReturnState() ?? OrderReturnStates::STATE_NEW,
            false,
        ));
    }

    private function getReturns(OrderInterface $order): array
    {
        $returns = $this->orderReturnRepository->getDocuments($order);
        $returnArray = [];

        foreach ($returns as $return) {
            if (!$return instanceof OrderReturnInterface) {
                continue;
            }

            $availableTransitions = $this->workflowStateInfoManager->parseTransitions(
                $return,
                ReturnStates::IDENTIFIER,
                [
                    'create',
                    'confirm',
                    'receive',
                    'cancel',
                ],
                false,
            );

            $data = $this->serializer->toArray($return);

            $data['stateInfo'] = $this->workflowStateInfoManager->getStateInfo(
                ReturnStates::IDENTIFIER,
                $return->getState() ?? ReturnStates::STATE_NEW,
                false,
            );
            $data['transitions'] = $availableTransitions;

            $returnArray[] = $data;
        }

        return $returnArray;
    }
}
