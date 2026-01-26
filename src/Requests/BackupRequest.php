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

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(BackupTypeEnum::class)],
        ];
    }

    public function backupType(): BackupTypeEnum
    {
        return BackupTypeEnum::from($this->validated('type'));
    }

    public function messages(): array
    {
        return [
            'type.required' => 'The backup type parameter is required.',
            'type.enum' => 'The backup type must be either "backup_database" or "backup_storage".',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
