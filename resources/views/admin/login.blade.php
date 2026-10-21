@extends('admin.layout')
@section('content')
<form class="panel login-panel" method="post" action="{{ url('/admin/login') }}">@csrf<span class="eyebrow">MB GREIČIO AUTO</span><h1>{{ __('admin.login_title') }}</h1><label>{{ __('admin.email') }}<input name="email" type="email" autocomplete="username" value="{{ old('email') }}" required></label><label>{{ __('admin.password') }}<input name="password" type="password" autocomplete="current-password" required></label><button class="button yellow">{{ __('admin.sign_in') }} ↗</button></form>
@endsection
