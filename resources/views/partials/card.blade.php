<article class="product-card">
<a class="product-image" href="{{ route('listing',[$locale,$listing]) }}">@if($listing->image_url)<img src="{{ $listing->image_url }}" alt="{{ $listing->translated('title') }}" loading="lazy">@else<div class="part-placeholder" aria-hidden="true">{{ $listing->type==='service'?'⚙':'↗' }}</div>@endif @if($listing->is_demo)<span class="badge demo">{{ __('site.demo') }}</span>@elseif($listing->type==='part')<span class="badge">{{ __('site.'.$listing->condition) }}</span>@endif</a>
<div class="product-info"><span class="eyebrow">{{ \App\Models\Listing::label($listing->category ?: $listing->make) }}</span><h3><a href="{{ route('listing',[$locale,$listing]) }}">{{ $listing->translated('title') }}</a></h3>
@if($listing->type==='part')@include('partials.part-vehicle')@endif
<p class="small">{{ $listing->type==='part' ? $listing->part_number : trim($listing->year.' · '.\App\Models\Listing::label($listing->transmission).' · '.\App\Models\Listing::label($listing->fuel),' ·') }}</p>
<p class="small">{{ __('business.'.$listing->availability) }}@if($listing->type==='sale' && $listing->mileage!==null) · {{ number_format($listing->mileage) }} km @endif</p>
<div class="product-bottom"><strong>{{ $listing->price!==null ? '€'.number_format($listing->price,2) : __('site.ask_price') }}@if($listing->type==='car')<small> / {{ __('site.day') }}</small>@endif</strong><a class="circle-link" aria-label="{{ __('site.details') }}" href="{{ route('listing',[$locale,$listing]) }}">↗</a></div>
@include('partials.messaging',['compact'=>true])
</div></article>
