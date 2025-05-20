<?php

namespace App\Http\Requests;

use App\ApiResponse;
use App\Helpers\FileHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DocumentRequest extends FormRequest
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
            'mentor_id' => 'sometimes|nullable|exists:users,id',
            'mentor_name' => 'sometimes|nullable|string|max:255',
            'mentor_rank_group' => 'sometimes|nullable|string|max:255',
            'mentor_position' => 'sometimes|nullable|string|max:255',
            'mentor_nik' => 'sometimes|nullable|string|max:50',

            'identity_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'application_letter' => 'sometimes|mimes:pdf,doc,docx|max:2048',
            'accepted_letter' => 'sometimes|nullable|mimes:pdf,doc,docx|max:2048',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'work_certificate' => 'sometimes|nullable|mimes:pdf,doc,docx|max:2048',
            'document_status' => 'sometimes|required|in:pending,approved,rejected',
            'verified_by_id' => 'sometimes|nullable|exists:users,id',
        ];
    
        if ($this->isMethod('POST')) {
            $rules['user_id'] = 'nullable|exists:users,id';
            $rules['school_university_id'] = 'nullable|exists:school_unis,id';
            $rules['identity_photo'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
            $rules['application_letter'] = 'required|mimes:pdf,doc,docx|max:2048';
            $rules['start_date'] = 'required|date';
            $rules['end_date'] = 'required|date|after_or_equal:start_date';
            $rules['document_status'] = 'nullable|in:pending,approved,rejected';

                 // Tambahkan validasi mentor juga saat POST jika diperlukan
            $rules['mentor_id'] = 'nullable|string|max:255';
            $rules['mentor_name'] = 'nullable|string|max:255';
            $rules['mentor_rank_group'] = 'nullable|string|max:255';
            $rules['mentor_position'] = 'nullable|string|max:255';
            $rules['mentor_nik'] = 'nullable|string|max:50';
        }
    
        return $rules;
    }

    public function handleUploads(): array
    {
        return [
            'identity_photo' => FileHelper::uploadFile($this->file('identity_photo'), 'documents/identity_photos'),
            'application_letter' => FileHelper::uploadFile($this->file('application_letter'), 'documents/application_letter'),
            'accepted_letter' => FileHelper::uploadFile($this->file('accepted_letter'), 'documents/accepted_letter'),
            'work_certificate' => FileHelper::uploadFile($this->file('work_certificate'), 'documents/work_certificate')
        ];
    }

    protected function failedValidation(Validator $validator)
    { 
        throw new HttpResponseException(
            $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    } 

    
}
