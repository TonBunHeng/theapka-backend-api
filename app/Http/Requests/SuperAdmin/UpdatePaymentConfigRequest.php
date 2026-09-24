<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('payway') && ! $this->has('aba_payway')) {
            $payway = $this->input('payway');
            if (isset($payway['environment'])) {
                $payway['sandbox'] = $payway['environment'] === 'sandbox';
            }
            $this->merge(['aba_payway' => $payway]);
        }

        if ($this->has('khqr')) {
            $khqr = $this->input('khqr');
            if (isset($khqr['environment'])) {
                $khqr['sandbox'] = $khqr['environment'] === 'sandbox';
            }
            if (isset($khqr['merchant_id']) && ! isset($khqr['bakong_account_id'])) {
                $khqr['bakong_account_id'] = $khqr['merchant_id'];
            }
            $this->merge(['khqr' => $khqr]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'khqr' => ['nullable', 'array'],
            'khqr.enabled' => ['nullable', 'boolean'],
            'khqr.merchant_id' => ['nullable', 'string', 'max:100'],
            'khqr.bakong_account_id' => ['nullable', 'string', 'max:100'],
            'khqr.merchant_name' => ['nullable', 'string', 'max:100'],
            'khqr.merchant_city' => ['nullable', 'string', 'max:100'],
            'khqr.sandbox' => ['nullable', 'boolean'],
            'khqr.environment' => ['nullable', 'string'],
            'khqr.api_key' => ['nullable', 'string'],

            'aba_payway' => ['nullable', 'array'],
            'aba_payway.enabled' => ['nullable', 'boolean'],
            'aba_payway.merchant_id' => ['nullable', 'string', 'max:100'],
            'aba_payway.merchant_name' => ['nullable', 'string', 'max:100'],
            'aba_payway.api_key' => ['nullable', 'string', 'max:255'],
            'aba_payway.api_url' => ['nullable', 'string'],
            'aba_payway.sandbox' => ['nullable', 'boolean'],
            'aba_payway.environment' => ['nullable', 'string'],

            'payway' => ['nullable', 'array'],
        ];
    }
}
