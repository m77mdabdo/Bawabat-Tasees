<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\VisitorSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitorSession>
 */
class VisitorSessionFactory extends Factory
{
    protected $model = VisitorSession::class;

    public function definition(): array
    {
        $startedAt = now()->subMinutes($this->faker->numberBetween(5, 60 * 24 * 30));

        return [
            'session_uid' => $this->faker->unique()->regexify('[a-f0-9]{32}'),
            'started_at' => $startedAt,
            'last_activity_at' => (clone $startedAt)->addMinutes(3),
            'entry_url' => '/services',
            'referrer' => 'https://www.google.com/',
            'attribution' => [
                'latest_touch' => ['utm_source' => 'google', 'utm_campaign' => 'brand-search'],
                'first_touch' => ['utm_source' => 'google', 'utm_campaign' => 'brand-search'],
            ],
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'ip_prefix' => '203.0.113.0',
            'country' => 'SA',
            'events' => [
                ['type' => VisitorSession::TYPE_PAGEVIEW, 'url' => '/services', 'label' => null, 'ts' => $startedAt->toIso8601String(), 'duration_ms' => 4200],
            ],
            'page_count' => 1,
            'total_duration_seconds' => 180,
            'lead_id' => null,
        ];
    }

    public function converted(?Lead $lead = null): static
    {
        return $this->state(fn () => [
            'lead_id' => $lead?->getKey() ?? Lead::factory(),
        ]);
    }

    public function onMobile(): static
    {
        return $this->state(fn () => [
            'device_type' => 'mobile',
            'browser' => 'Safari',
            'platform' => 'iOS',
        ]);
    }

    public function startedAt(\DateTimeInterface|string $when): static
    {
        return $this->state(fn () => [
            'started_at' => $when,
            'last_activity_at' => $when,
        ]);
    }

    /**
     * A journey longer than the stored cap, for the truncation cases.
     */
    public function truncated(): static
    {
        return $this->state(function () {
            $events = [];

            for ($i = 0; $i < VisitorSession::MAX_EVENTS; $i++) {
                $events[] = [
                    'type' => VisitorSession::TYPE_PAGEVIEW,
                    'url' => '/page-'.$i,
                    'label' => null,
                    'ts' => now()->subMinutes(VisitorSession::MAX_EVENTS - $i)->toIso8601String(),
                    'duration_ms' => 1000,
                ];
            }

            return ['events' => $events, 'page_count' => VisitorSession::MAX_EVENTS + 25];
        });
    }
}
