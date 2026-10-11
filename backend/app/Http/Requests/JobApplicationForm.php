<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobApplicationForm extends FormRequest
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
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'phone' => $clean($this->phone),
            'cover_letter' => $clean($this->cover_letter),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email:rfc|max:150',
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'cover_letter' => 'nullable|string|max:5000',
            'cv' => 'required|file|max:'.(int) config('cms.uploads.cv_max_kb', 5120).'|mimes:pdf,doc,docx',
            'consent' => 'accepted',
            'website' => 'nullable|string|max:255', // honeypot
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number.',
            'cv.mimes' => 'Your CV must be a PDF or Word (.doc / .docx) document.',
            'cv.max' => 'Your CV may not be larger than 5 MB.',
            'consent.accepted' => 'Please agree to the processing of your application data.',
        ];
    }
}
