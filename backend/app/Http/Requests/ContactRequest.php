<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public const SUBJECTS = [
        'Product Quotation',
        'Custom Packaging',
        'Partnership',
        'Export Enquiry',
        'Support',
        'Other',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim(strip_tags($this->name)) : $this->name,
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'phone' => is_string($this->phone) ? trim($this->phone) : $this->phone,
            'message' => is_string($this->message) ? trim(strip_tags($this->message)) : $this->message,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email:rfc|max:150',
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'subject' => 'required|string|in:'.implode(',', self::SUBJECTS),
            'message' => 'required|string|min:10|max:5000',
            'website' => 'nullable|string|max:255', // honeypot field, must stay empty
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number.',
            'subject.in' => 'Please select a subject from the list.',
        ];
    }
}
