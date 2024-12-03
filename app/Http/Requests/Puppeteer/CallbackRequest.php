<?php

namespace App\Http\Requests\Puppeteer;

use App\Enums\PuppeteerStatus;
use App\Models\File;
use BackedEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class CallbackRequest.
 */
class CallbackRequest extends FormRequest
{
    /**
     * Validation rules for this form request.
     *
     * @return array
     */
    public function rules(): array
    {
        $statuses = array_map(
            fn(BackedEnum $s) => $s->value,
            PuppeteerStatus::cases()
        );

        return [
            'username' => [
                'required',
                'string',
            ],
            'language' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'status' => [
                'required',
                'string',
                'in:' . implode(',', $statuses)
            ],
            'file_id' => [
                'required',
                'integer',
                Rule::exists(File::class, 'id'),
            ],
            'payload' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'timezone' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'tried_at' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'try_after' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }
}
