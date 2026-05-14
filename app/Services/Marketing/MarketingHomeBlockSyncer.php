<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\Marketing\MarketingFooterColumn;
use App\Models\Marketing\MarketingFooterLink;
use App\Models\Marketing\MarketingMenu;
use App\Models\Marketing\MarketingMenuItem;
use App\Models\Marketing\MarketingPage;
use App\Models\Marketing\MarketingPageVersion;
use App\Models\Marketing\MarketingSite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MarketingHomeBlockSyncer
{
    public function __construct(
        private readonly MarketingBootstrap $bootstrap,
    ) {}

    /**
     * @return array{changed: bool, appended_types: list<string>, menu_added: bool, footer_added: bool}
     */
    public function sync(bool $dryRun = false): array
    {
        if (! Schema::hasTable('marketing_sites')) {
            return ['changed' => false, 'appended_types' => [], 'menu_added' => false, 'footer_added' => false];
        }

        $site = MarketingSite::query()->first();
        if ($site === null) {
            if ($dryRun) {
                return ['changed' => false, 'appended_types' => [], 'menu_added' => false, 'footer_added' => false];
            }
            $this->bootstrap->ensureSiteExists();
            $site = MarketingSite::query()->firstOrFail();
        }

        $page = MarketingPage::query()
            ->where('marketing_site_id', $site->id)
            ->where('slug', 'home')
            ->first();

        if ($page === null) {
            if ($dryRun) {
                return ['changed' => false, 'appended_types' => [], 'menu_added' => false, 'footer_added' => false];
            }
            $this->bootstrap->ensureSiteExists();
            $page = MarketingPage::query()
                ->where('marketing_site_id', $site->id)
                ->where('slug', 'home')
                ->firstOrFail();
        }

        $mergeResult = $this->buildMergedHomeBlocks($page);
        $merged = $mergeResult['merged'];
        $appendedTypes = $mergeResult['appended_types'];
        $blocksChanged = $mergeResult['blocks_changed'];

        $menuAdded = false;
        $footerAdded = false;

        if ($dryRun) {
            $menuAdded = $this->needsMenuDefaults($site);
            $footerAdded = $this->needsFooterLegal($site);

            return [
                'changed' => $blocksChanged || $menuAdded || $footerAdded,
                'appended_types' => $appendedTypes,
                'menu_added' => $menuAdded,
                'footer_added' => $footerAdded,
            ];
        }

        DB::transaction(function () use ($page, $merged, $blocksChanged, $site, &$menuAdded, &$footerAdded): void {
            if ($blocksChanged) {
                $nextVersion = (int) ($page->versions()->max('version') ?? 0) + 1;
                $version = MarketingPageVersion::query()->create([
                    'marketing_page_id' => $page->id,
                    'version' => $nextVersion,
                    'blocks_json' => $merged,
                    'created_by' => null,
                ]);
                $page->update([
                    'published_version_id' => $version->id,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
            }

            if ($this->needsMenuDefaults($site)) {
                $this->addMenuDefaults($site);
                $menuAdded = true;
            }

            if ($this->needsFooterLegal($site)) {
                $this->addFooterLegal($site);
                $footerAdded = true;
            }
        });

        return [
            'changed' => $blocksChanged || $menuAdded || $footerAdded,
            'appended_types' => $appendedTypes,
            'menu_added' => $menuAdded,
            'footer_added' => $footerAdded,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function currentBlocks(MarketingPage $page): array
    {
        $published = $page->publishedVersion;
        if ($published !== null && is_array($published->blocks_json)) {
            return $published->blocks_json;
        }

        $latest = $page->versions()->orderByDesc('id')->first();
        if ($latest !== null && is_array($latest->blocks_json)) {
            return $latest->blocks_json;
        }

        return [];
    }

    /**
     * @return array{merged: list<array<string, mixed>>, appended_types: list<string>, blocks_changed: bool}
     */
    private function buildMergedHomeBlocks(MarketingPage $page): array
    {
        $currentBlocks = $this->currentBlocks($page);
        $defaults = MarketingBootstrap::defaultBlocks();

        $merged = array_values(array_filter(
            $currentBlocks,
            static fn (array $b): bool => ($b['type'] ?? '') !== 'hero',
        ));

        $typesPresent = [];
        foreach ($merged as $b) {
            if (! empty($b['type'])) {
                $typesPresent[$b['type']] = true;
            }
        }

        $appendedTypes = [];
        $heroSliderDefault = MarketingBootstrap::defaultBlock('hero_slider');
        if ($heroSliderDefault !== null && ! isset($typesPresent['hero_slider'])) {
            array_unshift($merged, $heroSliderDefault);
            $typesPresent['hero_slider'] = true;
            $appendedTypes[] = 'hero_slider';
        }

        foreach ($defaults as $block) {
            $type = $block['type'] ?? '';
            if ($type === '' || $type === 'hero' || $type === 'hero_slider') {
                continue;
            }
            if (isset($typesPresent[$type])) {
                continue;
            }
            $merged[] = $block;
            $typesPresent[$type] = true;
            $appendedTypes[] = $type;
        }

        $merged = $this->mergeHeroSliderDefaultsIntoBlocks($merged);
        $merged = $this->liftFeatureStripFromHero($merged);
        $merged = $this->repositionFeatureStripAfterStats($merged);
        $merged = $this->removeFeatureGridWhenFeatureStripPresent($merged);
        $merged = $this->swapAdjacentCtaStatsToStatsCta($merged);

        $blocksChanged = $this->blocksJsonChanged($currentBlocks, $merged);

        return [
            'merged' => $merged,
            'appended_types' => $appendedTypes,
            'blocks_changed' => $blocksChanged,
        ];
    }

    /**
     * @param list<array<string, mixed>> $before
     * @param list<array<string, mixed>> $after
     */
    private function blocksJsonChanged(array $before, array $after): bool
    {
        return json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            !== json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param list<array<string, mixed>> $merged
     * @return list<array<string, mixed>>
     */
    private function mergeHeroSliderDefaultsIntoBlocks(array $merged): array
    {
        $def = MarketingBootstrap::defaultBlock('hero_slider');
        if ($def === null || ! is_array($def['data'] ?? null)) {
            return $merged;
        }
        $dDef = $def['data'];

        foreach ($merged as $i => $block) {
            if (($block['type'] ?? '') !== 'hero_slider') {
                continue;
            }
            $dUser = is_array($block['data'] ?? null) ? $block['data'] : [];
            foreach ($dDef as $k => $v) {
                if (! array_key_exists($k, $dUser)) {
                    $dUser[$k] = $v;
                }
            }
            $merged[$i]['data'] = $dUser;
        }

        return $merged;
    }

    /**
     * Eski sürümlerde hero_slider.data içindeki kartları ayrı feature_strip bloğuna taşır.
     *
     * @param list<array<string, mixed>> $merged
     * @return list<array<string, mixed>>
     */
    private function liftFeatureStripFromHero(array $merged): array
    {
        $fromHero = [];
        foreach ($merged as $i => $block) {
            if (($block['type'] ?? '') !== 'hero_slider') {
                continue;
            }
            $d = is_array($block['data'] ?? null) ? $block['data'] : [];
            $raw = $d['feature_strip'] ?? null;
            if (is_array($raw)) {
                foreach (array_values($raw) as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    if (($row['title'] ?? '') === '' && ($row['body'] ?? '') === '') {
                        continue;
                    }
                    $fromHero[] = $row;
                }
            }
            unset($merged[$i]['data']['feature_strip']);
            break;
        }

        if ($fromHero === []) {
            return array_values($merged);
        }

        $merged = array_values(array_filter(
            $merged,
            static fn (array $b): bool => ($b['type'] ?? '') !== 'feature_strip',
        ));

        $merged[] = [
            'type' => 'feature_strip',
            'data' => ['items' => $fromHero],
        ];

        return array_values($merged);
    }

    /**
     * @param list<array<string, mixed>> $merged
     * @return list<array<string, mixed>>
     */
    private function repositionFeatureStripAfterStats(array $merged): array
    {
        $statsIdx = null;
        $stripIdx = null;
        foreach ($merged as $i => $block) {
            if ($statsIdx === null && ($block['type'] ?? '') === 'stats') {
                $statsIdx = $i;
            }
            if (($block['type'] ?? '') === 'feature_strip') {
                $stripIdx = $i;
            }
        }

        if ($statsIdx === null || $stripIdx === null) {
            return array_values($merged);
        }

        if ($stripIdx === $statsIdx + 1) {
            return array_values($merged);
        }

        $stripBlock = $merged[$stripIdx];
        array_splice($merged, $stripIdx, 1);
        if ($stripIdx < $statsIdx) {
            $statsIdx--;
        }
        array_splice($merged, $statsIdx + 1, 0, [$stripBlock]);

        return array_values($merged);
    }

    /**
     * @param list<array<string, mixed>> $merged
     * @return list<array<string, mixed>>
     */
    private function removeFeatureGridWhenFeatureStripPresent(array $merged): array
    {
        if (! $this->mergedHasNonEmptyFeatureStrip($merged)) {
            return $merged;
        }

        return array_values(array_filter(
            $merged,
            static fn (array $b): bool => ($b['type'] ?? '') !== 'feature_grid',
        ));
    }

    /** @param list<array<string, mixed>> $merged */
    private function mergedHasNonEmptyFeatureStrip(array $merged): bool
    {
        foreach ($merged as $block) {
            if (($block['type'] ?? '') !== 'feature_strip') {
                continue;
            }
            $items = $block['data']['items'] ?? $block['data']['feature_strip'] ?? null;
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (($row['title'] ?? '') !== '' || ($row['body'] ?? '') !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Üstte koyu CTA, hemen altında istatistik kartları ise sırayı tersine çevirir (istatistik üstte, CTA altta).
     *
     * @param list<array<string, mixed>> $merged
     * @return list<array<string, mixed>>
     */
    private function swapAdjacentCtaStatsToStatsCta(array $merged): array
    {
        $n = count($merged);
        for ($i = 0; $i < $n - 1; $i++) {
            if (($merged[$i]['type'] ?? '') === 'cta' && ($merged[$i + 1]['type'] ?? '') === 'stats') {
                $tmp = $merged[$i];
                $merged[$i] = $merged[$i + 1];
                $merged[$i + 1] = $tmp;
            }
        }

        return array_values($merged);
    }

    /** @return array<int, array{label: string, url: string}> */
    private function defaultMenuItems(): array
    {
        $home = route('marketing.home');

        return [
            ['label' => 'Anasayfa', 'url' => $home],
            ['label' => 'Hakkımızda', 'url' => $home.'#hakkimizda'],
            ['label' => 'Özellikler', 'url' => $home.'#ozellikler'],
            ['label' => 'Fiyatlarımız', 'url' => $home.'#fiyatlandirma'],
            ['label' => 'Referanslar', 'url' => $home.'#referanslar'],
            ['label' => 'Başvuru', 'url' => $home.'#basvuru'],
            ['label' => 'S.S.S.', 'url' => $home.'#sss'],
            ['label' => 'İletişim', 'url' => $home.'#iletisim'],
            ['label' => 'Panel girişi', 'url' => route('login')],
        ];
    }

    private function needsMenuDefaults(MarketingSite $site): bool
    {
        $menu = MarketingMenu::query()
            ->where('marketing_site_id', $site->id)
            ->where('key', 'header')
            ->first();
        if ($menu === null) {
            return true;
        }

        $existing = MarketingMenuItem::query()
            ->where('marketing_menu_id', $menu->id)
            ->whereNull('parent_id')
            ->get()
            ->map(fn (MarketingMenuItem $i) => ['label' => mb_strtolower(trim((string) $i->label)), 'url' => trim((string) $i->url)]);

        foreach ($this->defaultMenuItems() as $want) {
            $wl = mb_strtolower(trim($want['label']));
            $wu = trim($want['url']);
            $found = $existing->contains(fn (array $e): bool => $e['url'] === $wu || $e['label'] === $wl);
            if (! $found) {
                return true;
            }
        }

        return false;
    }

    private function addMenuDefaults(MarketingSite $site): void
    {
        $menu = MarketingMenu::query()->firstOrCreate(
            ['marketing_site_id' => $site->id, 'key' => 'header'],
            [],
        );

        $existing = MarketingMenuItem::query()
            ->where('marketing_menu_id', $menu->id)
            ->whereNull('parent_id')
            ->get()
            ->map(fn (MarketingMenuItem $i) => ['label' => mb_strtolower(trim((string) $i->label)), 'url' => trim((string) $i->url)]);

        $max = (int) MarketingMenuItem::query()->where('marketing_menu_id', $menu->id)->max('sort_order');
        foreach ($this->defaultMenuItems() as $want) {
            $wl = mb_strtolower(trim($want['label']));
            $wu = trim($want['url']);
            $found = $existing->contains(fn (array $e): bool => $e['url'] === $wu || $e['label'] === $wl);
            if ($found) {
                continue;
            }
            $max++;
            MarketingMenuItem::query()->create([
                'marketing_menu_id' => $menu->id,
                'parent_id' => null,
                'label' => $want['label'],
                'url' => $want['url'],
                'open_in_new_tab' => false,
                'sort_order' => $max,
            ]);
        }
    }

    private function needsFooterLegal(MarketingSite $site): bool
    {
        $hasKvkk = MarketingFooterLink::query()
            ->whereHas('column', fn ($q) => $q->where('marketing_site_id', $site->id))
            ->where('url', 'like', '%#kvkk%')
            ->exists();

        return ! $hasKvkk;
    }

    private function addFooterLegal(MarketingSite $site): void
    {
        $maxOrder = (int) MarketingFooterColumn::query()->where('marketing_site_id', $site->id)->max('sort_order');

        $col = MarketingFooterColumn::query()->create([
            'marketing_site_id' => $site->id,
            'heading' => 'Yasal',
            'sort_order' => $maxOrder + 1,
        ]);

        MarketingFooterLink::query()->create([
            'marketing_footer_column_id' => $col->id,
            'label' => 'KVKK aydınlatma',
            'url' => route('marketing.home').'#kvkk',
            'open_in_new_tab' => false,
            'sort_order' => 0,
        ]);
        MarketingFooterLink::query()->create([
            'marketing_footer_column_id' => $col->id,
            'label' => 'Kullanım koşulları',
            'url' => route('marketing.home').'#kosullar',
            'open_in_new_tab' => false,
            'sort_order' => 1,
        ]);
    }
}
