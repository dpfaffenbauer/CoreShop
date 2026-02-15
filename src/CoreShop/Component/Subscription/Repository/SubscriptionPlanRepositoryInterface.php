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

namespace CoreShop\Component\Subscription\Repository;

use CoreShop\Component\Resource\Repository\PimcoreRepositoryInterface;
use CoreShop\Component\Store\Model\StoreInterface;
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;

interface SubscriptionPlanRepositoryInterface extends PimcoreRepositoryInterface
{
    /**
     * @return SubscriptionPlanInterface[]
     */
    public function findActive(): array;

    /**
     * @return SubscriptionPlanInterface[]
     */
    public function findActiveForStore(StoreInterface $store): array;
}
