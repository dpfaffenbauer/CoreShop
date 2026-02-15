<?php

declare(strict_types=1);

namespace CoreShop\Bundle\RmaBundle\StateResolver;

use CoreShop\Bundle\WorkflowBundle\Manager\StateMachineManager;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Processable\ProcessableInterface;
use CoreShop\Component\Order\StateResolver\StateResolverInterface;
use CoreShop\Component\Rma\Model\OrderReturnInterface;
use CoreShop\Component\Rma\OrderReturnStates;
use CoreShop\Component\Rma\OrderReturnTransitions;
use CoreShop\Component\Rma\Repository\OrderReturnRepositoryInterface;
use CoreShop\Component\Rma\ReturnStates;

final class OrderReturnStateResolver implements StateResolverInterface
{
    public function __construct(
        private StateMachineManager $stateMachineManager,
        private OrderReturnRepositoryInterface $orderReturnRepository,
        private ProcessableInterface $processable,
    ) {
    }

    public function resolve(OrderInterface $order): void
    {
        if ($order->getReturnState() === OrderReturnStates::STATE_RETURNED) {
            return;
        }

        $workflow = $this->stateMachineManager->get($order, OrderReturnTransitions::IDENTIFIER);

        if ($this->allReturnsInStateButOrderStateNotUpdated($order, ReturnStates::STATE_RECEIVED, OrderReturnStates::STATE_RETURNED)) {
            $workflow->apply($order, OrderReturnTransitions::TRANSITION_RETURN);
        }

        if ($this->isPartiallyReturnedButOrderStateNotUpdated($order)) {
            $workflow->apply($order, OrderReturnTransitions::TRANSITION_PARTIALLY_RETURN);
        }
    }

    private function countOrderReturnsInState(OrderInterface $order, string $returnState): int
    {
        $returns = $this->orderReturnRepository->getDocuments($order);

        $items = 0;
        /** @var OrderReturnInterface $return */
        foreach ($returns as $return) {
            if ($return->getState() === $returnState) {
                ++$items;
            }
        }

        return $items;
    }

    private function allReturnsInStateButOrderStateNotUpdated(
        OrderInterface $order,
        string $returnState,
        string $orderReturnState,
    ): bool {
        $returnInStateAmount = $this->countOrderReturnsInState($order, $returnState);
        $returnAmount = count($this->orderReturnRepository->getDocumentsNotInState($order, OrderReturnStates::STATE_CANCELLED));

        return $returnAmount === $returnInStateAmount &&
            $orderReturnState !== $order->getReturnState() &&
            $this->processable->isFullyProcessed($order);
    }

    private function isPartiallyReturnedButOrderStateNotUpdated(OrderInterface $order): bool
    {
        $returnInReceivedStateAmount = $this->countOrderReturnsInState($order, ReturnStates::STATE_RECEIVED);

        return
            $returnInReceivedStateAmount > 0 &&
            !$this->processable->isFullyProcessed($order) &&
            OrderReturnStates::STATE_PARTIALLY_RETURNED !== $order->getReturnState();
    }
}
