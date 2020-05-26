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

namespace CoreShop\Bundle\OrderBundle\DataHub\InputType;

use CoreShop\Bundle\ResourceBundle\DataHub\DataHubInputTypeInterface;
use GraphQL\Type\Definition\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CartItemInputType implements DataHubInputTypeInterface
{
    public function buildType(array $options): ?array
    {
        return [
            'name' => 'CartItemInputType',
            'fields' => [
                'quantity' => [
                    'type' => Type::nonNull(Type::float()),
                ],
                'productId' => [
                    'type' => Type::nonNull(Type::int()),
                ],
            ],
        ];
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {

    }
}
