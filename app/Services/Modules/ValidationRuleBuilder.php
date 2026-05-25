<?php

namespace App\Services\Modules;

use App\Models\Module;
use App\Models\ModuleField;
use Illuminate\Validation\Rule;

class ValidationRuleBuilder
{
    public function rulesFor(Module $module, ModuleField $field, ?int $editingRecordId = null): array
    {
        $optionalOnEdit = $editingRecordId && in_array($field->type, ['password', 'file', 'image'], true);
        $rules = [($field->required && ! $optionalOnEdit) ? 'required' : 'nullable'];

        if ($field->unique) {
            $unique = Rule::unique($module->table_name, $field->name);

            if ($editingRecordId) {
                $unique->ignore($editingRecordId);
            }

            $rules[] = $unique;
        }

        $rules = [...$rules, ...$this->typeRules($field, $editingRecordId)];
        $rules = [...$rules, ...$this->configuredRules($field)];

        if ($field->validation_rules) {
            $rules = [...$rules, ...explode('|', $field->validation_rules)];
        }

        return array_values(array_filter($rules, fn ($rule) => $rule !== null && $rule !== ''));
    }

    protected function typeRules(ModuleField $field, ?int $editingRecordId): array
    {
        return match ($field->type) {
            'email' => ['email', 'max:255'],
            'number', 'currency' => ['numeric'],
            'password' => ['string', 'min:8'],
            'select', 'radio' => [Rule::in($field->optionList())],
            'checkbox', 'toggle' => ['boolean'],
            'date' => ['date'],
            'datetime' => ['date'],
            'color' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
            'json' => ['json'],
            'date_range' => ['array'],
            'relationship' => $this->relationshipRules($field),
            'file' => [
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,csv,txt,zip',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain,application/zip',
            ],
            'image' => [
                'image',
                'mimes:jpg,jpeg,png,gif,svg,webp',
                'mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp',
                'max:5120',
            ],
            default => ['string', 'max:2000'],
        };
    }

    protected function configuredRules(ModuleField $field): array
    {
        $config = $field->validation_config ?? [];
        $rules = [];

        foreach (['min', 'max'] as $rule) {
            if (filled($config[$rule] ?? null)) {
                $rules[] = $rule.':'.$config[$rule];
            }
        }

        if (filled($config['regex'] ?? null)) {
            $rules[] = 'regex:'.$config['regex'];
        }

        foreach (['email', 'numeric'] as $rule) {
            if ((bool) ($config[$rule] ?? false)) {
                $rules[] = $rule;
            }
        }

        if (filled($config['file_mimes'] ?? null)) {
            $rules[] = 'mimes:'.str_replace(' ', '', (string) $config['file_mimes']);
        }

        if (filled($config['max_file_size'] ?? null)) {
            $rules[] = 'max:'.$config['max_file_size'];
        }

        if (filled($config['custom'] ?? null)) {
            $rules = [...$rules, ...explode('|', (string) $config['custom'])];
        }

        return $rules;
    }

    protected function relationshipRules(ModuleField $field): array
    {
        if (! $field->relationshipModule) {
            return ['integer'];
        }

        return ['integer', Rule::exists($field->relationshipModule->table_name, 'id')];
    }
}
