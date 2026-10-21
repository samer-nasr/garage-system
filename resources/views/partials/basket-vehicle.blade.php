@if($part->make || $part->model)
<p class="small basket-vehicle">@if($part->make)<span>{{ __('site.make') }}: <strong>{{ $part->make }}</strong></span>@endif @if($part->model)<span> · {{ __('site.model') }}: <strong>{{ $part->model }}</strong></span>@endif</p>
@elseif($part->fitments->isNotEmpty())
<p class="small basket-vehicle">{{ __('business.fitments') }}: @foreach($part->fitments->unique(fn ($fitment) => $fitment->make.'|'.$fitment->model) as $fitment)<span>{{ $fitment->make }} {{ $fitment->model }}@if(!$loop->last), @endif</span>@endforeach</p>
@endif
