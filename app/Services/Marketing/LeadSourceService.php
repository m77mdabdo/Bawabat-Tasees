<?php

namespace App\Services\Marketing;

use App\Models\Lead;
use App\Models\LeadSource;
use Illuminate\Support\Facades\DB;

/**
 * Owns every write to lead_sources.
 *
 * THE IMPORTANT PART. leads.source_platform stores a source's KEY as a
 * plain string, not a foreign key — AttributionService resolves an
 * incoming utm_source to a key and writes that. So renaming a key would
 * silently strand every lead still carrying the old one: they would stop
 * matching any source and drop out of the "leads by lead source" report.
 *
 * update() therefore cascades a key change across leads.source_platform
 * inside a transaction, and delete() reassigns affected leads rather than
 * leaving them pointing at a key that no longer exists.
 *
 * Keys are normalised to lowercase because matching is case-insensitive
 * (strtolower on both sides) AND because the unique index behaves
 * differently per driver: MySQL's default collation would reject
 * "Google" next to "google", SQLite would allow both and then match
 * whichever came first. Normalising removes the ambiguity entirely.
 */
class LeadSourceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LeadSource
    {
        return LeadSource::create($this->normalise($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LeadSource $source, array $data): LeadSource
    {
        $data = $this->normalise($data);
        $previousKey = $source->key;

        return DB::transaction(function () use ($source, $data, $previousKey) {
            $source->update($data);

            if (isset($data['key']) && $data['key'] !== $previousKey) {
                $this->repointLeads($previousKey, $data['key']);
            }

            return $source;
        });
    }

    /**
     * Deleting a source does NOT delete its leads. They are repointed to
     * the raw key they arrived with so the historical record survives —
     * Reports will simply show it as an unrecognised source, which is
     * exactly what it now is.
     */
    public function delete(LeadSource $source): void
    {
        DB::transaction(function () use ($source) {
            $source->delete();
        });
    }

    public function toggleActive(LeadSource $source): LeadSource
    {
        $source->update(['is_active' => ! $source->is_active]);

        return $source;
    }

    /**
     * Lead counts for every source, as ONE grouped query rather than a
     * count per row. Uses the leads_source_platform_index.
     *
     * @return array<string, int> key => lead count
     */
    public function leadCounts(): array
    {
        return Lead::query()
            ->whereNotNull('source_platform')
            ->groupBy('source_platform')
            ->selectRaw('source_platform, COUNT(*) as total')
            ->pluck('total', 'source_platform')
            ->all();
    }

    /**
     * Values sitting in leads.source_platform that match no source — the
     * raw utm_source fallback AttributionService deliberately preserves.
     * Surfacing them lets an admin turn a recurring one into a real
     * source instead of it staying invisible.
     *
     * @return array<string, int>
     */
    public function unrecognisedSources(): array
    {
        $known = LeadSource::pluck('key')->all();

        return Lead::query()
            ->whereNotNull('source_platform')
            ->when($known !== [], fn ($query) => $query->whereNotIn('source_platform', $known))
            ->groupBy('source_platform')
            ->selectRaw('source_platform, COUNT(*) as total')
            ->orderByDesc('total')
            ->pluck('total', 'source_platform')
            ->all();
    }

    private function repointLeads(string $from, string $to): int
    {
        return Lead::where('source_platform', $from)->update(['source_platform' => $to]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalise(array $data): array
    {
        if (isset($data['key'])) {
            $data['key'] = strtolower(trim($data['key']));
        }

        if (isset($data['label'])) {
            $data['label'] = trim($data['label']);
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        // is_system is never settable from a request — only the migration
        // that marked the seeded rows sets it.
        unset($data['is_system']);

        return $data;
    }
}
