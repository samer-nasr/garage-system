@php($contactText = isset($listing) ? $listing->contactMessage() : ($contactText ?? __('business.message_general')))
<div class="messaging {{ ($compact ?? false) ? 'compact' : '' }}">
    <a class="button whatsapp" href="https://wa.me/{{ config('shop.messaging_phone') }}?text={{ rawurlencode($contactText) }}" target="_blank" rel="noopener">WhatsApp ↗</a>
    <a class="button viber" href="viber://chat?number={{ rawurlencode('+'.config('shop.messaging_phone')) }}">Viber ↗</a>
    @if(!($compact ?? false))
        <label class="copy-request">{{ __('business.copy_message') }}<textarea readonly rows="3" onclick="this.select()">{{ $contactText }}</textarea></label>
        <button class="button outline copy-contact" type="button" data-message="{{ $contactText }}" data-copied="{{ __('business.copied') }}">{{ __('business.copy_message') }}</button>
        <p class="small">{{ __('business.viber_help') }} {{ __('business.no_auto_send') }}</p>
    @endif
</div>
