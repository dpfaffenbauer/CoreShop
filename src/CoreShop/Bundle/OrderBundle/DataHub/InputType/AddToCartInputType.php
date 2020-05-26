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
use CoreShop\Bundle\ResourceBundle\DataHub\InputTypeFactoryInterface;
use CoreShop\Bundle\StoreBundle\DataHub\InputType\StoreInputType;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AddToCartInputType implements DataHubInputTypeInterface
{
    protected $inputTypeFactory;

    public function __construct(InputTypeFactoryInterface $inputTypeFactory)
    {
        $this->inputTypeFactory = $inputTypeFactory;
    }

    public function buildType(array $options): ?array
    {
        $cartInputType = $this->inputTypeFactory->buildType(CartInputType::class);
        $cartItemInputType = $this->inputTypeFactory->buildType(CartItemInputType::class);
        $storeInputType = $this->inputTypeFactory->buildType(StoreInputType::class);

        if ($cartInputType instanceof NullableType) {
            $cartInputType = Type::nonNull($cartInputType);
        }

        if ($cartItemInputType instanceof NullableType) {
            $cartItemInputType = Type::nonNull($cartItemInputType);
        }

        if ($storeInputType instanceof NullableType) {
            $storeInputType = Type::nonNull($storeInputType);
        }

        return [
            'name' => 'AddToCartCartItemInput',
            'fields' => [
                'cart' => [
                    'type' => $cartInputType
                ],
                'cartItem' => [
                    'type' => $cartItemInputType
                ],
                'store' => [
                    'type' => $storeInputType
                ]
            ],
        ];
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {

    }
}
