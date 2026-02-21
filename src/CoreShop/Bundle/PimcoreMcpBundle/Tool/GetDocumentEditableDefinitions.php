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
use Pimcore\Extension\Document\Areabrick\AreabrickManagerInterface;
use Pimcore\Model\Document;

#[McpTool(name: 'pimcore_get_document_editable_definitions')]
class GetDocumentEditableDefinitions
{
    public function __construct(
        private readonly AreabrickManagerInterface $areabrickManager,
    ) {
    }

    /**
     * Get the available editable definitions and areabricks for a Pimcore page or snippet document.
     * This tells the AI what editables exist on the page (from the template) and which areabricks can be used in areablock editables.
     * Use this before setting editables to know what names and types are available.
     *
     * @param int|null $id Document ID
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

        if (!$doc instanceof Document\PageSnippet) {
            return json_encode([
                'error' => 'Document is not a page or snippet. Only page/snippet documents have editables. This document is of type: ' . $doc->getType(),
            ]);
        }

        $editables = [];

        foreach ($doc->getEditables() as $editable) {
            $editableData = [
                'name' => $editable->getName(),
                'type' => $editable->getType(),
            ];

            if ($editable instanceof Document\Editable\Areablock) {
                $editableData['allowedBricks'] = $editable->getConfig()['allowed'] ?? null;
                $editableData['disallowedBricks'] = $editable->getConfig()['disallowed'] ?? null;
            }

            if ($editable instanceof Document\Editable\Area) {
                $editableData['allowedBricks'] = $editable->getConfig()['allowed'] ?? null;
            }

            if ($editable instanceof Document\Editable\Select) {
                $editableData['options'] = $editable->getConfig()['store'] ?? $editable->getConfig()['options'] ?? null;
            }

            if ($editable instanceof Document\Editable\Multiselect) {
                $editableData['options'] = $editable->getConfig()['store'] ?? $editable->getConfig()['options'] ?? null;
            }

            $editables[] = $editableData;
        }

        $areabricks = [];

        foreach ($this->areabrickManager->getBricks() as $brickId => $brick) {
            $areabricks[] = [
                'id' => $brickId,
                'name' => $brick->getName(),
                'description' => $brick->getDescription(),
                'icon' => $brick->getIcon(),
                'hasTemplate' => $brick->hasTemplate(),
            ];
        }

        return json_encode([
            'documentId' => $doc->getId(),
            'documentPath' => $doc->getRealFullPath(),
            'controller' => $doc->getController(),
            'template' => $doc->getTemplate(),
            'editables' => $editables,
            'availableAreabricks' => $areabricks,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
