<?php

namespace Tests\Feature\Public;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_active_faqs_only(): void
    {
        Faq::create(['question' => ['ar' => 'سؤال نشط؟'], 'answer' => ['ar' => 'إجابة نشطة'], 'is_active' => true]);
        Faq::create(['question' => ['ar' => 'سؤال غير نشط؟'], 'answer' => ['ar' => 'إجابة'], 'is_active' => false]);

        $response = $this->get(route('faqs.index'));

        $response->assertOk();
        $response->assertSee('سؤال نشط؟');
        $response->assertSee('إجابة نشطة');
        $response->assertDontSee('سؤال غير نشط؟');
    }

    public function test_index_orders_by_sort_order(): void
    {
        Faq::create(['question' => ['ar' => 'السؤال الثاني'], 'answer' => ['ar' => 'ج2'], 'is_active' => true, 'sort_order' => 2]);
        Faq::create(['question' => ['ar' => 'السؤال الأول'], 'answer' => ['ar' => 'ج1'], 'is_active' => true, 'sort_order' => 1]);

        $response = $this->get(route('faqs.index'));

        $content = $response->getContent();
        $this->assertTrue(strpos($content, 'السؤال الأول') < strpos($content, 'السؤال الثاني'));
    }

    public function test_index_shows_empty_state_when_no_faqs(): void
    {
        $response = $this->get(route('faqs.index'));

        $response->assertOk();
        $response->assertSee('لا توجد أسئلة شائعة متاحة حالياً');
    }
}
