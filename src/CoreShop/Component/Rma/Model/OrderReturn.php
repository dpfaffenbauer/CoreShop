<?php

declare(strict_types=1);

namespace CoreShop\Component\Rma\Model;

use Carbon\Carbon;
use CoreShop\Component\Resource\Pimcore\Model\AbstractPimcoreModel;

abstract class OrderReturn extends AbstractPimcoreModel implements OrderReturnInterface
{
    public static function getDocumentType(): string
    {
        return 'return';
    }

    public function getPrintBodyController(array $params = []): string
    {
        return 'CoreShop\Bundle\RmaBundle\Controller\OrderDocumentPrintController::returnAction';
    }

    public function getPrintHeaderController(array $params = []): string
    {
        return 'CoreShop\Bundle\RmaBundle\Controller\OrderDocumentPrintController::headerAction';
    }

    public function getPrintFooterController(array $params = []): string
    {
        return 'CoreShop\Bundle\RmaBundle\Controller\OrderDocumentPrintController::footerAction';
    }

    public function getRenderedAsset()
    {
        return $this->getProperty('rendered_asset');
    }

    public function setRenderedAsset($renderedAsset)
    {
        $this->setProperty('rendered_asset', 'asset', $renderedAsset);
    }

    public function getDocumentDate(): ?Carbon
    {
        return $this->getReturnDate();
    }

    public function setDocumentDate(?Carbon $documentDate)
    {
        return $this->setReturnDate($documentDate);
    }

    public function getDocumentNumber(): ?string
    {
        return $this->getReturnNumber();
    }

    public function setDocumentNumber(?string $documentNumber)
    {
        return $this->setReturnNumber($documentNumber);
    }
}
