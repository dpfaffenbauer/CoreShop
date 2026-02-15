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

namespace CoreShop\Bundle\SubscriptionBundle\Command;

use CoreShop\Bundle\SubscriptionBundle\Messenger\ExpireSubscriptionMessage;
use CoreShop\Component\Subscription\Repository\SubscriptionRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'coreshop:subscription:expire',
    description: 'Expires past-due subscriptions that exceeded the retry period (fallback for Messenger)',
)]
final class ExpireSubscriptionsCommand extends Command
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'max-retry-days',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximum days a subscription can be past due before expiring',
            '14',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $maxRetryDays = (int) $input->getOption('max-retry-days');

        $subscriptions = $this->subscriptionRepository->findPastDueForExpiration($maxRetryDays);

        foreach ($subscriptions as $subscription) {
            $this->messageBus->dispatch(new ExpireSubscriptionMessage(
                $subscription->getId(),
                'exceeded_max_retry_period',
            ));
        }

        $io->success(sprintf('Dispatched expiration for %d subscriptions.', count($subscriptions)));

        return Command::SUCCESS;
    }
}
