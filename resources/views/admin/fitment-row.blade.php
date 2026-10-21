<div class="fitment-row">
@foreach(['make','model','year_from','year_to'] as $field)<label>{{ in_array($field,['make','model']) ? __('site.'.$field) : __('admin.'.$field) }}<input name="fitments[{{ $index }}][{{ $field }}]" value="{{ $fitment[$field] ?? '' }}" @if(str_starts_with($field,'year'))type="number" min="1900" max="2100"@else maxlength="100"@endif></label>@endforeach
<button type="button" class="remove-item" data-remove-fitment>{{ __('site.remove') }}</button>
</div>
