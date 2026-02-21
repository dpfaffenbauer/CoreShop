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
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;

#[McpTool(name: 'pimcore_get_object')]
class GetObject
{
    /**
     * Get a single Pimcore data object by ID or path, including all its field values.
     *
     * @param int|null $id Object ID
     * @param string|null $path Object path (alternative to id). Example: "/products/my-product"
     * @param string|null $locale Locale for localized fields (e.g. "en", "de"). If not set, returns all locales.
     */
    public function __invoke(?int $id = null, ?string $path = null, ?string $locale = null): string
    {
        if ($id === null && $path === null) {
            return json_encode(['error' => 'Either "id" or "path" is required.']);
        }

        $obj = $id !== null ? DataObject::getById($id) : DataObject::getByPath($path);

        if (!$obj) {
            return json_encode(['error' => 'Object not found.']);
        }

        $data = [
            'id' => $obj->getId(),
            'key' => $obj->getKey(),
            'path' => $obj->getRealFullPath(),
            'type' => $obj->getType(),
            'parentId' => $obj->getParentId(),
            'creationDate' => $obj->getCreationDate(),
            'modificationDate' => $obj->getModificationDate(),
            'userOwner' => $obj->getUserOwner(),
            'userModification' => $obj->getUserModification(),
            'hasChildren' => $obj->hasChildren(),
        ];

        if ($obj instanceof DataObject\Concrete) {
            $data['className'] = $obj->getClassName();
            $data['published'] = $obj->getPublished();
            $data['fields'] = $this->extractFieldData($obj, $locale);
        }

        $properties = [];

        foreach ($obj->getProperties() as $name => $property) {
            $properties[$name] = [
                'type' => $property->getType(),
                'data' => (string) $property->getData(),
                'inheritable' => $property->getInheritable(),
            ];
        }

        $data['properties'] = $properties;

        return json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }

    private function extractFieldData(DataObject\Concrete $object, ?string $locale): array
    {
        $fieldDefinitions = $object->getClass()->getFieldDefinitions();
        $fields = [];

        DataObject::setGetInheritedValues(true);

        foreach ($fieldDefinitions as $fd) {
            $fieldName = $fd->getName();
            $getter = 'get' . ucfirst($fieldName);

            if (!method_exists($object, $getter)) {
                continue;
            }

            if ($fd instanceof Localizedfields) {
                $fields[$fieldName] = $this->extractLocalizedFieldData($object, $fd, $locale);

                continue;
            }

            try {
                $value = $object->$getter();
                $fields[$fieldName] = $this->serializeValue($fd, $value, $object);
            } catch (\Throwable) {
                $fields[$fieldName] = null;
            }
        }

        return $fields;
    }

    private function extractLocalizedFieldData(DataObject\Concrete $object, Localizedfields $fd, ?string $locale): array
    {
        $localizedFields = [];
        $childDefinitions = $fd->getFieldDefinitions();

        if ($locale !== null) {
            foreach ($childDefinitions as $childFd) {
                $getter = 'get' . ucfirst($childFd->getName());

                if (!method_exists($object, $getter)) {
                    continue;
                }

                try {
                    $value = $object->$getter($locale);
                    $localizedFields[$childFd->getName()] = $this->serializeValue($childFd, $value, $object);
                } catch (\Throwable) {
                    $localizedFields[$childFd->getName()] = null;
                }
            }

            return [$locale => $localizedFields];
        }

        $validLanguages = \Pimcore\Tool::getValidLanguages();

        foreach ($validLanguages as $lang) {
            $langFields = [];

            foreach ($childDefinitions as $childFd) {
                $getter = 'get' . ucfirst($childFd->getName());

                if (!method_exists($object, $getter)) {
                    continue;
                }

                try {
                    $value = $object->$getter($lang);
                    $langFields[$childFd->getName()] = $this->serializeValue($childFd, $value, $object);
                } catch (\Throwable) {
                    $langFields[$childFd->getName()] = null;
                }
            }

            $localizedFields[$lang] = $langFields;
        }

        return $localizedFields;
    }

    private function serializeValue(DataObject\ClassDefinition\Data $fd, mixed $value, DataObject\Concrete $object): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DataObject\AbstractObject) {
            return [
                'id' => $value->getId(),
                'type' => 'object',
                'path' => $value->getRealFullPath(),
                'className' => $value instanceof DataObject\Concrete ? $value->getClassName() : null,
            ];
        }

        if ($value instanceof \Pimcore\Model\Asset) {
            return [
                'id' => $value->getId(),
                'type' => 'asset',
                'path' => $value->getRealFullPath(),
                'filename' => $value->getFilename(),
            ];
        }

        if ($value instanceof \Pimcore\Model\Document) {
            return [
                'id' => $value->getId(),
                'type' => 'document',
                'path' => $value->getRealFullPath(),
            ];
        }

        if ($value instanceof \Pimcore\Model\Element\ElementDescriptor) {
            return [
                'id' => $value->getId(),
                'type' => $value->getType(),
            ];
        }

        if (is_array($value)) {
            return array_map(function ($item) use ($fd, $object) {
                return $this->serializeValue($fd, $item, $object);
            }, $value);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value)) {
            try {
                return $fd->getDataForEditmode($value, $object);
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        return $value;
    }
}
