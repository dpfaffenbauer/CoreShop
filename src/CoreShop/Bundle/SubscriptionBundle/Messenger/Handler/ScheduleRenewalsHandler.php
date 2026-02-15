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

namespace CoreShop\Bundle\SubscriptionBundle\Messenger\Handler;

use CoreShop\Bundle\SubscriptionBundle\Messenger\ActivateTrialMessage;
use CoreShop\Bundle\SubscriptionBundle\Messenger\ExpireSubscriptionMessage;
use CoreShop\Bundle\SubscriptionBundle\Messenger\ProcessRenewalMessage;
use CoreShop\Bundle\SubscriptionBundle\Messenger\ScheduleRenewalsMessage;
use CoreShop\Component\Subscription\Repository\SubscriptionRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ScheduleRenewalsHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(ScheduleRenewalsMessage $message): void
    {
        $now = new \DateTimeImmutable();

        // 1. Schedule renewals for active subscriptions with due billing date
        $dueSubscriptions = $this->subscriptionRepository->findDueForRenewal($now);
        foreach ($dueSubscriptions as $subscription) {
            $this->messageBus->dispatch(new ProcessRenewalMessage($subscription->getId()));
        }

        // 2. Activate trials that have expired
        $expiredTrials = $this->subscriptionRepository->findExpiredTrials($now);
        foreach ($expiredTrials as $subscription) {
            $this->messageBus->dispatch(new ActivateTrialMessage($subscription->getId()));
        }

        // 3. Expire past-due subscriptions that exceeded max retry period
        $pastDueSubscriptions = $this->subscriptionRepository->findPastDueForExpiration();
        foreach ($pastDueSubscriptions as $subscription) {
            $this->messageBus->dispatch(new ExpireSubscriptionMessage($subscription->getId()));
        }
    }
}
