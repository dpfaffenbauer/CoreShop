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

use CoreShop\Component\Currency\Model\CurrencyAwareInterface;
use CoreShop\Component\Customer\Model\CustomerInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Resource\Model\TimestampableInterface;
use CoreShop\Component\Resource\Pimcore\Model\PimcoreModelInterface;
use CoreShop\Component\Store\Model\StoreAwareInterface;

interface SubscriptionInterface extends
    PimcoreModelInterface,
    StoreAwareInterface,
    CurrencyAwareInterface,
    TimestampableInterface
{
    public function getCustomer(): ?CustomerInterface;

    public function setCustomer(?CustomerInterface $customer);

    public function getSubscriptionPlan(): ?SubscriptionPlanInterface;

    public function setSubscriptionPlan(?SubscriptionPlanInterface $subscriptionPlan);

    public function getState(): ?string;

    public function setState(?string $state);

    public function getStartDate(): ?\DateTimeInterface;

    public function setStartDate(?\DateTimeInterface $startDate);

    public function getNextBillingDate(): ?\DateTimeInterface;

    public function setNextBillingDate(?\DateTimeInterface $nextBillingDate);

    public function getEndDate(): ?\DateTimeInterface;

    public function setEndDate(?\DateTimeInterface $endDate);

    public function getTrialEndDate(): ?\DateTimeInterface;

    public function setTrialEndDate(?\DateTimeInterface $trialEndDate);

    public function getLastPaymentDate(): ?\DateTimeInterface;

    public function setLastPaymentDate(?\DateTimeInterface $lastPaymentDate);

    public function getCompletedCycles(): int;

    public function setCompletedCycles(int $completedCycles);

    /**
     * @return OrderInterface[]
     */
    public function getOrders(): array;

    public function setOrders(array $orders);

    public function getInitialOrder(): ?OrderInterface;

    public function setInitialOrder(?OrderInterface $order);

    public function getCancellationDate(): ?\DateTimeInterface;

    public function setCancellationDate(?\DateTimeInterface $cancellationDate);

    public function getCancellationReason(): ?string;

    public function setCancellationReason(?string $cancellationReason);
}
