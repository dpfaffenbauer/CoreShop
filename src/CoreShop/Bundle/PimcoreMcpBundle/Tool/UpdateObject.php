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

#[McpTool(name: 'pimcore_update_object')]
class UpdateObject
{
    /**
     * Update field values on an existing Pimcore data object.
     *
     * Use pimcore_get_class_definition first to see available fields and their types.
     *
     * @param int|null $id Object ID to update
     * @param string|null $path Object path (alternative to id)
     * @param string|null $key New object key (filename)
     * @param int|null $newParentId New parent object ID (moves the object)
     * @param bool|null $published Set published state
     * @param string|null $fieldData JSON object with field values to set. Keys are field names, values are the data. Example: {"name": "My Product", "price": 1999, "sku": "PROD-001"}. For relation fields use element IDs. For localized fields use: {"localizedfields": {"en": {"name": "English"}, "de": {"name": "Deutsch"}}}
     */
    public function __invoke(
        ?int $id = null,
        ?string $path = null,
        ?string $key = null,
        ?int $newParentId = null,
        ?bool $published = null,
        ?string $fieldData = null,
    ): string {
        if ($id === null && $path === null) {
            return json_encode(['error' => 'Either "id" or "path" is required.']);
        }

        $obj = $id !== null ? DataObject::getById($id) : DataObject::getByPath($path);

        if (!$obj) {
            return json_encode(['error' => 'Object not found.']);
        }

        if (!$obj instanceof DataObject\Concrete) {
            if ($obj instanceof DataObject\Folder) {
                if ($key !== null) {
                    $obj->setKey($key);
                }

                if ($newParentId !== null) {
                    $obj->setParentId($newParentId);
                }

                $obj->save();

                return json_encode([
                    'success' => true,
                    'id' => $obj->getId(),
                    'path' => $obj->getRealFullPath(),
                    'type' => 'folder',
                ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
            }

            return json_encode(['error' => 'Object is not a concrete data object (type: ' . $obj->getType() . '). Only concrete objects can have field data updated.']);
        }

        if ($key !== null) {
            $obj->setKey($key);
        }

        if ($newParentId !== null) {
            $obj->setParentId($newParentId);
        }

        if ($published !== null) {
            $obj->setPublished($published);
        }

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
