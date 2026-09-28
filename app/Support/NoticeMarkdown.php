<?php

namespace App\Support;

use Illuminate\Support\Str;

final class NoticeMarkdown
{
    public function toHtml(string $markdown): string
    {
        $html = Str::markdown($markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        // Markdown has already escaped HTML. Only a six-digit hex color is accepted.
        return preg_replace('/\[color=(#[0-9a-fA-F]{6})\]((?:[^<>]|<\/?(?:strong|em|code|del)>)*?)\[\/color\]/u', '<span style="color: $1">$2</span>', $html) ?? $html;
    }
}
