@component('mail::message')
# Nomor Registrasi Anda

Halo,

Berikut adalah Nomor Registrasi Anda:

- **Nomor Registrasi:** {{ $registration_number }} 

Silakan gunakan kredensial di atas untuk memantau perkembangan berkas.

@component('mail::button', ['url' => 'http://localhost:3000/program/' . $program_type . '/cek-berkas?registration_number=' . $registration_number])
Cek Sekarang
@endcomponent

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent