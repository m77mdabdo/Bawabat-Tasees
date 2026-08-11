<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\PageContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * PageContentSeeder used to call updateOrCreate, which made `db:seed` a
 * destructive operation on a live site: anyone reseeding to pick up an
 * unrelated change would silently overwrite legal copy an admin had refined
 * in the dashboard.
 *
 * It is now create-only, and content changes to an existing install ship as
 * data migrations instead. These tests hold both halves of that: the seeder
 * never overwrites, and the migration still delivers.
 */
class PageContentSeedingTest extends TestCase
{
    use RefreshDatabase;

    private function disclosureMigration(): object
    {
        return require database_path('migrations/2026_08_11_140000_add_visitor_session_privacy_disclosure.php');
    }

    // -----------------------------------------------------------------
    // The seeder bootstraps
    // -----------------------------------------------------------------

    public function test_a_fresh_install_gets_the_full_starter_copy(): void
    {
        $this->seed(PageContentSeeder::class);

        $this->assertSame(6, Page::count());

        $privacy = Page::where('slug', 'privacy-policy')->sole();

        $this->assertStringContainsString('<h2>', $privacy->getTranslation('body', 'ar', false));
        $this->assertStringContainsString('تسجيل جلسات الزوار', $privacy->getTranslation('body', 'ar', false));
    }

    public function test_pages_with_sections_get_them(): void
    {
        $this->seed(PageContentSeeder::class);

        $this->assertGreaterThan(0, Page::where('slug', 'why-invest-saudi-arabia')->sole()->sections()->count());
    }

    // -----------------------------------------------------------------
    // The seeder never overwrites
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function editablePages(): array
    {
        return [
            'privacy policy' => ['privacy-policy'],
            'terms' => ['terms-and-conditions'],
            'about' => ['about'],
        ];
    }

    #[DataProvider('editablePages')]
    public function test_reseeding_does_not_overwrite_admin_edited_copy(string $slug): void
    {
        $this->seed(PageContentSeeder::class);

        $page = Page::where('slug', $slug)->sole();
        $page->setTranslation('body', 'ar', '<p>نص كتبه المسؤول بنفسه.</p>');
        $page->setTranslation('title', 'ar', 'عنوان معدّل');
        $page->save();

        $this->seed(PageContentSeeder::class);

        $page->refresh();

        $this->assertSame('<p>نص كتبه المسؤول بنفسه.</p>', $page->getTranslation('body', 'ar', false));
        $this->assertSame('عنوان معدّل', $page->getTranslation('title', 'ar', false));
    }

    public function test_reseeding_does_not_unpublish_or_republish_a_page(): void
    {
        $this->seed(PageContentSeeder::class);

        $page = Page::where('slug', 'about')->sole();
        $page->update(['is_published' => false]);

        $this->seed(PageContentSeeder::class);

        $this->assertFalse($page->fresh()->is_published, 'A deliberately hidden page must stay hidden.');
    }

    /**
     * The old replaceSections() deleted every section and recreated it, so
     * reseeding threw away edits AND reassigned every primary key.
     */
    public function test_reseeding_preserves_edited_sections_and_their_ids(): void
    {
        $this->seed(PageContentSeeder::class);

        $page = Page::where('slug', 'why-invest-saudi-arabia')->sole();
        $section = $page->sections()->orderBy('sort_order')->first();
        $originalId = $section->id;

        $section->update(['content' => ['title' => ['ar' => 'عنوان معدّل'], 'description' => ['ar' => 'وصف معدّل'], 'icon' => 'star']]);
        $countBefore = $page->sections()->count();

        $this->seed(PageContentSeeder::class);

        $this->assertSame($countBefore, $page->sections()->count(), 'Sections must not be duplicated.');
        $this->assertSame($originalId, $page->sections()->orderBy('sort_order')->first()->id, 'Section ids must be stable.');
        // PageSection keeps one `content` JSON blob with its own accessors
        // rather than spatie/laravel-translatable, so this reads the blob.
        $this->assertSame('عنوان معدّل', $page->sections()->find($originalId)->content['title']['ar']);
    }

    public function test_reseeding_does_not_create_duplicate_pages(): void
    {
        $this->seed(PageContentSeeder::class);
        $this->seed(PageContentSeeder::class);
        $this->seed(PageContentSeeder::class);

        $this->assertSame(6, Page::count());
    }

    // -----------------------------------------------------------------
    // The migration delivers what the seeder no longer can
    // -----------------------------------------------------------------

