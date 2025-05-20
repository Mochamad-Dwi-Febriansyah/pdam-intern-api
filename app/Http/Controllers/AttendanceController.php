<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\FileHelper;
use App\Helpers\LogHelper;
use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AttendanceController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $userId = $request->get('user_id', $user->id); 

        $attendance = Attendance::with('dailyReport')->where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(20);
        
        LogHelper::log('attendance_index', 'Retrieved the list of attendance successfully', null, ['total_attendance' => $attendance->total()]);

        return $this->successResponse($attendance, 'Attendance list retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttendanceRequest $attendanceRequest)
    {
        DB::beginTransaction();

    
        try {
            $date = $attendanceRequest->tanggal;
            $userId = $attendanceRequest->user->id;
    
            // Cari attendance berdasarkan user & tanggal
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $date)
                ->first();
            // return response()->json($userId);

            $fotoPath = null;

            // Simpan foto
            if ($attendanceRequest->hasFile('foto')) {
                $foto = $attendanceRequest->file('foto');
                $fileName = time() . '_' . ($attendance ? 'checkout' : 'checkin') . '_' . $foto->getClientOriginalName();
                $fotoPath = $foto->storeAs('presensi', $fileName, 'public');
                $fotoPath = str_replace('public/', '', $fotoPath);
            }
    
            // Proses upload foto (check-in / check-out)
            $uploaded = $attendanceRequest->handleUploads();  
            if (!$attendance) {
                $attendance = Attendance::create([
                    'user_id' => $attendanceRequest->user->id,
                    'date' => Carbon::parse($date)->format('Y-m-d'),
                    'check_in_time' => $attendanceRequest->waktu,
                    'check_in_photo' => $fotoPath,
                    'check_in_latitude' => $attendanceRequest->latitude,
                    'check_in_longitude' => $attendanceRequest->longitude, 
                ]);
    
                $message = 'Presensi Check-In berhasil';
            } else {
                // Sudah ada → Update sebagai Check-Out
                $attendance->update([
                    'check_out_time' => $attendanceRequest->waktu,
                    'check_out_photo' => $fotoPath,
                    'check_out_latitude' => $attendanceRequest->latitude,
                    'check_out_longitude' => $attendanceRequest->longitude, 
                    'status' => 'present'
                ]);
    
                $message = 'Presensi Check-Out berhasil';
            }  

            DB::commit();

            LogHelper::log('attendance_store', 'Created a new attendance successfully', $attendance, [
                'attendance' => $attendance->id
            ]);
    
            $data = [
                'data' => $attendance
            ];

            return $this->successResponse(null, 'Attendance has been successfully created', Response::HTTP_CREATED);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('attendance_store', 'Failed to create a new attendance', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the attendance', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $attendance = Attendance::find($id);

        if(!$attendance)
        {
            return $this->errorResponse(null, 'Attendance not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('attendance_show', 'Viewed attendance details successfully', $attendance);

        return $this->successResponse($attendance, 'Attendance details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AttendanceRequest $attendanceRequest, string $id)
    {
        DB::beginTransaction();

        try {
            $attendance = Attendance::find($id);

            if(!$attendance)
            {
                return $this->errorResponse(null, 'Attendance not found', Response::HTTP_NOT_FOUND);
            }

            $uploadedFiles = [];
            $fileFields = ['check_in_photo', 'check_out_photo'];
    
            foreach ($fileFields as $field) {
                if ($attendanceRequest->hasFile($field)) {
                    FileHelper::deleteFile($attendance->$field);
                    $uploadedFiles[$field] = FileHelper::uploadFile($attendanceRequest->file($field), 'attendance');
                } else {
                    $uploadedFiles[$field] = $attendance->$field; // Tetap gunakan file lama jika tidak ada upload baru
                }
            }

            $attendanceData = array_filter($attendanceRequest->validated(), fn($value) => $value !== null);
            $mergedAttendanceData = array_merge($attendanceData, $uploadedFiles);

 

            if (!empty($mergedAttendanceData)) {
                $attendance->fill($mergedAttendanceData); 
                $attendance->save();
            }

            DB::commit();

            LogHelper::log('attendance_update', 'Attendance updated successfully', $attendance, [
                'updated_fields' => $attendanceData,
            ]);

            return $this->successResponse(null, 'Attendance has been successfully updated', Response::HTTP_OK);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('attendance_update', 'Failed to update attendance', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the attendance', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $attendance = Attendance::find($id);

            if(!$attendance)
            {
                return $this->errorResponse(null, 'Attendance not found', Response::HTTP_NOT_FOUND);
            }

            $attendance->delete();

            DB::commit();

            LogHelper::log('attendance_destroy', 'Attendance deleted successfully', $attendance, [
                'deleted_attendance_id' => $attendance->id,
                'deleted_attendance_name' => $attendance->name
            ]);

            return $this->successResponse(null, 'Attendance has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('attendance_destroy', 'Failed to delete attendance', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the attendance', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function today()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $today = now()->toDateString();  
        
        $attendance = Attendance::whereDate('date', $today)
        ->where('user_id', $user->id)  
        ->first();

        // dd($attendance);

        LogHelper::log('attendance_today', 'Retrieved today\'s attendance successfully', $attendance, [
            'user_id' => $user->id, 
        ]);

        return $this->successResponse($attendance, 'Today\'s attendance retrieved successfully', Response::HTTP_OK);
    }
}
