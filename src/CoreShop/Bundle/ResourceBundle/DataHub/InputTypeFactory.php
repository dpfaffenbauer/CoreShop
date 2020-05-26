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

namespace CoreShop\Bundle\ResourceBundle\DataHub;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InputType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InputTypeFactory implements InputTypeFactoryInterface
{
    protected $typeCache = [];

    /**
     * @var DataHubInputTypeInterface[]
     */
    protected $types;

    /**
     * @var DataHubInputTypeExtensionInterface[]
     */
    protected $extensions;

    public function __construct(iterable $types, iterable $extensions)
    {
        $this->types = $types;
        $this->extensions = $extensions;
    }

    public function buildType(string $name, array $options = []): InputType
    {
        $type = null;
        $resolver = new OptionsResolver();
        $type = $this->resolveType($name);

        $type->configureOptions($resolver);

        foreach ($this->resolveExtensions($name) as $extension) {
            $extension->configureOptions($resolver);
        }

        $resolvedOptions = $resolver->resolve($options);

        $type = $type->buildType($options);

        foreach ($this->resolveExtensions($name) as $extension) {
            $type = $extension->extendType($type, $resolvedOptions);
        }

        return $this->typeCache[$name] = new InputObjectType($type);
    }

    protected function resolveType($name): DataHubInputTypeInterface
    {
        foreach ($this->types as $type) {
            if (get_class($type) !== $name) {
                continue;
            }

            return $type;
        }

        return new $name();
    }

    protected function resolveExtensions($name): \Iterator
    {
        foreach ($this->extensions as $extension) {
            if (!in_array($name, $extension->getExtendedTypes(), true)) {
                continue;
            }

            yield $extension;
        }

        return [];
    }
}
