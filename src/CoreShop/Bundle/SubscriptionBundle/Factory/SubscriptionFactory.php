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

namespace CoreShop\Bundle\SubscriptionBundle\Factory;

use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Pimcore\DataObject\ObjectServiceInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Subscription\Factory\SubscriptionFactoryInterface;
use CoreShop\Component\Subscription\Model\SubscriptionInterface;
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;
use CoreShop\Component\Subscription\Model\SubscriptionStates;
use CoreShop\Component\Subscription\Resolver\NextBillingDateResolverInterface;

final class SubscriptionFactory implements SubscriptionFactoryInterface
{
    public function __construct(
        private FactoryInterface $decoratedFactory,
        private ObjectServiceInterface $objectService,
        private NextBillingDateResolverInterface $nextBillingDateResolver,
    ) {
    }

    public function createNew(): SubscriptionInterface
    {
        return $this->decoratedFactory->createNew();
    }

    public function createFromOrder(
        OrderInterface $order,
        SubscriptionPlanInterface $plan,
    ): SubscriptionInterface {
        /** @var SubscriptionInterface $subscription */
        $subscription = $this->createNew();

        $subscription->setKey(sprintf('subscription-%s-%s', $order->getId(), $plan->getId()));
        $subscription->setParent($this->objectService->createFolderByPath('/subscriptions'));
        $subscription->setPublished(true);

        $subscription->setCustomer($order->getCustomer());
        $subscription->setSubscriptionPlan($plan);
        $subscription->setStore($order->getStore());
        $subscription->setCurrency($order->getCurrency());
        $subscription->setInitialOrder($order);
        $subscription->setOrders([$order]);

        $now = new \DateTimeImmutable();
        $subscription->setStartDate($now);
        $subscription->setCompletedCycles(1);
        $subscription->setLastPaymentDate($now);

        $trialDays = $plan->getTrialPeriodDays();

        if ($trialDays !== null && $trialDays > 0) {
            $subscription->setState(SubscriptionStates::STATE_TRIAL);
            $subscription->setTrialEndDate($now->modify(sprintf('+%d days', $trialDays)));
            $subscription->setNextBillingDate($now->modify(sprintf('+%d days', $trialDays)));
            $subscription->setCompletedCycles(0);
        } else {
            $subscription->setState(SubscriptionStates::STATE_ACTIVE);
            $nextBillingDate = $this->nextBillingDateResolver->resolveNextBillingDate($subscription);
            $subscription->setNextBillingDate($nextBillingDate);
        }

        return $subscription;
    }
}
