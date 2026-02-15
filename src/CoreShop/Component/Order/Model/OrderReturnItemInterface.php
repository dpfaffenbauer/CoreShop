<?php

declare(strict_types=1);

namespace CoreShop\Component\Order\Model;

interface OrderReturnItemInterface extends OrderDocumentItemInterface
{
    public function getTotal(bool $withTax = true): int;

    public function setTotal(int $total, bool $withTax = true);

    public function getConvertedTotal(bool $withTax = true): int;

    public function setConvertedTotal(int $convertedTotal, bool $withTax = true);
}
