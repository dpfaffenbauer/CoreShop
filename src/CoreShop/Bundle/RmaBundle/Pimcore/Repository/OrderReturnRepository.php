<?php

declare(strict_types=1);

namespace CoreShop\Bundle\RmaBundle\Pimcore\Repository;

use CoreShop\Bundle\OrderBundle\Pimcore\Repository\AbstractOrderDocumentRepository;
use CoreShop\Component\Rma\Repository\OrderReturnRepositoryInterface;

class OrderReturnRepository extends AbstractOrderDocumentRepository implements OrderReturnRepositoryInterface
{
}
