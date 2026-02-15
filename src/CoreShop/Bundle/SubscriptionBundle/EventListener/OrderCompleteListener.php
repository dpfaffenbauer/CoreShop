<?php

declare(strict_types=1);

/*
 * CoreShop
 *
 * This source file is available under the terms of the
 * CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.com)
 * @license    CoreShop Commercial License (CCL)
 *
 */

namespace CoreShop\Bundle\SubscriptionBundle\EventListener;

use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\OrderItemInterface;
use CoreShop\Component\Subscription\Factory\SubscriptionFactoryInterface;
use CoreShop\Component\Subscription\Model\SubscriptionPlanInterface;
use CoreShop\Component\Subscription\Resolver\NextBillingDateResolverInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

final class OrderCompleteListener
{
    public function __construct(
        private SubscriptionFactoryInterface $subscriptionFactory,
        private NextBillingDateResolverInterface $nextBillingDateResolver,
    ) {
    }

    public function onOrderConfirm(CompletedEvent $event): void
    {
        $order = $event->getSubject();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $items = $order->getItems();

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (!$item instanceof OrderItemInterface) {
                continue;
            }

            $product = $item->getProduct();

            if (!$product instanceof SubscriptionPlanInterface) {
                continue;
            }

            $subscription = $this->subscriptionFactory->createFromOrder($order, $product);
            $subscription->save();
        }
    }
}
