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
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tool;

class SubscriptionPlanFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private int $decimalFactor,
        private FactoryInterface $subscriptionPlanFactory,
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
            TaxRuleGroupFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $decimalFactor = $this->decimalFactor;
        $defaultStore = $this->storeRepository->findStandard();
        $currency = $defaultStore->getCurrency();
        $parentFolder = $this->objectService->createFolderByPath('/demo/subscription-plans');

        $plans = [
            [
                'key' => 'basic-monthly',
                'names' => [
                    'en' => 'Basic Monthly',
                    'de' => 'Basis Monatlich',
                ],
                'billingCycle' => 'monthly',
                'billingInterval' => 1,
                'price' => (int) (999 * $decimalFactor / 100),
                'trialPeriodDays' => null,
                'maxCycles' => null,
                'autoRenew' => true,
                'reference' => 'subscription_plan_basic',
            ],
            [
                'key' => 'premium-quarterly',
                'names' => [
                    'en' => 'Premium Quarterly',
                    'de' => 'Premium Vierteljährlich',
                ],
                'billingCycle' => 'quarterly',
                'billingInterval' => 1,
                'price' => (int) (2499 * $decimalFactor / 100),
                'trialPeriodDays' => 14,
                'maxCycles' => 8,
                'autoRenew' => true,
                'reference' => 'subscription_plan_premium',
            ],
            [
                'key' => 'enterprise-yearly',
                'names' => [
                    'en' => 'Enterprise Yearly',
                    'de' => 'Enterprise Jährlich',
                ],
                'billingCycle' => 'yearly',
                'billingInterval' => 1,
                'price' => (int) (19999 * $decimalFactor / 100),
                'trialPeriodDays' => 30,
                'maxCycles' => null,
                'autoRenew' => true,
                'reference' => 'subscription_plan_enterprise',
            ],
        ];

        foreach ($plans as $planData) {
            /** @var SubscriptionPlanInterface $plan */
            $plan = $this->subscriptionPlanFactory->createNew();

            $plan->setKey(Service::getValidKey($planData['key'], 'object'));
            $plan->setParent($parentFolder);
            $plan->setPublished(true);

            foreach (Tool::getValidLanguages() as $lang) {
                $name = $planData['names'][$lang] ?? $planData['names']['en'];
                $plan->setName($name, $lang);
            }

            $plan->setBillingCycle($planData['billingCycle']);
            $plan->setBillingInterval($planData['billingInterval']);
            $plan->setPrice($planData['price']);
            $plan->setCurrency($currency);
            $plan->setStore($defaultStore);
            $plan->setActive(true);

            if ($planData['trialPeriodDays'] !== null) {
                $plan->setTrialPeriodDays($planData['trialPeriodDays']);
            }

            if ($planData['maxCycles'] !== null) {
                $plan->setMaxCycles($planData['maxCycles']);
            }

            $plan->setAutoRenew($planData['autoRenew']);
            $plan->save();

            $this->setReference($planData['reference'], $plan);
        }
    }
}
