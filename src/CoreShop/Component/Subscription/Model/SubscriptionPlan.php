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

namespace CoreShop\Component\Subscription\Model;

use CoreShop\Component\Currency\Model\CurrencyInterface;
use CoreShop\Component\Currency\Model\Money;
use CoreShop\Component\Resource\Exception\ImplementedByPimcoreException;
use CoreShop\Component\Resource\Pimcore\Model\AbstractPimcoreModel;
use CoreShop\Component\Store\Model\StoreAwareTrait;

abstract class SubscriptionPlan extends AbstractPimcoreModel implements SubscriptionPlanInterface
{
    use StoreAwareTrait;

    public function getName(?string $language = null): ?string
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setName(?string $name, ?string $language = null)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getDescription(?string $language = null): ?string
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setDescription(?string $description, ?string $language = null)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getBillingCycle(): ?string
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setBillingCycle(?string $billingCycle)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getBillingInterval(): ?int
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setBillingInterval(?int $billingInterval)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getPrice(): ?int
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setPrice(?int $price)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getCurrency(): ?CurrencyInterface
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setCurrency(?CurrencyInterface $currency)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getTrialPeriodDays(): ?int
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setTrialPeriodDays(?int $trialPeriodDays)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getMaxCycles(): ?int
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setMaxCycles(?int $maxCycles)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getAutoRenew(): bool
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setAutoRenew(bool $autoRenew)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getEnabled(): ?bool
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setEnabled(?bool $enabled)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getWholesaleBuyingPrice(): ?Money
    {
        return null;
    }

    public function getBillingCycleEnum(): ?BillingCycle
    {
        $cycle = $this->getBillingCycle();

        return $cycle !== null ? BillingCycle::tryFrom($cycle) : null;
    }
}
