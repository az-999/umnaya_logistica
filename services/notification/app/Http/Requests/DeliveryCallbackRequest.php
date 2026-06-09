<?php

namespace App\Http\Requests;

use App\Enums\NotificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                NotificationStatus::Delivered->value,
                NotificationStatus::Rejected->value,
            ])],
        ];
    }
}
