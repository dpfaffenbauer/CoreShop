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

#[McpTool(name: 'pimcore_get_document')]
class GetDocument
{
    /**
     * Get a single Pimcore document by ID or path, including all its properties and editables.
     *
     * @param int|null $id Document ID
     * @param string|null $path Document path (alternative to id). Example: "/en/my-page"
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

        $data = [
            'id' => $doc->getId(),
            'key' => $doc->getKey(),
            'path' => $doc->getRealFullPath(),
            'type' => $doc->getType(),
            'published' => $doc->getPublished(),
            'index' => $doc->getIndex(),
            'parentId' => $doc->getParentId(),
            'creationDate' => $doc->getCreationDate(),
            'modificationDate' => $doc->getModificationDate(),
            'userOwner' => $doc->getUserOwner(),
            'userModification' => $doc->getUserModification(),
            'hasChildren' => $doc->hasChildren(),
        ];

        // Properties
        $properties = [];

        foreach ($doc->getProperties() as $name => $property) {
            $properties[$name] = [
                'type' => $property->getType(),
                'data' => (string) $property->getData(),
                'inheritable' => $property->getInheritable(),
            ];
        }

        $data['properties'] = $properties;

        if ($doc instanceof Document\PageSnippet) {
            $data['controller'] = $doc->getController();
            $data['template'] = $doc->getTemplate();

            $editables = [];

            foreach ($doc->getEditables() as $editable) {
                $editables[$editable->getName()] = [
                    'name' => $editable->getName(),
                    'type' => $editable->getType(),
                    'data' => $editable->getDataForResource(),
                ];
            }

            $data['editables'] = $editables;
        }

        if ($doc instanceof Document\Page) {
            $data['title'] = $doc->getTitle();
            $data['description'] = $doc->getDescription();
            $data['prettyUrl'] = $doc->getPrettyUrl();
        }

        if ($doc instanceof Document\Link) {
            $data['href'] = $doc->getHref();
            $data['linktype'] = $doc->getLinktype();
            $data['direct'] = $doc->getDirect();
            $data['internalType'] = $doc->getInternalType();
            $data['internal'] = $doc->getInternal();
        }

        if ($doc instanceof Document\Email) {
            $data['subject'] = $doc->getSubject();
            $data['from'] = $doc->getFrom();
            $data['to'] = $doc->getTo();
            $data['cc'] = $doc->getCc();
            $data['bcc'] = $doc->getBcc();
        }

        return json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
