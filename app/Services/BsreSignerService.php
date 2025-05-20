<?php

namespace App\Services;

use App\ApiResponse;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

class BsreSignerService
{
    use ApiResponse;

    public function sign($pdfPath, $pdfFileName, $nik, $passphrase, $idLetter, $type)
    {
        $client = new Client();
        $bsreUrl = config('bsre.url') . '/api/sign/pdf';

        try {
            $response = $client->request('POST', $bsreUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(config('bsre.username') . ':' . config('bsre.password')),
                    'Accept' => 'application/json',
                ],
                'multipart' => [
                    ['name' => 'file', 'contents' => fopen($pdfPath, 'r'), 'filename' => $pdfFileName],
                    ['name' => 'nik', 'contents' => $nik],
                    ['name' => 'passphrase', 'contents' => $passphrase],
                    ['name' => 'tampilan', 'contents' => 'visible'],
                    ['name' => 'linkQR', 'contents' => config('bsre.linkqr') . '?id=' . $idLetter],
                    ['name' => 'tag_koordinat', 'contents' => '#'],
                    ['name' => 'width', 'contents' => '100'],
                    ['name' => 'height', 'contents' => '100'],
                ],
            ]);

            $signedPath = storage_path("app/public/documents/$type/signed_$pdfFileName");
            file_put_contents($signedPath, $response->getBody()->getContents());

            return response()->json('signed_' . $pdfFileName);

        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;

                    // Menangani error dengan BSrE dan menampilkan pesan error dari respons
                    if ($responseBody) {
                        $errorResponse = json_decode($responseBody, true); // Mengonversi JSON ke array
                        $errorMessage = $errorResponse['error'] ?? 'An unknown error occurred'; // Pesan error default jika tidak ada
        
                        // Mengembalikan respons dengan pesan error dari BSrE
                        return response()->json([
                            'message' => 'Gagal menandatangani dokumen dengan BSrE',
                            'error' => $errorMessage,
                            'details' => $errorResponse,
                        ], Response::HTTP_BAD_REQUEST);
                    } 
                    return $this->errorResponse($e->getMessage(), 'An error occurred while export work certificate the document', Response::HTTP_INTERNAL_SERVER_ERROR);
                }
    }
}
