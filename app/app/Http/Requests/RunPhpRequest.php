<?php

namespace App\Http\Requests;

use App\Sandbox\PhpSandbox;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RunPhpRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:'.PhpSandbox::MAX_CODE_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Write some PHP before running it.',
            'code.max' => 'Keep your code under :max characters.',
        ];
    }
}
