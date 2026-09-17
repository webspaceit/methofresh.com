<?php

namespace App\Core;

class wsit_Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? '';
            foreach (explode('|', $ruleString) as $rule) {
                $this->applyRule($field, $value, $data, $rule);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, $value, array $data, string $rule): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        switch ($name) {
            case 'required':
                if (trim((string)$value) === '' && (int)$value !== 0) {
                    $this->add($field, trans('validate_required'), [
                        'attribute' => trans('field_' . $field),
                    ]);
                }
                break;

            case 'email':
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->add($field, trans('validate_email'), [
                        'attribute' => trans('field_' . $field),
                    ]);
                }
                break;

            case 'min':
                if (mb_strlen((string)$value) < (int)$param) {
                    $this->add($field, trans('validate_min'), [
                        'attribute' => trans('field_' . $field),
                        'min'       => $param,
                    ]);
                }
                break;

            case 'max':
                if (mb_strlen((string)$value) > (int)$param) {
                    $this->add($field, trans('validate_max'), [
                        'attribute' => trans('field_' . $field),
                        'max'       => $param,
                    ]);
                }
                break;

            case 'numeric':
                if ($value !== '' && !is_numeric($value)) {
                    $this->add($field, trans('validate_numeric'), [
                        'attribute' => trans('field_' . $field),
                    ]);
                }
                break;

            case 'unique':
                if ($param !== null) {
                    [$table, $column, $ignoreId] = array_pad(explode(',', $param), 3, null);
                    $this->uniqueCheck($field, $value, $table, $column, $ignoreId);
                }
                break;

            case 'confirmed':
                if ((string)$value !== (string)($data[$field . '_confirmation'] ?? '')) {
                    $this->add($field, trans('validate_confirmed'), [
                        'attribute' => trans('field_' . $field),
                    ]);
                }
                break;

            case 'integer':
                if ($value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->add($field, trans('validate_integer'), [
                        'attribute' => trans('field_' . $field),
                    ]);
                }
                break;
        }
    }

    private function uniqueCheck(string $field, $value, string $table, string $column, string|null $ignoreId): void
    {
        if (trim((string)$value) === '') {
            return;
        }
        $count = $this->queryCount($table, $column, $value, $ignoreId);
        if ($count > 0) {
            $this->add($field, trans('validate_unique'), [
                'attribute' => trans('field_' . $field),
            ]);
        }
    }

    private function queryCount(string $table, string $column, $value, string|null $ignoreId): int
    {
        $sql = "SELECT COUNT(*) AS c FROM {$table} WHERE {$column} = ?";
        $params = [$value];
        if ($ignoreId !== null) {
            $sql .= ' AND id != ?';
            $params[] = (int)$ignoreId;
        }
        $row = app()->get('db')->fetch($sql, $params);
        return $row === null ? 0 : (int)$row['c'];
    }

    private function add(string $field, string $message, array $replace = []): void
    {
        foreach (['attribute' => 'attribute', 'min' => 'min', 'max' => 'max'] as $key => $placeholder) {
            if (isset($replace[$placeholder])) {
                $message = str_replace(':' . $placeholder, (string)$replace[$placeholder], $message);
            }
        }
        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }
}