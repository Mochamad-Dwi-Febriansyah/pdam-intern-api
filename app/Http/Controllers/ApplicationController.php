<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\FileHelper;
use App\Helpers\LogHelper;
use App\Http\Requests\DocumentRequest;
use App\Http\Requests\SchoolUniRequest;
use App\Http\Requests\UserRequest;
use App\Mail\MailSendCredentialsLogin;
use App\Mail\MailSendRegistrationNumber;
use App\Models\Document;
use App\Models\SchoolUni;
use App\Models\Signature;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use App\Services\BsreSignerService;

class ApplicationController extends Controller
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
    public function index(Request $request)
    {

        $query = Document::with([
            'user:id,name,email,nisn_npm_nim', // Ambil hanya kolom yang diperlukan
            'user.roles:id,name',
            'schoolUni:id,school_university_name,school_major,university_faculty,university_program_study'
        ]);

        // Filter berdasarkan status jika ada query parameter
        if ($request->has('status') && in_array($request->status, ['pending', 'accepted', 'rejected'])) {
            $query->where('document_status', $request->status);
        }

        $documents = $query->paginate(20);

        $documents->getCollection()->transform(function ($doc) {
            $user = $doc->user;

            // Ambil role pertama (karena Spatie bisa multi-role)
            $user->role = $user->roles->first()?->name;

            // Hapus properti roles supaya tidak ikut di response
            unset($user->roles);

            return $doc;
        });

        LogHelper::log('application_index', 'Retrieved application list', null, [
            'status' => $request->status ?? 'all',
            'total_documents' => $documents->total()
        ]);

        return $this->successResponse($documents, 'Application list retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $userRequest, SchoolUniRequest $schoolUniRequest, DocumentRequest $documentRequest)
    {
        // dd([$userRequest, $schoolUniRequest, $documentRequest]);
        DB::beginTransaction();

        try {
            $schoolUni = SchoolUni::firstOrCreate(
                [
                    'school_university_email' => $schoolUniRequest->school_university_email,
                ],
                $schoolUniRequest->validated()
            );

            $user = User::create(array_merge(
                $userRequest->validated(),
                ['password' => Hash::make('123456')]
            ));
            // dd(get_class($user)); // Seharusnya "App\Models\User"

            // dd($user);
            $user->assignRole($userRequest->role); // <-- Ini assign role

            $uploadedFiles = $documentRequest->handleUploads();

            $document = Document::create(array_merge(
                $documentRequest->validated(),
                $uploadedFiles,
                ['user_id' => $user->id, 'school_university_id' => $schoolUni->id]
            ));

            DB::commit();

            Mail::to($userRequest->email)->send(new MailSendRegistrationNumber($document->registration_number, $userRequest->role));

            LogHelper::log('application_store', 'Created a new application successfully', $document, [
                'user' => $user->only(['id', 'name', 'email']),
                'school_uni' => $schoolUni->only(['id', 'school_university_name']),
                'document_id' => $document->id,
            ]);

            // $data = [
            //     'user' => $user,
            //     'school_uni' => $schoolUni,
            //     'document' => $document
            // ];

            return $this->successResponse(null, 'Application has been successfully created', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('application_store', 'Failed to create a new application', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the application', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $document = Document::with('user', 'schoolUni')->where('id', $id)->first();

        if (!$document) {
            return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('appication_show', 'Viewed application details successfully', $document);

        return $this->successResponse($document, 'Application details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $userRequest, SchoolUniRequest $schoolUniRequest, DocumentRequest $documentRequest, string $id)
    {
        DB::beginTransaction();

        try {
            $document = Document::with('user', 'schoolUni')->find($id);


            if (!$document) {
                return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
            }

            $user = $document->user;
            $schoolUni = $document->schoolUni;

            if (!$user) {
                return $this->errorResponse(null, 'User not found for this document', Response::HTTP_NOT_FOUND);
            }

            if (!$schoolUni) {
                return $this->errorResponse(null, 'School/University not found for this document', Response::HTTP_NOT_FOUND);
            }

            $schoolUniData = array_filter($schoolUniRequest->validated(), fn($value) => $value !== null);

            if (!empty($schoolUniData)) {
                $schoolUni->fill($schoolUniData)->save();
            }

            $userData = array_filter($userRequest->validated(), fn($value) => $value !== null);
            if (!empty($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            if (!empty($userData)) {
                $user->fill($userData)->save();
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

            LogHelper::log('appication_update', 'Application updated successfully', $document, [
                'updated_fields' => $mergedDocumentData,
            ]);

            // $data = [
            //     'user' => $user,
            //     'school_uni' => $schoolUni,
            //     'document' => $document
            // ];  

            return $this->successResponse(null, 'Application has been successfully updated', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('appication_update', 'Failed to update appication', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating appication', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $document = Document::where('id', $id)->first();

            if (!$document) {
                return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
            }

            $user = $document->user;
            $schoolUni = $document->schoolUni;

            $fileFields = ['identity_photo', 'application_letter', 'accepted_letter', 'work_certificate'];

            foreach ($fileFields as $field) {
                if (!empty($document->$field)) {
                    FileHelper::deleteFile($document->$field);
                }
            }

            $document->delete();

            if ($user && !$user->documents()->exists()) {
                $user->delete();
            }

            if ($schoolUni && !$schoolUni->documents()->exists()) {
                $schoolUni->delete();
            }

            DB::commit();

            LogHelper::log('application_destroy', 'Application deleted successfully', $document);

            return $this->successResponse(null, 'Application has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('document_destroy', 'Failed to delete application', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the application', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkStatus(string $id)
    {
        if (!preg_match('/^BR-\d{6}-[A-Z0-9]+$/', $id)) {
            return $this->errorResponse(null, 'Invalid registration number format', Response::HTTP_BAD_REQUEST);
        }

        $document = Document::with(['user:id,name,email'])->where('registration_number', $id)->select('document_status', 'registration_number', 'user_id')->first();

        if (!$document) {
            return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('appication_check_status', 'Checked appication status successfully', $document);

        return $this->successResponse($document, 'Application status retrieved successfully', Response::HTTP_OK);
    }

    public function getMenteesByMentor(Request $request)
    {
        $mentorId = $request->user_sso_id;

        if (!$mentorId) {
            return $this->errorResponse(null, 'Mentor ID is missing', Response::HTTP_BAD_REQUEST);
        }

        $perPage = $request->input('per_page', 1); // Mendapatkan parameter 'per_page' dari request, default 1
        $applications = Document::with('user', 'schoolUni')
            ->where('mentor_id', $mentorId)
            ->paginate($perPage);

        // Mapping data untuk mengambil informasi yang dibutuhkan
        $mentees = $applications->map(function ($application) {
            return [
                'name' => $application->user->name,
                'email' => $application->user->email,
                'nisn_npm_nim' => $application->user->nisn_npm_nim,
                'start_date' => $application->start_date,
                'end_date' => $application->end_date,
                'school_university_name' => $application->schoolUni->school_university_name ?? null,
                'school_major' => $application->schoolUni->school_major ?? null,
                'university_faculty' => $application->schoolUni->university_faculty ?? null,
                'university_program_study' => $application->schoolUni->university_program_study ?? null,
            ];
        });

        // Menggunakan pagination helper dan mengirimkan response
        $pagination = $this->paginationData($applications, $mentees);

        return $this->successResponse(null, 'Mentees list retrieved successfully', Response::HTTP_OK, $pagination);
    }

    public function updateStatusAndMentor(Request $request, $id)
    {

        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'document_status' => 'required|in:accepted,pending,rejected',
                'mentor_id' => 'nullable',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $document = Document::find($id);

            if (!$document) {
                return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
            }

            $updateData = array_filter([
                'document_status' => $request->document_status,
                'mentor_id' => $request->mentor_id,
                'mentor_name' => $request->mentor_name,
                'mentor_rank_group' => $request->mentor_rank_group,
                'mentor_position' => $request->mentor_position,
                'mentor_nik' => $request->mentor_nik ?? null,
            ], fn($value) => $value !== null && $value !== '');

            $document->update($updateData);

            $userDocument = $document->user;
            if ($request->document_status === 'accepted' && $userDocument) {
                $defaultPassword = Str::random(8); // Buat password acak
                $hashedPassword = Hash::make($defaultPassword); // Hash sebelum menyimpan

                // Update password di database
                $userDocument->update([
                    'password' => $hashedPassword,
                    'status' => 'active',
                ]);

                Mail::to($userDocument->email)->send(new MailSendCredentialsLogin($userDocument->email, $defaultPassword));
            }

            DB::commit();

            LogHelper::log('document_update', 'Updated document status and mentor successfully', $document, [
                'status' => $document->document_status,
                'mentor_id' => $document->mentor_id,
                'mentor_name' => $document->mentor_name,
                'mentor_rank_group' => $document->mentor_rank_group,
                'mentor_position' => $document->mentor_position,
                'mentor_nik' => $document->mentor_nik ?? null,
            ]);

            return $this->successResponse(null, 'Document has been successfully updated', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('document_update_status_and_mentor', 'Failed to update document', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the document', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function submissionReceipt(Request $request, $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
        }

        if ($document->document_status != 'accepted') {
            return $this->errorResponse(null, 'Document has not been approved', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $submissionReceiptValidator = Validator::make($request->all(), [
            // 'status_berkas' => 'required',  
            // 'status_magang' => 'required|in:mahasiswa,siswa',   
            'name' => 'required',
            'school_major' => 'nullable',
            'university_program_study' => 'nullable',
            'nisn_npm_nim' => 'required',
            'date_document' => 'required',
            'number_document' => 'required',
            'nature' => 'nullable',
            'attachment' => 'nullable',
            'recipient' => 'required|string',
            'recipient_address' => 'required|string',
            'recipient_date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'passphrase' => 'required',
        ]);

        if ($submissionReceiptValidator->fails()) {
            return $this->errorResponse($submissionReceiptValidator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $dateFields = ['date_document', 'recipient_date', 'start_date', 'end_date'];

            $data = []; // Initialize the data array

            // Generate PDF using updated data
            $data = array_merge($data, $request->all());

            // Loop through each date field
            foreach ($dateFields as $field) {
                if (!empty($request->$field)) {
                    $data[$field] = $this->parseDate($request->$field);
                } else {
                    $data[$field] = '......'; // Default if empty
                }
            }

            // Set document status
            $data['document_status'] = $document->document_status ?? '......'; // Default if empty

            // Set internship status (student or university)
            if (empty($document->school_major)) {
                $data['internship_status'] = 'university';
            } elseif (!empty($document->university_faculty) && !empty($document->university_program_study)) {
                $data['internship_status'] = 'student';
            } else {
                $data['internship_status'] = 'unknown'; // Fallback if no condition matches
            }


            // dd($data); 
            $data['signature'] = Signature::whereJsonContains('purposes', ['name' => 'receipt_letter', 'status' => 'active'])->first();
            // dd($data['signature']);
            $data['skip_signature'] = $request->boolean('skip_signature');

            $pdfContent = Pdf::loadView('pdf.submission_receipt', ['result' => $data]);

            // return $pdfContent->download('submission_receipt.pdf');

            $pdfFileName = 'accepted_letter_' . time() . '.pdf';

            // Pastikan folder ada
            $pdfFolder = storage_path('app/public/documents/accepted_letter/' . $pdfFileName);
            if (!file_exists(storage_path('app/public/documents/accepted_letter'))) {
                mkdir(storage_path('app/public/documents/accepted_letter'), 0777, true);
            }

            // Simpan PDF ke local storage 
            file_put_contents($pdfFolder, $pdfContent->output());

            // return response()->json(['message' => 'Submission receipt successfully created', 'file_path' => $pdfFileName, 'real' => storage_path('app/public/documents/accepted_letter/' . $pdfFileName)], Response::HTTP_INTERNAL_SERVER_ERROR);

            $idLetter = (string) Str::uuid(); // Generate UUID for the file
            //   $signedPdfResponse = $this->bsreSignerService->sign(realpath(storage_path('app/public/documents/accepted_letter/' . $pdfFileName)), $pdfFileName, '1234567890123456', $request->passphrase, $id, 'accepted_letter');
            if (!$request->boolean('skip_signature')) {
                $signedPdfResponse = $this->bsreSignerService->sign(realpath(storage_path('app/public/documents/accepted_letter/' . $pdfFileName)), $pdfFileName, '1234567890123456', $request->passphrase, $idLetter, 'accepted_letter');
                if (isset($signedPdfResponse->original['message']) && isset($signedPdfResponse->original['error']) && isset($signedPdfResponse->original['details'])) {
                    LogHelper::log('submission_receipt_store', 'Failed to sign document with BSrE', null, [
                        'message' => $signedPdfResponse->original['message'],
                        'error' => $signedPdfResponse->original['error'],
                        'details' => $signedPdfResponse->original['details'],
                    ], 'error');

                    return $this->errorResponse($signedPdfResponse->original, $signedPdfResponse->original['error'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                $signedFileName = $signedPdfResponse->original;
            } else {
                LogHelper::log('submission_receipt_store', 'Skipped BSrE signing', null, [
                    'submission_receipt' => $idLetter
                ]);
                $signedFileName = $pdfFileName;
            }

            $document->update([
                'accepted_letter' => [
                    'id' => $idLetter,
                    'path' => 'documents/accepted_letter/' . $signedFileName,
                ]
            ]);


            DB::commit();

            return response()->json(['message' => 'Submission receipt successfully created', 'file_path' => 'documents/accepted_letter/'.$signedFileName], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //     'errors' => $th->getMessage(), // tampilkan error detail
            // 'trace' => $th->getTraceAsString(), // opsional: untuk debug trace
            DB::rollBack();
            LogHelper::log('submission_receipt_store', 'Failed to create a new submission receipt', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the submission receipt', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function fieldLetter(Request $request, $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->errorResponse(null, 'Document not found', Response::HTTP_NOT_FOUND);
        }
        // dd($document);

        if ($document->document_status != 'accepted') {
            return $this->errorResponse(null, 'Document has not been approved', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $submissionReceiptValidator = Validator::make($request->all(), [
            // 'status_berkas' => 'required',  
            // 'status_magang' => 'required|in:mahasiswa,siswa',   
            'name' => 'required',
            'school_major' => 'nullable',
            'university_program_study' => 'nullable',
            'nisn_npm_nim' => 'required',
            'date_document' => 'required',
            'number_document' => 'required',
            'recipient' => 'required|string',
            'recipient_address' => 'required|string',
            'recipient_date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'passphrase' => 'required',
            'delivered_to' => 'sometimes|array|min:1',
            'delivered_to.*.npp' => 'sometimes|string|exists:employees,npp',
            'delivered_to.*.name' => 'sometimes|string',
        ]);

        if ($submissionReceiptValidator->fails()) {
            return $this->errorResponse($submissionReceiptValidator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $dateFields = ['date_document', 'recipient_date', 'start_date', 'end_date'];

            $data = []; // Initialize the data array
            // Generate PDF using updated data
            $data = array_merge($data, $request->all());

            // Loop through each date field
            foreach ($dateFields as $field) {
                if (!empty($request->$field)) {
                    $data[$field] = $this->parseDate($request->$field);
                } else {
                    $data[$field] = '......'; // Default if empty
                }
            }
            // dd($data);

            // Set document status
            $data['document_status'] = $document->document_status ?? '......'; // Default if empty

            // Set internship status (student or university)
            if (empty($document->school_major)) {
                $data['internship_status'] = 'university';
            } elseif (!empty($document->university_faculty) && !empty($document->university_program_study)) {
                $data['internship_status'] = 'student';
            } else {
                $data['internship_status'] = 'unknown'; // Fallback if no condition matches
            }


            // dd($data); 
            $data['signature'] = Signature::whereJsonContains('purposes', ['name' => 'field_letter', 'status' => 'active'])->first();
            // dd($data); 
            $data['skip_signature'] = $request->boolean('skip_signature');

            $pdfContent = Pdf::loadView('pdf.field_letter', ['result' => $data]);

            // return $pdfContent->download('field_letter.pdf');

            $pdfFileName = 'field_letter_' . time() . '.pdf';

            // Pastikan folder ada
            $pdfFolder = storage_path('app/public/documents/field_letter/' . $pdfFileName);
            if (!file_exists(storage_path('app/public/documents/field_letter'))) {
                mkdir(storage_path('app/public/documents/field_letter'), 0777, true);
            }

            // Simpan PDF ke local storage 
            file_put_contents($pdfFolder, $pdfContent->output());

            // return response()->json(['message' => 'Submission receipt successfully created', 'file_path' => $pdfFileName, 'real' => storage_path('app/public/documents/accepted_letter/' . $pdfFileName)], Response::HTTP_INTERNAL_SERVER_ERROR);

            $idLetter = (string) Str::uuid(); // Generate UUID for the file

            // Tanda tangani dengan BSrE
            // $signedPdfResponse = $this->signWithBsre(realpath(storage_path('app/public/documents/field_letter/' . $pdfFileName)), $pdfFileName, '1234567890123456', $request->passphrase, $idLetter, $type = 'field_letter');
            if (!$request->boolean('skip_signature')) {
                $signedPdfResponse = $this->bsreSignerService->sign(realpath(storage_path('app/public/documents/field_letter/' . $pdfFileName)), $pdfFileName, '1234567890123456', $request->passphrase, $idLetter, 'field_letter');
                if (isset($signedPdfResponse->original['message']) && isset($signedPdfResponse->original['error']) && isset($signedPdfResponse->original['details'])) {
                    LogHelper::log('field_letter_store', 'Failed to sign document with BSrE', null, [
                        'message' => $signedPdfResponse->original['message'],
                        'error' => $signedPdfResponse->original['error'],
                        'details' => $signedPdfResponse->original['details'],
                    ], 'error');

                    return $this->errorResponse($signedPdfResponse->original, $signedPdfResponse->original['error'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                $signedFileName = $signedPdfResponse->original;
            } else {
                LogHelper::log('field_letter_store', 'Skipped BSrE signing', null, [
                    'field_letter' => $idLetter
                ]);
                $signedFileName = $pdfFileName;
            }

            $document->update([
                'field_letter' => [
                    'id' => $idLetter,
                    'path' => 'documents/field_letter/' . $signedFileName,
                    'delivered_to' => $request->delivered_to,
                ]
            ]);


            DB::commit();

            return response()->json(['message' => 'Field letter successfully created', 'file_path' => 'documents/field_letter/' . $signedFileName], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //     'errors' => $th->getMessage(), // tampilkan error detail
            // 'trace' => $th->getTraceAsString(), // opsional: untuk debug trace
            DB::rollBack();
            LogHelper::log('field_letter_store', 'Failed to create a new Field letter', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the Field letter', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // private function signWithBsre($pdfPath, $pdfFileName, $nik, $passphrase, $idLetter, $type)
    // {

    //     $client = new Client();
    //     // $bsreUrl = 'http://103.101.52.82/api/sign/pdf';
    //     $bsreUrl = config('bsre.url') . '/api/sign/pdf';
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
    //                     'contents' => config('bsre.linkqr') . '?id=' . $idLetter
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
    //         $signedPdfPath = storage_path('app/public/documents/' . $type . '/signed_' . $pdfFileName);
    //         file_put_contents($signedPdfPath, $response->getBody()->getContents());
    //         return response()->json('signed_' . $pdfFileName);
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

    // Di dalam controller kamu
    public function parseDate($date)
    {
        try {
            // Jika tanggal sudah dalam format 'Y-m-d' (contoh: 2025-06-01), kita bisa langsung mengonversinya
            if (preg_match('/\d{4}-\d{2}-\d{2}/', $date)) {
                return Carbon::parse($date)->translatedFormat('d F Y'); // Mengonversi ke format "d F Y" (contoh: 01 June 2025)
            }

            // Jika tanggal menggunakan format lainnya (seperti "12 Maret 2025"), coba parsing secara eksplisit
            $indonesianMonths = [
                'Januari' => 'January',
                'Februari' => 'February',
                'Maret' => 'March',
                'April' => 'April',
                'Mei' => 'May',
                'Juni' => 'June',
                'Juli' => 'July',
                'Agustus' => 'August',
                'September' => 'September',
                'Oktober' => 'October',
                'November' => 'November',
                'Desember' => 'December',
            ];

            // Mengganti nama bulan Indonesia ke dalam bahasa Inggris untuk mempermudah parsing
            $englishDate = strtr($date, $indonesianMonths);
            return Carbon::createFromFormat('d F Y', $englishDate)->translatedFormat('d F Y');
        } catch (\Exception $e) {
            // Jika terjadi error, log dan kembalikan nilai default
            LogHelper::log('field_letter_date_parse', 'Error parsing date', null, ['error_message' => $e->getMessage()], 'error');
            return '......'; // Nilai fallback jika terjadi error
        }
    }
}
