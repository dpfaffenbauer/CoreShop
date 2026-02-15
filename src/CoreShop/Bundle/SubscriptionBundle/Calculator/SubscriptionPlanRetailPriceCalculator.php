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

namespace CoreShop\Bundle\SubscriptionBundle\Calculator;

use CoreShop\Component\Order\Calculator\PurchasableRetailPriceCalculatorInterface;
use CoreShop\Component\Order\Exception\NoPurchasableRetailPriceFoundException;
use CoreShop\Component\Order\Model\PurchasableInterface;
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;

final class SubscriptionPlanRetailPriceCalculator implements PurchasableRetailPriceCalculatorInterface
{
    public function getRetailPrice(PurchasableInterface $purchasable, array $context): int
    {
        if (!$purchasable instanceof SubscriptionPlanInterface) {
            throw new NoPurchasableRetailPriceFoundException(__CLASS__);
        }

        $price = $purchasable->getPrice();

        if ($price === null || $price <= 0) {
            throw new NoPurchasableRetailPriceFoundException(__CLASS__);
        }

        return $price;
    }
}
