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

enum BillingCycle: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function getIntervalSpec(int $interval = 1): string
    {
        return match ($this) {
            self::Weekly => sprintf('P%dW', $interval),
            self::Monthly => sprintf('P%dM', $interval),
            self::Quarterly => sprintf('P%dM', $interval * 3),
            self::Yearly => sprintf('P%dY', $interval),
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
        };
    }
}
