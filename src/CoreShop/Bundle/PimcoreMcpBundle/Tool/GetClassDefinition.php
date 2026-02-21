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

namespace CoreShop\Bundle\PimcoreMcpBundle\Tool;

use Mcp\Capability\Attribute\McpTool;
use Pimcore\Model\DataObject\ClassDefinition;

#[McpTool(name: 'pimcore_get_class_definition')]
class GetClassDefinition
{
    /**
     * Get Pimcore class definitions. Without a className, lists all available classes.
     * With a className, returns the full field definition schema for that class.
     *
     * @param string|null $className Class name to get the definition for (e.g. "Product", "Category"). If omitted, lists all classes.
     */
    public function __invoke(?string $className = null): string
    {
        if ($className === null) {
            return $this->listClasses();
        }

        $classDefinition = ClassDefinition::getByName($className);

        if (!$classDefinition) {
            return json_encode(['error' => 'Class "' . $className . '" not found.']);
        }

        $fields = [];

        foreach ($classDefinition->getFieldDefinitions() as $fd) {
            $fields[] = $this->serializeFieldDefinition($fd);
        }

        return json_encode([
            'id' => $classDefinition->getId(),
            'name' => $classDefinition->getName(),
            'title' => $classDefinition->getTitle(),
            'description' => $classDefinition->getDescription(),
            'allowInherit' => $classDefinition->getAllowInherit(),
            'allowVariants' => $classDefinition->getAllowVariants(),
            'fields' => $fields,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }

    private function listClasses(): string
    {
        $listing = new ClassDefinition\Listing();
        $listing->setOrderKey('name');
        $listing->setOrder('ASC');

        $classes = [];

        foreach ($listing as $class) {
            $classes[] = [
                'id' => $class->getId(),
                'name' => $class->getName(),
                'title' => $class->getTitle(),
                'description' => $class->getDescription(),
                'fieldCount' => count($class->getFieldDefinitions()),
            ];
        }

        return json_encode([
            'total' => count($classes),
            'classes' => $classes,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }

    private function serializeFieldDefinition(ClassDefinition\Data $fd): array
    {
        $data = [
            'name' => $fd->getName(),
            'title' => $fd->getTitle(),
            'type' => $fd->getFieldType(),
            'mandatory' => $fd->getMandatory(),
            'noteditable' => $fd->getNoteditable(),
            'tooltip' => $fd->getTooltip(),
        ];

        if ($fd instanceof ClassDefinition\Data\Localizedfields) {
            $children = [];

            foreach ($fd->getFieldDefinitions() as $childFd) {
                $children[] = $this->serializeFieldDefinition($childFd);
            }

            $data['children'] = $children;
        }

        if ($fd instanceof ClassDefinition\Data\Select || $fd instanceof ClassDefinition\Data\Multiselect) {
            $data['options'] = $fd->getOptions();
        }

        if (method_exists($fd, 'getDefaultValue')) {
            $default = $fd->getDefaultValue();

            if ($default !== null && $default !== '') {
                $data['defaultValue'] = $default;
            }
        }

        if ($fd->isRelationType()) {
            $data['relationType'] = true;

            if (method_exists($fd, 'getObjectsAllowed')) {
                $data['objectsAllowed'] = $fd->getObjectsAllowed();
            }

            if (method_exists($fd, 'getAssetsAllowed')) {
                $data['assetsAllowed'] = $fd->getAssetsAllowed();
            }

            if (method_exists($fd, 'getDocumentsAllowed')) {
                $data['documentsAllowed'] = $fd->getDocumentsAllowed();
            }

            if (method_exists($fd, 'getClasses')) {
                $allowedClasses = $fd->getClasses();

                if (!empty($allowedClasses)) {
                    $data['allowedClasses'] = $allowedClasses;
                }
            }
        }

        if ($fd instanceof ClassDefinition\Data\Numeric) {
            if (method_exists($fd, 'getMinValue') && $fd->getMinValue() !== null) {
                $data['minValue'] = $fd->getMinValue();
            }

            if (method_exists($fd, 'getMaxValue') && $fd->getMaxValue() !== null) {
                $data['maxValue'] = $fd->getMaxValue();
            }

            if (method_exists($fd, 'getDecimalPrecision') && $fd->getDecimalPrecision() !== null) {
                $data['decimalPrecision'] = $fd->getDecimalPrecision();
            }
        }

        if ($fd instanceof ClassDefinition\Data\Fieldcollections) {
            $data['allowedTypes'] = $fd->getAllowedTypes();
        }

        if ($fd instanceof ClassDefinition\Data\Objectbricks) {
            $data['allowedTypes'] = $fd->getAllowedTypes();
        }

        if ($fd instanceof ClassDefinition\Data\Block) {
            $children = [];

            foreach ($fd->getFieldDefinitions() as $childFd) {
                $children[] = $this->serializeFieldDefinition($childFd);
            }

            $data['children'] = $children;
        }

        return $data;
    }
}
