<?php

namespace BookStack\References;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\Entity;
use BookStack\Entities\Models\Page;
use BookStack\Entities\Repos\RevisionRepo;
use DOMDocument;
use DOMXPath;

class ReferenceUpdater
{
    protected ReferenceFetcher $referenceFetcher;
    protected RevisionRepo $revisionRepo;

    public function __construct(ReferenceFetcher $referenceFetcher, RevisionRepo $revisionRepo)
    {
        $this->referenceFetcher = $referenceFetcher;
        $this->revisionRepo = $revisionRepo;
    }

    public function updateEntityPageReferences(Entity $entity, string $oldLink)
    {
        $references = $this->getReferencesToUpdate($entity);
        $newLink = $entity->getUrl();

        /** @var Reference $reference */
        foreach ($references as $reference) {
            /** @var Page $page */
            $page = $reference->from;
            $this->updateReferencesWithinPage($page, $oldLink, $newLink);
        }
    }

    /**
     * @return Reference[]
     */
    protected function getReferencesToUpdate(Entity $entity): array
    {
        /** @var Reference[] $references */
        $references = $this->referenceFetcher->getPageReferencesToEntity($entity)->values()->all();

        if ($entity instanceof Book) {
            $pages = $entity->pages()->get(['id']);
            $chapters = $entity->chapters()->get(['id']);
            $children = $pages->concat($chapters);
            foreach ($children as $bookChild) {
                $childRefs = $this->referenceFetcher->getPageReferencesToEntity($bookChild)->values()->all();
                array_push($references, ...$childRefs);
            }
        }

        $deduped = [];
        foreach ($references as $reference) {
            $key = $reference->from_id . ':' . $reference->from_type;
            $deduped[$key] = $reference;
        }

        return array_values($deduped);
    }

    protected function updateReferencesWithinPage(Page $page, string $oldLink, string $newLink)
    {
        $page = (clone $page)->refresh();
        $html = $this->updateLinksInHtml($page->html, $oldLink, $newLink);
        $markdown = $this->updateLinksInMarkdown($page->markdown, $oldLink, $newLink);

        $page->html = $html;
        $page->markdown = $markdown;
        $page->revision_count++;
        $page->save();

        $summary = trans('entities.pages_references_update_revision');
        $this->revisionRepo->storeNewForPage($page, $summary);
    }

    protected function updateLinksInMarkdown(string $markdown, string $oldLink, string $newLink): string
    {
        if (empty($markdown)) {
            return $markdown;
        }

        $commonLinkRegex = '/(\[.*?\]\()' . preg_quote($oldLink, '/') . '(.*?\))/i';
        $markdown = preg_replace($commonLinkRegex, '$1' . $newLink . '$2', $markdown);

        $referenceLinkRegex = '/(\[.*?\]:\s?)' . preg_quote($oldLink, '/') . '(.*?)($|\s)/i';
        $markdown = preg_replace($referenceLinkRegex, '$1' . $newLink . '$2$3', $markdown);

        return $markdown;
    }

    protected function updateLinksInHtml(string $html, string $oldLink, string $newLink): string
    {
        if (empty($html)) {
            return $html;
        }

        $html = '<body>' . $html . '</body>';
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $xPath = new DOMXPath($doc);
        $anchors = $xPath->query('//a[@href]');

        /** @var \DOMElement $anchor */
        foreach ($anchors as $anchor) {
            $link = $anchor->getAttribute('href');
            $updated = str_ireplace($oldLink, $newLink, $link);
            $anchor->setAttribute('href', $updated);
        }

        $html = '';
        $topElems = $doc->documentElement->childNodes->item(0)->childNodes;
        foreach ($topElems as $child) {
            $html .= $doc->saveHTML($child);
        }

        return $html;
    }
}
