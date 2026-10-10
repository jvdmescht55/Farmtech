<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoice {{ $p->reference }} · Farmtech</title>
<meta name="robots" content="noindex">
<style>
    body { font: 14px/1.5 system-ui, -apple-system, Segoe UI, sans-serif; color: #1f1b16; background: #f4efe6; margin: 0; }
    .sheet { max-width: 720px; margin: 24px auto; background: #fff; padding: 40px; border-radius: 16px; }
    h1 { font: 400 40px/1 Georgia, serif; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 24px; }
    td, th { padding: 10px 0; border-bottom: 1px solid #e7e0d4; text-align: left; } .r { text-align: right; }
    .muted { color: #7a7266; } .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 28px; }
    .paid { display: inline-block; padding: 4px 10px; border-radius: 99px; background: #3F7A3A; color: #fff; font-size: 12px; }
    @media print { body { background: #fff; } .sheet { margin: 0; border-radius: 0; } .noprint { display: none; } }
    @media (max-width: 600px) { .sheet { padding: 22px; margin: 0; border-radius: 0; } .grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="sheet">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
        <div><h1>Invoice</h1><div class="muted" style="margin-top:6px">{{ $p->reference }} · {{ $p->created_at->format('j F Y') }}</div></div>
        <div style="text-align:right"><strong>Farmtech</strong><br><span class="muted">farmtech.site</span>@if ($p->status === 'paid')<br><span class="paid">Paid {{ $p->paid_at?->format('j M Y') }}</span>@endif</div>
    </div>
    <div class="grid">
        <div><div class="muted">Billed to</div>{{ $p->user->name }}<br>{{ $p->user->farm_name }}<br>{{ $p->user->email }}</div>
        <div><div class="muted">Period</div>{{ $p->period_start?->format('j M Y') }} to {{ $p->period_end?->format('j M Y') }}</div>
    </div>
    <table>
        <tr><th>Item</th><th class="r">Amount</th></tr>
        <tr><td>Herd Manager, {{ $p->plan === 'yearly' ? 'one year' : 'one month' }} (whole farm, all devices)</td><td class="r">{{ $p->rand() }}</td></tr>
        <tr><td><strong>Total</strong></td><td class="r"><strong>{{ $p->rand() }}</strong></td></tr>
    </table>
    @if ($p->status !== 'paid')
        <div style="margin-top:28px"><div class="muted">Pay by EFT</div>
            @if (filled($bank['account_number'])){{ $bank['name'] }} · {{ $bank['account_name'] }} · Acc {{ $bank['account_number'] }} · Branch {{ $bank['branch_code'] }}<br>@endif
            Reference: <strong>{{ $p->reference }}</strong>
        </div>
    @endif
    <p class="noprint" style="margin-top:32px"><button onclick="print()">Print or save as PDF</button></p>
</div>
</body>
</html>
