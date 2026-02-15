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

use CoreShop\Component\Order\Calculator\PurchasableDiscountCalculatorInterface;
use CoreShop\Component\Order\Model\PurchasableInterface;
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;

final class SubscriptionPlanDiscountCalculator implements PurchasableDiscountCalculatorInterface
{
    public function getDiscount(PurchasableInterface $purchasable, array $context, int $basePrice): int
    {
        if (!$purchasable instanceof SubscriptionPlanInterface) {
            return 0;
        }

        // Subscription-Rule-based discounts can be added here later
        return 0;
    }
}
