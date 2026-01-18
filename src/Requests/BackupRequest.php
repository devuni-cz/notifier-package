<?php

declare(strict_types=1);

namespace Devuni\Notifier\Requests;

use Devuni\Notifier\Enums\BackupTypeEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'param' => ['required', Rule::enum(BackupTypeEnum::class)],
        ];
    }

    /**
     * Get the backup type enum from the validated request.
     */
    public function backupType(): BackupTypeEnum
    {
        return BackupTypeEnum::from($this->validated('param'));
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'param.required' => 'The backup type parameter is required.',
            'param.Illuminate\Validation\Rules\Enum' => 'Invalid backup type. Allowed values: ' . implode(', ', BackupTypeEnum::values()),
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
