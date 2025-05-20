<?php

namespace App\Http\Requests;

use App\ApiResponse;
use App\Helpers\FileHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AttendanceRequest extends FormRequest
{
    use ApiResponse;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'user_id' => 'sometimes|nullable|exists:users,id',
            'date' => 'sometimes|date',
            'check_in_time' => 'sometimes|nullable|date_format:H:i:s',
            'check_out_time' => 'sometimes|nullable|date_format:H:i:s',
            'check_in_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'check_out_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'check_in_latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'check_out_latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'check_in_longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'check_out_longitude' => 'sometimes|nullable|numeric|between:-180,180', 
            'status' => 'sometimes|in:present,permission,sick,absent',
        ];

        if ($this->isMethod('POST')) {
            $rules['user_id'] = 'nullable|exists:users,id';
            $rules['date'] = 'nullable|date';
            $rules['check_in_time'] = 'nullable|date_format:H:i:s';
            $rules['check_out_time'] = 'nullable|date_format:H:i:s|after_or_equal:check_in_time';
            $rules['check_in_photo'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048'; 
            $rules['check_out_photo'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048'; 
            $rules['check_in_latitude'] = 'nullable|numeric|between:-90,90';  
            $rules['check_out_latitude'] = 'nullable|numeric|between:-90,90';  
            $rules['check_in_longitude'] = 'nullable|numeric|between:-180,180';  
            $rules['check_out_longitude'] = 'nullable|numeric|between:-180,180';  
            $rules['status'] = 'nullable|in:present,permission,sick,absent';
        } 
    
        return $rules;
    }

    public function handleUploads(): array
    {
        return [
            'check_in_photo' => FileHelper::uploadFile($this->file('check_in_photo'), 'attendances/check_in_photos'),
            'check_out_photo' => FileHelper::uploadFile($this->file('check_out_photo'), 'attendances/check_out_photos'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
