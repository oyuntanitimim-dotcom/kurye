@extends('layouts.firm')

@section('content')
<h1 class="text-2xl font-semibold mb-2">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-6">Kurye bazında teslim sayısı, sipariş cirosu ve sistemde kayıtlı teslim başı ücret toplamları (nakit/kapıda tahsilat takibi ayrı tutulabilir).</p>

@include('firm.finance._date_filter', ['action' => route('firm.finance.courier_collections'), 'filters' => $filters, 'couriers' => $couriers ?? null])

<p class="text-slate-700 mb-4">Toplam kurye ücreti (kayıtlı): <strong>{{ number_format($payoutGrand, 2) }} ₺</strong></p>

<dialog id="courierDetailDialog" class="z-[200] rounded-2xl border-0 bg-white p-0 shadow-2xl">
    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Kurye finans detayı</h2>
        <div class="flex items-center gap-2">
            <button type="button" id="courierDetailPrintBtn" class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100">Yazdır</button>
            <button type="button" id="courierDetailPdfBtn" class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100">PDF</button>
            <button type="button" id="courierDetailSaveBtn" class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100">Kaydet</button>
            <form method="dialog"><button type="submit" class="rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-200">Kapat</button></form>
        </div>
    </div>
    <div id="courierDetailBody" class="p-4 text-sm text-slate-700">Yükleniyor...</div>
</dialog>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm">
    <table class="w-full min-w-[28rem]">
        <thead>
            <tr class="border-b border-slate-200 text-left text-slate-500">
                <th class="py-3 px-4">Kurye</th>
                <th class="py-3 px-4">Teslim adet</th>
                <th class="py-3 px-4">Ücret toplamı</th>
                <th class="py-3 px-4">Sipariş cirosu</th>
                <th class="py-3 px-4">Detay</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr class="border-b border-slate-100">
                    <td class="py-2 px-4 font-medium">{{ $courierNames[$row->courier_id] ?? ('#'.$row->courier_id) }}</td>
                    <td class="py-2 px-4">{{ $row->order_count }}</td>
                    <td class="py-2 px-4">{{ number_format((float) $row->payout_total, 2) }} ₺</td>
                    <td class="py-2 px-4">{{ number_format((float) $row->revenue_total, 2) }} ₺</td>
                    <td class="py-2 px-4">
                        <button
                            type="button"
                            class="courier-detail-btn rounded border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50"
                            data-url="{{ route('firm.finance.courier_collections.detail', ['courier' => $row->courier_id] + request()->only(['date_from','date_to','courier_id'])) }}"
                        >Detay</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-8 px-4 text-center text-slate-500">Bu aralıkta kuryeli teslim yok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('styles')
<style>
#courierDetailDialog[open] {
    position: fixed;
    inset: 0;
    margin: auto;
    width: min(100% - 1.5rem, 56rem);
    max-width: 56rem;
    max-height: min(100vh - 2rem, 88vh);
    height: fit-content;
    border: 1px solid #e2e8f0;
    padding: 0;
    overflow: auto;
}
#courierDetailDialog::backdrop {
    background: rgba(15, 23, 42, 0.5);
}
</style>
@endpush

