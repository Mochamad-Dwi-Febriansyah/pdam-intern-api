<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\LogHelper;
use App\Models\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TTEController extends Controller
{
    use ApiResponse;

    public function cekKeabsahan(Request $request)
    {
        $document = Document::where(function ($query) use ($request) {
            $query->whereNotNull('accepted_letter')
                  ->whereRaw('JSON_VALID(accepted_letter)')
                  ->where('accepted_letter->id', $request->id_document);
        })
        ->orWhere(function ($query) use ($request) {
            $query->whereNotNull('field_letter')
                  ->whereRaw('JSON_VALID(field_letter)')
                  ->where('field_letter->id', $request->id_document);
        })
        ->orWhere(function ($query) use ($request) {
            $query->whereNotNull('work_certificate')
                  ->whereRaw('JSON_VALID(work_certificate)')
                  ->where('work_certificate->id', $request->id_document);
        })
        ->first();
    

        if (!$document) {
            return $this->errorResponse(null, 'Document not found on the server.', Response::HTTP_NOT_FOUND);
        }
        $fileName = $document->accepted_letter['path'] ?? null;
        
        if (!$fileName) {
            return $this->errorResponse(null, 'File name is missing in accepted_letter field.', Response::HTTP_BAD_REQUEST);
        }
        $pdfPath = storage_path('app/public/' . $fileName);
        // dd($fileName); "documents/accepted_letter/signed_accepted_letter_1745411623.pdf" 

        if (!file_exists($pdfPath)) {
            return $this->errorResponse(null, 'PDF file not found on server.', Response::HTTP_NOT_FOUND);
        } 

        $client = new Client();
        $bsreUrl = config('bsre.url') . '/api/sign/verify';
        try {

            $response = $client->request('POST', $bsreUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(config('bsre.username') . ':' . config('bsre.password')),
                    'Accept' => 'application/json',
                ],
                'multipart' => [
                    [
                        'name' => 'signed_file',
                        'contents' => fopen($pdfPath, 'r'),
                        'filename' => $fileName
                    ],

                ],

            ]);

            $data = json_decode($response->getBody(), true);
            return $this->successResponse($data, 'Signature verification result retrieved successfully.', Response::HTTP_OK);

          } catch (RequestException $e) {
            LogHelper::log('cek_keabsahan', 'BSrE verification failed', null, [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ], 'error'); 

            return $this->errorResponse(null, 'Failed to verify document authenticity.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
