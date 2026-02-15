<?php

declare(strict_types=1);

namespace CoreShop\Component\Rma\Transformer;

use CoreShop\Component\Order\Model\OrderDocumentInterface;
use CoreShop\Component\Order\Model\OrderDocumentItemInterface;
use CoreShop\Component\Order\Model\OrderItemInterface;
use CoreShop\Component\Order\Transformer\OrderDocumentItemTransformerInterface;
use CoreShop\Component\Order\Transformer\TransformerEventDispatcherInterface;
use CoreShop\Component\Pimcore\DataObject\VersionHelper;
use CoreShop\Component\Resource\Service\FolderCreationServiceInterface;
use CoreShop\Component\Rma\Model\OrderReturnItemInterface;
use Webmozart\Assert\Assert;

class OrderItemToReturnItemTransformer implements OrderDocumentItemTransformerInterface
{
    public function __construct(
        protected FolderCreationServiceInterface $folderCreationService,
        protected TransformerEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function transform(
        OrderDocumentInterface $orderDocument,
        OrderItemInterface $orderItem,
        OrderDocumentItemInterface $documentItem,
        int $quantity,
        array $options = [],
    ): OrderDocumentItemInterface {
        Assert::isInstanceOf($documentItem, OrderReturnItemInterface::class);

        $this->eventDispatcher->dispatchPreEvent(
            'return_item',
            $documentItem,
            [
                'return' => $orderDocument,
                'order' => $orderItem->getOrder(),
                'order_item' => $orderItem,
                'options' => $options,
            ],
        );

        $itemFolder = $this->folderCreationService->createFolderForResource($documentItem, ['prefix' => $orderDocument->getFullPath()]);

        $documentItem->setKey($orderItem->getKey());
        $documentItem->setParent($itemFolder);
        $documentItem->setPublished(true);

        $documentItem->setOrderItem($orderItem);
        $documentItem->setQuantity($quantity);
        $documentItem->setTotal($orderItem->getItemPrice(true) * $quantity, true);
        $documentItem->setTotal($orderItem->getItemPrice(false) * $quantity, false);

        $documentItem->setConvertedTotal($orderItem->getConvertedItemPrice(true) * $quantity, true);
        $documentItem->setConvertedTotal($orderItem->getConvertedItemPrice(false) * $quantity, false);

        VersionHelper::useVersioning(function () use ($documentItem): void {
            $documentItem->save();
        }, false);

        $this->eventDispatcher->dispatchPostEvent(
            'return_item',
            $documentItem,
            [
                'return' => $orderDocument,
                'order' => $orderItem->getOrder(),
                'order_item' => $orderItem,
                'options' => $options,
            ],
        );

        return $documentItem;
    }
}
