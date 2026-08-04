<?php

namespace Tests\Feature\Public;

use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_active_countries_only(): void
    {
        Country::create(['slug' => 'saudi-arabia', 'name' => ['ar' => 'السعودية'], 'is_active' => true]);
        Country::create(['slug' => 'inactive-country', 'name' => ['ar' => 'دولة غير نشطة'], 'is_active' => false]);

        $response = $this->get(route('countries.index'));

        $response->assertOk();
        $response->assertSee('السعودية');
        $response->assertDontSee('دولة غير نشطة');
    }

    public function test_index_shows_notes_when_present(): void
    {
        Country::create([
            'slug' => 'uae',
            'name' => ['ar' => 'الإمارات'],
            'notes' => ['ar' => 'ملاحظات خاصة'],
            'is_active' => true,
        ]);

        $response = $this->get(route('countries.index'));

        $response->assertOk();
        $response->assertSee('ملاحظات خاصة');
    }

    public function test_index_shows_empty_state_when_no_countries(): void
    {
        $response = $this->get(route('countries.index'));

        $response->assertOk();
        $response->assertSee('لا توجد دول مضافة حالياً');
    }
}
