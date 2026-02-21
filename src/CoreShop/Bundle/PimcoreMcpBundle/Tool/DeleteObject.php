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

#[McpTool(name: 'pimcore_delete_object')]
class DeleteObject
{
    /**
     * Delete a Pimcore data object by ID or path.
     *
     * @param int|null $id Object ID to delete
     * @param string|null $path Object path (alternative to id)
     */
    public function __invoke(?int $id = null, ?string $path = null): string
    {
        if ($id === null && $path === null) {
            return json_encode(['error' => 'Either "id" or "path" is required.']);
        }

        $obj = $id !== null ? DataObject::getById($id) : DataObject::getByPath($path);

        if (!$obj) {
            return json_encode(['error' => 'Object not found.']);
        }

        $deletedId = $obj->getId();
        $deletedPath = $obj->getRealFullPath();
        $deletedType = $obj->getType();

        $obj->delete();

        return json_encode([
            'success' => true,
            'deletedId' => $deletedId,
            'deletedPath' => $deletedPath,
            'deletedType' => $deletedType,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
