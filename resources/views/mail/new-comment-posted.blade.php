@component('mail::message')
# New comment on {{ $projectName }}

**{{ $authorName }}** wrote:

> {{ $body }}

@component('mail::button', ['url' => $reportUrl])
View and reply
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
