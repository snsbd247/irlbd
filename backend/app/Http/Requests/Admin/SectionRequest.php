<?php

namespace App\Http\Requests\Admin;

use App\Models\PageSection;
use App\Rules\SafeUrl;

class SectionRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'type' => [$this->isUpdate() ? 'sometimes' : 'required', 'string', 'in:'.implode(',', array_keys(config('sections')))],
            'admin_label' => 'nullable|string|max:150',
            'eyebrow' => 'nullable|string|max:150',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:1000',
            'content' => 'nullable|string|max:100000',
            'image' => $this->media(),
            'image_alt' => 'nullable|string|max:255',
            'video_url' => ['nullable', 'string', 'max:500', new SafeUrl(httpsOnly: true)],
            'button_label' => 'nullable|string|max:60',
            'button_url' => $this->link(),
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
            ...$this->settingsRules(),
            'items' => 'nullable|array|max:50',
            'items.*.id' => 'nullable|integer',
            'items.*.title' => 'nullable|string|max:255',
            'items.*.subtitle' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string|max:2000',
            'items.*.value' => 'nullable|string|max:50',
            'items.*.icon' => $this->icon(),
            'items.*.image' => $this->media(),
            'items.*.link_url' => $this->link(),
            'items.*.is_active' => 'boolean',
            'items.*.translations' => 'nullable|array',
            'items.*.translations.*.*' => 'nullable|string|max:5000',
            'translations' => 'nullable|array',
            'translations.*' => 'array',
            'translations.*.*' => 'nullable|string|max:100000',
        ];
    }

    /** Section type whose settings are being validated (from the payload or the existing record). */
    public function sectionType(): ?string
    {
        $section = $this->route('section');

        return $this->input('type') ?: ($section instanceof PageSection ? $section : PageSection::find($section))?->type;
    }

    protected function settingsRules(): array
    {
        $rules = [];
        foreach (config('sections.'.$this->sectionType().'.settings', []) as $key => $rule) {
            $rules['settings.'.$key] = 'nullable|'.$rule;
        }

        return $rules;
    }

    /** Only settings keys declared for the section type are kept. */
    public function cleanSettings(): array
    {
        $allowed = array_keys(config('sections.'.$this->sectionType().'.settings', []));

        return collect($this->validated('settings') ?? [])->only($allowed)->all();
    }
}
