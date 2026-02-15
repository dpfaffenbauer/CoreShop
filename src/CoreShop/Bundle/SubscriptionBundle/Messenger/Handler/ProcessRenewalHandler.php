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

use CoreShop\Bundle\SubscriptionBundle\Messenger\ProcessRenewalMessage;
use CoreShop\Component\Subscription\Model\SubscriptionStates;
use CoreShop\Component\Subscription\Processor\RenewalProcessorInterface;
use CoreShop\Component\Subscription\Repository\SubscriptionRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ProcessRenewalHandler
{
    private LoggerInterface $logger;

    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private RenewalProcessorInterface $renewalProcessor,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ProcessRenewalMessage $message): void
    {
        $subscription = $this->subscriptionRepository->find($message->subscriptionId);

        if ($subscription === null) {
            $this->logger->warning('Subscription not found for renewal.', [
                'subscriptionId' => $message->subscriptionId,
            ]);

            return;
        }

        // Idempotency: Skip if already renewed (nextBillingDate is in the future)
        $now = new \DateTimeImmutable();
        if ($subscription->getNextBillingDate() !== null && $subscription->getNextBillingDate() > $now) {
            $this->logger->info('Subscription already renewed, skipping.', [
                'subscriptionId' => $message->subscriptionId,
            ]);

            return;
        }

        // Guard: Only process active subscriptions
        if ($subscription->getState() !== SubscriptionStates::STATE_ACTIVE) {
            $this->logger->info('Subscription not in active state, skipping renewal.', [
                'subscriptionId' => $message->subscriptionId,
                'state' => $subscription->getState(),
            ]);

            return;
        }

        $this->renewalProcessor->processRenewal($subscription);
    }
}
