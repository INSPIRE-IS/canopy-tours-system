<?php

namespace App\Http\Requests;

use App\Models\File;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return Gate::allows('file_create');
        /** @disregard P1013 */
        return true; // auth()->user()->can('file_create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpeg,png,jpg,pdf',
            ],
            'file_name' => [
                'string',
                'required',
            ],
            'file_type' => [
                'string',
            ],
            'type_file_id' => [
                'required',
            ],
            'table_name' => [
                'string',
            ],

            'table_row_id' => [
                'integer',
            ],
            'site_id' => [
                'required',
                'integer',
            ],
        ];
    }
}
