<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\FileHelper;
use App\Helpers\LogHelper;
use App\Http\Requests\FinalReportRequest;
use App\Models\FinalReport;
use App\Models\FinalReportHistori;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class FinalReportController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $userId = $request->get('user_id', $user->id); 

        $finalReports = FinalReport::where('user_id', $userId)->paginate(20);

        LogHelper::log('final_reports_index', 'Retrieved the list of final reports successfully', null, ['total_final_reports' => $finalReports->total()]);

        return $this->successResponse($finalReports, 'Final reports list retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FinalReportRequest $finalReportRequest)
    {
        DB::beginTransaction();

        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $alreadyExists = FinalReport::where('user_id', $user->id)->exists();

            if ($alreadyExists) {
                return $this->errorResponse(null, 'Final report already used', Response::HTTP_CONFLICT);
            }

            if (!$user->document) {
                throw new \Exception('Dokumen tidak ditemukan untuk user ini.');
            }
        
            if (!$user->document->schoolUni) {
                throw new \Exception('School/University tidak ditemukan untuk user ini.');
            }
        
            // Baru akses data setelah validasi aman
            $documentId = $user->document->id;
            $schoolUniversityId = $user->document->schoolUni->id;

            $uploadedFiles = $finalReportRequest->handleUploads();

            // dd([$schoolUniversityId]);
            $finalReports = FinalReport::create(array_merge(
                [
                    'user_id' => $user->id,
                    'document_id' => $documentId,
                    'school_university_id' => $schoolUniversityId,
                ],
                $finalReportRequest->validated(),
                $uploadedFiles
            ));

            $finalReportHistori = FinalReportHistori::create([
                'user_id' => $user->id,
                'document_id' => $documentId,
                'school_university_id' => $schoolUniversityId,
                'final_report_id' => $finalReports->id,
                'updated_by' => $user->id,
                'title' => $finalReportRequest->title,
                'report' => $finalReportRequest->report,
                'video' => $finalReportRequest->video,
                'version_number' => 1,
            ] + $uploadedFiles);

            DB::commit();

            LogHelper::log('final_report_store', 'Created a new final report successfully', $finalReports, [
                'final_report' => $finalReports->id
            ]);

            // $data = [
            //     'data' => $document
            // ];

            return $this->successResponse(null, 'Final report has been successfully created', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('final report_store', 'Failed to create a new final report', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the final report', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $finalReport = FinalReport::with('user', 'document' ,'histories')->find($id);

        if (!$finalReport) {
            return $this->errorResponse(null, 'Final report not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('final_report_show', 'Viewed final report details successfully', $finalReport);

        return $this->successResponse($finalReport, 'Final report details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FinalReportRequest $finalReportRequest, string $id)
    {
        DB::beginTransaction();

        try {
            $user = JWTAuth::parseToken()->authenticate();

            $finalReports = FinalReport::find($id);

            if (!$finalReports) {
                return $this->errorResponse(null, 'Final report not found', Response::HTTP_NOT_FOUND);
            }

            $lastHistory = FinalReportHistori::where('final_report_id', $finalReports->id)
                ->orderByDesc('version_number')->first();
            $nextVersion = $lastHistory ? $lastHistory->version_number + 1 : 1;

            $finalReportHistori = FinalReportHistori::create([
                'final_report_id' => $finalReports->id,
                'updated_by' => $user->id,
                'title' => $finalReports->title,
                'report' => $finalReports->report,
                'video' => $finalReports->video,
                'assessment_report_file' => $finalReports->assessment_report_file,
                'final_report_file' => $finalReports->final_report_file,
                'photo' => $finalReports->photo,
                'version_number' => $nextVersion
            ]);

            $uploadedFiles = [];
            $fileFields = ['assessment_report_file', 'final_report_file', 'photo'];

            foreach ($fileFields as $field) {
                if ($finalReportRequest->hasFile($field)) {
                    FileHelper::deleteFile($finalReports->$field);
                    $uploadedFiles[$field] = FileHelper::uploadFile($finalReportRequest->file($field), 'final_reports');
                } else {
                    $uploadedFiles[$field] = $finalReports->$field;
                }
            }

            $finalReportData = array_filter($finalReportRequest->validated(), fn($value) => $value !== null);
            $mergedFinalReportData = array_merge($finalReportData, $uploadedFiles);

            if (!empty($mergedFinalReportData)) {
                $finalReports->fill($mergedFinalReportData); 
                $finalReports['mentor_verification_status'] = 'pending';
                $finalReports['hr_verification_status'] = 'pending';
                // Log::info('Updated Final Reports:', $finalReports->toArray());
                $finalReports->save();
            }

            DB::commit();

            LogHelper::log('final_report_update', 'Final report updated successfully', $finalReports, [
                'updated_fields' => $finalReportData,
            ]);

            return $this->successResponse(null, 'Final report has been successfully updated', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('final_report_update', 'Failed to update final report', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the final report', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $finalReport = FinalReport::find($id);

            if (!$finalReport) {
                return $this->errorResponse(null, 'Final report not found', Response::HTTP_NOT_FOUND);
            }

            $finalReport->delete();

            DB::commit();

            LogHelper::log('final_report_destroy', 'Final report deleted successfully', $finalReport, [
                'deleted_final_report_id' => $finalReport->id,
                'deleted_final_report_name' => $finalReport->name
            ]);

            return $this->successResponse(null, 'Final report has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('final_report_destroy', 'Failed to delete final report', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the final report', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getFinalReportsByMentor(Request $request)
    {
        $mentorId = $request->user_sso_id;
        // return response()->json([$mentorId]);
        if (!$mentorId) {
            return $this->errorResponse(null, 'Mentor ID is missing', Response::HTTP_BAD_REQUEST);
        }

        $finalReports = FinalReport::whereHas('user', function ($query) use ($mentorId) {
            $query->whereHas('document', function ($subQuery) use ($mentorId) {
                $subQuery->where('mentor_id', $mentorId);
            });
        })->with(['user.document'])->where('mentor_verification_status', '!=' , 'approved')->paginate(20);


        LogHelper::log('final_report_mentor_index', 'Retrieved the list of final reports successfully', null, ['total_final_reports' => $finalReports->total()]);

        return $this->successResponse($finalReports, 'Final reports list retrieved successfully', Response::HTTP_OK);
    }

    public function getFinalReportsByHr()
    {
        $finalReports = FinalReport::with(['user.document'])->where('mentor_verification_status', 'approved')->where('hr_verification_status', '!=' , 'approved')->paginate(20);


        LogHelper::log('final_report_hr_index', 'Retrieved the list of final reports successfully', null, ['total_final_reports' => $finalReports->total()]);

        return $this->successResponse($finalReports, 'Final reports list retrieved successfully', Response::HTTP_OK);
    }

    public function mentorVerification(Request $request, $id)
    {
        $mentorId = $request->user_sso_id;

        if (!$mentorId) {
            return $this->errorResponse(null, 'Mentor ID is missing', Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'mentor_verification_status' => 'required|in:approved,pending,rejected',
            'mentor_rejection_note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            $finalReport = FinalReport::whereHas('user.document', function ($query) use ($mentorId) {
                $query->where('mentor_id', $mentorId);
            })->find($id);

            if (!$finalReport) {
                return $this->errorResponse(null, 'Final report not found', Response::HTTP_NOT_FOUND);
            }
            $finalReport->update([
                'mentor_verification_status' => $request->mentor_verification_status,
                'mentor_rejection_note' => $request->mentor_rejection_note ?? null,
                'mentor_verified_by_id' => $mentorId,
            ]);

            DB::commit();

            LogHelper::log('final_report_verified_mentor_update', 'Final report verification updated successfully', $finalReport, [
                'updated_fields' => [
                    'mentor_verification_status' => $request->mentor_verification_status,
                    'mentor_rejection_note' => $request->mentor_rejection_note ?? null,
                    'mentor_verified_by_id' => $mentorId,
                ],
            ]);


            return $this->successResponse($finalReport, 'Final report verified updated successfully', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('final_report_verified_mentor_update', 'Failed to updated final report verification', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updated the final report verification', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function hrVerification(Request $request, $id)
    {
        $hRId = $request->user_sso_id;

        if (!$hRId) {
            return $this->errorResponse(null, 'Hr ID is missing', Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'hr_verification_status' => 'required|in:approved,pending,rejected',
            'hr_rejection_note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            $finalReport = FinalReport::where('mentor_verification_status', 'approved')->find($id);

            if (!$finalReport) {
                return $this->errorResponse(null, 'Final report not found', Response::HTTP_NOT_FOUND);
            }
            $finalReport->update([
                'hr_verification_status' => $request->hr_verification_status,
                'hr_rejection_note' => $request->hr_rejection_note ?? null,
                'hr_verified_by_id' => $hRId,
            ]);

            DB::commit();

            LogHelper::log('final_report_verified_hr_update', 'Final report verification updated successfully', $finalReport, [
                'updated_fields' => [
                    'hr_verification_status' => $request->hr_verification_status,
                    'hr_rejection_note' => $request->hr_rejection_note ?? null,
                    'hr_verified_by_id' => $hRId,
                ],
            ]);


            return $this->successResponse($finalReport, 'Final report verified updated successfully', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('final_report_verified_hr_update', 'Failed to updated final report verification', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updated the final report verification', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
