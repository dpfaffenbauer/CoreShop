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

use CoreShop\Component\Order\Calculator\PurchasablePriceCalculatorInterface;
use CoreShop\Component\Order\Exception\NoPurchasablePriceFoundException;
use CoreShop\Component\Order\Model\PurchasableInterface;
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;

final class SubscriptionPlanPriceCalculator implements PurchasablePriceCalculatorInterface
{
    public function getPrice(PurchasableInterface $purchasable, array $context, bool $includingDiscounts = false): int
    {
        if (!$purchasable instanceof SubscriptionPlanInterface) {
            throw new NoPurchasablePriceFoundException(__CLASS__);
        }

        $price = $purchasable->getPrice();

        if ($price === null || $price <= 0) {
            throw new NoPurchasablePriceFoundException(__CLASS__);
        }

        return $price;
    }
}
