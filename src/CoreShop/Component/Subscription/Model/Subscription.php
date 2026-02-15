<?php

declare(strict_types=1);

/*
 * CoreShop
 *
 * This source file is available under the terms of the
 * CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.com)
 * @license    CoreShop Commercial License (CCL)
 *
 */

namespace CoreShop\Component\Subscription\Model;

use CoreShop\Component\Currency\Model\CurrencyAwareTrait;
use CoreShop\Component\Customer\Model\CustomerInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Resource\Exception\ImplementedByPimcoreException;
use CoreShop\Component\Resource\Pimcore\Model\AbstractPimcoreModel;
use CoreShop\Component\Store\Model\StoreAwareTrait;

abstract class Subscription extends AbstractPimcoreModel implements SubscriptionInterface
{
    use StoreAwareTrait;
    use CurrencyAwareTrait;

    public function getCustomer(): ?CustomerInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setCustomer(?CustomerInterface $customer)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getSubscriptionPlan(): ?SubscriptionPlanInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setSubscriptionPlan(?SubscriptionPlanInterface $subscriptionPlan)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getState(): ?string
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setState(?string $state)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setStartDate(?\DateTimeInterface $startDate)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getNextBillingDate(): ?\DateTimeInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setNextBillingDate(?\DateTimeInterface $nextBillingDate)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setEndDate(?\DateTimeInterface $endDate)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getTrialEndDate(): ?\DateTimeInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setTrialEndDate(?\DateTimeInterface $trialEndDate)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getLastPaymentDate(): ?\DateTimeInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setLastPaymentDate(?\DateTimeInterface $lastPaymentDate)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getCompletedCycles(): int
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setCompletedCycles(int $completedCycles)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getOrders(): array
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setOrders(array $orders)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getInitialOrder(): ?OrderInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setInitialOrder(?OrderInterface $order)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getCancellationDate(): ?\DateTimeInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setCancellationDate(?\DateTimeInterface $cancellationDate)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getCancellationReason(): ?string
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setCancellationReason(?string $cancellationReason)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function addOrder(OrderInterface $order): void
    {
        $orders = $this->getOrders();
        $orders[] = $order;
        $this->setOrders($orders);
    }

    public function isInTrial(): bool
    {
        return $this->getState() === SubscriptionStates::STATE_TRIAL;
    }

    public function isActive(): bool
    {
        return $this->getState() === SubscriptionStates::STATE_ACTIVE;
    }

    public function isCancelled(): bool
    {
        return $this->getState() === SubscriptionStates::STATE_CANCELLED;
    }
}
