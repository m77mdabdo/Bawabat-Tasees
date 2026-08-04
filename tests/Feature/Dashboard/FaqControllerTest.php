<?php

namespace Tests\Feature\Dashboard;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'question' => ['ar' => 'كيف أبدأ؟', 'en' => 'How do I start?'],
            'answer' => ['ar' => 'اتبع الخطوات التالية', 'en' => 'Follow these steps'],
            'sort_order' => 1,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_index_loads_for_admin(): void
    {
        $admin = $this->makeAdmin();
        Faq::create($this->validPayload());

        $response = $this->actingAs($admin)->get(route('dashboard.faqs.index'));

        $response->assertOk();
        $response->assertSee('كيف أبدأ؟');
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('dashboard.faqs.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.faqs.index'))->assertForbidden();
    }

    public function test_store_creates_a_valid_faq(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('dashboard.faqs.store'), $this->validPayload());

        $response->assertRedirect(route('dashboard.faqs.index'));
        $this->assertDatabaseCount('faqs', 1);
    }

    public function test_store_rejects_missing_arabic_question(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload();
        unset($payload['question']['ar']);

        $response = $this->actingAs($admin)->post(route('dashboard.faqs.store'), $payload);

        $response->assertSessionHasErrors('question.ar');
        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_update_modifies_a_faq(): void
    {
        $admin = $this->makeAdmin();
        $faq = Faq::create($this->validPayload());

        $payload = $this->validPayload(['question' => ['ar' => 'سؤال جديد', 'en' => 'New question']]);
        $response = $this->actingAs($admin)->put(route('dashboard.faqs.update', $faq), $payload);

        $response->assertRedirect(route('dashboard.faqs.index'));
        $this->assertSame('سؤال جديد', $faq->fresh()->getTranslation('question', 'ar'));
    }

    public function test_update_rejects_invalid_data(): void
    {
        $admin = $this->makeAdmin();
        $faq = Faq::create($this->validPayload());

        $payload = $this->validPayload();
        unset($payload['answer']['ar']);

        $response = $this->actingAs($admin)->put(route('dashboard.faqs.update', $faq), $payload);

        $response->assertSessionHasErrors('answer.ar');
    }

    public function test_destroy_removes_a_faq(): void
    {
        $admin = $this->makeAdmin();
        $faq = Faq::create($this->validPayload());

        $response = $this->actingAs($admin)->delete(route('dashboard.faqs.destroy', $faq));

        $response->assertRedirect(route('dashboard.faqs.index'));
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}
