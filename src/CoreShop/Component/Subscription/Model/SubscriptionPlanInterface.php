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
use CoreShop\Component\Order\Model\PurchasableInterface;
use CoreShop\Component\Resource\Model\ToggleableInterface;
use CoreShop\Component\Resource\Model\TimestampableInterface;
use CoreShop\Component\Resource\Pimcore\Model\PimcoreModelInterface;
use CoreShop\Component\Store\Model\StoreAwareInterface;

interface SubscriptionPlanInterface extends
    PurchasableInterface,
    PimcoreModelInterface,
    StoreAwareInterface,
    ToggleableInterface,
    TimestampableInterface
{
    public function getName(?string $language = null): ?string;

    public function setName(?string $name, ?string $language = null);

    public function getDescription(?string $language = null): ?string;

    public function setDescription(?string $description, ?string $language = null);

    public function getBillingCycle(): ?string;

    public function setBillingCycle(?string $billingCycle);

    public function getBillingInterval(): ?int;

    public function setBillingInterval(?int $billingInterval);

    public function getPrice(): ?int;

    public function setPrice(?int $price);

    public function getCurrency(): ?CurrencyInterface;

    public function setCurrency(?CurrencyInterface $currency);

    public function getTrialPeriodDays(): ?int;

    public function setTrialPeriodDays(?int $trialPeriodDays);

    public function getMaxCycles(): ?int;

    public function setMaxCycles(?int $maxCycles);

    public function getAutoRenew(): bool;

    public function setAutoRenew(bool $autoRenew);
}
