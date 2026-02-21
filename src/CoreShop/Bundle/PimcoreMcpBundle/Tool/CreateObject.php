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
use Pimcore\Model\DataObject;

#[McpTool(name: 'pimcore_create_object')]
class CreateObject
{
    /**
     * Create a new Pimcore data object or folder.
     *
     * Use pimcore_get_class_definition first to discover available classes and their fields.
     *
     * @param string $key Object key (filename/URL slug). Example: "my-new-product"
     * @param string $className Class name for the object (e.g. "Product", "Category"). Use "folder" to create a folder.
     * @param int|null $parentId Parent object ID. Defaults to 1 (root).
     * @param string|null $parentPath Parent object path (alternative to parentId). Example: "/products"
     * @param bool $published Whether the object should be published
     * @param string|null $fieldData JSON object with field values to set. Keys are field names, values are the data. Example: {"name": "My Product", "price": 1999}. For localized fields: {"localizedfields": {"en": {"name": "English"}, "de": {"name": "Deutsch"}}}
     */
    public function __invoke(
        string $key,
        string $className,
        ?int $parentId = null,
        ?string $parentPath = null,
        bool $published = false,
        ?string $fieldData = null,
    ): string {
        if ($parentPath !== null) {
            $parent = DataObject::getByPath($parentPath);

            if (!$parent) {
                return json_encode(['error' => 'Parent object not found at path: ' . $parentPath]);
            }

            $parentId = $parent->getId();
        }

        $parentId = $parentId ?? 1;

        if ($className === 'folder') {
            $folder = new DataObject\Folder();
            $folder->setKey($key);
            $folder->setParentId($parentId);
            $folder->save();

            return json_encode([
                'success' => true,
                'id' => $folder->getId(),
                'path' => $folder->getRealFullPath(),
                'type' => 'folder',
            ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        }

        $classDefinition = DataObject\ClassDefinition::getByName($className);

        if (!$classDefinition) {
            return json_encode(['error' => 'Class "' . $className . '" not found. Use pimcore_get_class_definition to list available classes.']);
        }

        $objectClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className);

        if (!class_exists($objectClass)) {
            return json_encode(['error' => 'Object class not found for: ' . $className]);
        }

        $obj = new $objectClass();
        $obj->setKey($key);
        $obj->setParentId($parentId);
        $obj->setPublished($published);

        if ($fieldData !== null) {
            $fields = json_decode($fieldData, true);

            if (json_last_error() !== \JSON_ERROR_NONE) {
                return json_encode(['error' => 'Invalid JSON in fieldData: ' . json_last_error_msg()]);
            }

            $result = $this->applyFieldData($obj, $fields);

            if ($result !== null) {
                return $result;
            }
        }

        try {
            $obj->save();
        } catch (\Throwable $e) {
            return json_encode([
                'error' => 'Failed to save object: ' . $e->getMessage(),
            ]);
        }

        return json_encode([
            'success' => true,
            'id' => $obj->getId(),
            'path' => $obj->getRealFullPath(),
            'className' => $obj->getClassName(),
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    private function applyFieldData(DataObject\Concrete $object, array $fields): ?string
    {
        $classDefinition = $object->getClass();
        $fieldDefinitions = $classDefinition->getFieldDefinitions();

        foreach ($fields as $fieldName => $value) {
            if ($fieldName === 'localizedfields') {
                $result = $this->applyLocalizedFieldData($object, $fieldDefinitions, $value);

                if ($result !== null) {
                    return $result;
                }

                continue;
            }

            $fd = $fieldDefinitions[$fieldName] ?? null;

            if ($fd === null) {
                return json_encode([
                    'error' => 'Unknown field "' . $fieldName . '" for class "' . $classDefinition->getName() . '". Use pimcore_get_class_definition to see available fields.',
                ]);
            }

            $setter = 'set' . ucfirst($fieldName);

            if (!method_exists($object, $setter)) {
                return json_encode(['error' => 'No setter found for field "' . $fieldName . '".']);
            }

            try {
                $convertedValue = $fd->getDataFromEditmode($value, $object);
                $object->$setter($convertedValue);
            } catch (\Throwable $e) {
                return json_encode([
                    'error' => 'Failed to set field "' . $fieldName . '": ' . $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    private function applyLocalizedFieldData(DataObject\Concrete $object, array $fieldDefinitions, mixed $localizedData): ?string
    {
        if (!is_array($localizedData)) {
            return json_encode(['error' => 'localizedfields must be an object with locale keys, e.g. {"en": {"name": "English"}, "de": {"name": "Deutsch"}}']);
        }

        $localizedFd = $fieldDefinitions['localizedfields'] ?? null;

        if (!$localizedFd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
            return json_encode(['error' => 'This class does not have localized fields.']);
        }

        $childDefinitions = $localizedFd->getFieldDefinitions();

        foreach ($localizedData as $locale => $localeFields) {
            if (!is_array($localeFields)) {
                continue;
            }

            foreach ($localeFields as $fieldName => $value) {
                $childFd = $childDefinitions[$fieldName] ?? null;

                if ($childFd === null) {
                    return json_encode([
                        'error' => 'Unknown localized field "' . $fieldName . '". Use pimcore_get_class_definition to see available fields.',
                    ]);
                }

                $setter = 'set' . ucfirst($fieldName);

                if (!method_exists($object, $setter)) {
                    return json_encode(['error' => 'No setter found for localized field "' . $fieldName . '".']);
                }

                try {
                    $convertedValue = $childFd->getDataFromEditmode($value, $object);
                    $object->$setter($convertedValue, $locale);
                } catch (\Throwable $e) {
                    return json_encode([
                        'error' => 'Failed to set localized field "' . $fieldName . '" for locale "' . $locale . '": ' . $e->getMessage(),
                    ]);
                }
            }
        }

        return null;
    }
}
