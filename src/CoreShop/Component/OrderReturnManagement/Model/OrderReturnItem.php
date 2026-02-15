<?php

declare(strict_types=1);

namespace CoreShop\Component\OrderReturnManagement\Model;

use CoreShop\Component\Order\Model\OrderDocumentInterface;
use CoreShop\Component\Resource\Exception\ImplementedByPimcoreException;
use CoreShop\Component\Resource\Pimcore\Model\AbstractPimcoreModel;

abstract class OrderReturnItem extends AbstractPimcoreModel implements OrderReturnItemInterface
{
    public function getDocument(): OrderDocumentInterface
    {
        $parent = $this->getParent();

        do {
            if ($parent instanceof OrderDocumentInterface) {
                return $parent;
            }

            $parent = $parent->getParent();
        } while ($parent !== null);

        throw new \InvalidArgumentException('Order Return could not be found!');
    }

    public function getTotal(bool $withTax = true): int
    {
        return $withTax ? $this->getTotalGross() : $this->getTotalNet();
    }

    public function setTotal(int $total, bool $withTax = true)
    {
        $withTax ? $this->setTotalGross($total) : $this->setTotalNet($total);
    }

    public function getConvertedTotal(bool $withTax = true): int
    {
        return $withTax ? $this->getConvertedTotalGross() : $this->getConvertedTotalNet();
    }

    public function setConvertedTotal(int $convertedTotal, bool $withTax = true)
    {
        $withTax ? $this->setConvertedTotalGross($convertedTotal) : $this->setConvertedTotalNet($convertedTotal);
    }

    public function getTotalNet(): int
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setTotalNet(int $totalNet)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getTotalGross(): int
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setTotalGross(int $totalGross)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getConvertedTotalNet(): int
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setConvertedTotalNet(int $convertedTotalNet)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function getConvertedTotalGross(): int
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }

    public function setConvertedTotalGross(int $convertedTotalGross)
    {
        throw new ImplementedByPimcoreException(__CLASS__, __METHOD__);
    }
}
