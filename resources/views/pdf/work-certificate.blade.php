<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan</title>
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
        .text-justify{
            text-align: justify
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
                        <p class="letter-spacing1 line-height12 ">Alamat: Jl. Kelud Raya Semarang, Kode Pos : 50237 <br> Telp. (024) 8315514 Fax. 8314078 Email: pdam@pdamkotasmg.co.id</p>
                    </strong> 
                </td>
                <td class="text-right pb-0"><img src="{{ public_path('images/logo-web-pdam2.png') }}" alt="Logo Kanan" width="90"></td>
            </tr>
        </table>
    </div>
  
      <!-- Isi Surat -->
      <table width="100%" cellspacing="0" cellpadding="5" border="0" class="body-content text-justify">
        <tr>
            <td colspan="2" class="text-center">
                <p class="d-inline-block mb-0 border-b-1 bold">SURAT KETERANGAN</p>
                <p>Nomor: {{ $result['number_letter'] }}</p>
            </td>
        </tr> 
        <tr>
            <td colspan="2">
                <ol>
                    <li>
                        <p>Yang bertanda tangan dibawah ini :</p>
                        <table width="100%" cellspacing="0" cellpadding="5" border="0" class="pl-40">
                            <tr >
                                <td class="pb-0 letter-spacing1" width="30%">Nama</td>
                                <td class="pb-0"  width="2%">:</td>
                                <td class="pb-0">{{ $result['signature']['name_snapshot'] ?? '.......' }}</td>
                            </tr> 
                            <tr >
                                <td class="pb-0 letter-spacing1">NPP</td>
                                <td class="pb-0">:</td>
                                <td class="pb-0">{{ $result['signature']['user_id'] ?? '.......' }}</td>
                            </tr>
                            <tr >
                                <td class="pb-0">Pangkat / Gol.</td>
                                <td class="pb-0">:</td>
                                <td class="pb-0">{{ $result['signature']['position'] ?? '.......' }}</td>
                            </tr>
                            <tr >
                                <td class="pb-0">Jabatan</td>
                                <td class="pb-0">:</td>
                                <td class="pb-0">Kepala Bagian Kepegawaian</td>
                            </tr>
                        </table>
                    </li>
                    <li> 
                        <p>Dengan ini menerangkan bahwa :</p>
                        <table width="100%" cellspacing="0" cellpadding="5" border="0" class="pl-40"> 
                            <tr >
                                <td class="pb-0 letter-spacing1"  width="30%">Nama</td>
                                <td class="pb-0" width="2%">:</td>
                                <td class="pb-0">{{ $result['document']['user']['name'] ?? '.......'    }}</td>
                            </tr>
                            <tr >
                                <td class="pb-0 letter-spacing1">{{ $result['internship_status'] == 'university' ? 'NIM' : 'NISN' }}</td>
                                <td class="pb-0">:</td>
                                <td class="pb-0">{{ $result['document']['user']['nisn_npm_nim'] ?? '.......'    }} </td>
                            </tr> 

                            @if ( !empty($result['document']['schoolUni']['school_major']) )
                           
                            <tr >
                                <td class="pb-0">Jurusan</td>
                                <td class="pb-0">:</td>
                                <td class="pb-0">{{ $result['document']['schoolUni']['school_major'] ?? '.......'    }}  <br> {{ $result['document']['schoolUni']['school_university_name'] ?? '.......'    }} </td>
                            </tr>
                            @else 
                            <tr >
                                <td class="pb-0">Program Studi</td>
                                <td class="pb-0">:</td> 
                                <td class="pb-0">{{ $result['document']['schoolUni']['university_program_study'] ?? '.......'    }} </td>
                            </tr>
                            <tr >
                                <td class="pb-0">Fakultas</td>
                                <td class="pb-0">:</td>
                                <td class="pb-0">{{ $result['document']['schoolUni']['university_faculty'] ?? '.......'    }}  <br> {{ $result['document']['schoolUni']['school_university_name'] ?? '.......'    }} </td>
                            </tr>
                            @endif

                        </table>
                        <p class="text-indent0">telah melakukan Magang di Perusahaan Umum Daerah Air Minum Tirta Moedal Kota Semarang dari tanggal {{ $result['start_date'] ?? '.......' }} sampai dengan {{ $result['end_date'] ?? '.......' }}.</p>
                    </li>
                    <li>
                        <p>Demikian Surat Keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
                    </li>
                </ol>
            </td>
        </tr> 
    </table>

    <!-- Tanda Tangan -->
    <br> 
    <table width="100%" cellspacing="0" cellpadding="5" border="0" class="body-content text-center">
       
        <tr>
            <td width="35%"></td>
            <td>
                <p>Semarang, {{ $result['date_now'] ?? '.......' }}</p>
            </td> 
        </tr>
        <tr>
            <td width="35%"></td>
            <td class="pb-0">
                <p>An. Direksi Perusahaan Umum Daerah Air Minum <br>
                    Tirta Moedal Kota Semarang <br>
                    Direktur Umum <br>
                    Ub <br>
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
            {{-- <td class="pb-0"><img src="{{ public_path('storage/'.$result['signature']['signature']) }}" width="80"> </td>  --}}
        </tr>
        <tr>
            <td width="35%"></td>
            <td>
                <p><span class="border-b-1 bold">{{ $result['signature']['name_snapshot'] ?? '.......' }}</span><br>
                    {{ $result['signature']['position'] ?? '.......' }}<br>
                    NPP. {{ $result['signature']['user_id'] ?? '.......' }}
                </p> 
            </td>
        </tr>
    </table>

</body>
</html>
