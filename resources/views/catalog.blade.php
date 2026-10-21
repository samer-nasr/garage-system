@extends('layout')
@section('title',__(in_array($section,['dismantling','cars-for-sale'])?'business.'.$section:'site.'.$section))
@section('content')
@php($heading=__(in_array($section,['dismantling','cars-for-sale'])?'business.'.$section:'site.'.$section))
<section class="page-heading"><div class="container"><span class="eyebrow">MB GREIČIO AUTO</span><h1>{{ $heading }}<span class="yellow-text">.</span></h1><p>{{ __(in_array($section,['dismantling','cars-for-sale'])?'business.'.$section.'_text':'site.'.$section.'_text') }}</p></div></section>
<section class="section"><div class="container">
<div class="notice">{{ $section==='parts' ? __('site.delivery_note') : ($section==='services' ? __('site.garage_note') : __('business.manual_confirmation')) }}</div>
@if(in_array($section,['parts','dismantling']))<a class="button yellow catalog-request" href="{{ route('inquiry.form',[$locale,'request-part']) }}">{{ __('business.request-part') }} ↗</a>@endif
<form class="filters" method="get">
<label>{{ __('site.search') }}<input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $section==='parts' ? __('site.search_hint') : __('site.search') }}"></label>
<label>{{ __('site.category') }}<select name="category"><option value="">{{ __('site.all') }}</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category')===$category)>{{ \App\Models\Listing::label($category) }}</option>@endforeach</select></label>
@if($section!=='services')
<label>{{ __('site.make') }}<select name="make"><option value="">{{ __('site.all') }}</option>@foreach($makes as $make)<option @selected(request('make')===$make)>{{ $make }}</option>@endforeach</select></label>
<label>{{ __('site.model') }}<select name="model"><option value="">{{ __('site.all') }}</option>@foreach($models as $model)<option @selected(request('model')===$model)>{{ $model }}</option>@endforeach</select></label>
<label>{{ __('site.year') }}<input type="number" name="year" min="1900" max="{{ date('Y')+1 }}" value="{{ request('year') }}"></label>
@endif
@if($section==='parts')<label>{{ __('site.condition') }}<select name="condition"><option value="">{{ __('site.all') }}</option>@foreach(['new','used'] as $condition)<option value="{{ $condition }}" @selected(request('condition')===$condition)>{{ __('site.'.$condition) }}</option>@endforeach</select></label>@endif
<button class="button dark">{{ __('site.search') }} ↗</button><a class="small" href="{{ route('catalog',[$locale,$section]) }}">{{ __('site.reset') }}</a>
</form>
<div class="results-line">{{ $listings->total() }} {{ __('site.results') }}</div><div class="product-grid">@forelse($listings as $listing)@include('partials.card')@empty<div class="empty"><h3>{{ __('site.no_results') }}</h3><p>{{ __('site.inventory_help') }}</p><a class="button dark" href="tel:+37065498007">{{ __('site.call') }}</a></div>@endforelse</div>
<div class="pagination">{{ $listings->links('pagination') }}</div></div></section>
@endsection