@push('scripts')
<script>
(function () {
  const dlg = document.getElementById('courierDetailDialog');
  const body = document.getElementById('courierDetailBody');
  const printBtn = document.getElementById('courierDetailPrintBtn');
  const pdfBtn = document.getElementById('courierDetailPdfBtn');
  const saveBtn = document.getElementById('courierDetailSaveBtn');
  if (!dlg || !body) return;
  let lastDetail = null;

  function money(v) { return `${Number(v || 0).toFixed(2)} ₺`; }
  function row(k, v, strong = false) {
    return `<div class="flex items-center justify-between py-1"><span>${k}</span><span class="${strong ? 'font-semibold' : ''}">${v}</span></div>`;
  }
  function textLines(d) {
    return [
      `Kurye finans detayı - ${d.courier?.name ?? '—'}`,
      `Teslim adet: ${d.orders?.count ?? 0}`,
      `Sipariş cirosu: ${money(d.orders?.revenue_total)}`,
      `Hakediş (sipariş): ${money(d.orders?.payout_total)}`,
      `Aldıkları (ödenen): ${money(d.settlements?.paid_total)}`,
      `Verdikleri (avans): ${money(d.ledger?.advance_total)}`,
      `Giderleri (kesinti): ${money(d.ledger?.expense_total)}`,
      `Gelirleri (prim): ${money(d.ledger?.credit_total)}`,
      `Kurye alacağı: ${money(d.balance?.courier_receivable)}`,
      `Kurye vereceği: ${money(d.balance?.courier_payable)}`,
      `Net bakiye: ${money(d.balance?.net)}`,
    ];
  }
  function printDetail(d, forPdf) {
    const w = window.open('', '_blank', 'width=900,height=700');
    if (!w) return;
    const title = `Kurye Finans Detayı - ${d.courier?.name ?? 'Kurye'}`;
    const lines = textLines(d)
      .map((line) => `<tr><td style="padding:6px 10px;border:1px solid #cbd5e1;">${line.split(':')[0]}</td><td style="padding:6px 10px;border:1px solid #cbd5e1;text-align:right;">${line.split(':').slice(1).join(':').trim()}</td></tr>`)
      .join('');
    w.document.write(`
      <html><head><title>${title}</title></head>
      <body style="font-family: Arial, sans-serif; padding: 16px;">
        <h2 style="margin:0 0 12px 0;">${title}</h2>
        <table style="border-collapse: collapse; width: 100%;">${lines}</table>
        <p style="margin-top:12px;color:#475569;font-size:12px;">${forPdf ? 'İpucu: Yazdır penceresinde Hedef → PDF olarak kaydet seçin.' : ''}</p>
      </body></html>
    `);
    w.document.close();
    w.focus();
    w.print();
  }

  document.querySelectorAll('.courier-detail-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const url = btn.getAttribute('data-url');
      if (!url) return;
      body.innerHTML = 'Yükleniyor...';
      dlg.showModal();
      try {
        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const d = await r.json();
        lastDetail = d;
        body.innerHTML = [
          `<div class="mb-2 font-medium">${d.courier?.name ?? '—'}</div>`,
          row('Teslim adet', `${d.orders?.count ?? 0}`),
          row('Sipariş cirosu', money(d.orders?.revenue_total)),
          row('Hakediş (sipariş)', money(d.orders?.payout_total)),
          '<hr class="my-2">',
          row('Aldıkları (ödenen)', money(d.settlements?.paid_total)),
          row('Verdikleri (avans)', money(d.ledger?.advance_total)),
          row('Giderleri (kesinti)', money(d.ledger?.expense_total)),
          row('Gelirleri (prim)', money(d.ledger?.credit_total)),
          '<hr class="my-2">',
          row('Kurye alacağı', money(d.balance?.courier_receivable), true),
          row('Kurye vereceği', money(d.balance?.courier_payable), true),
          row('Net bakiye', money(d.balance?.net), true),
        ].join('');
      } catch (e) {
        body.innerHTML = 'Detay yüklenemedi.';
      }
    });
  });

  if (printBtn) {
    printBtn.addEventListener('click', function () {
      if (!lastDetail) return;
      printDetail(lastDetail, false);
    });
  }
  if (pdfBtn) {
    pdfBtn.addEventListener('click', function () {
      if (!lastDetail) return;
      printDetail(lastDetail, true);
    });
  }
  if (saveBtn) {
    saveBtn.addEventListener('click', function () {
      if (!lastDetail) return;
      const txt = textLines(lastDetail).join('\n');
      const blob = new Blob([txt], { type: 'text/plain;charset=utf-8' });
      const a = document.createElement('a');
      const name = (lastDetail.courier?.name ?? 'kurye').toString().replace(/\s+/g, '_').toLowerCase();
      a.href = URL.createObjectURL(blob);
      a.download = `kurye-finans-detay-${name}.txt`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(a.href);
    });
  }
})();
</script>
@endpush
