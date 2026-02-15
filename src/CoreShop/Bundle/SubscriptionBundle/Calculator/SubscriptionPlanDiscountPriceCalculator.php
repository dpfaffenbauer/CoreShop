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

use CoreShop\Component\Order\Calculator\PurchasableDiscountPriceCalculatorInterface;
use CoreShop\Component\Order\Exception\NoPurchasableDiscountPriceFoundException;
use CoreShop\Component\Order\Model\PurchasableInterface;
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;

final class SubscriptionPlanDiscountPriceCalculator implements PurchasableDiscountPriceCalculatorInterface
{
    public function getDiscountPrice(PurchasableInterface $purchasable, array $context): int
    {
        if (!$purchasable instanceof SubscriptionPlanInterface) {
            throw new NoPurchasableDiscountPriceFoundException(__CLASS__);
        }

        // Subscription-Rule-based discounts can be added here later
        // For now, no discount price is available
        throw new NoPurchasableDiscountPriceFoundException(__CLASS__);
    }
}
