<?php

namespace App\Http\Requests;

use App\ApiResponse;
use App\Helpers\FileHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
    public function rules()
    {
        $userId = $this->route('user'); 
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone_number' => 'sometimes|string|regex:/^\+?[\d\s\(\)-]+$/|max:15',
            'photo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
            'address' => 'sometimes|string|max:255',
            'postal_code' => 'sometimes|numeric|digits_between:5,10',
            'province' => 'sometimes|string|max:100',
            'city' => 'sometimes|string|max:100',
            'district' => 'sometimes|string|max:100',
            'village' => 'sometimes|string|max:100',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female',
            'password' => 'sometimes|string|min:6|confirmed',
            'role' => 'sometimes|in:intern,researcher,admin',
        ];

        if ($this->isMethod('POST')) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|email|unique:users,email';
            $rules['password'] = 'sometimes|string|min:6|confirmed';
            $rules['nisn_npm_nim'] = 'required|string|max:20';
            $rules['date_of_birth'] = 'required|date';
            $rules['gender'] = 'required|in:male,female';
            $rules['phone_number'] = 'required|string|regex:/^\+?[\d\s\(\)-]+$/|max:15';
            $rules['photo'] = 'sometimes|image|mimes:jpg,jpeg,png|max:2048';
            $rules['address'] = 'required|string|max:255';
            // $rules['postal_code'] = 'required|numeric|digits_between:5,10';
            $rules['province'] = 'required|string|max:100';
            $rules['city'] = 'required|string|max:100';
            $rules['district'] = 'required|string|max:100';
            $rules['village'] = 'required|string|max:100';
            // $rules['role'] = 'nullable|in:intern,researcher';
        }

        return $rules;
    }
    public function handleUploads(): array
    {
        return [ 
            'photo' => FileHelper::uploadFile($this->file('photo'), 'users/photo'), 
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
