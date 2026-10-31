@extends('admin.layout')
@section('content')
<a class="back" href="{{ route('admin') }}">← {{ __('admin.dashboard') }}</a><h1>{{ $listing->exists?__('admin.edit_listing'):__('admin.add_listing') }}</h1>
<form class="panel admin-form" method="post" enctype="multipart/form-data" action="{{ $listing->exists ? route('admin.update',$listing) : route('admin.store') }}">
@csrf @if($listing->exists)@method('put')@endif
<div class="form-grid">
<label>{{ __('admin.type') }} *<select name="type">@foreach(['part','car','sale','dismantling','service'] as $type)<option value="{{ $type }}" @selected(old('type',$listing->type)===$type)>{{ __('admin.'.$type) }}</option>@endforeach</select></label>
<label>{{ __('site.condition') }}<select name="condition">@foreach(['new','used'] as $condition)<option value="{{ $condition }}" @selected(old('condition',$listing->condition)===$condition)>{{ __('site.'.$condition) }}</option>@endforeach</select></label>
<label>{{ __('business.availability') }}<select name="availability">@foreach(['available','reserved','sold','unavailable','breaking'] as $status)<option value="{{ $status }}" @selected(old('availability',$listing->availability ?? 'available')===$status)>{{ __('business.'.$status) }}</option>@endforeach</select></label>
<label>{{ __('business.vehicle_condition') }}<select name="vehicle_condition"><option value="">—</option>@foreach(['excellent','good','needs_repair','damaged','non_runner'] as $condition)<option value="{{ $condition }}" @selected(old('vehicle_condition',$listing->vehicle_condition)===$condition)>{{ __('business.'.$condition) }}</option>@endforeach</select></label>
</div><p class="small">{{ __('admin.pricing_help') }}</p>
<div class="three-cards">@foreach(['lt','en','ru'] as $lang)<fieldset><legend>{{ __('admin.'.$lang) }}</legend><label>{{ __('admin.title') }} {{ $lang==='lt'?'*':'' }}<input name="title[{{ $lang }}]" value="{{ old('title.'.$lang,$listing->title[$lang] ?? '') }}" maxlength="180" @required($lang==='lt')></label><label>{{ __('admin.description') }}<textarea rows="6" name="description[{{ $lang }}]">{{ old('description.'.$lang,$listing->description[$lang] ?? '') }}</textarea></label></fieldset>@endforeach</div>
<div class="form-grid">
@foreach(['category','make','model','compatibility','part_number','oem_number','year','transmission','fuel'] as $field)<label>{{ __('site.'.$field) }}<input name="{{ $field }}" value="{{ old($field,$listing->$field) }}" @if($field==='year')type="number" min="1900" max="2100"@endif></label>@endforeach
<label>{{ __('admin.price') }}<input type="number" name="price" value="{{ old('price',$listing->price) }}" min="0" max="999999.99" step="0.01"></label>
<label>{{ __('admin.stock') }} *<input type="number" name="stock" value="{{ old('stock',$listing->stock ?? 1) }}" min="0" max="999999" required></label>
<label>{{ __('business.mileage') }}<input type="number" name="mileage" value="{{ old('mileage',$listing->mileage) }}" min="0" max="9999999"></label>
<label>{{ __('business.engine') }}<input name="engine" value="{{ old('engine',$listing->engine) }}" maxlength="120"></label>
</div>
<h3>{{ __('business.fitments') }}</h3><p class="small">{{ __('admin.fitment_help') }}</p>
@php($fitmentRows=old('fitments',$listing->exists?$listing->fitments->toArray():[]))
@php($fitmentRows=$fitmentRows ?: [[]])
<div id="fitment-rows" data-next="{{ count($fitmentRows) }}">@foreach($fitmentRows as $index=>$fitment)@include('admin.fitment-row')@endforeach</div>
<template id="fitment-template">@include('admin.fitment-row',['index'=>'__INDEX__','fitment'=>[]])</template>
<button type="button" class="button outline" data-add-fitment>{{ __('admin.add_fitment') }} +</button>
<label>{{ __('business.photos') }}<input name="images[]" type="file" multiple accept="image/jpeg,image/png,image/webp"><span class="small">{{ __('admin.photo_help') }}</span></label>
@if($listing->images)<div class="gallery">@foreach($listing->images as $index=>$image)<label><img src="{{ asset('storage/'.$image) }}" alt="{{ __('business.photos') }}"><span class="checkbox"><input type="checkbox" name="remove_images[]" value="{{ $index }}">{{ __('site.remove') }}</span></label>@endforeach</div>
<label class="checkbox"><input type="checkbox" name="clear_images" value="1">{{ __('admin.replace_photos') }}</label>@endif
<input type="hidden" name="is_active" value="0"><label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$listing->exists?$listing->is_active:true))>{{ __('admin.visible_on_site') }}</label>
<input type="hidden" name="is_demo" value="0"><label class="checkbox"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo',$listing->is_demo))>{{ __('admin.demo') }}</label>
<button class="button yellow">{{ __('admin.save') }} ↗</button></form>
@if($listing->exists)<form class="panel archive-panel" data-confirm="{{ __('admin.delete_confirm') }}" method="post" action="{{ route('admin.archive',$listing) }}">@csrf @method('delete')<p>{{ __('admin.archive_help') }}</p><button class="button outline">{{ __('admin.delete_listing') }}</button></form>@endif
@endsection
