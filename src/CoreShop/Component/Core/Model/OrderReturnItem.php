<?php

declare(strict_types=1);

namespace CoreShop\Component\Core\Model;

use CoreShop\Component\Order\Model\OrderReturnItem as BaseOrderReturnItem;

abstract class OrderReturnItem extends BaseOrderReturnItem implements OrderReturnItemInterface
{
}
