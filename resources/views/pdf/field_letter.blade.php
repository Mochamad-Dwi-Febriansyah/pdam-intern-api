<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Terima</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }
        @page {
            size: 21cm 33cm portrait;
            margin: .8cm .6cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            text-align: center;
            padding: 30px;
        }
        .kop-surat {
            width: 100%;
            border-bottom: 3px solid black;
            margin-bottom: 3px; 
            padding-bottom: 0; 
        }
        .border-plus{
            padding: 1px solid black;
            border-bottom: 1px solid black;
            margin-bottom: 20px; 
        }
        .kop-surat td {
            vertical-align: middle;
        }
        .logo {
            width: 100px; /* Sesuaikan ukuran logo */
        }
        .text {
            text-align: center;
        }
        .text h1 {
            font-size: 18px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .text p {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .text-right{
            text-align: right;
        }
        .text-center{
            text-align: center;
        }
        .letter-spacing3{
            letter-spacing: 3px;
        }
        .letter-spacing1{
            letter-spacing: 1px;
        }
        .line-height12{
            line-height: 1.2
        } 
        .body-content{ 
            padding: 0 70px 0 90px;
            font-size: 17px
        }
        .body-content-surat-terima-head{ 
            padding: 0 70px;
            font-size: 17px
        }
        .body-content-surat-terima{ 
            padding: 0 70px 0 180px;
            font-size: 17px
        }
        p{
            margin-bottom: 10px; 
        }
        li{
            text-indent: 40px;
            margin-bottom: 3px;
            line-height: 1.5
        }
        td{
            padding-bottom: 10px;
            vertical-align: top
        }
        .mb-0{
            margin-bottom: 0
        }
        .border-b-1{
            border-bottom: 1px solid black
        }
        .d-inline-block{
            display: inline-block
        }
        .bold{
            font-weight: bold
        }
        .pl-40{
            padding-left: 40px
        }
        .pb-0{
            padding-bottom: 0;
        }
        table{
            padding-bottom: 10px;
        } 
        .text-indent0{
            text-indent: 0
        }
        .pl-30{
            padding-left: 30px
        }
        .text-justify{
            text-align: justify
        }
        .text-white{
            color: white
        }
        .auto-width {
            table-layout: auto;
            width: 100%;
        }
        .narrow {
            padding: 0;
            margin: 0;
            width: 10%;
            white-space: nowrap;
            min-width: 10px; /* Mencegah lebar melebar */
            text-align: center;
        }
        .p5{
            padding: 5px
        }
        .p1{
            padding: 1px
        }
        .border{
        border: 1px solid black
        }

        .footer {
            position: fixed;
            bottom: 0; /* posisi dari bawah halaman */
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            font-size: 12px;
            page-break-before: avoid;
        }
        .i{
            font-style: italic;
        }

    </style>
</head>
<body>

    <!-- Kop Surat dengan Tabel -->
    <div class="border-plus">
        <table class="kop-surat">
            <tr>
                <td class="pb-0"><img src="{{ public_path('images/Lambang_Kota_Semarang.png') }}" alt="Logo Kiri" width="90"></td>
                <td class="text pb-0"> 
                    <strong>
                        <h1 class="letter-spacing3">PEMERINTAH KOTA SEMARANG</h1>
                        <h2>PERUSAHAAN UMUM DAERAH AIR MINUM</h2>
                        <h2>"TIRTA MOEDAL"</h2>
                        <p class="letter-spacing1 line-height12">Alamat: Jl. Kelud Raya Semarang, Kode Pos : 50237 <br> Telp. (024) 8315514 Fax. 8314078 Email: pdam@pdamkotasmg.co.id</p>
                    </strong> 
                </td>
                <td class="text-right pb-0"><img src="{{ public_path('images/logo-web-pdam2.png') }}" alt="Logo Kanan" width="90"></td>
            </tr>
        </table>
    </div>
  
  
    <!-- Isi Surat -->
    <table width="100%" cellspacing="0" cellpadding="5" border="0" class="body-content-surat-terima-head text-justify auto-width">
        <tr>
            <td width="50%">
                <table  width="100%" cellspacing="0" cellpadding="" border="0">
                    <tr>
                        <td class="pb-0 text-white" width="35%">visible</td>
                        <td class="pb-0 text-white " width="5%">:</td>
                        <td class="pb-0 text-white">visible</td>
                    </tr>
                    <tr>
                        <td class="pb-0" width="35%">Nomor</td>
                        <td class="pb-0 " width="5%">:</td>
                        <td class="pb-0">{{ $result['number_document'] ?? '.......' }}</td>
                    </tr> 
                </table>
            </td>
            <td width="5%">
                <table  cellspacing="0" cellpadding="" border="0" class="p-0 bold">
                    <tr>
                        <td class="pb-0 text-white">Yth.x</td> 
                    </tr>
                    <tr>
                        <td class="pb-0 text-white">Yth.x</td> 
                    </tr>
                    <tr>
                        <td class="pb-0 text-white">Yth.x</td> 
                    </tr>
                    <tr>
                        <td class="pb-0 text-white">Yth.x</td> 
                    </tr>
                    <tr>
                        <td class="pb-0 text-white">Yth.x</td> 
                    </tr>
                    <tr> 
                        <td class="pb-0">Yth. </td> 
                    </tr>
                </table>
            </td>
            <td width="45%">
                <table  width="100%" cellspacing="0" cellpadding="5" border="0">
                    <tr>
                        <td class="pb-0">Semarang, {{ $result['date_document'] ?? '.......' }}</td> 
                    </tr>
                    <tr>
                        <td class="pb-0 text-white">visible</td> 
                    </tr>
                    <tr>
                        <td class="pb-0 text-white">visible</td> 
                    </tr>
                    <tr>
                        <td class="pb-0 text-white">Lampiran</td> 
                    </tr>
                    <tr>
                        <td class="pb-0 bold">
                            <span class="letter-spacing1">Kepada : </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="pb-0 bold">
                            <p class="line-height12">
                                {{ $result['recipient'] ?? '.......' }} 
                                <br>
                                {{ $result['recipient_address'] ?? '.......' }}
                                 <br>di -  <br>
                                <span class="letter-spacing1 pl-30" style=" display: block"><u>SEMARANG</u></span></p>
                            </td>  
                    </tr>
                </table>
            </td>
        </tr> 
    </table>
    <br>
    <table width="100%" cellspacing="0" cellpadding="5" border="0" class="body-content-surat-terima text-justify"> 
        <tr>
            <td colspan="2">
                <ol>
                    <li>  
                        <p class="line-height12">Memperhatikan Surat {{ $result['recipient'] ?? '.......' }} Nomor : {{ $result['number_document'] ?? '.......' }} tanggal {{ $result['recipient_date'] ?? '.......' }} perihal permohonan izin magang.</p> 
                    </li>
                    <li>  
                        <p class="line-height12">Sehubungan dengan hal tersebut diatas, bersama ini dapat kami sampaikan bahwa pada prinsipnya Perumda Air Minum Tirta Moedal Kota Semarang dapat menerima {{ $result['internship_status'] == 'mahasiswa' ? 'mahasiswa' : 'siswa' }} Saudara untuk Magang alokasi waktu tanggal {{ $result['start_date'] ?? '.......' }} sampai dengan {{ $result['end_date'] ?? '.......' }} dengan data sebagai berikut :</p> 
                        <table width="100%" cellspacing="0" cellpadding="5" border="0" >
                            <tr >
                                <th class="p5 border">NO</th>
                                <th class="p1 border">NAMA</th>
                                <th class="p1 border">{{ $result['internship_status'] == 'university' ? 'NIM' : 'NISN' }}</th>
                                <th class="p1 border">{{ $result['internship_status'] == 'university' ? 'PROGRAM STUDI' : 'JURUSAN' }}</th> 
                            </tr>
                            <tr > 
                                <td class="p5 border text-center">1</td>
                                <td class="p5 border">{{ $result['name'] ?? '.......' }}</td>
                                <td class="p5 border text-center">{{ $result['nisn_npm_nim'] ?? '.......' }}</td>
                                <td class="p5 border text-center">{{ $result['internship_status'] == 'university' ? $result['university_program_study'] :  $result['school_major'] }}</td> 
                            </tr>  
                        </table> 
                        <p class="text-indent0 line-height12">Untuk melakukan PKL di <i> Perumda Air Minum Tirta Moedal Kota Semarang </i> alokasi waktu tanggal {{ $result['start_date'] ?? '.......' }} sampai dengan {{ $result['end_date'] ?? '.......' }}. Untuk keterangan lebih lanjut dapat menghubungi.</p>
                    </li>
                    <li>
                        <p class="line-height12">Demikian informasi kami, atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p> 
                    </li>
                  
                </ol>
            </td>
        </tr> 
    </table>

    <!-- Tanda Tangan -->
   
    <table width="100%" cellspacing="0" cellpadding="5" border="0" class="body-content text-center"> 
        <tr>
            <td width="35%"></td>
            <td class="pb-0">
                <p>Semarang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                <p> 
                    Kepala Bagian Kepegawaian
                </p>
            </td>
        </tr>
        <tr>
            <td width="35%"></td>
            <td class="pb-0">
                <br>
                <br> 
                <p>{{ empty($result['skip_signature']) ? '#' : '' }}</p> 
                <br>
                <br>
                <br>
                <br> 
            </td> 
        </tr>
        <tr>
            <td width="35%"></td>
            <td>
                @if(!empty($result['signature']))
                    <p>
                        <span class="border-b-1 bold">{{ $result['signature']['name_snapshot'] ?? "?" }}</span><br>
                        {{ $result['signature']['rank_group'] ?? "?" }}<br>
                        NPP. {{ $result['signature']['user_id'] ?? "?" }}
                    </p>
                @else
                    <span class="text-gray-500">Data tanda tangan tidak tersedia</span>
                @endif 
            </td>
        </tr>
    </table> 

    <div class="footer i">Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik <br>
        yang diterbitkan oleh Balai Besar Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara</div>

</body>
</html>
