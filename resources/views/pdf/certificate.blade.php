<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sertifikat</title> 

    <style>
       @font-face {
                font-family: 'Satoshi';
                src: url('/fonts/satoshi/Satoshi-Light.otf') format('opentype');
                font-weight: 300;
                font-style: normal;
        }

        @font-face {
            font-family: 'Satoshi';
            src: url('/fonts/satoshi/Satoshi-Regular.otf') format('opentype');
            font-weight: 400;
            font-style: normal;
        }

        @font-face {
            font-family: 'Satoshi';
            src: url('/fonts/satoshi/Satoshi-Bold.otf') format('opentype');
            font-weight: 700;
            font-style: normal;
        } 
/*  
        @page { 
            size: A4 landscape; 
            margin: 0;
        } */

        @page { 
    size: 210mm 330mm landscape; /* Ukuran F4 */
    margin: 0;
}
         
         body {   
            font-family: 'Satoshi', sans-serif;
            font-size: 14px;
            margin: 0; 
            display: flex;
            align-items: center;
            justify-content: center;
            /* border: 20px solid #2d3e50; */
            padding: 0
        }
        .border-figure {
            min-height: 100vh;
            border-width: 5px;
            border-style: solid;
            border-color: #4285F4 #EA4335 #FBBC05 #34A853  ;
        }

        header { 
            position: absolute;
            top: 0;
            right: 150px; /* Koreksi sintaks */
            height: 150px;
            width: 120px;  
            background-color: #2d3e50;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center; 
            z-index: 2; /* Pastikan di bawah header */
        }

        header::after {
            content: "";
            position: absolute;
            bottom: -57px; /* Posisikan di bawah header */
            left: 50%;
            transform: translateX(-50%) rotate(45deg); /* Membuat belah ketupat */
            width: 113px;
            height: 113px;
            background-color: #2d3e50;
            z-index: -10; /* Pastikan di bawah header */
        }  
        .title-header{
            color: white;
            font-size: 20px
        } 
        .number-certificate{
            padding: 5px 10px ;
            background-color: #2d3e50;
            border-radius: 5px;
            color: white;
            display: inline-block;
            margin: 0
        }
        .for-certificate{
           font-size: 24px;
           font-weight: 700; 
        }
        .for-certificate2{
           font-size: 20px;
           font-weight: 700; 
        } 
        .content{ 
            width: 100%;
            height: calc(94.5% - 2px);
            text-align: center;
            border: 1px solid  black; 
            /* border: 1px solid  #2d3e50;  */
            /* border: 5px solid  #2d3e50;  */
        }
        table{
            width: 100%;
            padding: 20px;
        }
        /* table, tr, td{
            border: 1px solid black
        } */ 
        tr:last-child {
            height: 100%;  
            vertical-align: bottom;  
        }
        .text-right{
            text-align: right
        }
        .line-height1{
            line-height: .5em;
        }
        .line-height{
            line-height: normal
        }
        .title-description{
            margin: 0;
            padding: 0; 
        } 
        .subtitle-description { 
            margin: 0;
            padding: 0;  
        }
        .row4{  
            min-height: 4em; 
            max-height: 4em; 
            overflow: hidden;
        }
        .pb-3{
            padding-bottom: 18px;
        }
        .pb-1{
            padding-bottom: 8px;
        }
        .m0{
            margin: 0;
        }
        .p0{
            padding: 0;
        }
        .text-2d3e50{
            color: #2d3e50;
        }
        .text2{
            font-size: 28px;
        }  

        /* body{
            background-color: red
        } */

        .page-break {
            page-break-before: always;
            display: block;
            width: 100%;
            background-color: violet
        }

        .page-after {
            background-color: white;
            margin-top: 50px; /* Tambahkan margin atas agar lebih renggang */
        }

        .page1{
            /* padding: 50px; */
            min-height: 100vh;
            padding: 10px;
            background-color: white
        }
        .page2{
            min-height: 100vh;
            padding: 10px;
            background-color: white
        } 

        .content-two {
            background-color: white;
            height: calc(94.5% - 2px);
            width: 100%;
            max-width: 1123px; /* Lebar kertas A4 dalam px (1123px untuk landscape) */
            aspect-ratio: 11 / 8.5; /* Rasio A4 landscape (11:8.5 inci) */ 
            box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.3); /* Efek mengambang */
            text-align: center;
            border: 1px solid #d2d2d2;
            box-sizing: border-box;
            min-height: 696px
        } 
        .text-left{
            text-align: left
        }
        .text-right{
            text-align: right
        }
        .text-center{
            text-align: center
        }
        .table-bordered{
            border-collapse: collapse
        }
        .table-bordered thead tr,  .table-bordered tbody tr td, .table-bordered thead tr th{
            padding: 10px;
            border: 1px solid #d2d2d2
        }
        .text-blue{
            color: rgb(37, 150, 190)
        }

        footer { position: fixed; bottom: 5px; left: 0px; right: 0px;  text-align: center; font-size: .8rem; color: #2d3e50;height: 50px; }
        .page-intitial { page-break-after: always; }
        .page-intitial:last-child { page-break-after: never; }
    </style>
</head>
<body> 
    <footer><i>Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik <br>
        yang diterbitkan oleh Balai Besar Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara</i></footer>
   

        
    <div class="page-intitial page1 border-figure"> 
        {{-- <header>
            <div>
                <p class="title-header">Sertifikat <br> Magang</p> 
                <img src="{{ public_path('images/logo-web-pdam2.png') }}" alt="Logo Kiri" width="60">
            </div>
        </header>  --}}
        <table class="content">
            <thead>
                <tr>
                    {{-- <td colspan="2" class="pb-3 text-2d3e50"></td> --}}
                    <td colspan="2" class="text-center pb-1">
                        <img src="{{ public_path('images/logo-web-pdam2.png') }}" alt="Logo Kiri" width="80">
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="text2">Tirta Moedal Certificate Internship</td> 
                </tr>
                <tr>
                    <td colspan="2" class="pb-3"><p class="number-certificate">{{ $result['certificate_number'] }}</p></td> 
                </tr>
                <tr> 
                    <td colspan="2" class="">Given to:</td> 
                </tr>
                <tr> 
                    <td colspan="2" class="pb-3">
                        <p class="subtitle-description for-certificate text2 ">{{ $result['user']['name'] ?? '.......'    }}</p>
                    </td> 
                </tr>
                <tr>
                    <td colspan="2" class="pb-3 text-2d3e50"> 
                        <p class="subtitle-description ">Implementation of Internship at Perumda Tirta Moedal Drinking Water, Semarang City <br> On the date {{ $result['start_date'] ?? '.......' }} until {{ $result['end_date'] ?? '.......' }}</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="line-height1 text-2d3e50">
                        <p>Semarang, {{ $result['now_date'] ?? '.......' }}</p> 
                        <p>An. Direksi Perusahaan Umum Daerah Air Minum</p>
                        <p>Tirta Moedal Kota Semarang</p>
                        <p>Direktur Umum</p>
                        <p>Ub</p>
                        <p>Head of Personnel Section</p>
                        @if(!empty($result['signature']))
                        <br>
                        <br> 
                        <br>
                        <br>
                        <br>
                        <p>{{ empty($result['skip_signature']) ? '#' : '' }}</p> 
                        <br> 
                        <br> 
                        <br> 
                        <br> 
                        <br>
                        <br>
                        <p class="border-b-1 bold">{{ $result['signature']['name_snapshot'] ?? "?" }}</p>
                        <p>{{ $result['signature']['rank_group'] ?? "?" }}<br></p>
                        <p>NPP. {{ $result['signature']['user_id'] ?? "?" }}</p>
                        @endif  
                    </td> 
                </tr>
            </thead>
        </table>
    </div>

    {{-- <div class="page-break"></div> --}}

    <div class="page-intitial page2 border-figure">  
            <table class="content-two">
                <thead>
                    <tr >
                        <td  class="pb-3 text-left text-2d3e50">
                            <img src="{{ public_path('images/logo-web-pdam2.png') }}" alt="Logo Kiri" width="25">
                        </td>
                        <td  class="pb-3 text-right text-2d3e50"><p>Halaman 2</p></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="pb-3 text-2d3e50">
                            <table class="table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>ASPEK YANG DINILAI</th>
                                        <th>HURUF</th>
                                        <th>ANGKA</th>
                                    </tr>
                                </thead>
                                <tbody> 
                                  @foreach ($result['fields'] as $index => $field)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td> <!-- Nomor urut -->
                                        <td>{{ $field['assessmentAspect']['name_field'] }}</td> <!-- Nama aspek -->
                                        <td class="text-center"></td> <!-- Bisa diubah jika ada nilai lain -->
                                        <td class="text-center">{{ $field['score'] }}</td> <!-- Skor aspek -->
                                    </tr>
                                    @endforeach  
                                    <tr>
                                        <td colspan="3" class="text-right">JUMLAH</td> 
                                        <td class="text-center">{{ $result['certificate']['total_score'] }}</td> 
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-right">RATA-RATA</td> 
                                        <td class="text-center">{{ $result['certificate']['average_score'] }}</td> 
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-right">PREDIKAT</td>
                                        <td class="text-center"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </thead>
            </table> 
    </div>
 
</body>
</html> 

 