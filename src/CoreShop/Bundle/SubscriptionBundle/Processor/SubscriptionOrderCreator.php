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

namespace CoreShop\Bundle\SubscriptionBundle\Processor;

use CoreShop\Component\Order\Cart\CartModifierInterface;
use CoreShop\Component\Order\Factory\OrderItemFactoryInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Processor\CartProcessorInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Subscription\Model\SubscriptionInterface;
use CoreShop\Component\Subscription\Processor\SubscriptionOrderCreatorInterface;

final class SubscriptionOrderCreator implements SubscriptionOrderCreatorInterface
{
    public function __construct(
        private FactoryInterface $orderFactory,
        private OrderItemFactoryInterface $orderItemFactory,
        private CartModifierInterface $cartModifier,
        private CartProcessorInterface $cartProcessor,
    ) {
    }

    public function createOrder(SubscriptionInterface $subscription): OrderInterface
    {
        $plan = $subscription->getSubscriptionPlan();

        if ($plan === null) {
            throw new \RuntimeException('Subscription has no plan assigned.');
        }

        /** @var OrderInterface $cart */
        $cart = $this->orderFactory->createNew();
        $cart->setKey(uniqid('subscription-renewal-'));
        $cart->setPublished(true);
        $cart->setStore($subscription->getStore());
        $cart->setCurrency($subscription->getCurrency());

        $item = $this->orderItemFactory->createWithCart($cart, $plan);
        $item->setQuantity(1);

        $this->cartProcessor->process($cart);

        return $cart;
    }
}
