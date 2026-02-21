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

#[McpTool(name: 'pimcore_update_document')]
class UpdateDocument
{
    /**
     * Update properties of an existing Pimcore document (key, title, description, controller, template, published state, etc.).
     *
     * @param int|null $id Document ID to update
     * @param string|null $path Document path (alternative to id)
     * @param string|null $key New document key (URL slug)
     * @param int|null $newParentId New parent document ID (moves the document)
     * @param string|null $title New page title (page type only)
     * @param string|null $description New meta description (page type only)
     * @param string|null $controller New controller action
     * @param string|null $template New template path
     * @param bool|null $published Set published state
     * @param int|null $index Set sort index
     * @param string|null $href New URL for link documents
     * @param string|null $subject New email subject (email type only)
     * @param string|null $from New sender address (email type only)
     * @param string|null $to New recipient address (email type only)
     */
    public function __invoke(
        ?int $id = null,
        ?string $path = null,
        ?string $key = null,
        ?int $newParentId = null,
        ?string $title = null,
        ?string $description = null,
        ?string $controller = null,
        ?string $template = null,
        ?bool $published = null,
        ?int $index = null,
        ?string $href = null,
        ?string $subject = null,
        ?string $from = null,
        ?string $to = null,
    ): string {
        if ($id === null && $path === null) {
            return json_encode(['error' => 'Either "id" or "path" is required.']);
        }

        $doc = $id !== null ? Document::getById($id) : Document::getByPath($path);

        if (!$doc) {
            return json_encode(['error' => 'Document not found.']);
        }

        if ($key !== null) {
            $doc->setKey($key);
        }

        if ($newParentId !== null) {
            $doc->setParentId($newParentId);
        }

        if ($published !== null) {
            $doc->setPublished($published);
        }

        if ($index !== null) {
            $doc->setIndex($index);
        }

        if ($doc instanceof Document\PageSnippet) {
            if ($controller !== null) {
                $doc->setController($controller);
            }

            if ($template !== null) {
                $doc->setTemplate($template);
            }
        }

        if ($doc instanceof Document\Page) {
            if ($title !== null) {
                $doc->setTitle($title);
            }

            if ($description !== null) {
                $doc->setDescription($description);
            }
        }

        if ($doc instanceof Document\Link && $href !== null) {
            $doc->setDirect($href);
            $doc->setLinktype('direct');
        }

        if ($doc instanceof Document\Email) {
            if ($subject !== null) {
                $doc->setSubject($subject);
            }

            if ($from !== null) {
                $doc->setFrom($from);
            }

            if ($to !== null) {
                $doc->setTo($to);
            }
        }

        $doc->save();

        return json_encode([
            'success' => true,
            'id' => $doc->getId(),
            'path' => $doc->getRealFullPath(),
            'type' => $doc->getType(),
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }
}
