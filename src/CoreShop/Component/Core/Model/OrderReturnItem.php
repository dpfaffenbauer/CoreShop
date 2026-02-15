<?php

declare(strict_types=1);

namespace CoreShop\Component\Core\Model;

use CoreShop\Component\OrderReturnManagement\Model\OrderReturnItem as BaseOrderReturnItem;

abstract class OrderReturnItem extends BaseOrderReturnItem implements OrderReturnItemInterface
{
}
