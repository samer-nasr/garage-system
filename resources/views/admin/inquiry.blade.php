@extends('admin.layout')
@section('content')
<a class="back" href="{{ route('admin') }}">← {{ __('admin.dashboard') }}</a><h1>{{ __('admin.'.$inquiry->type) }}</h1><p>{{ $inquiry->reference }} · {{ $inquiry->created_at }}</p>
<div class="detail-grid"><div class="panel"><h2>{{ $inquiry->name }}</h2><p><a href="tel:{{ $inquiry->phone }}">{{ $inquiry->phone }}</a><br>{{ $inquiry->email }}</p>
<dl class="specs">@foreach(['make','model','year','oem_number'] as $field)@if($inquiry->$field)<div><dt>{{ __('site.'.$field) }}</dt><dd>{{ $inquiry->$field }}</dd></div>@endif @endforeach
@foreach(['mileage','asking_price','condition'] as $field)@if($inquiry->$field!==null)<div><dt>{{ __('business.'.($field==='condition'?'vehicle_condition':$field)) }}</dt><dd>{{ $field==='condition' ? __('business.'.$inquiry->$field) : $inquiry->$field }}</dd></div>@endif @endforeach</dl>
@if($inquiry->listing)<p>{{ __('admin.title') }}: {{ $inquiry->listing->translated('title') }} · #{{ $inquiry->listing_id }}</p>@endif
<p class="description">{{ $inquiry->message }}</p><p>{{ __('admin.language') }}: {{ strtoupper($inquiry->locale) }}</p>
<h3>{{ __('admin.private_photos') }}</h3><div class="gallery inquiry-photos">@foreach($inquiry->photos ?? [] as $index=>$photo)<a href="{{ route('admin.inquiry.photo',[$inquiry,$index]) }}" target="_blank"><img src="{{ route('admin.inquiry.photo',[$inquiry,$index]) }}" alt="{{ __('admin.private_photos') }} {{ $index+1 }}"></a>@endforeach</div></div>
<form class="panel" method="post" action="{{ route('admin.inquiry.update',$inquiry) }}">@csrf @method('put')<h2>{{ __('admin.review') }}</h2><label>{{ __('admin.status') }}<select name="status">@foreach(['new','reviewing','contacted','completed','cancelled'] as $status)<option value="{{ $status }}" @selected($inquiry->status===$status)>{{ __('admin.'.$status) }}</option>@endforeach</select></label><p>{{ $inquiry->notification_sent?__('admin.notification_sent'):__('admin.notification_not_sent') }}</p><p class="small">{{ __('admin.contact_customer') }}</p><button class="button yellow">{{ __('admin.update') }}</button></form></div>
@endsection
