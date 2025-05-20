<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\FileHelper;
use App\Helpers\LogHelper;
use App\Http\Requests\DocumentRequest;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
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
        $document = Document::paginate(20);

        LogHelper::log('document_index', 'Retrieved the list of documents successfully', null, ['total_documents' => $document->total()]);

        return $this->successResponse($document, 'Document list retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DocumentRequest $documentRequest)
    {
        DB::beginTransaction();

        try {
            
            $uploadedFiles = $documentRequest->handleUploads();

            $document = Document::create(array_merge(
                $documentRequest->validated(),
                $uploadedFiles,
                ['user_id' => $documentRequest->user_id, 'school_university_id' => $documentRequest->school_university_id]
            ));

        DB::commit();

        LogHelper::log('document_store', 'Created a new document successfully', $document, [
            'document' => $document->id
        ]);

        // $data = [
        //     'data' => $document
        // ];

        return $this->successResponse(null, 'Document has been successfully created', Response::HTTP_CREATED);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('document_store', 'Failed to create a new document', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the document', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $document = Document::find($id);

        if(!$document)
        {
            return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('document_show', 'Viewed document details successfully', $document);

        return $this->successResponse($document, 'Document details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DocumentRequest $documentRequest, string $id) 
    { 

        DB::beginTransaction();

        try {
            $document = Document::find($id);

            if(!$document)
            {
                return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
            }

            $uploadedFiles = [];
            $fileFields = ['identity_photo', 'application_letter', 'work_certificate'];
    
            foreach ($fileFields as $field) {
                if ($documentRequest->hasFile($field)) {
                    FileHelper::deleteFile($document->$field);
                    $uploadedFiles[$field] = FileHelper::uploadFile($documentRequest->file($field), 'documents');
                } else {
                    $uploadedFiles[$field] = $document->$field; // Tetap gunakan file lama jika tidak ada upload baru
                }
            }

            $documentData = array_filter($documentRequest->validated(), fn($value) => $value !== null);
            $mergedDocumentData = array_merge($documentData, $uploadedFiles);

 

            if (!empty($mergedDocumentData)) {
                $document->fill($mergedDocumentData);
                // Log::info('Updated Document:', $document->toArray());
                $document->save();
            }

            DB::commit();

            LogHelper::log('document_update', 'Document updated successfully', $document, [
                'updated_fields' => $documentData,
            ]);

            return $this->successResponse(null, 'Document has been successfully updated', Response::HTTP_OK);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('document_update', 'Failed to update document', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the document', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $document = Document::find($id);

            if(!$document)
            {
                return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
            }

            $document->delete();

            DB::commit();

            LogHelper::log('document_destroy', 'Document deleted successfully', $document, [
                'deleted_document_id' => $document->id,
                'deleted_document_name' => $document->name
            ]);

            return $this->successResponse(null, 'Document has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('document_destroy', 'Failed to delete document', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the document', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    } 
    
    public function exportWorkCertificate(DocumentRequest $documentRequest, string $user_id)
    {
        $validator = Validator::make($documentRequest->all(), [
            'number_letter' => 'required|string|max:255',
            'passphrase'    => 'required|string|min:6',
        ]);
    
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 'Validasi gagal', Response::HTTP_UNPROCESSABLE_ENTITY);
        } 
        
        $document = Document::with('user','schoolUni')->where('user_id',$user_id)->first();

        if(!$document)
        {
            return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
        }
        DB::beginTransaction();
        try {  
            $data = [
                'document' => $document,
                'number_letter' => $documentRequest->number_letter,
            ];  

            $data['signature'] = Signature::whereJsonContains('purposes', ['name' => 'work_certificate', 'status'=>'active'])->first();

            // dd($data['signature']);

            if (empty($document->schoolUni->school_major)) {
                $data['internship_status'] = 'university';
            } elseif (!empty($document->university_faculty) && !empty($document->university_program_study)) {
                $data['internship_status'] = 'student';
            } else {
                $data['internship_status'] = 'unknown';  
            }
            $data['start_date'] =  \Carbon\Carbon::parse($document->start_date)->translatedFormat('d F Y');
            $data['end_date'] = \Carbon\Carbon::parse( $document->end_date)->translatedFormat('d F Y');
            $data['date_now'] = \Carbon\Carbon::now()->translatedFormat('d F Y');

            $data['skip_signature'] = $documentRequest->boolean('skip_signature');
        // dd($document);
        $pdf = Pdf::loadView('pdf.work-certificate', ['result' => $data]);

        // return $pdf->download();
        // Simpan file ke storage/app/public/documents/work_certificate

        // here
        $pdfFileName = 'work_certificate_' . time() . '.pdf';

        // Pastikan folder ada
        $pdfFolder = storage_path('app/public/documents/work_certificate/'. $pdfFileName);
        if (!file_exists(storage_path('app/public/documents/work_certificate'))) {
            mkdir(storage_path('app/public/documents/work_certificate'), 0777, true);
        }

        // Simpan PDF ke local storage 
        file_put_contents($pdfFolder, $pdf->output());
        
        // return response()->json(['message' => 'Submission receipt successfully created', 'file_path' => $pdfFileName, 'real' => storage_path('app/public/documents/work_certificate/' . $pdfFileName)], Response::HTTP_INTERNAL_SERVER_ERROR);

        $idLetter = (string) Str::uuid(); // Generate UUID for the file
        // Tanda tangani dengan BSrE
        // $signedPdfResponse = $this->signWithBsre(realpath(storage_path('app/public/documents/work_certificate/' . $pdfFileName)), $pdfFileName, '1234567890123456', $documentRequest->passphrase, $idLetter, $type = 'work_certificate');
        if (!$documentRequest->boolean('skip_signature')) {
            $signedPdfResponse = $this->bsreSignerService->sign(realpath(storage_path('app/public/documents/work_certificate/' . $pdfFileName)), $pdfFileName, '1234567890123456', $documentRequest->passphrase, $documentRequest->id, 'work_certificate');
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
           LogHelper::log('work_certificate_store', 'Skipped BSrE signing', null, [
               'work_certificate' => $idLetter
           ]);
           $signedFileName = $pdfFileName;
       }

        $document->update([
            'work_certificate' => [
                'id' => $idLetter,
                'path' => 'documents/work_certificate/'.$signedFileName,
            ]
        ]); 

        // $pdfFilePath = FileHelper::savePdfToStorage($pdf, 'documents/work_certificate', 'public');
        
        // // Cek passphrase
        // if (empty($documentRequest->passphrase)) {
        //     return $this->errorResponse(null, 'Gagal menandatangani dokumen. Passphrase belum tersedia.', Response::HTTP_UNPROCESSABLE_ENTITY);
        // }
        
        // // Ambil nama file dari path
        // $fileName = basename($pdfFilePath);
        
        // // Buat URL publik (pastikan `php artisan storage:link` sudah dijalankan)
        // $fileUrl = Storage::url("documents/work_certificate/{$fileName}");
        // $document->work_certificate = "documents/work_certificate/".$fileName;
        // $document->save();
        // dd($document)  ;
        
        // return $this->successResponse([
        //     'document' => $document->user,
        //     'file_name' => $fileName,
        //     'file_url' => asset($fileUrl),
        // ], 'Export work certificate list retrieved successfully', Response::HTTP_OK);

        DB::commit();

        return $this->successResponse(null , 'Export work certificate list retrieved successfully', Response::HTTP_OK);


        }catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('document_destroy', 'Failed to export work certificate document', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while export work certificate the document', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    private function signWithBsre($pdfPath, $pdfFileName, $nik, $passphrase, $idLetter, $type){
        
        $client = new Client();
        // $bsreUrl = 'http://103.101.52.82/api/sign/pdf';
        $bsreUrl = config('bsre.url').'/api/sign/pdf'; 
        try { 
            
            $response = $client->request('POST', $bsreUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(config('bsre.username') . ':' . config('bsre.password')),
                    'Accept' => 'application/json',
                ], 
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($pdfPath, 'r'),
                        'filename' => $pdfFileName
                    ],
                    [
                        'name' => 'nik',
                        'contents' => $nik
                    ],
                    [
                        'name' => 'passphrase',
                        'contents' => $passphrase
                    ],
                    [
                        'name' => 'tampilan',
                        'contents' => 'visible'
                    ],
                    [
                        'name' => 'linkQR',
                        'contents' => config('bsre.linkqr').'?id='.$idLetter
                    ],
                    [
                        'name' => 'tag_koordinat',
                        'contents' => '#'
                    ],
                    [
                        'name' => 'width',
                        'contents' => '100'
                    ],
                    [
                        'name' => 'height',
                        'contents' => '100'
                    ],
                ],

            ]); 
            $signedPdfPath = storage_path('app/public/documents/'.$type.'/signed_' . $pdfFileName);
            file_put_contents($signedPdfPath, $response->getBody()->getContents());
            return response()->json('signed_'.$pdfFileName); 
        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;

            LogHelper::log('bsre_signing_error', 'BSrE signing request failed', null, [
                'message' => $e->getMessage(),
                'response' => $responseBody,
            ], 'error');
        
            // Buat respons error yang lebih ramah
            throw new HttpResponseException(response()->json([
                'message' => 'Gagal menandatangani dokumen dengan BSrE',
                'error' => $e->getMessage(),
                'details' => $responseBody,
            ], 500));
        }
    }


}
