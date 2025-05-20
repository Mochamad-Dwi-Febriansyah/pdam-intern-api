<?php

namespace App\Http\Requests;

use App\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class CertificateRequest extends FormRequest
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
            'user_id' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'uuid',
                'exists:users,id'
            ],
            'document_id' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'uuid',
                'exists:documents,id'
            ],
            'certificate_number' => 'nullable|string|max:255|unique:certificates,certificate_number',
         'passphrase' => 'required_unless:skip_signature,true',
            'total_score' => 'nullable|numeric|min:0|max:100',
            'average_score' => 'nullable|numeric|min:0|max:100',
            'certificate_path' => 'nullable|string|max:500',
            'status' => 'sometimes|in:draft,issued,revoked',
            'issued_at' => 'nullable|date_format:Y-m-d H:i:s',
        ]; 
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
