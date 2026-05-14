<section @if(!empty($data['id'])) id="{{ $data['id'] }}" @endif class="prose prose-headings:font-semibold prose-headings:text-red-950 prose-p:text-red-900/85 prose-li:text-red-900/85 prose-a:text-red-600 mt-12 scroll-mt-28 max-w-none rounded-2xl border border-red-100 bg-white px-6 py-8 shadow-sm sm:px-10">
    {!! $data['html'] ?? '' !!}
</section>
