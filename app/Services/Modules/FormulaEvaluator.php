<?php

namespace App\Services\Modules;

use App\Models\ModuleField;

class FormulaEvaluator
{
    public function compute(ModuleField $field, array $form): mixed
    {
        $config = $field->computed_config ?? [];
        $expression = trim((string) ($config['expression'] ?? ''));

        if ($expression === '') {
            return data_get($form, $field->name);
        }

        return ($config['mode'] ?? 'template') === 'math'
            ? $this->computeMath($expression, $form)
            : $this->computeTemplate($expression, $form);
    }

    protected function computeTemplate(string $expression, array $form): string
    {
        return preg_replace_callback('/\{([a-zA-Z][a-zA-Z0-9_]*)\}/', function (array $matches) use ($form) {
            return (string) data_get($form, $matches[1], '');
        }, $expression) ?? '';
    }

    protected function computeMath(string $expression, array $form): float|int|null
    {
        $tokens = $this->tokens($expression, $form);

        if ($tokens === []) {
            return null;
        }

        return $this->evaluateReversePolish($this->toReversePolish($tokens));
    }

    protected function tokens(string $expression, array $form): array
    {
        preg_match_all('/[a-zA-Z][a-zA-Z0-9_]*|\d+(?:\.\d+)?|[+\-*\/()]/', $expression, $matches);

        return collect($matches[0] ?? [])
            ->map(function (string $token) use ($form) {
                if (preg_match('/^[a-zA-Z]/', $token)) {
                    $value = data_get($form, $token, 0);

                    return is_numeric($value) ? (float) $value : 0.0;
                }

                return is_numeric($token) ? (float) $token : $token;
            })
            ->all();
    }

    protected function toReversePolish(array $tokens): array
    {
        $output = [];
        $operators = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        foreach ($tokens as $token) {
            if (is_float($token) || is_int($token)) {
                $output[] = $token;
            } elseif (isset($precedence[$token])) {
                while ($operators && end($operators) !== '(' && $precedence[end($operators)] >= $precedence[$token]) {
                    $output[] = array_pop($operators);
                }

                $operators[] = $token;
            } elseif ($token === '(') {
                $operators[] = $token;
            } elseif ($token === ')') {
                while ($operators && end($operators) !== '(') {
                    $output[] = array_pop($operators);
                }

                array_pop($operators);
            }
        }

        while ($operators) {
            $output[] = array_pop($operators);
        }

        return $output;
    }

    protected function evaluateReversePolish(array $tokens): float|int|null
    {
        $stack = [];

        foreach ($tokens as $token) {
            if (is_float($token) || is_int($token)) {
                $stack[] = $token;

                continue;
            }

            $right = array_pop($stack);
            $left = array_pop($stack);

            if ($left === null || $right === null) {
                return null;
            }

            $stack[] = match ($token) {
                '+' => $left + $right,
                '-' => $left - $right,
                '*' => $left * $right,
                '/' => (float) $right === 0.0 ? 0 : $left / $right,
                default => 0,
            };
        }

        $result = array_pop($stack);

        return is_float($result) && floor($result) === $result ? (int) $result : $result;
    }
}
