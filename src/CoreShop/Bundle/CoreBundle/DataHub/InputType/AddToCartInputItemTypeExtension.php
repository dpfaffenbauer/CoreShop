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

namespace CoreShop\Bundle\CoreBundle\DataHub\InputType;

use CoreShop\Bundle\OrderBundle\DataHub\InputType\AddToCartInputType;
use CoreShop\Bundle\OrderBundle\DataHub\InputType\CartItemInputType;
use CoreShop\Bundle\ResourceBundle\DataHub\DataHubInputTypeExtensionInterface;
use CoreShop\Bundle\ResourceBundle\DataHub\InputTypeFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddToCartInputItemTypeExtension implements DataHubInputTypeExtensionInterface
{
    protected $inputTypeFactory;

    public function __construct(InputTypeFactoryInterface $inputTypeFactory)
    {
        $this->inputTypeFactory = $inputTypeFactory;
    }

    public function extendType(array $type, array $options): ?array
    {
        $type['fields']['cartItem']['type'] = $this->inputTypeFactory->buildType(CartItemInputType::class, ['allow_units' => true]);

        return $type;
    }

    public function getExtendedTypes(): ?array
    {
        return [
            AddToCartInputType::class
        ];
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {

    }
}
