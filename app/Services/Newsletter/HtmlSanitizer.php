<?php

namespace App\Services\Newsletter;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Ripulisce l'HTML del flux:editor prima di salvarlo: il testo di una
 * campagna finisce identico in migliaia di caselle, e un tag o un attributo
 * sfuggito all'editor (incolla da Word, script, stili) non deve arrivarci.
 *
 * Allowlist minima di ciò che l'editor produce. I tag sconosciuti vengono
 * "scartati ma svuotati nel genitore" (il testo resta), quelli pericolosi
 * spariscono con il contenuto. Degli attributi sopravvive solo href sui
 * link, e solo http(s) o mailto.
 */
class HtmlSanitizer
{
    /** @var array<int, string> */
    private const ALLOWED = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'a', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'blockquote', 'hr', 'code', 'pre', 'mark',
    ];

    /** Tolti con tutto il contenuto: il testo dentro non è testo per il lettore. */
    private const DROPPED = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'head', 'title', 'meta', 'link', 'svg', 'math', 'form', 'noscript'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="nl-root">'.$html.'</div></body></html>',
            LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('nl-root');

        if ($root === null) {
            return '';
        }

        self::cleanChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $document->saveHTML($child);
        }

        $out = trim($out);

        // Un editor vuoto salva "<p></p>": non è un testo.
        return trim(strip_tags($out)) === '' && ! str_contains($out, '<hr') ? '' : $out;
    }

    /** Testo semplice per la parte text/plain della mail. */
    public static function toText(?string $html): string
    {
        $html = (string) $html;
        $html = preg_replace('#<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 ($1)', $html) ?? $html;
        $html = preg_replace('#<br\s*/?>#i', "\n", $html) ?? $html;
        $html = preg_replace('#</(p|h1|h2|h3|blockquote|pre|ul|ol)>#i', "\n\n", $html) ?? $html;
        $html = preg_replace('#<li[^>]*>#i', '- ', $html) ?? $html;
        $html = preg_replace('#</li>#i', "\n", $html) ?? $html;

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                // Commenti, istruzioni, CDATA.
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROPPED, true)) {
                $node->removeChild($child);

                continue;
            }

            self::cleanChildren($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $href = $tag === 'a' ? trim($element->getAttribute('href')) : '';

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->nodeName);
        }

        if ($tag !== 'a') {
            return;
        }

        if (preg_match('#^(https?://|mailto:)#i', $href) === 1) {
            $element->setAttribute('href', $href);
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener');
        }
    }
}
