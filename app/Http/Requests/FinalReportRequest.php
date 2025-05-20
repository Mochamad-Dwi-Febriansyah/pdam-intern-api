<?php

namespace App\Http\Requests;

use App\ApiResponse;
use App\Helpers\FileHelper;
use Illuminate\Foundation\Http\FormRequest; 
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Contracts\Validation\Validator;

class FinalReportRequest extends FormRequest
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
            'document_id' => 'sometimes|nullable|exists:documents,id',
            'school_university_id' => 'sometimes|nullable|exists:school_unis,id',
            'title' => 'sometimes|string|max:255',
            'report'=> 'sometimes|string',
            'assessment_report_file' => 'sometimes|mimes:pdf,doc,docx|max:2048',
            'final_report_file' => 'sometimes|mimes:pdf,doc,docs|max:2048',
            'photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'video' => 'sometimes|string',
            'mentor_verified_by_id' => 'sometimes',
            'mentor_verification_status' => 'sometimes|in:pending,approved,rejected',
            'mentor_rejection_note' => 'sometimes',
            'hr_verified_by_id' => 'sometimes',
            'hr_verification_status' => 'sometimes|in:pending,approved,rejected',
            'hr_rejection_note' => 'sometimes', 
        ];
    
        if ($this->isMethod('POST')) { 

            $rules['user_id'] = 'nullable|uuid|exists:users,id';
            $rules['document_id'] = 'nullable|uuid|exists:documents,id';
            $rules['school_university_id'] = 'nullable|uuid|exists:school_universities,id';
            $rules['title'] = 'required|string|max:255';
            $rules['report'] = 'required|string|min:10';
            $rules['assessment_report_file'] = 'required|mimes:pdf,doc,docx|max:2048';
            $rules['final_report_file'] = 'required|mimes:pdf,doc,docx|max:2048';
            $rules['photo'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
            $rules['video'] = 'nullable|string|max:255';
 
            $rules['mentor_verified_by_id'] = 'nullable|uuid|exists:users,id';
            $rules['mentor_verification_status'] = 'nullable|in:pending,approved,rejected';
            $rules['mentor_rejection_note'] = 'nullable|string|max:255';
 
            $rules['hr_verified_by_id'] = 'nullable|uuid|exists:users,id';
            $rules['hr_verification_status'] = 'nullable|in:pending,approved,rejected';
            $rules['hr_rejection_note'] = 'nullable|string|max:255';
        }
    
        return $rules;
    }

    public function handleUploads(): array
    {
        return [
            'assessment_report_file' => FileHelper::uploadFile($this->file('assessment_report_file'), 'final_reports/assessment_report_file'),
            'final_report_file' => FileHelper::uploadFile($this->file('final_report_file'), 'final_reports/final_report_file'),
            'photo' => FileHelper::uploadFile($this->file('photo'), 'final_reports/photo'), 
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse($validator->errors(), 'Validation error', Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
