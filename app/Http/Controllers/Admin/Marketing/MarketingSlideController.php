<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\MarketingSlide;
use App\Services\Marketing\MarketingBootstrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class MarketingSlideController extends Controller
{
    public function index(MarketingBootstrap $bootstrap): View
    {
        $site = $bootstrap->ensureSiteExists();
        $slides = MarketingSlide::query()
            ->where('marketing_site_id', $site->id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.marketing.slides.index', [
            'title' => 'Slider',
            'site' => $site,
            'slides' => $slides,
        ]);
    }

    public function create(MarketingBootstrap $bootstrap): View
    {
        $site = $bootstrap->ensureSiteExists();

        return view('admin.marketing.slides.form', [
            'title' => 'Yeni slayt',
            'site' => $site,
            'slide' => new MarketingSlide(['active' => true]),
        ]);
    }

    public function store(Request $request, MarketingBootstrap $bootstrap): RedirectResponse
    {
        $site = $bootstrap->ensureSiteExists();
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:2048'],
            'active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('marketing/slides', 'public');
        }

        $max = (int) MarketingSlide::query()->where('marketing_site_id', $site->id)->max('sort_order');

        MarketingSlide::query()->create([
            'marketing_site_id' => $site->id,
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'image_path' => $path,
            'cta_label' => $validated['cta_label'] ?? null,
            'cta_url' => $validated['cta_url'] ?? null,
            'sort_order' => $max + 1,
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        return redirect()->route('admin.marketing.slides.index')->with('ok', 'Slayt eklendi.');
    }

    public function edit(MarketingBootstrap $bootstrap, MarketingSlide $slide): View
    {
        $site = $bootstrap->ensureSiteExists();
        abort_unless((int) $slide->marketing_site_id === (int) $site->id, 404);

        return view('admin.marketing.slides.form', [
            'title' => 'Slayt düzenle',
            'site' => $site,
            'slide' => $slide,
        ]);
    }

    public function update(Request $request, MarketingBootstrap $bootstrap, MarketingSlide $slide): RedirectResponse
    {
        $site = $bootstrap->ensureSiteExists();
        abort_unless((int) $slide->marketing_site_id === (int) $site->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:2048'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $path = $slide->image_path;
        if ($request->hasFile('image')) {
            if ($path !== null && ! str_starts_with((string) $path, 'http')) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('image')->store('marketing/slides', 'public');
        }

        $slide->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'image_path' => $path,
            'cta_label' => $validated['cta_label'] ?? null,
            'cta_url' => $validated['cta_url'] ?? null,
            'sort_order' => $validated['sort_order'],
            'active' => (bool) ($validated['active'] ?? false),
        ]);

        return redirect()->route('admin.marketing.slides.index')->with('ok', 'Slayt güncellendi.');
    }

    public function destroy(MarketingBootstrap $bootstrap, MarketingSlide $slide): RedirectResponse
    {
        $site = $bootstrap->ensureSiteExists();
        abort_unless((int) $slide->marketing_site_id === (int) $site->id, 404);

        if ($slide->image_path !== null && ! str_starts_with((string) $slide->image_path, 'http')) {
            Storage::disk('public')->delete($slide->image_path);
        }
        $slide->delete();

        return redirect()->route('admin.marketing.slides.index')->with('ok', 'Slayt silindi.');
    }
}
