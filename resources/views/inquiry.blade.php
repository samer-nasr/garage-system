@extends('layout')
@section('title',__('business.'.$kind))
@section('content')
<section class="page-heading"><div class="container"><span class="eyebrow">MB GREIČIO AUTO</span><h1>{{ __('business.'.$kind) }}<span class="yellow-text">.</span></h1><p>{{ $kind==='sell-car' ? __('business.sell-car_text') : __('business.request_intro') }}</p></div></section>
<section class="section"><div class="container checkout-grid">
<form class="panel" action="{{ route('inquiry.store',[$locale,$kind]) }}" method="post" enctype="multipart/form-data">
@csrf
@if($listing)<div class="notice"><a href="{{ route('listing',[$locale,$listing]) }}">{{ $listing->translated('title') }} · #{{ $listing->id }}</a></div><input type="hidden" name="listing_id" value="{{ $listing->id }}">@endif
<div class="form-grid">
@foreach(['name','phone','email'] as $field)<label>{{ __('site.'.$field) }} {{ $field!=='email'?'*':'' }}<input type="{{ $field==='email'?'email':($field==='phone'?'tel':'text') }}" name="{{ $field }}" value="{{ old($field) }}" maxlength="{{ $field==='name'?120:($field==='phone'?40:180) }}" autocomplete="{{ $field==='phone'?'tel':$field }}" @required($field!=='email')></label>@endforeach
@foreach(['make','model','year'] as $field)<label>{{ __('site.'.$field) }} {{ $kind==='sell-car'?'*':'' }}<input name="{{ $field }}" value="{{ old($field) }}" @if($field==='year')type="number" min="1900" max="{{ date('Y')+1 }}"@else maxlength="100"@endif @required($kind==='sell-car')></label>@endforeach
@if($kind==='sell-car')
<label>{{ __('business.mileage') }} *<input type="number" name="mileage" value="{{ old('mileage') }}" min="0" max="9999999" required></label>
<label>{{ __('business.asking_price') }}<input type="number" name="asking_price" value="{{ old('asking_price') }}" step="0.01" min="0" max="9999999.99"></label>
<label>{{ __('business.vehicle_condition') }}<select name="condition"><option value="">—</option>@foreach(['excellent','good','needs_repair','damaged','non_runner'] as $condition)<option value="{{ $condition }}" @selected(old('condition')===$condition)>{{ __('business.'.$condition) }}</option>@endforeach</select></label>
@else<label>{{ __('site.oem_number') }}<input name="oem_number" value="{{ old('oem_number') }}" maxlength="100"></label>@endif
</div>
<label>{{ __('business.message') }} *<textarea name="message" rows="5" maxlength="5000" required>{{ old('message') }}</textarea></label>
<label>{{ __('business.photos') }}<input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp"><span class="small">{{ __('business.photo_help') }}</span></label>
<div class="honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
<label class="checkbox"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>{{ __('business.consent') }} <a href="{{ route('privacy',$locale) }}" target="_blank">{{ __('site.privacy') }}</a></span></label>
<button class="button yellow">{{ __('business.send') }} ↗</button>
</form>
<aside><span class="eyebrow">{{ __('business.contact_options') }}</span><h2>{{ __('site.talk') }}</h2><p>{{ __('business.manual_confirmation') }}</p>@include('partials.messaging',['contactText'=>$kind==='sell-car' ? __('business.message_offer') : __('business.message_general')])<p><a href="tel:+37065498007">+370 654 98007</a></p></aside>
</div></section>
@endsection
