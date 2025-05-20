<?php

namespace App\Http\Requests;

use App\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SignatureRequest extends FormRequest
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
        $id = $this->route('signature');
        // dd($id);
        $isUpdate = $id !== null;
        return [
            'user_id' => [
                ...($isUpdate ? [] : ['required']),
                'max:255',
                Rule::unique('signatures', 'user_id')->ignore($id),
            ],
            'nik' => [
                'sometimes',
                'max:255',
                Rule::unique('signatures', 'nik')->ignore($id),
            ],
            'name_snapshot' =>'sometimes|string',
            'rank_group' =>'sometimes|string',
            'position' =>'sometimes|string',
            'department' =>'sometimes|string',
            'purposes' => 'sometimes|array',
            'purposes.*.name' => 'in:receipt_letter,division_letter,certificate,work_certificate,daily_report,field_letter',
            'purposes.*.status' => 'required|in:active,inactive',
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
