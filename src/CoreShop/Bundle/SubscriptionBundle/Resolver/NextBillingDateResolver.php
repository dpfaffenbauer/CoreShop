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

namespace CoreShop\Bundle\SubscriptionBundle\Resolver;

use CoreShop\Component\Subscription\Model\BillingCycle;
use CoreShop\Component\Subscription\Model\SubscriptionInterface;
use CoreShop\Component\Subscription\Resolver\NextBillingDateResolverInterface;

final class NextBillingDateResolver implements NextBillingDateResolverInterface
{
    public function resolveNextBillingDate(SubscriptionInterface $subscription): \DateTimeInterface
    {
        $plan = $subscription->getSubscriptionPlan();

        if ($plan === null) {
            throw new \RuntimeException('Subscription has no plan assigned.');
        }

        $billingCycle = BillingCycle::tryFrom($plan->getBillingCycle() ?? '');

        if ($billingCycle === null) {
            throw new \RuntimeException(sprintf(
                'Invalid billing cycle "%s" for subscription plan #%d.',
                $plan->getBillingCycle() ?? 'null',
                $plan->getId(),
            ));
        }

        $interval = $plan->getBillingInterval() ?? 1;
        $intervalSpec = $billingCycle->getIntervalSpec($interval);

        $baseDate = $subscription->getNextBillingDate() ?? $subscription->getStartDate() ?? new \DateTimeImmutable();

        if ($baseDate instanceof \DateTime) {
            $baseDate = \DateTimeImmutable::createFromMutable($baseDate);
        }

        return $baseDate->add(new \DateInterval($intervalSpec));
    }
}
