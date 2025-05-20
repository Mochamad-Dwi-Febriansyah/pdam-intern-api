<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Helpers\FileHelper;
use App\Helpers\LogHelper;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['document', 'document.schoolUni']);

        // Filter berdasarkan role dari Spatie
        if ($request->has('role') && in_array($request->role, ['intern', 'researcher'])) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
    
        // Filter berdasarkan status jika ada query parameter
        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('status', $request->status);
        } 

        $users = $query->paginate(20);  
        
        LogHelper::log('user_index', 'Retrieved the list of users successfully', null, [
            'role' => $request->role ?? 'all',
            'status' => $request->status ?? 'all',
            'total_users' => $users->total()
        ]);

        return $this->successResponse($users, 'User list retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $userRequest)
    {
        DB::beginTransaction();

        try {
            $user = User::create(array_merge(
                $userRequest->validated(),
                ['password' => Hash::make('123456')]
            ));

        DB::commit();

        LogHelper::log('user_store', 'Created a new user successfully', $user, [
            'user' => $user->id
        ]);

        $data = [
            'data' => $user
        ];

        return $this->successResponse($data, 'User has been successfully created', Response::HTTP_CREATED);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('user_store', 'Failed to create a new user', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while creating the user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['document.schoolUni'])->find($id);
        $user['role'] = $user->roles->first()?->name;

        if(!$user)
        {
            return $this->errorResponse(null, 'User not found', Response::HTTP_NOT_FOUND);
        }

        LogHelper::log('user_show', 'Viewed user details successfully', $user);

        return $this->successResponse($user, 'User details retrieved successfully', Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $userRequest, string $id)
    {
        // return response()->json(["sa" => $this->route('id')]);
        DB::beginTransaction();

        try {
            $user = User::find($id);

            if(!$user)
            {
                return $this->errorResponse(null, 'User not found', Response::HTTP_NOT_FOUND);
            }

            $uploadedFiles = [];
            $fileFields = ['photo'];

            foreach ($fileFields as $field) {
                if ($userRequest->hasFile($field)) {
                    FileHelper::deleteFile($user->$field);
                    $uploadedFiles[$field] = FileHelper::uploadFile($userRequest->file($field), 'users');
                } else {
                    $uploadedFiles[$field] = $user->$field;
                }
            }

            $userData = array_filter($userRequest->validated(), fn($value) => $value !== null);
            $mergedUserData = array_merge($userData, $uploadedFiles);

            if (!empty($userData['password']))
            {
                $userData['password'] = Hash::make($userData['password']);
            }
 
            if (!empty($userData)) 
            {
                $user->fill($mergedUserData)->save();
            }

            DB::commit();

            LogHelper::log('user_update', 'User updated successfully', $user, [
                'updated_fields' => $userData,
            ]);

            return $this->successResponse(null, 'User has been successfully updated', Response::HTTP_OK);

        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('user_update', 'Failed to update user', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while updating the user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $user = User::find($id);

            if(!$user)
            {
                return $this->errorResponse(null, 'User not found', Response::HTTP_NOT_FOUND);
            }

            $user->delete();

            DB::commit();

            LogHelper::log('user_destroy', 'User deleted successfully', $user, [
                'deleted_user_id' => $user->id,
                'deleted_user_name' => $user->name
            ]);

            return $this->successResponse(null, 'User has been successfully deleted', Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::log('user_destroy', 'Failed to delete user', null, [], 'error');
            return $this->errorResponse($th->getMessage(), 'An error occurred while deleting the user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    } 
}
