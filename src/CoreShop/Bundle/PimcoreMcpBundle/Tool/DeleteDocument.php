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
use Pimcore\Model\Document;

#[McpTool(name: 'pimcore_delete_document')]
class DeleteDocument
{
    /**
     * Delete a Pimcore document by ID or path.
     *
     * @param int|null $id Document ID to delete
     * @param string|null $path Document path (alternative to id)
     */
    public function __invoke(?int $id = null, ?string $path = null): string
    {
        if ($id === null && $path === null) {
            return json_encode(['error' => 'Either "id" or "path" is required.']);
        }

        $doc = $id !== null ? Document::getById($id) : Document::getByPath($path);

        if (!$doc) {
            return json_encode(['error' => 'Document not found.']);
        }

        $deletedId = $doc->getId();
        $deletedPath = $doc->getRealFullPath();

        $doc->delete();

        return json_encode([
            'success' => true,
            'deletedId' => $deletedId,
            'deletedPath' => $deletedPath,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
