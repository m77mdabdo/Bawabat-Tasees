<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Country;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasSlugTest extends TestCase
{
    use RefreshDatabase;

    private function makeArticle(string $title): Article
    {
        return Article::create([
            'title' => ['ar' => $title],
            'body' => ['ar' => '<p>نص</p>'],
            'is_published' => true,
        ]);
    }

    private function makeService(string $name): Service
    {
        return Service::create([
            'name' => ['ar' => $name],
            'summary' => ['ar' => 'ملخص'],
            'body' => ['ar' => '<p>نص</p>'],
            'requirements' => ['ar' => 'متطلبات'],
            'process' => ['ar' => 'خطوات'],
            'is_active' => true,
        ]);
    }

    /**
     * The regression this whole trait change exists for: the DB unique
     * index on `slug` counts soft-deleted rows, so a slug check that runs
     * through the default global scope thinks the slug is free and the
     * insert dies with a UniqueConstraintViolationException.
     */
    public function test_slug_does_not_collide_with_a_soft_deleted_article(): void
    {
        $first = $this->makeArticle('مقال مكرر');
        $first->delete();

        $this->assertSoftDeleted($first);

        $second = $this->makeArticle('مقال مكرر');

        $this->assertNotSame($first->slug, $second->slug);
        $this->assertSame($first->slug.'-2', $second->slug);
    }

    public function test_slug_does_not_collide_with_a_soft_deleted_service(): void
    {
        $first = $this->makeService('خدمة مكررة');
        $first->delete();

        $second = $this->makeService('خدمة مكررة');

        $this->assertNotSame($first->slug, $second->slug);
        $this->assertSame($first->slug.'-2', $second->slug);
    }

    public function test_slug_still_increments_against_a_live_row(): void
    {
        $first = $this->makeArticle('مقال حي');
        $second = $this->makeArticle('مقال حي');
        $third = $this->makeArticle('مقال حي');

        $this->assertSame($first->slug.'-2', $second->slug);
        $this->assertSame($first->slug.'-3', $third->slug);
    }

    /**
     * Country uses HasSlug WITHOUT SoftDeletes — withTrashed() would throw
     * on it, so the trait must only reach for it when the model actually
     * soft-deletes.
     */
    public function test_slug_generation_works_on_a_model_without_soft_deletes(): void
    {
        $first = Country::create(['name' => ['ar' => 'دولة مكررة'], 'is_active' => true]);
        $second = Country::create(['name' => ['ar' => 'دولة مكررة'], 'is_active' => true]);

        $this->assertSame($first->slug.'-2', $second->slug);
    }

    public function test_an_explicitly_provided_slug_is_never_overwritten(): void
    {
        $article = Article::create([
            'slug' => 'slug-mkhss',
            'title' => ['ar' => 'عنوان مختلف'],
            'body' => ['ar' => '<p>نص</p>'],
            'is_published' => true,
        ]);

        $this->assertSame('slug-mkhss', $article->slug);
    }
}
