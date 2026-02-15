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

namespace CoreShop\Bundle\SubscriptionBundle\Checker;

use CoreShop\Component\Subscription\Checker\EligibilityCheckerInterface;
use CoreShop\Component\Subscription\Model\SubscriptionInterface;
use CoreShop\Component\Subscription\Model\SubscriptionStates;

final class EligibilityChecker implements EligibilityCheckerInterface
{
    public function isEligibleForRenewal(SubscriptionInterface $subscription): bool
    {
        if ($subscription->getState() !== SubscriptionStates::STATE_ACTIVE) {
            return false;
        }

        $plan = $subscription->getSubscriptionPlan();

        if ($plan === null) {
            return false;
        }

        if ($plan->getEnabled() !== true) {
            return false;
        }

        $maxCycles = $plan->getMaxCycles();

        if ($maxCycles !== null && $subscription->getCompletedCycles() >= $maxCycles) {
            return false;
        }

        if (!$plan->getAutoRenew()) {
            return false;
        }

        return true;
    }
}
