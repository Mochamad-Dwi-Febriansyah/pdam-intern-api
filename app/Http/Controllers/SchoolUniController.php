<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\LogHelper;
use App\Http\Requests\SchoolUniRequest;
use App\Models\SchoolUni;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SchoolUniController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schoolUni = SchoolUni::paginate(20);

        LogHelper::log('school_university_index', 'Retrieved the list of schoolUnis successfully', null, ['total_schoolUnis' => $schoolUni->total()]);

        return $this->successResponse($schoolUni, 'User list retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SchoolUniRequest $schoolUniRequest)
    {
        DB::beginTransaction();

        try {
            $schoolUni = SchoolUni::firstOrCreate(
                [
                    'school_university_email' => $schoolUniRequest->school_university_email,
                ],
                $schoolUniRequest->validated()
            );

        DB::commit();

        LogHelper::log('school_university_store', 'Created a new school university successfully', $schoolUni, [
            'school_university' => $schoolUni->id
        ]);

        $data = [
            'data' => $schoolUni
        ];

        return $this->successResponse($data, 'School university has been successfully created', Response::HTTP_CREATED);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('school_uni_store', 'Failed to create a new school university', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the school university', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $schoolUni = SchoolUni::find($id);

        if(!$schoolUni)
        {
            return $this->errorResponse(null, 'School university not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('school_university_show', 'Viewed school university details successfully', $schoolUni);

        return $this->successResponse($schoolUni, 'School university details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SchoolUniRequest $schoolUniRequest, string $id)
    {
        DB::beginTransaction();

        try {
            $schoolUni = SchoolUni::find($id);

            if(!$schoolUni)
            {
                return $this->errorResponse(null, 'School university not found', Response::HTTP_NOT_FOUND);
            }

            $schoolUniData = array_filter($schoolUniRequest->validated(), fn($value) => $value !== null); 
 
            if (!empty($schoolUniData)) 
            {
                $schoolUni->fill($schoolUniData)->save();
            }

            DB::commit();

            LogHelper::log('school_university_update', 'School university updated successfully', $schoolUni, [
                'updated_fields' => $schoolUniData,
            ]);

            return $this->successResponse(null, 'School university has been successfully updated', Response::HTTP_OK);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('school_university_update', 'Failed to update school university', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the school university', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $schoolUni = SchoolUni::find($id);

            if(!$schoolUni)
            {
                return $this->errorResponse(null, 'School university not found', Response::HTTP_NOT_FOUND);
            }

            $schoolUni->delete();

            DB::commit();

            LogHelper::log('school_university_destroy', 'School university deleted successfully', $schoolUni, [
                'deleted_school_university_id' => $schoolUni->id,
                'deleted_school_university_name' => $schoolUni->name
            ]);

            return $this->successResponse(null, 'School university has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('school_university_destroy', 'Failed to delete school university', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the school university', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
