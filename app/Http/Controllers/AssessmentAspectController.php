<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\LogHelper;
use App\Http\Requests\AssessmentAspectRequest;
use App\Models\AssessmentAspect;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AssessmentAspectController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AssessmentAspect::query();
 
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $sortBy = $request->get('sort_by', 'created_at'); // default sorting by created_at
        $sortOrder = $request->get('sort_order', 'desc'); // default order desc
    
        // Validasi kolom yang bisa disort
        $allowedSortFields = ['code_field', 'name_field', 'status', 'created_at'];
    
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }
    
        $assessmentAspects = $query->paginate(20);
    
        LogHelper::log('assessment_aspect_index', 'Retrieved the list of assessment aspect successfully', null, [
            'total_assessment_aspect' => $assessmentAspects->total(),
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ]);
    

        return $this->successResponse($assessmentAspects, 'Assessment aspect list retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssessmentAspectRequest $assessmentAspectRequest)
    {
        DB::beginTransaction();

        try {
            $assessmentAspect = AssessmentAspect::create( 
                $assessmentAspectRequest->validated()
            );

        DB::commit();

        LogHelper::log('assessment_aspect_store', 'Created a new assessment aspect successfully', $assessmentAspect, [
            'assessment_aspect' => $assessmentAspect->id
        ]);

        $data = [
            'data' => $assessmentAspect
        ];

        return $this->successResponse($data, 'Assessment aspect has been successfully created', Response::HTTP_CREATED);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('assessment_aspect_store', 'Failed to create a new assessment aspect', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the assessment aspect', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $assessmentAspect = AssessmentAspect::find($id);

        if(!$assessmentAspect)
        {
            return $this->errorResponse(null, 'Assessment aspect not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('assessment_aspect_show', 'Viewed assessment aspect details successfully', $assessmentAspect);

        return $this->successResponse($assessmentAspect, 'Assessment aspect details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssessmentAspectRequest $assessmentAspectRequest, string $id)
    {
        DB::beginTransaction();

        try {
            $assessmentAspect = AssessmentAspect::find($id);

            if(!$assessmentAspect)
            {
                return $this->errorResponse(null, 'Assessment aspect not found', Response::HTTP_NOT_FOUND);
            }

            $assessmentAspectData = array_filter($assessmentAspectRequest->validated(), fn($value) => $value !== null); 
 
            if (!empty($assessmentAspectData)) 
            {
                $assessmentAspect->fill($assessmentAspectData)->save();
            }

            DB::commit();

            LogHelper::log('assessment_aspect_update', 'Assessment aspect updated successfully', $assessmentAspect, [
                'updated_fields' => $assessmentAspectData,
            ]);

            return $this->successResponse(null, 'Assessment aspect has been successfully updated', Response::HTTP_OK);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('assessment_aspect_update', 'Failed to update assessment aspect', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the assessment aspect', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $assessmentAspect = AssessmentAspect::find($id);

            if(!$assessmentAspect)
            {
                return $this->errorResponse(null, 'Assessment aspect not found', Response::HTTP_NOT_FOUND);
            }

            $assessmentAspect->delete();

            DB::commit();

            LogHelper::log('assessment_aspect_destroy', 'Assessment aspect deleted successfully', $assessmentAspect, [
                'deleted_assessment_aspect_id' => $assessmentAspect->id,
                'deleted_assessment_aspect_name' => $assessmentAspect->name
            ]);

            return $this->successResponse(null, 'Assessment aspect has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('assessment_aspect_destroy', 'Failed to delete assessment aspect', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the assessment aspect', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
