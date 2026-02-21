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

#[McpTool(name: 'pimcore_list_objects')]
class ListObjects
{
    /**
     * List Pimcore data objects. Supports filtering by class name, parent, and conditions.
     *
     * @param string|null $className Filter by class name (e.g. "Product", "Category", "CoreShopProduct"). Required for class-specific field conditions.
     * @param int|null $parentId Parent object ID to list children of
     * @param string|null $parentPath Parent object path (alternative to parentId). Example: "/"
     * @param string|null $objectType Filter by type: object, folder, variant. Defaults to "object".
     * @param string|null $condition SQL condition string for filtering (e.g. "name LIKE '%shirt%'"). Only works with className set.
     * @param string|null $orderKey Field to order by (e.g. "key", "creationDate", "modificationDate")
     * @param string $order Sort direction: ASC or DESC
     * @param int $limit Maximum number of objects to return
     * @param int $offset Offset for pagination
     * @param bool $unpublished Include unpublished objects
     */
    public function __invoke(
        ?string $className = null,
        ?int $parentId = null,
        ?string $parentPath = null,
        ?string $objectType = null,
        ?string $condition = null,
        ?string $orderKey = null,
        string $order = 'ASC',
        int $limit = 50,
        int $offset = 0,
        bool $unpublished = false,
    ): string {
        if ($parentPath !== null) {
            $parent = DataObject::getByPath($parentPath);

            if (!$parent) {
                return json_encode(['error' => 'Parent object not found at path: ' . $parentPath]);
            }

            $parentId = $parent->getId();
        }

        if ($className !== null) {
            $classDefinition = DataObject\ClassDefinition::getByName($className);

            if (!$classDefinition) {
                return json_encode(['error' => 'Class "' . $className . '" not found. Use pimcore_get_class_definition to list available classes.']);
            }

            $listingClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';

            if (!class_exists($listingClass)) {
                return json_encode(['error' => 'Listing class not found for: ' . $className]);
            }

            $listing = new $listingClass();
        } else {
            $listing = new DataObject\Listing();
        }

        $conditions = [];
        $conditionParams = [];

        if ($parentId !== null) {
            $conditions[] = 'parentId = ?';
            $conditionParams[] = $parentId;
        }

        if ($objectType !== null && $className === null) {
            $conditions[] = 'type = ?';
            $conditionParams[] = $objectType;
        }

        if ($condition !== null) {
            $conditions[] = '(' . $condition . ')';
        }

        if (!empty($conditions)) {
            $listing->setCondition(implode(' AND ', $conditions), $conditionParams);
        }

        $listing->setLimit($limit);
        $listing->setOffset($offset);

        if ($orderKey !== null) {
            $listing->setOrderKey($orderKey);
            $listing->setOrder($order);
        }

        if ($unpublished) {
            $listing->setUnpublished(true);
        }

        if ($objectType !== null && $className !== null) {
            $listing->setObjectTypes([$objectType]);
        }

        $objects = [];

        foreach ($listing as $obj) {
            $data = [
                'id' => $obj->getId(),
                'key' => $obj->getKey(),
                'path' => $obj->getRealFullPath(),
                'type' => $obj->getType(),
                'parentId' => $obj->getParentId(),
                'creationDate' => $obj->getCreationDate(),
                'modificationDate' => $obj->getModificationDate(),
            ];

            if ($obj instanceof DataObject\Concrete) {
                $data['className'] = $obj->getClassName();
                $data['published'] = $obj->getPublished();
            }

            $objects[] = $data;
        }

        return json_encode([
            'total' => $listing->getTotalCount(),
            'limit' => $limit,
            'offset' => $offset,
            'objects' => $objects,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
