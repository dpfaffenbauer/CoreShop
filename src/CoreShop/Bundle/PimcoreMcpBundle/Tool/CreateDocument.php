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

#[McpTool(name: 'pimcore_create_document')]
class CreateDocument
{
    /**
     * Create a new Pimcore document (page, snippet, folder, link, email, hardlink).
     * Can also copy an existing document by providing sourceId or sourcePath.
     *
     * @param string $key Document key (URL slug). Example: "my-new-page"
     * @param string $type Document type: page, snippet, folder, link, email, hardlink
     * @param int|null $parentId Parent document ID. Defaults to 1 (root).
     * @param string|null $parentPath Parent document path (alternative to parentId). Example: "/en"
     * @param string|null $title Page title (page type only)
     * @param string|null $description Page meta description (page type only)
     * @param string|null $controller Controller action. Example: "App\Controller\DefaultController::defaultAction"
     * @param string|null $template Template path. Example: "default/default.html.twig"
     * @param bool $published Whether the document should be published
     * @param string|null $href URL for link documents
     * @param string|null $subject Email subject (email type only)
     * @param string|null $from Sender address (email type only)
     * @param string|null $to Recipient address (email type only)
     * @param int|null $sourceId Source document ID to copy from. When provided, creates a copy instead of an empty document.
     * @param string|null $sourcePath Source document path to copy from (alternative to sourceId). Example: "/en/my-page"
     * @param bool $recursive When copying, whether to include child documents. Defaults to true.
     */
    public function __invoke(
        string $key,
        string $type,
        ?int $parentId = null,
        ?string $parentPath = null,
        ?string $title = null,
        ?string $description = null,
        ?string $controller = null,
        ?string $template = null,
        bool $published = false,
        ?string $href = null,
        ?string $subject = null,
        ?string $from = null,
        ?string $to = null,
        ?int $sourceId = null,
        ?string $sourcePath = null,
        bool $recursive = true,
    ): string {
        if ($parentPath !== null) {
            $parent = Document::getByPath($parentPath);

            if (!$parent) {
                return json_encode(['error' => 'Parent document not found at path: ' . $parentPath]);
            }

            $parentId = $parent->getId();
        }

        $parentId = $parentId ?? 1;

        if ($sourceId !== null || $sourcePath !== null) {
            return $this->copyDocument($sourceId, $sourcePath, $parentId, $key, $recursive);
        }

        $doc = $this->createDocumentByType($type);

        if ($doc === null) {
            return json_encode(['error' => 'Unsupported document type: ' . $type]);
        }

        $doc->setKey($key);
        $doc->setParentId($parentId);
        $doc->setPublished($published);

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

    private function copyDocument(?int $sourceId, ?string $sourcePath, int $parentId, string $key, bool $recursive): string
    {
        $source = $sourceId !== null ? Document::getById($sourceId) : Document::getByPath($sourcePath);

        if (!$source) {
            return json_encode(['error' => 'Source document not found.']);
        }

        $targetParent = Document::getById($parentId);

        if (!$targetParent) {
            return json_encode(['error' => 'Target parent document not found.']);
        }

        $service = new Document\Service();

        if ($recursive) {
            $newDoc = $service->copyRecursive($targetParent, $source);
        } else {
            $newDoc = $service->copyAsChild($targetParent, $source);
        }

        $newDoc->setKey($key);
        $newDoc->save();

        return json_encode([
            'success' => true,
            'id' => $newDoc->getId(),
            'path' => $newDoc->getRealFullPath(),
            'type' => $newDoc->getType(),
            'copiedFrom' => $source->getRealFullPath(),
            'recursive' => $recursive,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    private function createDocumentByType(string $type): ?Document
    {
        return match ($type) {
            'page' => new Document\Page(),
            'snippet' => new Document\Snippet(),
            'folder' => new Document\Folder(),
            'link' => new Document\Link(),
            'email' => new Document\Email(),
            'hardlink' => new Document\Hardlink(),
            default => null,
        };
    }
}
