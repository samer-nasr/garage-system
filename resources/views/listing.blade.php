@extends('layout')
@section('title',$listing->translated('title'))
@section('content')
<section class="section"><div class="container">
<a class="back" href="{{ route('catalog',[$locale,$listing->section()]) }}">← {{ __('site.back') }}</a>
<div class="detail-grid"><div><div class="detail-image">@if($listing->image_url)<img src="{{ $listing->image_url }}" alt="{{ $listing->translated('title') }}">@else<div class="part-placeholder">⚙</div>@endif</div>
@if(count($listing->images ?? [])>1)<div class="gallery">@foreach(array_slice($listing->images,1) as $image)<a href="{{ asset('storage/'.$image) }}"><img src="{{ asset('storage/'.$image) }}" alt="{{ $listing->translated('title') }}" loading="lazy"></a>@endforeach</div>@endif</div>
<div><span class="eyebrow">{{ \App\Models\Listing::label($listing->category ?: $listing->make) }}</span><h1>{{ $listing->translated('title') }}</h1>
@if($listing->is_demo)<div class="notice">{{ __('site.demo_note') }}</div>@endif
<p class="detail-price">{{ $listing->price!==null ? '€'.number_format($listing->price,2) : __('site.ask_price') }}@if($listing->type==='car')<small> / {{ __('site.day') }}</small>@endif</p>
<p class="description">{{ $listing->translated('description') }}</p>
<dl class="specs">
@foreach(['part_number','oem_number','make','model','compatibility','year','transmission','fuel'] as $field)@if($listing->$field)<div><dt>{{ __('site.'.$field) }}</dt><dd>{{ \App\Models\Listing::label($listing->$field) }}</dd></div>@endif @endforeach
@foreach(['engine','mileage','vehicle_condition'] as $field)@if($listing->$field!==null)<div><dt>{{ __('business.'.$field) }}</dt><dd>{{ $field==='vehicle_condition' ? __('business.'.$listing->$field) : $listing->$field }}</dd></div>@endif @endforeach
<div><dt>{{ __('site.availability') }}</dt><dd>{{ __('business.'.$listing->availability) }}@if($listing->type==='part') · {{ $listing->stock>0 ? __('site.in_stock') : __('site.out_stock') }}@endif</dd></div>
@if($listing->type==='part')<div><dt>{{ __('site.condition') }}</dt><dd>{{ __('site.'.$listing->condition) }}</dd></div>@endif
</dl>
@if($listing->fitments->isNotEmpty())<h3>{{ __('business.fitments') }}</h3><ul>@foreach($listing->fitments as $fitment)<li>{{ $fitment->make }} {{ $fitment->model }} · {{ $fitment->year_from }}–{{ $fitment->year_to }}</li>@endforeach</ul>@endif
@if($listing->type==='part')
<form class="actions" method="post" action="{{ route('basket.add',[$locale,$listing]) }}">@csrf<label>{{ __('site.quantity') }}<input type="number" name="quantity" value="1" min="1" max="{{ min($listing->stock,999) }}" required></label><button class="button yellow" @disabled($listing->stock<1 || $listing->price===null || $listing->availability!=='available')>{{ __('site.add_basket') }} +</button></form><p class="small">{{ __('site.delivery_note') }}</p>
@endif
@if(in_array($listing->type,['part','dismantling']))<a class="button outline" href="{{ route('inquiry.form',[$locale,'request-part','listing'=>$listing->id]) }}">{{ __('business.request-part') }} ↗</a>@endif
@if($listing->type!=='part')<p class="notice">{{ $listing->type==='service' ? __('site.garage_note') : __('business.manual_confirmation') }}</p>@endif
<h3>{{ $listing->type==='sale' ? __('business.viewing') : __('business.contact_options') }}</h3>
@include('partials.messaging',['compact'=>false])
<a class="button outline" href="tel:+37065498007">{{ __('site.call') }} · +370 654 98007</a>
</div></div></div></section>
@endsection
