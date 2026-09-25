<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RunRequest extends FormRequest
{
    public const MAX_FILES = 5;

    public const MAX_TOTAL_BYTES = 100_000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // No `files.*` rule: file names contain dots, which validation would read as nested keys.
        return [
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'entry' => ['required', 'string'],
        ];
    }

    /**
     * File names become paths, so they must be plain names. They must also start with a
     * letter or digit: `entry` is passed to the php CLI as a positional argument, and a
     * name starting with `-` would be parsed as a CLI option.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $files = $this->input('files');

                if (! is_array($files)) {
                    return;
                }

                foreach ($files as $name => $contents) {
                    if (preg_match('/^[a-z0-9][a-z0-9_-]*\.(php|json)$/', (string) $name) !== 1) {
                        $validator->errors()->add('files', "Invalid file name [$name].");
                    }

                    if (! is_string($contents) || $contents === '') {
                        $validator->errors()->add('files', "File [$name] must have contents.");
                    }
                }

                if (array_sum(array_map(fn ($contents) => is_string($contents) ? strlen($contents) : 0, $files)) > self::MAX_TOTAL_BYTES) {
                    $validator->errors()->add('files', 'Files may total at most '.self::MAX_TOTAL_BYTES.' bytes.');
                }

                if (! array_key_exists((string) $this->input('entry'), $files)) {
                    $validator->errors()->add('entry', 'The entry must be one of the files.');
                }
            },
        ];
    }
}
