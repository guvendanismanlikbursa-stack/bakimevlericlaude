<?php

namespace App\Console\Commands;

use App\Http\Controllers\Public\Concerns\FiltersFacilities;
use App\Models\FamilySavedSearch;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

// 14 Agustos 2026: kullanicinin talebi - aile bir aramayi kaydettiginde
// (bkz. Family\SavedSearchController), o kriterlere uyan YENI bir kurum
// eklendiginde bildirim alsin. Her kayitli arama icin, en son kontrolden
// (last_checked_at) SONRA eklenmis eslesen kurumlari bulur, TEK bir
// bildirimde ozetler, sonra last_checked_at'i simdiye guncelleyerek ayni
// kurumun tekrar tekrar bildirilmesini engeller.
class NotifyFamilySavedSearches extends Command
{
    use FiltersFacilities;

    protected $signature = 'family:notify-saved-searches';

    protected $description = 'Kayitli aramalara uyan yeni eklenmis kurumlar icin ailelere bildirim gonderir';

    public function handle(): int
    {
        $brands = config('brands.brands', []);
        $notified = 0;

        FamilySavedSearch::with('familyUser')->chunkById(200, function ($searches) use ($brands, &$notified) {
            foreach ($searches as $search) {
                $checkedFrom = $search->last_checked_at ?? $search->created_at;
                $family = $search->familyUser;
                $section = service_section($search->section_slug);

                if (! $family || empty($section)) {
                    $search->update(['last_checked_at' => now()]);

                    continue;
                }

                $fakeRequest = Request::create('/', 'GET', $search->filters ?? []);
                $matches = $this->filteredQuery($fakeRequest, $section['scopes'], $section['scopes'])
                    ->where('facilities.created_at', '>', $checkedFrom)
                    ->orderByDesc('facilities.created_at')
                    ->limit(5)
                    ->get(['facilities.id', 'facilities.name', 'facilities.slug']);

                if ($matches->isNotEmpty() && isset($brands[$search->brand])) {
                    app()->instance('currentBrand', $brands[$search->brand]);

                    $title = $matches->count() === 1
                        ? 'Aramanıza uyan yeni bir kurum eklendi'
                        : $matches->count().' aramanıza uyan yeni kurum eklendi';
                    $body = $matches->count() === 1
                        ? "\"{$search->label}\" aramanıza uyan \"{$matches->first()->name}\" eklendi."
                        : "\"{$search->label}\" aramanıza uyan yeni kurumlar: ".$matches->pluck('name')->implode(', ');

                    notify_user($family, 'saved_search_match', $title, $body, [
                        'facility_slug' => $matches->first()->slug,
                    ]);

                    $notified++;
                }

                $search->update(['last_checked_at' => now()]);
            }
        });

        $this->info("{$notified} kayitli arama icin bildirim gonderildi.");

        return self::SUCCESS;
    }
}
