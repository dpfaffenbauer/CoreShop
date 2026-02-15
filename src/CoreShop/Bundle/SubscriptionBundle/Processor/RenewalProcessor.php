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

namespace CoreShop\Bundle\SubscriptionBundle\Processor;

use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Subscription\Checker\EligibilityCheckerInterface;
use CoreShop\Component\Subscription\Model\SubscriptionInterface;
use CoreShop\Component\Subscription\Model\SubscriptionTransitions;
use CoreShop\Component\Subscription\Processor\RenewalProcessorInterface;
use CoreShop\Component\Subscription\Processor\SubscriptionOrderCreatorInterface;
use CoreShop\Component\Subscription\Resolver\NextBillingDateResolverInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class RenewalProcessor implements RenewalProcessorInterface
{
    public function __construct(
        private EligibilityCheckerInterface $eligibilityChecker,
        private SubscriptionOrderCreatorInterface $orderCreator,
        private NextBillingDateResolverInterface $nextBillingDateResolver,
        private Registry $workflowRegistry,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function processRenewal(SubscriptionInterface $subscription): OrderInterface
    {
        if (!$this->eligibilityChecker->isEligibleForRenewal($subscription)) {
            throw new \RuntimeException(sprintf(
                'Subscription #%d is not eligible for renewal.',
                $subscription->getId(),
            ));
        }

        $order = $this->orderCreator->createOrder($subscription);
        $order->save();

        // Update subscription
        $subscription->setCompletedCycles($subscription->getCompletedCycles() + 1);
        $subscription->setLastPaymentDate(new \DateTimeImmutable());
        $subscription->setNextBillingDate($this->nextBillingDateResolver->resolveNextBillingDate($subscription));
        $subscription->addOrder($order);

        // Check if max cycles reached → complete
        $plan = $subscription->getSubscriptionPlan();
        $maxCycles = $plan?->getMaxCycles();

        if ($maxCycles !== null && $subscription->getCompletedCycles() >= $maxCycles) {
            $workflow = $this->workflowRegistry->get($subscription, SubscriptionTransitions::IDENTIFIER);

            if ($workflow->can($subscription, SubscriptionTransitions::TRANSITION_COMPLETE)) {
                $workflow->apply($subscription, SubscriptionTransitions::TRANSITION_COMPLETE);
            }
        }

        $subscription->save();

        return $order;
    }
}
