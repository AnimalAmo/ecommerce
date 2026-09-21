<?php

namespace Tests\Feature\Newsletter;

use App\Services\Newsletter\HtmlSanitizer;
use Tests\TestCase;

/** Il testo di una campagna finisce identico in migliaia di caselle: solo ciò che produce l'editor. */
class HtmlSanitizerTest extends TestCase
{
    public function test_it_keeps_what_the_editor_produces(): void
    {
        $html = '<h2>Titolo</h2><p>Uno <strong>due</strong> <em>tre</em></p><ul><li>a</li></ul><blockquote><p>cit</p></blockquote>';

        $this->assertSame($html, HtmlSanitizer::clean($html));
    }

    public function test_it_drops_scripts_styles_and_attributes(): void
    {
        $clean = HtmlSanitizer::clean('<p style="color:red" onclick="x()">Ciao<script>alert(1)</script></p><style>p{}</style><iframe src="https://x"></iframe>');

        $this->assertSame('<p>Ciao</p>', $clean);
    }

    public function test_links_keep_only_safe_targets(): void
    {
        $this->assertSame(
            '<p><a href="https://animalamo.it" target="_blank" rel="noopener">sito</a> <a>js</a> <a href="mailto:info@animalamo.it" target="_blank" rel="noopener">mail</a></p>',
            HtmlSanitizer::clean('<p><a href="https://animalamo.it" class="x">sito</a> <a href="javascript:alert(1)">js</a> <a href="mailto:info@animalamo.it">mail</a></p>'),
        );
    }

    public function test_unknown_tags_are_unwrapped_and_their_text_kept(): void
    {
        $this->assertSame('<p>Testo incollato da Word</p>', HtmlSanitizer::clean('<p><span class="MsoNormal"><font face="Arial">Testo incollato da Word</font></span></p>'));
    }

    public function test_an_empty_editor_is_an_empty_text(): void
    {
        $this->assertSame('', HtmlSanitizer::clean('<p></p>'));
        $this->assertSame('', HtmlSanitizer::clean('   '));
        $this->assertSame('', HtmlSanitizer::clean(null));
    }

    public function test_the_plain_text_version_keeps_links_and_paragraphs(): void
    {
        $this->assertSame(
            "Titolo\n\nVai al sito (https://animalamo.it).\n\n- uno\n- due",
            HtmlSanitizer::toText('<h2>Titolo</h2><p>Vai al <a href="https://animalamo.it">sito</a>.</p><ul><li>uno</li><li>due</li></ul>'),
        );
    }
}
