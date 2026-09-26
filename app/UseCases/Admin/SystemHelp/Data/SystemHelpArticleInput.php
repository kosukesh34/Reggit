<?php

namespace App\UseCases\Admin\SystemHelp\Data;

class SystemHelpArticleInput
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $category,
        public readonly string $body,
        public readonly int $sortOrder,
        public readonly bool $isPublished,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated, bool $isPublished): self
    {
        return new self(
            slug: $validated['slug'],
            title: $validated['title'],
            summary: $validated['summary'],
            category: $validated['category'],
            body: $validated['body'],
            sortOrder: (int) ($validated['sort_order'] ?? 0),
            isPublished: $isPublished,
        );
    }

    /** @return array{slug: string, title: string, summary: string, category: string, body: string, sort_order: int, is_published: bool} */
    public function toAttributes(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'category' => $this->category,
            'body' => $this->body,
            'sort_order' => $this->sortOrder,
            'is_published' => $this->isPublished,
        ];
    }
}
