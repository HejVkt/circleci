@component('mail::message')
# Registration

Your registered at forum, Please confirm that you are human. Push the button below.

@component('mail::button', ['url' => url('/register/confirm?token=' . $user->confirmation_token)])
Confirm registration
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
