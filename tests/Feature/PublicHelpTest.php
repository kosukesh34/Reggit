<?php

namespace Tests\Feature;

use App\Models\HelpArticle;
use Database\Seeders\HelpArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_browse_all_published_guides_from_the_landing_page(): void
    {
        $this->get('/')->assertOk()->assertSee(route('help.index'), false);
        $this->get('/help')->assertOk()->assertViewIs('help.index')
            ->assertViewHas('articles', fn (array $articles): bool => count($articles) === count((new HelpArticleSeeder)->articles()))
            ->assertSee(route('help.show', ['slug' => 'existing-member-link']), false);

        foreach ((new HelpArticleSeeder)->articles() as $article) {
            $this->get('/help/'.$article['slug'])->assertOk()->assertSee($article['title'])
                ->assertSee('class="help-shot"', false);
        }
        $this->assertGuest('admin');
    }

    public function test_guests_can_search_body_text_and_follow_public_related_guides(): void
    {
        $this->get('/help?q='.urlencode('合算'))->assertOk()
            ->assertSee('スマレジの既存会員をアプリに連携する')
            ->assertDontSee('クーポンを作成・編集して利用状況を確認する');
        $this->get('/help/existing-member-link')->assertOk()
            ->assertSee('href="/help/feature-overview"', false)
            ->assertSee('href="/help"', false)
            ->assertSee('href="/demo/liff#/more/link"', false)
            ->assertDontSee('{project_key}');
    }

    public function test_unpublished_and_unknown_articles_are_not_public(): void
    {
        HelpArticle::where('slug', 'existing-member-link')->update(['is_published' => false]);
        $this->get('/help')->assertOk()->assertDontSee('スマレジの既存会員をアプリに連携する');
        $this->get('/help/existing-member-link')->assertNotFound();
        $this->get('/help/unknown-article')->assertNotFound();
    }

    public function test_public_search_rejects_array_input_and_escapes_html(): void
    {
        $this->getJson('/help?q[]=invalid')->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->get('/help?q='.urlencode('<script>alert(1)</script>'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_public_article_escapes_stored_titles_summaries_and_body(): void
    {
        HelpArticle::create([
            'slug' => 'escaped-guide', 'title' => '<script>alert(1)</script>',
            'summary' => '<script>alert(1)</script>', 'body' => '<script>alert(1)</script>',
            'category' => 'guide', 'is_published' => true,
        ]);
        $this->get('/help/escaped-guide')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
