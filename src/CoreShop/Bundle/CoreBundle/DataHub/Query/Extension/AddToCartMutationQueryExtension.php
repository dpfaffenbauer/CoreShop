<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace CoreShop\Bundle\CoreBundle\DataHub\Query\Extension;

use CoreShop\Bundle\OrderBundle\DataHub\AddToCartMutationQuery;
use CoreShop\Bundle\ResourceBundle\DataHub\DataHubQueryExtensionInterface;
use CoreShop\Component\Core\Model\OrderItemInterface;
use CoreShop\Component\Core\Model\ProductInterface;

final class AddToCartMutationQueryExtension implements DataHubQueryExtensionInterface
{
    public function supports($query): bool
    {
        return $query instanceof AddToCartMutationQuery;
    }

    public function handle(string $event, array $args)
    {
        if ($event === 'cartItemFactory') {
            /**
             * @var OrderItemInterface $cartItem
             */
            $cartItem = $args['cartItem'];
            $product = $cartItem->getProduct();

            if (!$product instanceof ProductInterface) {
                return;
            }

            if (isset($args['args']['cartItem']['unitDefinitionId'])) {
                foreach ($product->getUnitDefinitions() as $definition) {
                    if ($definition->getId() === $args['args']['cartItem']['unitDefinitionId']) {
                        $cartItem->setUnitDefinition($definition);
                    }
                }
            }
        }
    }
}
