<?php

namespace App\Http\Requests;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Support\JapanPrefectures;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $projectId = AdminContext::requireCurrentProjectId();

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('project_id', $projectId),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.option_ids' => ['nullable', 'array'],
            'items.*.option_ids.*' => [
                'integer',
                Rule::exists('product_options', 'id')->where('project_id', $projectId),
            ],
            'payment_brand' => ['nullable', Rule::in(['card', 'paypay', 'apple_pay', 'google_pay'])],
            'payment_method' => ['required', Rule::in(array_keys(PaymentMethod::getValues()))],
            'points_used' => ['integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'order_type' => ['nullable', Rule::in(array_keys(OrderType::getValues()))],
            'shipping_name' => ['nullable', 'string', 'max:100'],
            'shipping_phone' => ['nullable', 'string', 'max:30'],
            'shipping_postal_code' => ['nullable', 'string', 'max:16'],
            'shipping_prefecture' => ['nullable', 'string', Rule::in(JapanPrefectures::names())],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'delivery_time_slot' => ['nullable', 'string', 'max:32'],
        ];
    }
}
