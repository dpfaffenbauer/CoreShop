<?php

declare(strict_types=1);

namespace CoreShop\Component\Core\Model;

use CoreShop\Component\Order\Model\OrderReturn as BaseOrderReturn;

abstract class OrderReturn extends BaseOrderReturn implements OrderReturnInterface
{
}
