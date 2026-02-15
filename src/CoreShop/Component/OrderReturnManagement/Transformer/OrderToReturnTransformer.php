<?php

declare(strict_types=1);

namespace CoreShop\Component\OrderReturnManagement\Transformer;

use Carbon\Carbon;
use CoreShop\Component\Order\Model\OrderDocumentInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\OrderItemInterface;
use CoreShop\Component\Order\NumberGenerator\NumberGeneratorInterface;
use CoreShop\Component\Order\Transformer\OrderDocumentItemTransformerInterface;
use CoreShop\Component\Order\Transformer\OrderDocumentTransformerInterface;
use CoreShop\Component\Order\Transformer\TransformerEventDispatcherInterface;
use CoreShop\Component\Pimcore\DataObject\VersionHelper;
use CoreShop\Component\Resource\Factory\PimcoreFactoryInterface;
use CoreShop\Component\Resource\Repository\PimcoreRepositoryInterface;
use CoreShop\Component\Resource\Service\FolderCreationServiceInterface;
use CoreShop\Component\OrderReturnManagement\Model\OrderReturnInterface;
use Pimcore\Model\DataObject\Service;
use Webmozart\Assert\Assert;

class OrderToReturnTransformer implements OrderDocumentTransformerInterface
{
    public function __construct(
        protected OrderDocumentItemTransformerInterface $orderItemToReturnItemTransformer,
        protected NumberGeneratorInterface $numberGenerator,
        protected FolderCreationServiceInterface $folderCreationService,
        protected PimcoreRepositoryInterface $orderItemRepository,
        protected PimcoreFactoryInterface $returnItemFactory,
        protected TransformerEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function transform(
        OrderInterface $order,
        OrderDocumentInterface $document,
        array $itemsToTransform,
    ): OrderDocumentInterface {
        Assert::isInstanceOf($document, OrderReturnInterface::class);

        $this->eventDispatcher->dispatchPreEvent('return', $document, ['order' => $order, 'items' => $itemsToTransform]);

        $documentFolder = $this->folderCreationService->createFolderForResource($document, ['prefix' => $order->getFullPath()]);

        $document->setOrder($order);

        $documentNumber = $this->numberGenerator->generate($document);

        $document->setKey(Service::getValidKey($documentNumber, 'object'));
        $document->setReturnNumber($documentNumber);
        $document->setParent($documentFolder);
        $document->setPublished(true);
        $document->setReturnDate(Carbon::now());

        VersionHelper::useVersioning(function () use ($document): void {
            $document->save();
        }, false);

        $items = [];

        foreach ($itemsToTransform as $item) {
            $documentItem = $this->returnItemFactory->createNew();
            $orderItem = $this->orderItemRepository->find($item['orderItemId']);
            $quantity = $item['quantity'];

            if ($orderItem instanceof OrderItemInterface) {
                $items[] = $this->orderItemToReturnItemTransformer->transform(
                    $document,
                    $orderItem,
                    $documentItem,
                    (int) $quantity,
                    $item,
                );
            }
        }

        $document->setItems($items);
        VersionHelper::useVersioning(function () use ($document): void {
            $document->save();
        }, false);

        $this->eventDispatcher->dispatchPostEvent('return', $document, ['order' => $order, 'items' => $itemsToTransform]);

        return $document;
    }
}
