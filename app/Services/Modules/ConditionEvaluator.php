<?php

namespace App\Services\Modules;

use App\Models\ModuleField;

class ConditionEvaluator
{
    public function visible(ModuleField $field, array $form): bool
    {
        if (! $field->hasCondition()) {
            return true;
        }

        if ($field->condition_config) {
            return $this->evaluateGroup($field->condition_config, $form);
        }

        return $this->evaluateRule([
            'field' => $field->condition_field,
            'operator' => $field->condition_operator,
            'value' => $field->condition_value,
        ], $form);
    }

    protected function evaluateGroup(array $group, array $form): bool
    {
        $boolean = strtolower((string) ($group['boolean'] ?? 'and')) === 'or' ? 'or' : 'and';
        $results = [];

        foreach ($group['rules'] ?? [] as $rule) {
            if (is_array($rule)) {
                $results[] = $this->evaluateRule($rule, $form);
            }
        }

        foreach ($group['groups'] ?? [] as $nestedGroup) {
            if (is_array($nestedGroup)) {
                $results[] = $this->evaluateGroup($nestedGroup, $form);
            }
        }

        if ($results === []) {
            return true;
        }

        return $boolean === 'or'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    protected function evaluateRule(array $rule, array $form): bool
    {
        $field = (string) ($rule['field'] ?? '');
        $operator = (string) ($rule['operator'] ?? 'equals');
        $value = data_get($form, $field);
        $expected = $rule['value'] ?? null;

        return match ($operator) {
            'equals' => (string) $value === (string) $expected,
            'not_equals' => (string) $value !== (string) $expected,
            'contains' => str_contains((string) $value, (string) $expected),
            'greater_than' => is_numeric($value) && is_numeric($expected) && (float) $value > (float) $expected,
            'less_than' => is_numeric($value) && is_numeric($expected) && (float) $value < (float) $expected,
            'empty' => blank($value),
            'not_empty' => filled($value),
            default => true,
        };
    }
}
