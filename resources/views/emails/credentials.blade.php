@component('mail::message')
# Akun Login Anda

Halo,

Berikut adalah kredensial login Anda:

- **Email:** {{ $email }}
- **Password:** {{ $password }}

Silakan gunakan kredensial di atas untuk masuk ke aplikasi.

@component('mail::button', ['url' => 'http://localhost:3000/masuk'])
Login Sekarang
@endcomponent

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent
