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

class ListDocuments
{
    /**
     * List Pimcore documents. Supports filtering by parent ID/path, document type, and pagination.
     *
     * @param int|null $parentId Parent document ID to list children of. Defaults to 1 (root).
     * @param string|null $parentPath Parent document path (alternative to parentId). Example: "/"
     * @param string|null $type Filter by document type: page, snippet, link, hardlink, folder, email
     * @param int $limit Maximum number of documents to return
     * @param int $offset Offset for pagination
     * @param bool $unpublished Include unpublished documents
     */
    #[McpTool(name: 'pimcore_list_documents')]
    public function __invoke(
        ?int $parentId = null,
        ?string $parentPath = null,
        ?string $type = null,
        int $limit = 50,
        int $offset = 0,
        bool $unpublished = false,
    ): string {
        if ($parentPath !== null) {
            $parent = Document::getByPath($parentPath);

            if (!$parent) {
                return json_encode(['error' => 'Parent document not found at path: ' . $parentPath]);
            }

            $parentId = $parent->getId();
        }

        $parentId = $parentId ?? 1;

        $listing = new Document\Listing();

        $conditions = ['parentId = ?'];
        $conditionParams = [$parentId];

        if ($type !== null) {
            $conditions[] = 'type = ?';
            $conditionParams[] = $type;
        }

        $listing->setCondition(implode(' AND ', $conditions), $conditionParams);
        $listing->setLimit($limit);
        $listing->setOffset($offset);
        $listing->setOrderKey('index');
        $listing->setOrder('ASC');

        if ($unpublished) {
            $listing->setUnpublished(true);
        }

        $documents = [];

        foreach ($listing as $doc) {
            $data = [
                'id' => $doc->getId(),
                'key' => $doc->getKey(),
                'path' => $doc->getRealFullPath(),
                'type' => $doc->getType(),
                'published' => $doc->getPublished(),
                'index' => $doc->getIndex(),
                'creationDate' => $doc->getCreationDate(),
                'modificationDate' => $doc->getModificationDate(),
            ];

            if ($doc instanceof Document\Page) {
                $data['title'] = $doc->getTitle();
                $data['description'] = $doc->getDescription();
                $data['controller'] = $doc->getController();
                $data['template'] = $doc->getTemplate();
            }

            if ($doc instanceof Document\Link) {
                $data['href'] = $doc->getHref();
            }

            $documents[] = $data;
        }

        return json_encode([
            'total' => $listing->getTotalCount(),
            'limit' => $limit,
            'offset' => $offset,
            'documents' => $documents,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
