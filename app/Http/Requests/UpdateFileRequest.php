<?php

namespace App\Http\Requests;

use App\Models\File;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @disregard P1013 */
        return auth()->user()->can('file_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'site_id' => [
                'required',
                'integer',
            ],
            'table_name' => [
                'string',
                'required',
            ],
            'file_name' => [
                'string',
                'required',
            ],
            'row' => [
                'string',
                'required',
            ],
            'path' => [
                'string',
                'required',
            ],
        ];
    }
}
