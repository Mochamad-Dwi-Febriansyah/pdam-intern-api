<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\LogHelper;
use App\Http\Requests\CertificateRequest;
use App\Models\Certificate;
use App\Models\Document;
use App\Models\Signature;
use App\Services\BsreSignerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class CertificateController extends Controller
{
    use ApiResponse;

    protected $bsreSignerService;

    public function __construct(BsreSignerService $bsreSignerService)
    {
        $this->bsreSignerService = $bsreSignerService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $certificate = Certificate::where('user_id', $user->id)->paginate(20);

        LogHelper::log('certificate_index', 'Retrieved the list of certificate successfully', null, ['total_certificate' => $certificate->total()]);

        return $this->successResponse($certificate, 'Certificate list retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CertificateRequest $certificateRequest)
    {
        DB::beginTransaction();
        try {
            // Pengecekan menggunakan query dengan where
            $document = Document::where('id', $certificateRequest->document_id)
                ->where('user_id', $certificateRequest->user_id)
                ->first();

            // dd($document);

            // Jika document tidak ditemukan
            if (!$document) {
                return $this->errorResponse(null, 'Document with specified user_id not found', Response::HTTP_NOT_FOUND);
            }

            // Simpan sertifikat
            $certificate = Certificate::create($certificateRequest->validated());

            $totalScore = 0;

            // Simpan fields jika ada
            if ($certificateRequest->has('fields')) {
                foreach ($certificateRequest->fields as $field) {
                    $totalScore += $field['score'] ?? 0;
                    $certificate->fields()->create([
                        'certificate_id' => $certificate->id,
                        'assessment_aspects_id' => $field['assessment_aspects_id'],
                        'score' => $field['score'] ?? null,
                        'status' => $field['status'] ?? 'active',
                    ]);
                }
            }
            $average_score = 0;
            $average_score = $totalScore / count($certificateRequest->fields);

            $certificate->update([
                'total_score' => $totalScore,
                'average_score' => $average_score,
            ]);




            $data = [];

            $certificate->load(['fields.assessmentAspect', 'user', 'document']);
            // dd($certificate);

            Carbon::setLocale('en');
            $data = [
                'certificate' => $certificate,
                'user' => $certificate->user,
                'document' => $certificate->document,
                'fields' => $certificate->fields,
                'certificate_number' => $certificate->certificate_number,
                'start_date' => Carbon::parse($certificate->document->start_date)->translatedFormat('d F Y'),
                'end_date' => Carbon::parse($certificate->document->end_date)->translatedFormat('d F Y'),
                'now_date' => Carbon::now()->translatedFormat('d F Y'),
            ];
            $data['signature'] = Signature::whereJsonContains('purposes', ['name' => 'certificate', 'status' => 'active'])->first();

            $data['skip_signature'] = $certificateRequest->boolean('skip_signature');
            // dd($certificate);
            $pdf = Pdf::loadView('pdf.certificate', ['result' => $data]);

            $pdfFileName = 'certificate_' . time() . '.pdf';

            // Pastikan folder ada
            $pdfFolder = storage_path('app/public/documents/certificate/' . $pdfFileName);
            if (!file_exists(storage_path('app/public/documents/certificate'))) {
                mkdir(storage_path('app/public/documents/certificate'), 0777, true);
            }

            // Simpan PDF ke local storage 
            file_put_contents($pdfFolder, $pdf->output());

            if (!$certificateRequest->boolean('skip_signature')) {
                 $signedPdfResponse = $this->bsreSignerService->sign(realpath(storage_path('app/public/documents/certificate/' . $pdfFileName)), $pdfFileName, '1234567890123456', $certificateRequest->passphrase, $certificate->id, 'certificate');
                  if (isset($signedPdfResponse->original['message']) && isset($signedPdfResponse->original['error']) && isset($signedPdfResponse->original['details'])) {
                     LogHelper::log('certificate_store', 'Failed to sign document with BSrE', null, [
                        'message' => $signedPdfResponse->original['message'],
                        'error' => $signedPdfResponse->original['error'],
                        'details' => $signedPdfResponse->original['details'],
                    ], 'error');

                    return $this->errorResponse($signedPdfResponse->original, $signedPdfResponse->original['error'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                $signedFileName = $signedPdfResponse->original;
            }else{
                LogHelper::log('certificate_store', 'Skipped BSrE signing', null, [
                    'certificate_id' => $certificate->id
                ]);
                $signedFileName = $pdfFileName;
            }

            $certificate->update([
                'certificate_path' => 'documents/certificate/' . $signedFileName,
            ]);
            DB::commit();


            LogHelper::log('certificate_store', 'Created a new certificate successfully', $certificate, [
                'certificate_id' => $certificate->id
            ]);

            $data = [
                'data' => $certificate->load('fields')
            ];

            return $this->successResponse($data, 'Certificate has been successfully created', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();



            // Logging error
            LogHelper::log('certificate_store', 'Failed to create a new certificate', null, [], 'error');

            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the certificate', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $certificate = Certificate::find($id);

        if (!$certificate) {
            return $this->errorResponse(null, 'Certificate not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('certificate_show', 'Viewed certificate details successfully', $certificate);

        return $this->successResponse($certificate, 'Certificate details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $certificate = Certificate::find($id);

            if (!$certificate) {
                return $this->errorResponse(null, 'Certificate not found', Response::HTTP_NOT_FOUND);
            }

            $certificate->update($request->validated());

            if ($request->has('fields')) {
                foreach ($request->fields as $field) {
                    $certificateField = $certificate->fields()->where('assessment_aspects_id', $field['assessment_aspects_id'])->first();

                    if ($certificateField) {
                        // Jika field sudah ada, update dengan data baru
                        $certificateField->update([
                            'score' => $field['score'] ?? $certificateField->score,
                            'status' => $field['status'] ?? $certificateField->status,
                        ]);
                    } else {
                        $certificate->fields()->create([
                            'certificate_id' => $certificate->id,
                            'assessment_aspects_id' => $field['assessment_aspects_id'],
                            'score' => $field['score'] ?? null,
                            'status' => $field['status'] ?? 'active',
                        ]);
                    }
                }
            }

            DB::commit();


            LogHelper::log('certificate_update', 'Updated the certificate successfully', $certificate, [
                'certificate_id' => $certificate->id
            ]);

            $data = [
                'data' => $certificate->load('fields')
            ];

            return $this->successResponse($data, 'Certificate has been successfully updated', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();

            // Log error
            LogHelper::log('certificate_update', 'Failed to update certificate', null, [], 'error');

            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the certificate', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $certificate = Certificate::find($id);

            if (!$certificate) {
                return $this->errorResponse(null, 'Certificate not found', Response::HTTP_NOT_FOUND);
            }

            $certificate->delete();

            DB::commit();

            LogHelper::log('certificate_destroy', 'Certificate deleted successfully', $certificate, [
                'deleted_certificate_id' => $certificate->id,
                'deleted_certificate_name' => $certificate->name
            ]);

            return $this->successResponse(null, 'Certificate has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('certificate_destroy', 'Failed to delete certificate', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the certificate', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // private function signWithBsre($pdfPath, $pdfFileName, $nik, $passphrase, $idLetter, $type){

    //     $client = new Client();
    //     // $bsreUrl = 'http://103.101.52.82/api/sign/pdf';
    //     $bsreUrl = config('bsre.url').'/api/sign/pdf'; 
    //     try { 

    //         $response = $client->request('POST', $bsreUrl, [
    //             'headers' => [
    //                 'Authorization' => 'Basic ' . base64_encode(config('bsre.username') . ':' . config('bsre.password')),
    //                 'Accept' => 'application/json',
    //             ], 
    //             'multipart' => [
    //                 [
    //                     'name' => 'file',
    //                     'contents' => fopen($pdfPath, 'r'),
    //                     'filename' => $pdfFileName
    //                 ],
    //                 [
    //                     'name' => 'nik',
    //                     'contents' => $nik
    //                 ],
    //                 [
    //                     'name' => 'passphrase',
    //                     'contents' => $passphrase
    //                 ],
    //                 [
    //                     'name' => 'tampilan',
    //                     'contents' => 'visible'
    //                 ],
    //                 [
    //                     'name' => 'linkQR',
    //                     'contents' => config('bsre.linkqr').'?id='.$idLetter
    //                 ],
    //                 [
    //                     'name' => 'tag_koordinat',
    //                     'contents' => '#'
    //                 ],
    //                 [
    //                     'name' => 'width',
    //                     'contents' => '100'
    //                 ],
    //                 [
    //                     'name' => 'height',
    //                     'contents' => '100'
    //                 ],
    //             ],

    //         ]); 
    //         $signedPdfPath = storage_path('app/public/documents/'.$type.'/signed_' . $pdfFileName);
    //         file_put_contents($signedPdfPath, $response->getBody()->getContents());
    //         return response()->json('signed_'.$pdfFileName); 
    //     } catch (RequestException $e) {
    //         $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;

    //         // Menangani error dengan BSrE dan menampilkan pesan error dari respons
    //         if ($responseBody) {
    //             $errorResponse = json_decode($responseBody, true); // Mengonversi JSON ke array
    //             $errorMessage = $errorResponse['error'] ?? 'An unknown error occurred'; // Pesan error default jika tidak ada

    //             // Mengembalikan respons dengan pesan error dari BSrE
    //             return response()->json([
    //                 'message' => 'Gagal menandatangani dokumen dengan BSrE',
    //                 'error' => $errorMessage,
    //                 'details' => $errorResponse,
    //             ], Response::HTTP_BAD_REQUEST);
    //         }

    //         // Logging error jika respons tidak ada
    //         LogHelper::log('bsre_signing_error', 'BSrE signing request failed', null, [
    //             'message' => $e->getMessage(),
    //             'response' => $responseBody,
    //         ], 'error');

    //         return $this->errorResponse($e->getMessage(), 'An error occurred while export work certificate the document', Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }
}
