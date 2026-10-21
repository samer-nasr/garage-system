@if($listing->make || $listing->model)
<p class="small part-vehicle">{{ trim(($listing->make ?? '').' '.($listing->model ?? '')) }}</p>
@elseif($listing->fitments->isNotEmpty())
<p class="small part-vehicle">@foreach($listing->fitments->unique(fn ($fitment) => $fitment->make.'|'.$fitment->model) as $fitment)<span>{{ $fitment->make }} {{ $fitment->model }}@if(!$loop->last), @endif</span>@endforeach</p>
@endif
