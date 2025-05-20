<?php

namespace App\Http\Requests;

use App\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class CertificateFieldRequest extends FormRequest
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
        return [
            'certificate_id' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'uuid',
                'exists:certificates,id'
            ],
            'assessment_aspects_id' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'uuid',
                'exists:assessment_aspects,id'
            ],
            'score' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'numeric',
                'between:0,100'
            ],
            'status' => 'sometimes|in:active,inactive',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
