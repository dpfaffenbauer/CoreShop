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

namespace CoreShop\Bundle\SubscriptionBundle\Pimcore\Repository;

use CoreShop\Bundle\ResourceBundle\Pimcore\PimcoreRepository;
use CoreShop\Component\Subscription\Model\SubscriptionInterface;
use CoreShop\Component\Subscription\Model\SubscriptionStates;
use CoreShop\Component\Subscription\Repository\SubscriptionRepositoryInterface;

class SubscriptionRepository extends PimcoreRepository implements SubscriptionRepositoryInterface
{
    public function findDueForRenewal(\DateTimeInterface $now = null): array
    {
        $now = $now ?? new \DateTimeImmutable();

        $list = $this->getList();
        $list->setCondition(
            'state = ? AND nextBillingDate <= ?',
            [SubscriptionStates::STATE_ACTIVE, $now->getTimestamp()],
        );

        return $list->getObjects();
    }

    public function findExpiredTrials(\DateTimeInterface $now = null): array
    {
        $now = $now ?? new \DateTimeImmutable();

        $list = $this->getList();
        $list->setCondition(
            'state = ? AND trialEndDate IS NOT NULL AND trialEndDate <= ?',
            [SubscriptionStates::STATE_TRIAL, $now->getTimestamp()],
        );

        return $list->getObjects();
    }

    public function findPastDueForExpiration(int $maxRetryDays = 14): array
    {
        $cutoff = new \DateTimeImmutable(sprintf('-%d days', $maxRetryDays));

        $list = $this->getList();
        $list->setCondition(
            'state = ? AND lastPaymentDate IS NOT NULL AND lastPaymentDate <= ?',
            [SubscriptionStates::STATE_PAST_DUE, $cutoff->getTimestamp()],
        );

        return $list->getObjects();
    }

    public function findByState(string $state): array
    {
        $list = $this->getList();
        $list->setCondition('state = ?', [$state]);

        return $list->getObjects();
    }
}
