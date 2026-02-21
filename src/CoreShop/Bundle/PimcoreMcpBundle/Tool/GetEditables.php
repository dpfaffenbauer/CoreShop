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

class GetEditables
{
    /**
     * Get all editables (content elements) of a Pimcore page or snippet document. Returns editable names, types, and their data.
     *
     * @param int|null $id Document ID
     * @param string|null $path Document path (alternative to id)
     */
    #[McpTool(name: 'pimcore_get_editables')]
    public function __invoke(?int $id = null, ?string $path = null): string
    {
        if ($id === null && $path === null) {
            return json_encode(['error' => 'Either "id" or "path" is required.']);
        }

        $doc = $id !== null ? Document::getById($id) : Document::getByPath($path);

        if (!$doc) {
            return json_encode(['error' => 'Document not found.']);
        }

        if (!$doc instanceof Document\PageSnippet) {
            return json_encode([
                'error' => 'Document is not a page or snippet. Only page/snippet documents have editables. This document is of type: ' . $doc->getType(),
            ]);
        }

        $editables = [];

        foreach ($doc->getEditables() as $editable) {
            $editables[] = [
                'name' => $editable->getName(),
                'type' => $editable->getType(),
                'data' => $editable->getDataForResource(),
            ];
        }

        return json_encode([
            'documentId' => $doc->getId(),
            'documentPath' => $doc->getRealFullPath(),
            'editableCount' => count($editables),
            'editables' => $editables,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
