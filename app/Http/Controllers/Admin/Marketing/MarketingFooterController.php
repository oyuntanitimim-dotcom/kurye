<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\MarketingFooterColumn;
use App\Models\Marketing\MarketingFooterLink;
use App\Services\Marketing\MarketingBootstrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class MarketingFooterController extends Controller
{
    public function edit(MarketingBootstrap $bootstrap): View
    {
        $site = $bootstrap->ensureSiteExists();
        $columns = MarketingFooterColumn::query()
            ->where('marketing_site_id', $site->id)
            ->with('links')
            ->orderBy('sort_order')
            ->get();

        return view('admin.marketing.footer', [
            'title' => 'Footer',
            'site' => $site,
            'columns' => $columns,
        ]);
    }

    public function update(Request $request, MarketingBootstrap $bootstrap): RedirectResponse
    {
        $site = $bootstrap->ensureSiteExists();

        $data = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*.id' => ['required', 'integer', 'exists:marketing_footer_columns,id'],
            'columns.*.heading' => ['nullable', 'string', 'max:255'],
            'columns.*.links' => ['nullable', 'array'],
            'columns.*.links.*.id' => ['nullable', 'integer'],
            'columns.*.links.*.label' => ['nullable', 'string', 'max:255'],
            'columns.*.links.*.url' => ['nullable', 'string', 'max:2048'],
            'columns.*.links.*.open_in_new_tab' => ['sometimes', 'boolean'],
            'columns.*.links.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        foreach ($data['columns'] as $colInput) {
            $column = MarketingFooterColumn::query()->where('marketing_site_id', $site->id)->findOrFail($colInput['id']);
            $column->update([
                'heading' => $colInput['heading'] ?? null,
            ]);

            foreach ($colInput['links'] ?? [] as $linkInput) {
                if (empty($linkInput['id'])) {
                    if (empty($linkInput['label']) && empty($linkInput['url'])) {
                        continue;
                    }
                    MarketingFooterLink::query()->create([
                        'marketing_footer_column_id' => $column->id,
                        'label' => (string) ($linkInput['label'] ?? ''),
                        'url' => (string) ($linkInput['url'] ?? '#'),
                        'open_in_new_tab' => ! empty($linkInput['open_in_new_tab']),
                        'sort_order' => (int) ($linkInput['sort_order'] ?? 0),
                    ]);

                    continue;
                }

                $link = MarketingFooterLink::query()->where('marketing_footer_column_id', $column->id)->find((int) $linkInput['id']);
                if ($link === null) {
                    continue;
                }

                if (($linkInput['label'] ?? '') === '' && ($linkInput['url'] ?? '') === '') {
                    $link->delete();

                    continue;
                }

                $link->update([
                    'label' => (string) ($linkInput['label'] ?? ''),
                    'url' => (string) ($linkInput['url'] ?? '#'),
                    'open_in_new_tab' => ! empty($linkInput['open_in_new_tab']),
                    'sort_order' => (int) ($linkInput['sort_order'] ?? 0),
                ]);
            }
        }

        return redirect()->route('admin.marketing.footer.edit')->with('ok', 'Footer kaydedildi.');
    }

    public function storeColumn(MarketingBootstrap $bootstrap): RedirectResponse
    {
        $site = $bootstrap->ensureSiteExists();
        $max = (int) MarketingFooterColumn::query()->where('marketing_site_id', $site->id)->max('sort_order');

        MarketingFooterColumn::query()->create([
            'marketing_site_id' => $site->id,
            'heading' => 'Yeni sütun',
            'sort_order' => $max + 1,
        ]);

        return redirect()->route('admin.marketing.footer.edit')->with('ok', 'Sütun eklendi.');
    }

    public function destroyColumn(MarketingBootstrap $bootstrap, MarketingFooterColumn $column): RedirectResponse
    {
        $site = $bootstrap->ensureSiteExists();
        abort_unless((int) $column->marketing_site_id === (int) $site->id, 404);
        $column->delete();

        return redirect()->route('admin.marketing.footer.edit')->with('ok', 'Sütun silindi.');
    }
}
