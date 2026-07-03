@component('mail::message')
# Your report was just viewed

**{{ $projectName }}** was opened for the first time.

@component('mail::button', ['url' => $reportUrl])
View the report
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
