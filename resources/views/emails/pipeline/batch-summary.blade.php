@component('mail::message')
# {{ count($pending) }} new product{{ count($pending) === 1 ? '' : 's' }} sourced and awaiting review

A scraper batch just ran through AI vetting and landed-cost calculation. {{ count($pending) }} listing{{ count($pending) === 1 ? '' : 's' }} passed and {{ count($pending) === 1 ? 'is' : 'are' }} staged as `pending_review` — nothing is live on the storefront until you approve it.

@component('mail::table')
| Title | SKU | Verdict |
|:------|:----|:-------:|
@foreach ($pending as $item)
| {{ $item['title'] ?? '—' }} | {{ $item['sku'] ?? '—' }} | {{ $item['verdict'] ?? '—' }} |
@endforeach
@endcomponent

@if (count($rejected) > 0)
{{ count($rejected) }} other listing{{ count($rejected) === 1 ? '' : 's' }} in this batch failed compliance and {{ count($rejected) === 1 ? 'was' : 'were' }} recorded as rejected, not staged for review.
@endif

@if (count($errored) > 0)
{{ count($errored) }} listing{{ count($errored) === 1 ? '' : 's' }} errored out during processing and {{ count($errored) === 1 ? 'was' : 'were' }} not inserted at all — check the queue logs.
@endif

@component('mail::button', ['url' => route('admin.products.index')])
Review Staging Queue
@endcomponent
@endcomponent
