<?php

namespace Tests\Feature\Dashboard;

use App\Enums\Role;
use App\Filament\Resources\Campaigns\Pages as CampaignPages;
use App\Filament\Resources\Comments\Pages\ListComments;
use App\Filament\Resources\LeadSources\Pages as LeadSourcePages;
use App\Filament\Resources\Media\Pages\ManageMedia;
use App\Filament\Resources\MenuItems\Pages as MenuPages;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\RelationManagers\SectionsRelationManager;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\LeadSource;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** Every add / edit / delete / show-hide button on the content and marketing screens without a generic form. */
class ScreensCrudTest extends DashboardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->makeStaff(Role::Manager));
    }

    // Comments ---------------------------------------------------------------

    public function test_comments_approve_reject_and_delete(): void
    {
        [$a, $b, $c] = Comment::factory()->count(3)->create();

        $list = Livewire::test(ListComments::class);
        $list->callTableAction('approve', $a);
        $list->callTableAction('reject', $b);
        $list->callTableAction('delete', $c);

        $this->assertSame('approved', $a->fresh()->status);
        $this->assertSame('rejected', $b->fresh()->status);
        $this->assertModelMissing($c);
    }

    public function test_comments_bulk_approve_and_bulk_delete(): void
    {
        $comments = Comment::factory()->count(3)->create();

        Livewire::test(ListComments::class)->callTableBulkAction('approveAll', $comments->take(2));
        $this->assertSame(2, Comment::where('status', 'approved')->count());

        Livewire::test(ListComments::class)->filterTable('status', null)->callTableBulkAction('delete', $comments);
        $this->assertSame(0, Comment::count());
    }

    public function test_only_approved_comments_reach_the_article(): void
    {
        $comment = Comment::factory()->create(['body' => 'تعليق ينتظر']);
        $url = route('articles.show', $comment->article);

        $this->get($url)->assertDontSee('تعليق ينتظر');
        Livewire::test(ListComments::class)->callTableAction('approve', $comment);
        $this->get($url)->assertSee('تعليق ينتظر');
    }

    // Pages and their sections ----------------------------------------------

    public function test_a_page_is_edited_and_shown_on_the_site(): void
    {
        $page = Page::factory()->create(['slug' => 'about']);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => ['ar' => 'من نحن الآن', 'en' => 'About us now'], 'body' => ['ar' => '<p>قصتنا</p>']])
            ->call('save')->assertHasNoFormErrors();

        $this->get(route('pages.about'))->assertOk()->assertSee('من نحن الآن')->assertSee('قصتنا');
    }

    public function test_page_sections_add_edit_hide_and_delete(): void
    {
        $page = Page::factory()->create();
        $manager = fn () => Livewire::test(SectionsRelationManager::class, ['ownerRecord' => $page, 'pageClass' => EditPage::class]);

        $manager()->callTableAction('create', data: ['key' => 'card-1', 'content' => ['title' => ['ar' => 'بطاقة', 'en' => ''], 'description' => ['ar' => 'وصف']], 'is_active' => true])
            ->assertHasNoTableActionErrors();
        $section = PageSection::where('key', 'card-1')->firstOrFail();
        $this->assertSame(['ar' => 'بطاقة'], $section->content['title']);

        $manager()->callTableAction('edit', $section, data: ['content' => ['title' => ['ar' => 'بطاقة معدلة']]])->assertHasNoTableActionErrors();
        $this->assertSame('بطاقة معدلة', $section->fresh()->content['title']['ar']);

        $manager()->call('updateTableColumnState', 'is_active', (string) $section->getKey(), false);
        $this->assertFalse($section->fresh()->is_active);

        $manager()->callTableAction('delete', $section);
        $this->assertModelMissing($section);
    }

    // Media ------------------------------------------------------------------

    public function test_media_upload_describe_and_delete(): void
    {
        Storage::fake('public');

        Livewire::test(ManageMedia::class)
            ->callAction('upload', ['file' => UploadedFile::fake()->image('office.jpg', 800, 600), 'alt_text' => 'المكتب'])
            ->assertHasNoActionErrors();

        $media = Media::firstOrFail();
        $this->assertSame('image', $media->type);
        Storage::disk('public')->assertExists($media->path);

        Livewire::test(ManageMedia::class)->callTableAction('edit', $media, data: ['alt_text' => 'مكتب الرياض'])->assertHasNoTableActionErrors();
        $this->assertSame('مكتب الرياض', $media->fresh()->alt_text);

        Livewire::test(ManageMedia::class)->callTableAction('delete', $media);
        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_svg_uploads_are_refused(): void
    {
        Storage::fake('public');

        Livewire::test(ManageMedia::class)
            ->callAction('upload', ['file' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml')])
            ->assertHasActionErrors(['file']);

        $this->assertSame(0, Media::count());
    }

    // Menu -------------------------------------------------------------------

    public function test_menu_edit_hide_and_delete_keeps_children(): void
    {
        $parent = MenuItem::factory()->create();
        $child = MenuItem::factory()->create(['parent_id' => $parent->id]);

        Livewire::test(MenuPages\EditMenuItem::class, ['record' => $parent->getKey()])
            ->fillForm(['label' => ['ar' => 'القائمة الجديدة'], 'link_type' => 'url', 'url' => 'https://example.com'])
            ->call('save')->assertHasNoFormErrors();
        $parent->refresh();
        $this->assertSame('url', $parent->link_type);
        $this->assertNull($parent->route_name);

        Livewire::test(MenuPages\ListMenuItems::class)->call('updateTableColumnState', 'is_visible', (string) $parent->getKey(), false);
        $this->assertFalse($parent->fresh()->is_visible);

        Livewire::test(MenuPages\ListMenuItems::class)->callTableAction('delete', $parent);
        $this->assertModelMissing($parent);
        $this->assertNull($child->fresh()->parent_id, 'Children move up instead of being deleted.');
    }

    public function test_menu_links_must_be_safe(): void
    {
        Livewire::test(MenuPages\CreateMenuItem::class)
            ->fillForm(['label' => ['ar' => 'رابط'], 'link_type' => 'url', 'url' => 'javascript:alert(1)'])
            ->call('create')->assertHasFormErrors(['url']);
    }

    // Campaigns --------------------------------------------------------------

    public function test_campaign_edit_toggle_and_delete(): void
    {
        $campaign = Campaign::factory()->create(['spend' => 1000]);

        Livewire::test(CampaignPages\EditCampaign::class, ['record' => $campaign->getKey()])
            ->fillForm(['spend' => 2500, 'ends_on' => null])->call('save')->assertHasNoFormErrors();
        $this->assertEquals(2500, $campaign->fresh()->spend);

        Livewire::test(CampaignPages\ListCampaigns::class)->call('updateTableColumnState', 'is_active', (string) $campaign->getKey(), false);
        $this->assertFalse($campaign->fresh()->is_active);

        Livewire::test(CampaignPages\ListCampaigns::class)->callTableAction('delete', $campaign);
        $this->assertModelMissing($campaign);
    }

    public function test_a_campaign_cannot_end_before_it_starts(): void
    {
        Livewire::test(CampaignPages\CreateCampaign::class)
            ->fillForm(['name' => 'Bad dates', 'currency' => 'SAR', 'starts_on' => '2026-05-10', 'ends_on' => '2026-05-01'])
            ->call('create')->assertHasFormErrors(['ends_on']);
    }

    // Lead sources -----------------------------------------------------------

    public function test_lead_source_add_edit_and_delete(): void
    {
        Livewire::test(LeadSourcePages\CreateLeadSource::class)
            ->fillForm(['key' => 'snapchat_ads', 'label' => 'Snapchat Ads', 'is_active' => true])
            ->call('create')->assertHasNoFormErrors();
        $source = LeadSource::where('key', 'snapchat_ads')->firstOrFail();

        Livewire::test(LeadSourcePages\EditLeadSource::class, ['record' => $source->getKey()])
            ->fillForm(['label' => 'Snapchat'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Snapchat', $source->fresh()->label);

        Livewire::test(LeadSourcePages\ListLeadSources::class)->callTableAction('delete', $source);
        $this->assertModelMissing($source);
    }

    public function test_built_in_lead_sources_cannot_be_deleted_or_rekeyed(): void
    {
        $source = LeadSource::factory()->create(['key' => 'google']);
        $source->forceFill(['is_system' => true])->save();

        Livewire::test(LeadSourcePages\ListLeadSources::class)->assertTableActionHidden('delete', $source);
        Livewire::test(LeadSourcePages\EditLeadSource::class, ['record' => $source->getKey()])
            ->assertFormFieldIsDisabled('key')
            ->assertActionHidden('delete');
    }
}
