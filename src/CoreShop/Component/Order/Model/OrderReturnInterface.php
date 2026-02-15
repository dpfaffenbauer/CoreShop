<?php

declare(strict_types=1);

namespace CoreShop\Component\Order\Model;

use Carbon\Carbon;

interface OrderReturnInterface extends OrderDocumentInterface
{
    public function getReturnDate(): ?Carbon;

    public function setReturnDate(?Carbon $returnDate);

    public function getReturnNumber(): ?string;

    public function setReturnNumber(?string $returnNumber);
}
