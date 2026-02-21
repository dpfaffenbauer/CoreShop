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

class SetEditable
{
    /**
     * Set or update an editable (content element) on a Pimcore page or snippet document.
     * Supports wysiwyg, input, textarea, numeric, checkbox, select, link, image, video, date, embed, relation, relations types.
     *
     * @param string $name Editable name as defined in the template
     * @param string $type Editable type: wysiwyg, input, textarea, numeric, checkbox, select, link, image, video, date, embed, relation, relations
     * @param string $data The data to set. For wysiwyg/input/textarea: HTML or text string. For image: asset ID as string. For checkbox: "1" or "0". For link: JSON string with path/text/title keys.
     * @param int|null $id Document ID
     * @param string|null $path Document path (alternative to id)
     */
    #[McpTool(name: 'pimcore_set_editable')]
    public function __invoke(
        string $name,
        string $type,
        string $data,
        ?int $id = null,
        ?string $path = null,
    ): string {
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

        $editable = $this->createEditable($type, $name, $data);

        if ($editable === null) {
            $supported = ['wysiwyg', 'input', 'textarea', 'numeric', 'checkbox', 'select', 'link', 'image', 'video', 'date', 'embed', 'relation', 'relations'];

            return json_encode([
                'error' => 'Unsupported editable type: ' . $type,
                'supportedTypes' => $supported,
            ]);
        }

        $doc->setEditable($editable);
        $doc->save();

        return json_encode([
            'success' => true,
            'documentId' => $doc->getId(),
            'documentPath' => $doc->getRealFullPath(),
            'editableName' => $name,
            'editableType' => $type,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    private function createEditable(string $type, string $name, string $data): ?Document\Editable
    {
        $editable = match ($type) {
            'wysiwyg' => new Document\Editable\Wysiwyg(),
            'input' => new Document\Editable\Input(),
            'textarea' => new Document\Editable\Textarea(),
            'numeric' => new Document\Editable\Numeric(),
            'checkbox' => new Document\Editable\Checkbox(),
            'select' => new Document\Editable\Select(),
            'link' => new Document\Editable\Link(),
            'image' => new Document\Editable\Image(),
            'video' => new Document\Editable\Video(),
            'date' => new Document\Editable\Date(),
            'embed' => new Document\Editable\Embed(),
            'relation' => new Document\Editable\Relation(),
            'relations' => new Document\Editable\Relations(),
            default => null,
        };

        if ($editable === null) {
            return null;
        }

        $editable->setName($name);
        $editable->setDataFromResource($data);

        return $editable;
    }
}
