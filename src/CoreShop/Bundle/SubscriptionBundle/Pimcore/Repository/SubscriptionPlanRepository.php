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

namespace CoreShop\Bundle\SubscriptionBundle\Pimcore\Repository;

use CoreShop\Bundle\ResourceBundle\Pimcore\PimcoreRepository;
use CoreShop\Component\Store\Model\StoreInterface;
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;
use CoreShop\Component\Subscription\Repository\SubscriptionPlanRepositoryInterface;

class SubscriptionPlanRepository extends PimcoreRepository implements SubscriptionPlanRepositoryInterface
{
    public function findActive(): array
    {
        $list = $this->getList();
        $list->setCondition('enabled = 1');

        return $list->getObjects();
    }

    public function findActiveForStore(StoreInterface $store): array
    {
        $list = $this->getList();
        $list->setCondition('enabled = 1 AND store__id = ?', [$store->getId()]);

        return $list->getObjects();
    }
}
