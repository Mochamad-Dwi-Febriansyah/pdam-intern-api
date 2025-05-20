<?php

namespace App\Http\Requests;

use App\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SchoolUniRequest extends FormRequest
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
            'school_university_name' => 'sometimes|string|max:100',
            'school_major' => 'sometimes|nullable|string|max:100',
            'university_faculty' => 'sometimes|nullable|string|max:100',
            'university_program_study' => 'sometimes|nullable|string|max:100',
            'school_university_address' => 'sometimes|string|max:255',
            'school_university_postal_code' => 'sometimes|string|max:10',
            'school_university_province' => 'sometimes|string|max:100',
            'school_university_city' => 'sometimes|string|max:100',
            'school_university_district' => 'sometimes|string|max:100',
            'school_university_village' => 'sometimes|string|max:100',
            'school_university_phone_number' => 'sometimes|nullable|string|regex:/^\+?[\d\s\(\)-]+$/',
            'school_university_email' => 'sometimes|nullable|email|max:255',
        ];

        if ($this->isMethod('POST')) {
            $rules['school_university_name'] = 'required|string|max:100';
            $rules['school_university_address'] = 'required|string|max:255';
            $rules['school_university_postal_code'] = 'required|string|max:10';
            $rules['school_university_province'] = 'required|string|max:100';
            $rules['school_university_city'] = 'required|string|max:100';
            $rules['school_university_district'] = 'required|string|max:100';
            $rules['school_university_village'] = 'required|string|max:100';
            $rules['school_university_email'] = 'required|string|max:100';
            $rules['school_university_phone_number'] = 'required|nullable|string|regex:/^\+?[\d\s\(\)-]+$/';
        }

        return $rules;
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
