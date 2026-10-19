<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('site.tagline')) · MB Greičio Auto</title>
    <meta name="description" content="{{ __('site.meta') }}">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
</head>
<body>
@php($locale=app()->getLocale())
<div class="topbar"><div class="container"><span>Šiaures gatve 2, Vilnius</span><a href="tel:+37065498007">+370 654 98007 <span class="desktop"> · {{ __('site.call') }}</span></a></div></div>
<header><div class="container nav">
    <a class="brand" href="{{ route('home',$locale) }}"><span class="brand-icon">G<span>↗</span></span><span>GREIČIO AUTO<small>{{ __('site.brandline') }}</small></span></a>
    <button class="menu-toggle" aria-label="Menu" aria-expanded="false" onclick="const n=document.getElementById('navigation'); n.classList.toggle('open');this.setAttribute('aria-expanded',n.classList.contains('open'))">☰</button>
    <nav id="navigation" aria-label="Main">
        @foreach(['services','rentals','parts'] as $section)<a class="{{ request()->is($locale.'/'.$section.'*') ? 'active':'' }}" href="{{ route('catalog',[$locale,$section]) }}">{{ __('site.'.$section) }}</a>@endforeach
        <a href="{{ route('contact',$locale) }}">{{ __('site.contact') }}</a>
    </nav>
    <div class="nav-tools"><div class="languages" aria-label="Language">@foreach(['lt','en','ru'] as $lang)<a lang="{{ $lang }}" class="{{ $lang===$locale?'selected':'' }}" href="{{ url(preg_replace('#^'.$locale.'(?=/|$)#',$lang,request()->path())) }}{{ request()->getQueryString() ? '?'.request()->getQueryString():'' }}">{{ strtoupper($lang) }}</a>@endforeach</div><a class="basket-link" href="{{ route('basket',$locale) }}" aria-label="{{ __('site.basket') }}">{{ __('site.basket') }} <b>{{ array_sum(session('basket',[])) }}</b></a></div>
</div></header>
<main>
@if(session('success'))<div class="container"><div class="alert success" role="status">{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="container"><div class="alert error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>@endif
@yield('content')
</main>
<section class="contact-strip"><div class="container"><div><span class="eyebrow">{{ __('site.help') }}</span><h2>{{ __('site.talk') }}</h2></div><a class="button yellow" href="tel:+37065498007">+370 654 98007 ↗</a></div></section>
<footer><div class="container footer-grid"><div><a class="brand" href="{{ route('home',$locale) }}">GREIČIO AUTO</a><p>{{ __('site.meta') }}</p></div><div><strong>{{ __('site.visit') }}</strong><p>MB Greičio Auto<br>Šiaures gatve 2, Vilnius</p><a href="{{ route('contact',$locale) }}">{{ __('site.directions') }} ↗</a></div><div><strong>{{ __('site.explore') }}</strong>@foreach(['services','rentals','parts'] as $section)<a href="{{ route('catalog',[$locale,$section]) }}">{{ __('site.'.$section) }}</a>@endforeach</div></div><div class="container footer-bottom"><span>© {{ date('Y') }} MB Greičio Auto</span><a href="{{ route('privacy',$locale) }}">{{ __('site.privacy') }}</a></div></footer>
</body></html>
