<?php

namespace App\Http\Requests\Admin;

use App\Support\HelpManual;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemHelpArticleRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(HelpManual $helpManual): array
    {
        $reservedSlugs = array_map(
            static fn (array $article): string => $article['slug'],
            $helpManual->articles(),
        );
        $uniqueSlug = Rule::unique('help_articles', 'slug');
        $articleId = $this->route('id');
        if ($articleId !== null) {
            $uniqueSlug->ignore((int) $articleId);
        }

        return [
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn($reservedSlugs), $uniqueSlug],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:500'],
            'category' => ['required', Rule::in(['guide', 'faq'])],
            'body' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
