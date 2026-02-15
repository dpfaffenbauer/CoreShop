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

namespace CoreShop\Bundle\CoreBundle\Fixtures\Data\Demo;

use CoreShop\Component\Pimcore\DataObject\ObjectServiceInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Store\Repository\StoreRepositoryInterface;
use CoreShop\Component\Subscription\Model\SubscriptionInterface;
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;
use CoreShop\Component\Subscription\Model\SubscriptionStates;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Pimcore\Model\DataObject\Service;

class SubscriptionFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private FactoryInterface $subscriptionFactory,
        private ObjectServiceInterface $objectService,
        private StoreRepositoryInterface $storeRepository,
    ) {
    }

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        return [
            SubscriptionPlanFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $defaultStore = $this->storeRepository->findStandard();
        $currency = $defaultStore->getCurrency();
        $parentFolder = $this->objectService->createFolderByPath('/demo/subscriptions');

        /** @var SubscriptionPlanInterface $basicPlan */
        $basicPlan = $this->getReference('subscription_plan_basic');
        /** @var SubscriptionPlanInterface $premiumPlan */
        $premiumPlan = $this->getReference('subscription_plan_premium');
        /** @var SubscriptionPlanInterface $enterprisePlan */
        $enterprisePlan = $this->getReference('subscription_plan_enterprise');

        $now = new \DateTimeImmutable();

        $subscriptions = [
            [
                'key' => 'subscription-active-basic',
                'plan' => $basicPlan,
                'state' => SubscriptionStates::STATE_ACTIVE,
                'startDate' => $now->modify('-90 days'),
                'nextBillingDate' => $now->modify('+15 days'),
                'completedCycles' => 3,
                'lastPaymentDate' => $now->modify('-15 days'),
            ],
            [
                'key' => 'subscription-trial-premium',
                'plan' => $premiumPlan,
                'state' => SubscriptionStates::STATE_TRIAL,
                'startDate' => $now->modify('-4 days'),
                'nextBillingDate' => $now->modify('+10 days'),
                'trialEndDate' => $now->modify('+10 days'),
                'completedCycles' => 0,
            ],
            [
                'key' => 'subscription-paused-basic',
                'plan' => $basicPlan,
                'state' => SubscriptionStates::STATE_PAUSED,
                'startDate' => $now->modify('-180 days'),
                'completedCycles' => 6,
                'lastPaymentDate' => $now->modify('-30 days'),
            ],
            [
                'key' => 'subscription-cancelled-enterprise',
                'plan' => $enterprisePlan,
                'state' => SubscriptionStates::STATE_CANCELLED,
                'startDate' => $now->modify('-400 days'),
                'completedCycles' => 1,
                'lastPaymentDate' => $now->modify('-365 days'),
                'cancellationDate' => $now->modify('-5 days'),
                'cancellationReason' => 'Too expensive',
            ],
            [
                'key' => 'subscription-completed-basic',
                'plan' => $basicPlan,
                'state' => SubscriptionStates::STATE_COMPLETED,
                'startDate' => $now->modify('-365 days'),
                'endDate' => $now->modify('-5 days'),
                'completedCycles' => 12,
                'lastPaymentDate' => $now->modify('-35 days'),
            ],
        ];

        foreach ($subscriptions as $data) {
            /** @var SubscriptionInterface $subscription */
            $subscription = $this->subscriptionFactory->createNew();

            $subscription->setKey(Service::getValidKey($data['key'], 'object'));
            $subscription->setParent($parentFolder);
            $subscription->setPublished(true);

            $subscription->setSubscriptionPlan($data['plan']);
            $subscription->setStore($defaultStore);
            $subscription->setCurrency($currency);
            $subscription->setState($data['state']);
            $subscription->setStartDate($data['startDate']);
            $subscription->setCompletedCycles($data['completedCycles']);

            if (isset($data['nextBillingDate'])) {
                $subscription->setNextBillingDate($data['nextBillingDate']);
            }

            if (isset($data['trialEndDate'])) {
                $subscription->setTrialEndDate($data['trialEndDate']);
            }

            if (isset($data['endDate'])) {
                $subscription->setEndDate($data['endDate']);
            }

            if (isset($data['lastPaymentDate'])) {
                $subscription->setLastPaymentDate($data['lastPaymentDate']);
            }

            if (isset($data['cancellationDate'])) {
                $subscription->setCancellationDate($data['cancellationDate']);
            }

            if (isset($data['cancellationReason'])) {
                $subscription->setCancellationReason($data['cancellationReason']);
            }

            $subscription->save();
        }
    }
}