    /**
     * The case this whole change exists for: an install that predates the
     * disclosure, whose policy the admin has already edited.
     */
    public function test_the_migration_appends_the_disclosure_without_touching_the_rest(): void
    {
        $this->seed(PageContentSeeder::class);

        $page = Page::where('slug', 'privacy-policy')->sole();
        $page->setTranslation('body', 'ar', '<p>مقدمة كتبها المسؤول.</p>');
        $page->setTranslation('body', 'en', '<p>An intro the admin wrote.</p>');
        $page->save();

        $this->disclosureMigration()->up();

        $page->refresh();

        foreach (['ar' => 'تسجيل جلسات الزوار', 'en' => 'Visitor Session Recording'] as $locale => $heading) {
            $body = $page->getTranslation('body', $locale, false);

            $this->assertStringContainsString($heading, $body, 'The disclosure was not delivered.');
        }

        $this->assertStringContainsString('مقدمة كتبها المسؤول.', $page->getTranslation('body', 'ar', false));
        $this->assertStringContainsString('An intro the admin wrote.', $page->getTranslation('body', 'en', false));
    }

    public function test_running_the_migration_twice_does_not_duplicate_the_section(): void
    {
        $this->seed(PageContentSeeder::class);

        $page = Page::where('slug', 'privacy-policy')->sole();
        $page->setTranslation('body', 'ar', '<p>مقدمة.</p>');
        $page->setTranslation('body', 'en', '<p>Intro.</p>');
        $page->save();

        $migration = $this->disclosureMigration();
        $migration->up();
        $migration->up();
        $migration->up();

        $body = $page->fresh()->getTranslation('body', 'en', false);

        $this->assertSame(1, substr_count($body, '<h2>Visitor Session Recording</h2>'));
    }

    /**
     * A fresh install already has the section from the seeder, so the
     * migration must recognise it and do nothing.
     */
    public function test_the_migration_is_a_no_op_on_a_freshly_seeded_install(): void
    {
        $this->seed(PageContentSeeder::class);

        $before = Page::where('slug', 'privacy-policy')->sole()->getTranslation('body', 'ar', false);

        $this->disclosureMigration()->up();

        $this->assertSame($before, Page::where('slug', 'privacy-policy')->sole()->getTranslation('body', 'ar', false));
    }

    /**
     * Appending an English-only block to a page with no English translation
     * would leave /en/privacy-policy rendering a page that is otherwise
     * Arabic.
     */
    public function test_the_migration_leaves_an_untranslated_locale_untranslated(): void
    {
        Page::create([
            'slug' => 'privacy-policy',
            'is_published' => true,
            'title' => ['ar' => 'سياسة الخصوصية'],
            'body' => ['ar' => '<p>مقدمة.</p>'],
            'meta_title' => ['ar' => 'سياسة الخصوصية'],
            'meta_description' => ['ar' => 'سياسة الخصوصية'],
        ]);

        $this->disclosureMigration()->up();

        $page = Page::where('slug', 'privacy-policy')->sole();

        $this->assertStringContainsString('تسجيل جلسات الزوار', $page->getTranslation('body', 'ar', false));
        $this->assertEmpty($page->getTranslation('body', 'en', false));
    }

    public function test_the_migration_is_harmless_when_the_page_does_not_exist(): void
    {
        $this->assertSame(0, Page::count());

        $this->disclosureMigration()->up();

        $this->assertSame(0, Page::count());
    }

    public function test_the_migration_reverses_cleanly(): void
    {
        $this->seed(PageContentSeeder::class);

        $page = Page::where('slug', 'privacy-policy')->sole();
        $page->setTranslation('body', 'en', '<p>Intro.</p>');
        $page->save();

        $migration = $this->disclosureMigration();
        $migration->up();
        $migration->down();

        $body = $page->fresh()->getTranslation('body', 'en', false);

        $this->assertStringNotContainsString('Visitor Session Recording', $body);
        $this->assertSame('<p>Intro.</p>', $body, 'down() must remove exactly what up() added.');
    }

    /**
     * The seeder's copy and the migration's copy have to stay identical, or
     * a fresh install and a migrated one end up with different policies.
     */
    public function test_the_seeded_and_migrated_disclosures_are_the_same_text(): void
    {
        $this->seed(PageContentSeeder::class);
        $seeded = Page::where('slug', 'privacy-policy')->sole()->getTranslation('body', 'en', false);

        Page::query()->delete();
        Page::create([
            'slug' => 'privacy-policy',
            'is_published' => true,
            'title' => ['en' => 'Privacy Policy'],
            'body' => ['en' => '<p>Intro.</p>'],
            'meta_title' => ['en' => 'Privacy Policy'],
            'meta_description' => ['en' => 'Privacy Policy'],
        ]);

        $this->disclosureMigration()->up();
        $migrated = Page::where('slug', 'privacy-policy')->sole()->getTranslation('body', 'en', false);

        // What the migration appended, isolated by removing the intro it was
        // appended to. Not substr() from the heading onwards: the seeded
        // policy carries further sections AFTER the disclosure, so that
        // would compare the disclosure against the disclosure plus the rest
        // of the document.
        $appended = str_replace('<p>Intro.</p>', '', $migrated);

        $this->assertNotSame('', $appended);
        $this->assertStringContainsString(
            $appended,
            $seeded,
            'The seeder and the migration must ship byte-identical copy, or a fresh install and a migrated one end up with different policies.'
        );
    }
}
