<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequestForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = fn ($v) => is_string($v) ? trim(strip_tags($v)) : $v;

        $this->merge([
            'name' => $clean($this->name),
            'company' => $clean($this->company),
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'phone' => $clean($this->phone),
            'quantity' => $clean($this->quantity),
            'specification' => $clean($this->specification),
            'message' => $clean($this->message),
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|integer|exists:products,id',
            'name' => 'required|string|min:2|max:120',
            'company' => 'nullable|string|max:150',
            'email' => 'required|email:rfc|max:150',
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'quantity' => 'required|string|max:60',
            'specification' => 'nullable|string|max:500',
            'needed_by' => 'nullable|date|after_or_equal:today|before:+3 years',
            'message' => 'nullable|string|max:5000',
            'website' => 'nullable|string|max:255', // honeypot, must stay empty
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number.',
            'needed_by.after_or_equal' => 'The date cannot be in the past.',
        ];
    }
}
