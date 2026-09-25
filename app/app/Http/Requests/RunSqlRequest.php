<?php

namespace App\Http\Requests;

use App\Sandbox\SqlSandbox;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RunSqlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sql' => ['required', 'string', 'max:'.SqlSandbox::MAX_SQL_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sql.required' => 'Write some SQL before running it.',
            'sql.max' => 'Keep your SQL under :max characters.',
        ];
    }
}
