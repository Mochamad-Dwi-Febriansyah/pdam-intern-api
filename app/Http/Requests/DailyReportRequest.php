<?php

namespace App\Http\Requests;

use App\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class DailyReportRequest extends FormRequest
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
            'user_id' => 'sometimes|uuid|exists:users,id',
            'attendance_id' => 'sometimes|uuid|exists:attendances,id',
            'title' => 'sometimes|string|max:255',
            'report' => 'sometimes|string',
            'result' => 'sometimes|string',
            'status' => 'sometimes|in:approved,pending,rejected',
            'rejection_note' => 'sometimes|string|max:255',
            'verified_by_id' => 'sometimes|uuid|exists:users,id',
        ];
        if ($this->isMethod('POST')) {
            $rules['user_id'] = 'sometimes|uuid|exists:users,id';
            $rules['attendance_id'] = 'required|uuid|exists:attendances,id';
            $rules['title'] = 'nullable|string|max:255';
            $rules['report'] = 'nullable|string';
            $rules['result'] = 'nullable|string';
            $rules['status'] = 'nullable|in:approved,pending,rejected';
            $rules['rejection_note'] = 'nullable|string|max:255';
            $rules['verified_by_id'] = 'nullable|uuid|exists:users,id';
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
