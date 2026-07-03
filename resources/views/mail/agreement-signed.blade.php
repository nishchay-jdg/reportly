@component('mail::message')
@if ($isSignerCopy)
# You signed {{ $projectName }}

This confirms your signature on **{{ $projectName }}**.
@else
# Agreement signed

**{{ $fullName }}** signed **{{ $projectName }}**.
@endif

- **Name:** {{ $fullName }}
- **Email:** {{ $email }}
@if ($companyName)
- **Company:** {{ $companyName }}
@endif
- **Signed at:** {{ $signedAt }}

@component('mail::button', ['url' => $reportUrl])
View agreement
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
