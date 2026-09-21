<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Ripulisce l'HTML che arriva dall'editor del pannello (flux:editor) prima di
 * salvarlo: quel corpo finisce sul sito con {!! !!}, quindi passa solo una
 * lista chiusa di tag e attributi. Tutto il resto viene tolto; i tag non
 * ammessi ma innocui (span, div…) lasciano il loro testo, quelli pericolosi
 * (script, style, iframe…) spariscono con il contenuto.
 *
 * Gli id sui titoli e il `type` delle liste ordinate restano: li producono i
 * convertitori dei documenti legali (docx-to-html.py) e il loro HTML deve
 * attraversare il filtro senza perdere niente.
 */
final class HtmlSanitizer
{
    /** @var array<string, list<string>> tag => attributi ammessi */
    private const ALLOWED = [
        'p' => [],
        'h2' => ['id'],
        'h3' => ['id'],
        'strong' => [],
        'em' => [],
        'u' => [],
        's' => [],
        'ul' => [],
        'ol' => ['type'],
        'li' => [],
        'a' => ['href', 'target'],
        'br' => [],
        'blockquote' => [],
    ];

    /** Equivalenti che l'editor o un copia-incolla possono produrre. */
    private const RENAME = [
        'b' => 'strong',
        'i' => 'em',
        'strike' => 's',
        'del' => 's',
        'h1' => 'h2',
        'h4' => 'h3',
        'h5' => 'h3',
        'h6' => 'h3',
    ];

    /** Tolti con tutto il contenuto. */
    private const DROP = [
        'script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript',
        'svg', 'math', 'head', 'title', 'form', 'select', 'textarea', 'button', 'input',
    ];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        // Il prologo xml dice a libxml che il testo è UTF-8 (di default legge Latin-1).
        $document->loadHTML('<?xml encoding="UTF-8"?><html><body>'.$html.'</body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        $clean = $body === null ? '' : trim(self::children($body));

        // "<p></p>" è l'editor vuoto: per chi salva vale come "niente".
        return trim(strip_tags($clean)) === '' ? '' : $clean;
    }

    private static function children(DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= self::node($child);
        }

        return $html;
    }

    private static function node(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->textContent, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if (! $node instanceof DOMElement) {
            return ''; // commenti, istruzioni di elaborazione
        }

        $tag = strtolower($node->nodeName);
        $tag = self::RENAME[$tag] ?? $tag;

        if (in_array($tag, self::DROP, true)) {
            return '';
        }

        if (! array_key_exists($tag, self::ALLOWED)) {
            return self::children($node);
        }

        if ($tag === 'br') {
            return '<br>';
        }

        $attributes = self::attributes($node, $tag);

        // Un link senza un indirizzo accettabile resta testo.
        if ($tag === 'a' && ! str_contains($attributes, 'href=')) {
            return self::children($node);
        }

        return "<{$tag}{$attributes}>".self::children($node)."</{$tag}>";
    }

    private static function attributes(DOMElement $node, string $tag): string
    {
        $html = '';

        foreach (self::ALLOWED[$tag] as $name) {
            if (! $node->hasAttribute($name)) {
                continue;
            }

            $value = trim($node->getAttribute($name));

            $accepted = match ($name) {
                'href' => (bool) preg_match('~^(https?://|mailto:|tel:|/|#)~i', $value),
                'id' => (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $value),
                'type' => in_array($value, ['1', 'a', 'A', 'i', 'I'], true),
                'target' => $value === '_blank',
                default => false,
            };

            if ($accepted) {
                $html .= ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
            }
        }

        // Una scheda nuova non deve poter pilotare la pagina che l'ha aperta.
        if ($tag === 'a' && str_contains($html, 'target=')) {
            $html .= ' rel="noopener noreferrer"';
        }

        return $html;
    }
}
