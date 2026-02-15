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
use CoreShop\Component\Subscription\Model\SubscriptionStates;
use CoreShop\Component\Subscription\Model\SubscriptionTransitions;
use CoreShop\Component\Subscription\Repository\SubscriptionRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Workflow\Registry;

final class ActivateTrialHandler
{
    private LoggerInterface $logger;

    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private Registry $workflowRegistry,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ActivateTrialMessage $message): void
    {
        $subscription = $this->subscriptionRepository->find($message->subscriptionId);

        if ($subscription === null) {
            return;
        }

        if ($subscription->getState() !== SubscriptionStates::STATE_TRIAL) {
            return;
        }

        $workflow = $this->workflowRegistry->get($subscription, SubscriptionTransitions::IDENTIFIER);

        if (!$workflow->can($subscription, SubscriptionTransitions::TRANSITION_ACTIVATE)) {
            $this->logger->warning('Cannot activate trial subscription.', [
                'subscriptionId' => $message->subscriptionId,
                'state' => $subscription->getState(),
            ]);

            return;
        }

        $workflow->apply($subscription, SubscriptionTransitions::TRANSITION_ACTIVATE);
        $subscription->save();

        $this->logger->info('Trial subscription activated.', [
            'subscriptionId' => $message->subscriptionId,
        ]);
    }
}
