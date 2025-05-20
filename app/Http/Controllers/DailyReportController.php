<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\LogHelper;
use App\Http\Requests\DailyReportRequest;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class DailyReportController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $userId = $request->get('user_id', $user->id); 

        $dailyReports = DailyReport::with('attendance')->where('user_id', $userId)->paginate(20);

        LogHelper::log('daily_reports_index', 'Retrieved the list of daily reports successfully', null, ['total_daily_reports' => $dailyReports->total()]);

        return $this->successResponse($dailyReports, 'Daily reports list retrieved successfully', Response::HTTP_OK);
    } 

    /**
     * Store a newly created resource in storage.
     */
    public function store(DailyReportRequest $dailyReportRequest)
    {
        DB::beginTransaction();

        try {
            $user = JWTAuth::parseToken()->authenticate();

            $dailyReport = DailyReport::create(array_merge(
                $dailyReportRequest->validated(),
                ['user_id' => $user->id,]
            ));

            DB::commit();

            LogHelper::log('daily_report_store', 'Created a new daily report successfully', $dailyReport, [
                'daily_report' => $dailyReport->id
            ]);

            $data = [
                'data' => $dailyReport
            ];

            return $this->successResponse($data, 'Daily report has been successfully created', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('daily_reports_store', 'Failed to create a new daily report', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the daily report', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $dailyReport = DailyReport::with('attendance')->find($id);

        if (!$dailyReport) {
            return $this->errorResponse(null, 'Daily report not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('daily_report_show', 'Viewed daily report details successfully', $dailyReport);

        return $this->successResponse($dailyReport, 'Daily report details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DailyReportRequest $dailyReportRequest, string $id)
    {
        DB::beginTransaction();

        try {
            $dailyReport = DailyReport::find($id);

            if (!$dailyReport) {
                return $this->errorResponse(null, 'Daily report not found', Response::HTTP_NOT_FOUND);
            }

            $dailyReportData = array_filter($dailyReportRequest->validated(), fn($value) => $value !== null);

            if (!empty($dailyReportData)) {
                $dailyReport->fill($dailyReportData)->save();
            }

            DB::commit();

            LogHelper::log('daily_report_update', 'Daily report updated successfully', $dailyReport, [
                'updated_fields' => $dailyReportData,
            ]);

            return $this->successResponse(null, 'Daily report has been successfully updated', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('daily_report_update', 'Failed to update daily report', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the daily report', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $dailyReport = DailyReport::find($id);

            if (!$dailyReport) {
                return $this->errorResponse(null, 'Daily report not found', Response::HTTP_NOT_FOUND);
            }

            $dailyReport->delete();

            DB::commit();

            LogHelper::log('daily_report_destroy', 'Daily report deleted successfully', $dailyReport, [
                'deleted_daily_report_id' => $dailyReport->id,
                'deleted_daily_report_name' => $dailyReport->name
            ]);

            return $this->successResponse(null, 'Daily report has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('daily_report_destroy', 'Failed to delete daily report', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the daily report', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDailyReportsByMentor(Request $request)
    {
        $mentorId = $request->user_sso_id;

        if (!$mentorId) {
            return $this->errorResponse(null, 'Mentor ID is missing', Response::HTTP_BAD_REQUEST);
        }

        $dailyReports = DailyReport::whereHas('user.document', function ($query) use ($mentorId) {
            $query->where('mentor_id', $mentorId);
        })->with(['user', 'attendance'])->where('status', '!=', 'approved')->get(); // gunakan get() bukan paginate()

        $grouped = $dailyReports->groupBy(function ($report) {
            return $report->user_id;
        })->map(function ($reports, $userId) {
            return [
                'user' => $reports->first()->user,
                'reports' => $reports,
            ];
        });

        return $this->successResponse($grouped->values(), 'Daily reports grouped by user', Response::HTTP_OK);
    }

    public function mentorVerification(Request $request)
    {
        $mentorId = $request->user_sso_id;

        if (!$mentorId) {
            return $this->errorResponse(null, 'Mentor ID is missing', Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'uuid|exists:daily_reports,id',
            'status' => 'required|in:approved,pending,rejected',
            'rejection_note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            $updatedReports = [];

            foreach ($request->ids as $id) {
                $dailyReport = DailyReport::find($id);

                if ($dailyReport) {
                    $dailyReport->update([
                        'status' => $request->status,
                        'rejection_note' => $request->rejection_note,
                        'verified_by_id' => $mentorId,
                    ]);

                    $updatedReports[] = $dailyReport;

                    LogHelper::log('daily_report_status_update', 'Updated daily report status successfully', $dailyReport, [
                        'updated_fields' => [
                            'status' => $request->status,
                            'rejection_note' => $request->rejection_note,
                            'verified_by_id' => $mentorId,
                        ],
                    ]);
                }
            }

            DB::commit();

            return $this->successResponse($updatedReports, 'Daily reports updated successfully', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('daily_report_status_update', 'Failed to update daily reports', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the daily reports', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function export(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate()->load(['document', 'document.schoolUni']);

        $attendances = Attendance::with('dailyReport')->where('user_id', $user->id)->get();
        // dd($attendances);

        $statusApprovedTtdMentor = true;

        foreach ($attendances as $logBook) {
            if ($logBook->dailyReport && $logBook->dailyReport->status !== 'approved') {
                $statusApprovedTtdMentor = false;
                break;
            }
        } 

        $logBook = [
            'user' => [
                'name' => $user->name,
                'nisn_npm_nim' => $user->nisn_npm_nim,

                'start_date' => \Carbon\Carbon::parse($user->document->start_date)->translatedFormat('d F Y'),
                'end_date' => \Carbon\Carbon::parse($user->document->end_date)->translatedFormat('d F Y'),

                'mentor_id' => $user->document->mentor_id,

                'school_university_name' => $user->document->schoolUni->school_university_name,
                'school_major' => $user->document->schoolUni->school_major,
                'university_faculty' => $user->document->schoolUni->university_faculty,
                'university_program_study' => $user->document->schoolUni->university_program_study,

                'mentor_ttd' => $statusApprovedTtdMentor,
                'mentor_name' => $user->document->mentor_name,
                'mentor_rank_group' => $user->document->mentor_rank_group,
                'mentor_position' => $user->document->mentor_position,
                'mentor_nik' => $user->document->mentor_nik
            ],
            'logBook' => $attendances,
        ];
        // dd($logBook);

        $tempDir = storage_path('app/temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Generate PDF dan simpan ke file sementara
        $pdf = Pdf::loadView('pdf.logbook', ['result' => $logBook]);
        // dd($logBook);
        $tempFileName = 'logbook_' . Str::random(10) . '.pdf';
        $tempFilePath = storage_path('app/temp/' . $tempFileName);
        $pdf->save($tempFilePath);

        $finalPdfPath = $tempFilePath;
        // Jika semua status approved, lakukan tanda tangan
        if ($statusApprovedTtdMentor) {
            if (empty($user->document->mentor_nik)) {
                return $this->errorResponse(null, 'Gagal menandatangani dokumen. NIK mentor belum tersedia.', Response::HTTP_UNPROCESSABLE_ENTITY);
            
            }
            $finalPdfPath = $this->signWithBsre(
                $tempFilePath,
                $tempFileName,
                '1234567890123456',
                '#Bsr3DeVUser.!2025' // Bisa juga diganti $request->passphrase kalau mau dinamis
            );

            // Hapus file original jika sudah ditandatangani
            File::delete($tempFilePath);
        }

        // Kirim hasil file ke client untuk didownload
        $fileContent = file_get_contents($finalPdfPath);
        $downloadName = 'logbook_signed_' . now()->format('Ymd_His') . '.pdf';

        // Hapus file sementara setelah dibaca
        File::delete([$tempFilePath, $finalPdfPath]);

        return response($fileContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
        ]);

        return $pdf->download();

        return $this->successResponse($logBook, 'Daily reports list retrieved successfully', Response::HTTP_OK);
    }


    private function signWithBsre($pdfPath, $pdfFileName, $nik, $passphrase)
    {

        $client = new Client();
        // $bsreUrl = 'http://103.101.52.82/api/sign/pdf';
        $bsreUrl = env('BSRE_API_URL') . '/api/sign/pdf';
        try {

            $response = $client->request('POST', $bsreUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(env('BSRE_USERNAME') . ':' . env('BSRE_PASSWORD')),
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
                        'contents' => env('BSRE_LINKQR')
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
                        'contents' => '80'
                    ],
                ],

            ]);
            $tempPath = storage_path('app/temp/');
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0777, true); // Buat folder jika belum ada
            }

            $signedPdfPath = $tempPath . 'signed_' . $pdfFileName;
            file_put_contents($signedPdfPath, $response->getBody()->getContents());

            return $signedPdfPath; // Ini yang akan digunakan untuk download
        } catch (RequestException $e) {
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;

            $parsedResponse = json_decode($responseBody, true);
            if (!$parsedResponse) {
                $parsedResponse = ['status' => 'error', 'message' => 'Response dari BSrE tidak dapat diproses', 'raw_response' => $responseBody];
            }

            throw new HttpResponseException(response()->json($parsedResponse, $statusCode));
        }
    }
}
