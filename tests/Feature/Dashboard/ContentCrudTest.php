<?php

namespace Tests\Feature\Dashboard;

use App\Enums\Role;
use App\Filament\Resources\Articles\Pages as ArticlePages;
use App\Filament\Resources\Countries\Pages as CountryPages;
use App\Filament\Resources\Faqs\Pages as FaqPages;
use App\Filament\Resources\Services\Pages as ServicePages;
use App\Filament\Resources\Testimonials\Pages as TestimonialPages;
use App\Models\Article;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Add, edit, show/hide and delete for every translatable content screen, driven by one table:
 * each row names the screen's pages, the model, a valid "add" form and the field an edit changes.
 */
class ContentCrudTest extends DashboardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->makeStaff(Role::Editor));
    }

    /** @return array<string, array{class-string, class-string, class-string, class-string<Model>, array, string, ?string}> */
    public static function screens(): array
    {
        $tr = fn (string $ar, string $en = '') => ['ar' => $ar, 'en' => $en];

        return [
            'services' => [ServicePages\ListServices::class, ServicePages\CreateService::class, ServicePages\EditService::class, Service::class, [
                'name' => $tr('خدمة جديدة', 'New service'), 'summary' => $tr('ملخص'), 'body' => $tr('<p>وصف</p>'),
                'requirements' => $tr('متطلبات'), 'process' => $tr('خطوات'), 'is_active' => true, 'sort_order' => 3,
            ], 'name', 'is_active'],
            'countries' => [CountryPages\ListCountries::class, CountryPages\CreateCountry::class, CountryPages\EditCountry::class, Country::class, [
                'name' => $tr('مصر', 'Egypt'), 'notes' => $tr('ملاحظة'), 'is_active' => true, 'sort_order' => 1,
            ], 'name', 'is_active'],
            'faqs' => [FaqPages\ListFaqs::class, FaqPages\CreateFaq::class, FaqPages\EditFaq::class, Faq::class, [
                'question' => $tr('كم يستغرق التأسيس؟', 'How long?'), 'answer' => $tr('أسبوعان'), 'is_active' => true, 'sort_order' => 1,
            ], 'question', 'is_active'],
            'testimonials' => [TestimonialPages\ListTestimonials::class, TestimonialPages\CreateTestimonial::class, TestimonialPages\EditTestimonial::class, Testimonial::class, [
                'client_name' => 'Client A', 'quote' => $tr('خدمة ممتازة'), 'is_active' => true, 'sort_order' => 1,
            ], 'quote', 'is_active'],
            'articles' => [ArticlePages\ListArticles::class, ArticlePages\CreateArticle::class, ArticlePages\EditArticle::class, Article::class, [
                'title' => $tr('مقال جديد', 'New article'), 'excerpt' => $tr('مقتطف'), 'body' => $tr('<p>نص المقال</p>'), 'is_published' => true,
            ], 'title', 'is_published'],
        ];
    }

    #[DataProvider('screens')]
    public function test_add(string $list, string $create, string $edit, string $model, array $data, string $textField): void
    {
        Livewire::test($create)->fillForm($data)->call('create')->assertHasNoFormErrors();

        $record = $model::query()->latest('id')->firstOrFail();
        $this->assertSame($data[$textField]['ar'], strip_tags($record->getTranslation($textField, 'ar')));
        Livewire::test($list)->assertCanSeeTableRecords([$record]);
    }

    #[DataProvider('screens')]
    public function test_edit(string $list, string $create, string $edit, string $model, array $data, string $textField): void
    {
        $record = $model::factory()->create();

        Livewire::test($edit, ['record' => $record->getRouteKey()])
            ->fillForm([$textField => ['ar' => 'نص معدّل', 'en' => 'Edited text']])
            ->call('save')
            ->assertHasNoFormErrors();

        $record->refresh();
        $this->assertSame('نص معدّل', strip_tags($record->getTranslation($textField, 'ar')));
        $this->assertSame('Edited text', strip_tags($record->getTranslation($textField, 'en')));
    }

    #[DataProvider('screens')]
    public function test_show_and_hide_from_the_list(string $list, string $create, string $edit, string $model, array $data, string $textField, ?string $toggle): void
    {
        $record = $model::factory()->create([$toggle => true]);

        Livewire::test($list)->call('updateTableColumnState', $toggle, (string) $record->getKey(), false);

        $this->assertFalse((bool) $record->fresh()->{$toggle});
    }

    #[DataProvider('screens')]
    public function test_delete_from_the_list(string $list, string $create, string $edit, string $model): void
    {
        $record = $model::factory()->create();

        Livewire::test($list)->callTableAction('delete', $record);

        $this->assertGone($record);
    }

    #[DataProvider('screens')]
    public function test_delete_from_the_edit_page(string $list, string $create, string $edit, string $model): void
    {
        $record = $model::factory()->create();

        Livewire::test($edit, ['record' => $record->getRouteKey()])->callAction('delete');

        $this->assertGone($record);
    }

    private function assertGone(Model $record): void
    {
        in_array(SoftDeletes::class, class_uses_recursive($record), true)
            ? $this->assertSoftDeleted($record)
            : $this->assertModelMissing($record);
    }

    public function test_sales_and_viewers_cannot_open_content_screens(): void
    {
        foreach ([Role::Sales, Role::Viewer] as $role) {
            $this->actingAs($this->makeStaff($role, ['email' => $role->value.'@x.test']));
            foreach (['services', 'countries', 'faqs', 'testimonials', 'articles', 'comments', 'pages', 'menu-items', 'media', 'website-texts'] as $screen) {
                $this->get('/dashboard/'.$screen)->assertForbidden();
            }
        }
    }
}
