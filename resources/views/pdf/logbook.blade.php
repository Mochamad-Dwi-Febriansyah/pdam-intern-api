 
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logbook PDF</title>
        <style>
            @page { 
                size: A4 portrait; 
                margin: 2cm 1.5cm 1.5cm 2cm; 
            }
    
            body {
                font-family: Arial, sans-serif; 
                font-size: 12px;
                margin: 0;
                padding: 0;
                position: relative;
            }
    
            h2 {
                text-align: center;
                margin-bottom: 20px;
            }
    
            table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        page-break-inside: auto;
    }
    
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
            
    
            th, td {
                border: 1px solid black;
                padding: 6px;
                text-align: left;
            }
    
            th {
                background-color: #f2f2f2;
                font-weight: bold;
                text-align: center
            }
    
            td {
                font-size: 10px;
            }
    
            .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px;
            border: none;
            /* border: 1px solid #ddd; */
            vertical-align: middle;
        }
        .info-table .label {
            font-weight: bold;
            width: 10%;
        }
        .info-table .separator {
            width: 1%;
            text-align: center;
        }
        .info-table .value {
            width: 25%;
        }
        .info-table .date-label {
            text-align: left;
            width: 10%;
            font-weight: bold;
        }
        .info-table .date-value {
            text-align: left;
            width: 20%;
        }
             
    
            .whitespacenowrap {
                white-space: nowrap;
            }
    
            .header {
                text-align: center;
                margin-bottom: 20px;
            }
    
            .header img {
                width: 50px;
            }
    
            .header .company-details {
                font-size: 12px;
                line-height: 1.5;
            }
    
            .company-details .company-name {
                font-weight: bold;
                font-size: 16px;
                margin: 0;
            }
    
            .company-details .address {
                font-style: italic;
                margin: 0;
            }
    
            .company-details .telepon {
                margin: 0;
            }
    
            .pending {
                padding: 4px 8px;
                border-radius: 5px;
            background-color: yellow;
            color: black;
            /* font-weight: bold; */
        }
        .approved {
            padding: 4px 8px;
            border-radius: 5px;
            background-color: green;
            color: white;
            /* font-weight: bold; */
        }
        .rejected {
            padding: 4px 8px;
            border-radius: 5px;
            background-color: red;
            color: white;
            /* font-weight: bold; */
        }
    
            /* Layout tanda tangan */
            .signature-container {
                display: block;
                page-break-inside: avoid;
                margin-top: 50px;
                width: 200px;
                text-align: center;
                position: absolute;
                right: 0;
                /* bottom: 50px; */
            }
    
           /* Box untuk tanda tangan */
    .signature-container .sign-box {
        background-color: aqua;
        text-align: center;
        margin: 5px;
        width: 100%;
        height: 100px; /* Pastikan ukuran konsisten */
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden; /* Mencegah gambar keluar */
        /* border: 1px solid #ddd; Opsional: tambahkan border untuk estetika */
    }
    
    /* Gambar tanda tangan */
    .signature-container .sign-box img {
        max-width: 100%; /* Pastikan tidak melebihi wadah */
        max-height: 100%;
        object-fit: contain; /* Menjaga proporsi gambar tanpa crop */
    }
    
            .signature-container p {
                margin: 0;
            }
            
            .signature-container .notes{
                margin: 5px 0 ;
                font-style: italic; 
                font-size: .9em;
            }

            .text-center{
                text-align: center
            }
            .align-top{
                vertical-align: top;
            }
    
            .whitespace-nowrap{
                white-space: nowrap
            }

            .footer {
    position: fixed;
    bottom: 0; /* posisi dari bawah halaman */
    left: 0;
    right: 0;
    height: 50px;
    text-align: center;
    font-size: 12px;
  }
  .i{
    font-style: italic;
  }
        </style>
    </head>
    <body>
    
        <!-- Header Perusahaan -->
        <div class="header">
            <img src="{{ public_path('images/logo-web-pdam2.png') }}" alt="Logo">
            <div class="company-details">
                <p class="company-name">Perusahaan Umum Daerah Air Minum Tirta Moedal Kota Semarang</p>
                <p class="address">Jl. Kelud Raya No. 60 Semarang 50237, Indonesia</p>
                <p class="telepon">Telepon: +62 24 8315514 (Fax) 0800 1503 888 | Email: pdam@pdamkotasmg.co.id</p>
            </div>
        </div>
    
        <h2>Laporan Harian</h2>
        
        <table class="info-table">
            <tbody>
                <tr>
                    <td class="label">Nama</td>
                    <td class="separator">:</td>
                    <td class="value">{{ $result['user']['name'] }}</td> 
                    <td class="date-label">Tanggal Mulai</td>
                    <td class="separator">:</td>
                    <td class="date-value">{{ $result['user']['start_date'] }}</td>
                </tr> 
                <tr>
                    <td class="label">NISN/NPM/NIM</td>
                    <td class="separator">:</td>
                    <td class="value">{{ $result['user']['nisn_npm_nim'] }}</td> 
                    <td class="date-label">Tanggal Selesai</td>
                    <td class="separator">:</td>
                    <td class="date-value">{{ $result['user']['end_date'] }}</td>
                </tr>
                @if (!empty($result['user']['school_major']))
                <tr>
                    <td class="label">Nama Sekolah</td>
                    <td class="separator">:</td>
                    <td class="value" colspan="4">{{ $result['user']['school_university_name'] }}</td>
                </tr>
                    <tr>
                        <td class="label">Jurusan</td>
                        <td class="separator">:</td>
                        <td class="value" colspan="4">{{ $result['user']['school_major'] }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="label">Nama Universitas</td>
                        <td class="separator">:</td>
                        <td class="value" colspan="4">{{ $result['user']['school_university_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Fakultas</td>
                        <td class="separator">:</td>
                        <td class="value" colspan="4">{{ $result['user']['university_faculty'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Program Studi</td>
                        <td class="separator">:</td>
                        <td class="value" colspan="4">{{ $result['user']['university_program_study'] ?? '-' }}</td>
                    </tr>
                @endif
            
            </tbody>
        </table>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Presensi</th> 
                    <th>Kegiatan</th>
                    <th>Deskripsi</th>
                    <th>Hasil Capaian</th>
                    <th>Status Laporan</th>
                </tr>
            </thead>
            <tbody>
                {{-- @php
                dd($result['logBook']->laporanHarian);
            @endphp --}}
                @if (!empty($result['logBook']))
                @foreach ($result['logBook'] as $index => $log)
                    
                        <tr> 
                            <td class="text-center align-top">{{ $index +1 }}</td>
                            <td class="whitespace-nowrap align-top">
                               {{ \Carbon\Carbon::parse($log['date'])->translatedFormat('l, d F Y') }}<br>
                                Datang: {{ \Carbon\Carbon::parse($log['check_in_time'])->translatedFormat('H:i:s') }}<br>
                                Pulang: {{ $log['check_out_time'] ? \Carbon\Carbon::parse($log['check_out_time'])->translatedFormat('H:i:s') : '--:--' }}
                            </td>
                            
                            <td class="align-top">{{ optional($log->dailyReport)->title ?? '-' }}</td> 
                            <td class="align-top">{{ strip_tags(optional($log->dailyReport)->report ?? '-') }}</td>
                            <td class="align-top">{{ strip_tags(optional($log->dailyReport)->result ?? '-') }}</td>
                            
                            
                            <td class="text-center">
                                <span  class=" 
                                @if(optional($log->dailyReport)->status === 'pending') pending
                                @elseif(optional($log->dailyReport)->status === 'approved') approved 
                                @elseif(optional($log->dailyReport)->status === 'rejected') rejected 
                                @endif">
                                @if(optional($log->dailyReport)->status === 'pending') Menunggu
                                @elseif(optional($log->dailyReport)->status === 'approved') Disetujui
                                @elseif(optional($log->dailyReport)->status === 'rejected') Ditolak
                                @else -
                                @endif
                                </span>
                            </td>
                        </tr> 
                   
                @endforeach
            @else
                <tr><td colspan="6" class="text-center">Logbook kosong</td></tr>
            @endif
            
            </tbody>
        </table>
    
        <!-- Tanda Tangan -->
    
        <div class="signature-container" >
            <p>Semarang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p>Pembimbing Lapangan</p>
            {{-- <div class="sign-box">
                @if(!empty($result['user']['mentor_ttd']))
                    <img src="{{ public_path('storage/' . $result['user']['mentor_tanda_tangan']['signature']) }}" alt="Tanda Tangan">
                @endif
            </div> --}}
            <br>
            <br> 
            <br>
            <p>{{ empty($result['skip_signature']) ? '#' : '' }}</p> 
            <br> 
            <br> 
            <br>
            <br>
            <p><b><u>{{ $result['user']['mentor_name'] ?? ' ' }}</u></b></p>
            <p>{{ $result['user']['mentor_position'] ?? ' ' }}</p>  
            <p>NPP. {{ $result['user']['mentor_id'] ?? ' ' }}</p> {{ $result['user']['mentor_ttd'] }}
            @if (!$result['user']['mentor_ttd'])
                <p class="notes">(Laporan harian belum lengkap)</p>
            @endif
        
    
        </div>

        <div class="footer i">Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik <br>
            yang diterbitkan oleh Balai Besar Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara</div>
    
    </body>
    </html> 
    