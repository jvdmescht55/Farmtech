@component('mail::message')
# {{ count($published) }} product{{ count($published) === 1 ? '' : 's' }} auto-published — no review needed

Gemini was fully confident in {{ count($published) }} pending listing{{ count($published) === 1 ? '' : 's' }}, and its category wasn't already at its live cap, so {{ count($published) === 1 ? 'it went' : 'they went' }} live automatically. Worth a quick look — every listing here skipped human review entirely.

@component('mail::table')
| Title | Category | Why the AI was confident |
|:------|:---------|:--------------------------|
@foreach ($published as $row)
| {{ $row['product']->title }} | {{ $row['product']->category->label() }} | {{ implode('; ', $row['reasons']) }} |
@endforeach
@endcomponent

@if (count($capped) > 0)
{{ count($capped) }} other listing{{ count($capped) === 1 ? '' : 's' }} the AI was equally confident in stayed in the review queue — their category is already at its live cap:

@component('mail::table')
| Title | Category |
|:------|:---------|
@foreach ($capped as $row)
| {{ $row['product']->title }} | {{ $row['product']->category->label() }} |
@endforeach
@endcomponent
@endif

@component('mail::button', ['url' => route('admin.products.live')])
View Live Products
@endcomponent
@endcomponent
