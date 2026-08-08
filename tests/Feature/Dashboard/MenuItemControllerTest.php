<?php

namespace Tests\Feature\Dashboard;

use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\MenuItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class MenuItemControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'label' => ['ar' => 'عنصر جديد', 'en' => 'New Item'],
            'link_type' => 'route',
            'route_name' => 'faqs.index',
            'target' => '_self',
            'parent_id' => '',
            'is_visible' => '1',
        ], $overrides);
    }

    private function header(string $url = '/'): string
    {
        $html = $this->get($url)->assertOk()->getContent();

        return substr($html, 0, strpos($html, '</header>'));
    }

    // ---------------------------------------------------------------
    // Index / CRUD
    // ---------------------------------------------------------------

    public function test_admin_can_view_the_index(): void
    {
        $this->seed(MenuItemSeeder::class);

        $this->actingAs($this->makeAdmin())->get(route('dashboard.menu.index'))
            ->assertOk()
            ->assertSee(__('dashboard.menu.title'), escape: false);
    }

    public function test_index_shows_an_empty_state(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('dashboard.menu.index'))
            ->assertOk()
            ->assertSee(__('dashboard.menu.empty'), escape: false);
    }

    public function test_admin_can_create_a_route_item(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.store'), $this->payload())
            ->assertRedirect(route('dashboard.menu.index'));

        $item = MenuItem::firstOrFail();
        $this->assertSame('faqs.index', $item->route_name);
        $this->assertSame('عنصر جديد', $item->getTranslation('label', 'ar'));
        $this->assertSame('New Item', $item->getTranslation('label', 'en'));
        $this->assertFalse($item->is_system, 'Admin-created items must never be system items.');
    }

    public function test_admin_can_create_a_custom_url_item(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.store'), $this->payload([
                'link_type' => 'url',
                'route_name' => '',
                'url' => 'https://example.com/partner',
                'target' => '_blank',
            ]))->assertRedirect();

        $item = MenuItem::firstOrFail();
        $this->assertSame('https://example.com/partner', $item->url);
        $this->assertSame('_blank', $item->target);
        $this->assertNull($item->route_name, 'Switching type must clear the unused field.');
    }

    public function test_admin_can_update_an_item(): void
    {
        $item = MenuItem::factory()->route('faqs.index')->create();

        $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.menu.update', $item), $this->payload([
                'label' => ['ar' => 'اسم محدث', 'en' => 'Updated'],
                'route_name' => 'contact',
            ]))->assertRedirect(route('dashboard.menu.index'));

        $this->assertSame('اسم محدث', $item->fresh()->getTranslation('label', 'ar'));
        $this->assertSame('contact', $item->fresh()->route_name);
    }

    public function test_switching_from_url_to_route_clears_the_url(): void
    {
        $item = MenuItem::factory()->url('https://example.com')->create();

        $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.menu.update', $item), $this->payload())
            ->assertRedirect();

        $this->assertNull($item->fresh()->url);
        $this->assertSame('faqs.index', $item->fresh()->route_name);
    }

    public function test_blank_english_label_is_not_stored_as_an_empty_string(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.store'), $this->payload(['label' => ['ar' => 'عربي فقط', 'en' => '']]))
            ->assertRedirect();

        $this->assertSame(['ar' => 'عربي فقط'], MenuItem::firstOrFail()->getTranslations('label'));
    }

    public function test_admin_can_delete_an_item(): void
    {
        $item = MenuItem::factory()->create();

        $this->actingAs($this->makeAdmin())
            ->delete(route('dashboard.menu.destroy', $item))
            ->assertRedirect(route('dashboard.menu.index'));

        $this->assertDatabaseCount('menu_items', 0);
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_arabic_label_is_required(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.store'), $this->payload(['label' => ['ar' => '', 'en' => 'Only English']]))
            ->assertSessionHasErrors('label.ar');

        $this->assertDatabaseCount('menu_items', 0);
    }

    public function test_route_name_is_required_when_link_type_is_route(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.store'), $this->payload(['route_name' => '']))
            ->assertSessionHasErrors('route_name');
    }

    /**
     * The core guard rail: a parameter-bound route can never be selected,
     * so the navbar cannot be made to 500.
     */
    public function test_a_parameter_bound_route_is_rejected(): void
    {
        foreach (['services.show', 'articles.show', 'dashboard'] as $route) {
            $this->actingAs($this->makeAdmin())
                ->post(route('dashboard.menu.store'), $this->payload(['route_name' => $route]))
                ->assertSessionHasErrors('route_name');
        }

        $this->assertDatabaseCount('menu_items', 0);
    }

    public function test_url_is_required_and_validated_when_link_type_is_url(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('dashboard.menu.store'), $this->payload(['link_type' => 'url', 'route_name' => '', 'url' => '']))
            ->assertSessionHasErrors('url');

        $this->actingAs($admin)
            ->post(route('dashboard.menu.store'), $this->payload(['link_type' => 'url', 'route_name' => '', 'url' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('menu_items', 0);
    }

    public function test_a_root_relative_url_is_accepted(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.store'), $this->payload(['link_type' => 'url', 'route_name' => '', 'url' => '/custom-landing']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    /**
     * Regression: nullable fields are absent from validated() entirely
     * when the client omits them rather than sending "". Reading them
     * unguarded raised an undefined-key warning, which Laravel escalates
     * to an ErrorException — a 500 on a perfectly valid submission. The
     * earlier tests all sent parent_id => '' and so never hit it.
     */
    public function test_a_minimal_payload_with_every_optional_field_omitted_succeeds(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.store'), [
                'label' => ['ar' => 'الحد الأدنى'],
                'link_type' => 'none',
            ])
            ->assertRedirect(route('dashboard.menu.index'))
            ->assertSessionHasNoErrors();

        $item = MenuItem::firstOrFail();
        $this->assertNull($item->parent_id);
        $this->assertNull($item->route_name);
        $this->assertNull($item->url);
        $this->assertSame('_self', $item->target);
        $this->assertFalse($item->is_visible, 'An omitted checkbox means unchecked.');
    }

    public function test_a_none_type_item_needs_neither_route_nor_url(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.store'), $this->payload(['link_type' => 'none', 'route_name' => '', 'url' => '']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull(MenuItem::firstOrFail()->href());
    }

    /**
     * Nesting is capped at one level, so a child may not be chosen as a
     * parent.
     */
    public function test_a_nested_item_cannot_be_used_as_a_parent(): void
    {
        $parent = MenuItem::factory()->dropdownParent()->create();
        $child = MenuItem::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.store'), $this->payload(['parent_id' => $child->id]))
            ->assertSessionHasErrors('parent_id');
    }

    public function test_an_item_cannot_be_its_own_parent(): void
    {
        $item = MenuItem::factory()->create();

        $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.menu.update', $item), $this->payload(['parent_id' => $item->id]))
            ->assertSessionHasErrors('parent_id');
    }

    // ---------------------------------------------------------------
    // System items + no-orphan rule
    // ---------------------------------------------------------------

    public function test_system_items_show_a_stronger_delete_confirmation(): void
    {
        $this->seed(MenuItemSeeder::class);

        $this->actingAs($this->makeAdmin())->get(route('dashboard.menu.index'))
            ->assertOk()
            ->assertSee(__('dashboard.menu.confirm_delete_system'), escape: false)
            ->assertSee(__('dashboard.menu.system_badge'), escape: false);
    }

    public function test_system_items_can_still_be_renamed_hidden_and_deleted(): void
    {
        $this->seed(MenuItemSeeder::class);
        $admin = $this->makeAdmin();
        $contact = MenuItem::where('route_name', 'contact')->firstOrFail();

        $this->actingAs($admin)->put(route('dashboard.menu.update', $contact), $this->payload([
            'label' => ['ar' => 'كلمنا'],
            'route_name' => 'contact',
        ]))->assertRedirect();
        $this->assertSame('كلمنا', $contact->fresh()->getTranslation('label', 'ar'));

        $this->actingAs($admin)->patch(route('dashboard.menu.visibility', $contact))->assertRedirect();
        $this->assertFalse($contact->fresh()->is_visible);

        $this->actingAs($admin)->delete(route('dashboard.menu.destroy', $contact))->assertRedirect();
        $this->assertDatabaseMissing('menu_items', ['id' => $contact->id]);
    }

    public function test_deleting_a_parent_reparents_children_instead_of_deleting_them(): void
    {
        $parent = MenuItem::factory()->dropdownParent()->create();
        $child = MenuItem::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->makeAdmin())
            ->delete(route('dashboard.menu.destroy', $parent))
            ->assertRedirect();

        $this->assertDatabaseHas('menu_items', ['id' => $child->id]);
        $this->assertNull($child->fresh()->parent_id);
    }

    // ---------------------------------------------------------------
    // Visibility toggle
    // ---------------------------------------------------------------

    public function test_visibility_toggle_hides_and_shows_the_item_on_the_public_navbar(): void
    {
        $admin = $this->makeAdmin();
        $item = MenuItem::factory()->route('faqs.index')->create(['label' => ['ar' => 'الأسئلة']]);

        $this->assertStringContainsString('href="'.route('faqs.index').'"', $this->header());

        $this->actingAs($admin)->patch(route('dashboard.menu.visibility', $item))->assertRedirect();
        $this->assertStringNotContainsString('href="'.route('faqs.index').'"', $this->header());

        $this->actingAs($admin)->patch(route('dashboard.menu.visibility', $item))->assertRedirect();
        $this->assertStringContainsString('href="'.route('faqs.index').'"', $this->header());
    }

    // ---------------------------------------------------------------
    // Reordering + nesting
    // ---------------------------------------------------------------

    public function test_reorder_persists_the_new_order(): void
    {
        $a = MenuItem::factory()->route('home')->create(['sort_order' => 0]);
        $b = MenuItem::factory()->route('contact')->create(['sort_order' => 1]);

        $this->actingAs($this->makeAdmin())->post(route('dashboard.menu.reorder'), [
            'items' => [
                ['id' => $b->id, 'parent_id' => ''],
                ['id' => $a->id, 'parent_id' => ''],
            ],
        ])->assertRedirect(route('dashboard.menu.index'));

        $this->assertSame(0, $b->fresh()->sort_order);
        $this->assertSame(1, $a->fresh()->sort_order);
        $this->assertSame([$b->id, $a->id], MenuItem::topLevel()->ordered()->pluck('id')->all());
    }

    public function test_reorder_persists_nesting_and_renders_a_public_dropdown(): void
    {
        $parent = MenuItem::factory()->dropdownParent()->create(['label' => ['ar' => 'الشركة']]);
        $child = MenuItem::factory()->route('pages.about')->create(['label' => ['ar' => 'من نحن']]);

        $this->actingAs($this->makeAdmin())->post(route('dashboard.menu.reorder'), [
            'items' => [
                ['id' => $parent->id, 'parent_id' => ''],
                ['id' => $child->id, 'parent_id' => $parent->id],
            ],
        ])->assertRedirect();

        $this->assertSame($parent->id, $child->fresh()->parent_id);

        // …and the public navbar now renders it as a dropdown.
        $header = $this->header();
        $this->assertStringContainsString('aria-haspopup="true"', $header);
        $this->assertStringContainsString('الشركة', $header);
        $this->assertStringContainsString('href="'.route('pages.about').'"', $header);
    }

    public function test_reorder_rejects_an_unknown_id(): void
    {
        $item = MenuItem::factory()->create(['sort_order' => 5]);

        $this->actingAs($this->makeAdmin())->post(route('dashboard.menu.reorder'), [
            'items' => [['id' => 999999, 'parent_id' => '']],
        ])->assertSessionHasErrors('items.0.id');

        $this->assertSame(5, $item->fresh()->sort_order, 'A rejected payload must write nothing.');
    }

    public function test_reorder_rejects_a_nested_item_as_a_parent(): void
    {
        $parent = MenuItem::factory()->dropdownParent()->create();
        $child = MenuItem::factory()->create(['parent_id' => $parent->id]);
        $other = MenuItem::factory()->create();

        $this->actingAs($this->makeAdmin())->post(route('dashboard.menu.reorder'), [
            'items' => [['id' => $other->id, 'parent_id' => $child->id]],
        ])->assertSessionHasErrors('items.0.parent_id');

        $this->assertNull($other->fresh()->parent_id);
    }

    /**
     * The whole payload is one transaction: a later invalid row must not
     * leave earlier rows already written.
     */
    public function test_reorder_is_all_or_nothing(): void
    {
        $a = MenuItem::factory()->create(['sort_order' => 0]);
        $b = MenuItem::factory()->create(['sort_order' => 1]);

        $this->actingAs($this->makeAdmin())->post(route('dashboard.menu.reorder'), [
            'items' => [
                ['id' => $b->id, 'parent_id' => ''],
                ['id' => 999999, 'parent_id' => ''],
            ],
        ])->assertSessionHasErrors();

        $this->assertSame(0, $a->fresh()->sort_order);
        $this->assertSame(1, $b->fresh()->sort_order);
    }

    public function test_reorder_requires_at_least_one_item(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('dashboard.menu.reorder'), ['items' => []])
            ->assertSessionHasErrors('items');
    }

    // ---------------------------------------------------------------
    // Gating
    // ---------------------------------------------------------------

    public function test_guest_is_redirected_from_every_menu_route(): void
    {
        $item = MenuItem::factory()->create();

        $this->get(route('dashboard.menu.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard.menu.create'))->assertRedirect(route('login'));
        $this->get(route('dashboard.menu.edit', $item))->assertRedirect(route('login'));
        $this->post(route('dashboard.menu.store'), $this->payload())->assertRedirect(route('login'));
        $this->put(route('dashboard.menu.update', $item), $this->payload())->assertRedirect(route('login'));
        $this->patch(route('dashboard.menu.visibility', $item))->assertRedirect(route('login'));
        $this->post(route('dashboard.menu.reorder'), ['items' => [['id' => $item->id]]])->assertRedirect(route('login'));
        $this->delete(route('dashboard.menu.destroy', $item))->assertRedirect(route('login'));

        $this->assertDatabaseCount('menu_items', 1);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create();
        $item = MenuItem::factory()->create();

        $this->actingAs($user)->get(route('dashboard.menu.index'))->assertForbidden();
        $this->actingAs($user)->post(route('dashboard.menu.store'), $this->payload())->assertForbidden();
        $this->actingAs($user)->patch(route('dashboard.menu.visibility', $item))->assertForbidden();
        $this->actingAs($user)->delete(route('dashboard.menu.destroy', $item))->assertForbidden();

        $this->assertDatabaseCount('menu_items', 1);
    }

    // ---------------------------------------------------------------
    // Form rendering
    // ---------------------------------------------------------------

    public function test_create_form_offers_only_whitelisted_routes(): void
    {
        $html = $this->actingAs($this->makeAdmin())->get(route('dashboard.menu.create'))->assertOk()->getContent();

        foreach (array_keys(MenuItem::ROUTE_WHITELIST) as $name) {
            $this->assertStringContainsString('value="'.$name.'"', $html);
        }

        $this->assertStringNotContainsString('value="services.show"', $html);
        $this->assertStringNotContainsString('value="articles.show"', $html);
    }

    public function test_edit_form_does_not_offer_the_item_itself_as_a_parent(): void
    {
        $item = MenuItem::factory()->create(['label' => ['ar' => 'أنا']]);
        $other = MenuItem::factory()->create(['label' => ['ar' => 'آخر']]);

        $html = $this->actingAs($this->makeAdmin())->get(route('dashboard.menu.edit', $item))->assertOk()->getContent();

        // Scoped to the parent <select>: value="1" also matches the
        // is_visible checkbox, so an unscoped assertion proves nothing.
        preg_match('#<select[^>]*name="parent_id".*?</select>#s', $html, $select);
        $this->assertNotEmpty($select, 'Parent selector missing from the edit form.');

        $this->assertStringNotContainsString('value="'.$item->id.'"', $select[0]);
        $this->assertStringContainsString('value="'.$other->id.'"', $select[0]);
    }
}
