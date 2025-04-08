@component('mail::message')
# {{ $subject ?? 'Action Required' }}

{!! nl2br(e($body)) !!}

@if(isset($actionText) && isset($actionUrl))
@component('mail::button', ['url' => $actionUrl])
{{ $actionText }}
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent 