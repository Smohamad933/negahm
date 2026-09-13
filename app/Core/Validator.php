<?php

declare(strict_types=1);

namespace App\Core;

/**
 * اعتبارسنج ساده با پیام‌های فارسی
 *
 * قواعد پشتیبانی‌شده:
 * required, nullable, email, url, numeric, integer, min:n, max:n, minlen:n, maxlen:n,
 * in:a|b|c, regex:/.../, phone, slug, unique:table,column[,ignoreId], confirmed, boolean, date, json
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /** @var array<string,mixed> */
    private array $data;

    /** @var array<string,string> */
    private array $labels;

    /**
     * @param array<string,mixed> $data
     * @param array<string,string> $rules
     * @param array<string,string> $labels
     */
    public function __construct(array $data, private array $rules, array $labels = [])
    {
        $this->data   = $data;
        $this->labels = $labels;
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,string> $rules
     * @param array<string,string> $labels
     */
    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);

            $isRequired = in_array('required', $rules, true);
            $isEmpty    = $value === null || $value === '' || (is_array($value) && $value === []);

            if ($isEmpty && !$isRequired) {
                continue;
            }
            if ($isEmpty && $isRequired) {
                $this->addError($field, 'required');
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'required' || $rule === 'nullable') {
                    continue;
                }
                $this->applyRule($field, $value, $rule);
            }
        }

        return $this->errors === [];
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $param = null;
        if (str_contains($rule, ':')) {
            [$rule, $param] = explode(':', $rule, 2);
        }
        $stringValue = is_scalar($value) ? (string) $value : '';

        switch ($rule) {
            case 'email':
                if (!filter_var($stringValue, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email');
                }
                break;

            case 'url':
                if (!filter_var($stringValue, FILTER_VALIDATE_URL) && !str_starts_with($stringValue, '/')) {
                    $this->addError($field, 'url');
                }
                break;

            case 'numeric':
                if (!is_numeric($stringValue)) {
                    $this->addError($field, 'numeric');
                }
                break;

            case 'integer':
                if (filter_var($stringValue, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, 'integer');
                }
                break;

            case 'min':
                if (is_numeric($stringValue) && (float) $stringValue < (float) $param) {
                    $this->addError($field, 'min', ['param' => $param]);
                }
                break;

            case 'max':
                if (is_numeric($stringValue) && (float) $stringValue > (float) $param) {
                    $this->addError($field, 'max', ['param' => $param]);
                }
                break;

            case 'minlen':
                if (mb_strlen($stringValue) < (int) $param) {
                    $this->addError($field, 'minlen', ['param' => $param]);
                }
                break;

            case 'maxlen':
                if (mb_strlen($stringValue) > (int) $param) {
                    $this->addError($field, 'maxlen', ['param' => $param]);
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $param);
                if (!in_array($stringValue, $allowed, true)) {
                    $this->addError($field, 'in');
                }
                break;

            case 'regex':
                if (!preg_match((string) $param, $stringValue)) {
                    $this->addError($field, 'regex');
                }
                break;

            case 'phone':
                $normalized = Str::toLatinDigits(preg_replace('/\s+/', '', $stringValue) ?? '');
                if (!preg_match('/^(\+?98|0)?9\d{9}$/', $normalized) && !preg_match('/^0\d{9,10}$/', $normalized)) {
                    $this->addError($field, 'phone');
                }
                break;

            case 'slug':
                if (!preg_match('/^[a-z0-9\-\p{L}\p{N}]+$/u', $stringValue)) {
                    $this->addError($field, 'slug');
                }
                break;

            case 'unique':
                $parts = explode(',', (string) $param);
                $table = $parts[0] ?? '';
                $column = $parts[1] ?? $field;
                $ignore = isset($parts[2]) && $parts[2] !== '' ? (int) $parts[2] : null;
                if ($this->exists($table, $column, $stringValue, $ignore)) {
                    $this->addError($field, 'unique');
                }
                break;

            case 'confirmed':
                if ((string) ($this->data[$field . '_confirmation'] ?? '') !== $stringValue) {
                    $this->addError($field, 'confirmed');
                }
                break;

            case 'boolean':
                if (!in_array($stringValue, ['0', '1', 'true', 'false', 'on', 'off', 'yes', 'no'], true)) {
                    $this->addError($field, 'boolean');
                }
                break;

            case 'date':
                if (strtotime($stringValue) === false) {
                    $this->addError($field, 'date');
                }
                break;

            case 'json':
                json_decode($stringValue);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->addError($field, 'json');
                }
                break;
        }
    }

    private function exists(string $table, string $column, string $value, ?int $ignore): bool
    {
        if ($table === '' || !preg_match('/^[a-z0-9_]+$/', $table) || !preg_match('/^[a-z0-9_]+$/', $column)) {
            return false;
        }
        $sql    = sprintf('SELECT COUNT(*) FROM %s WHERE %s = ?', DB::quoteIdent($table), DB::quoteIdent($column));
        $params = [$value];
        if ($ignore !== null) {
            $sql     .= ' AND id <> ?';
            $params[] = $ignore;
        }
        return (int) DB::scalar($sql, $params) > 0;
    }

    /** @param array<string,mixed> $context */
    private function addError(string $field, string $rule, array $context = []): void
    {
        if (isset($this->errors[$field])) {
            return;
        }
        $label = $this->labels[$field] ?? $field;
        $param = Str::toPersianDigits((string) ($context['param'] ?? ''));

        $message = match ($rule) {
            'required' => "«{$label}» الزامی است.",
            'email'    => "«{$label}» باید یک ایمیل معتبر باشد.",
            'url'      => "«{$label}» باید یک آدرس معتبر باشد.",
            'numeric'  => "«{$label}» باید عدد باشد.",
            'integer'  => "«{$label}» باید عدد صحیح باشد.",
            'min'      => "«{$label}» نباید کمتر از {$param} باشد.",
            'max'      => "«{$label}» نباید بیشتر از {$param} باشد.",
            'minlen'   => "«{$label}» باید حداقل {$param} نویسه باشد.",
            'maxlen'   => "«{$label}» باید حداکثر {$param} نویسه باشد.",
            'in'       => "مقدار «{$label}» معتبر نیست.",
            'regex'    => "قالب «{$label}» صحیح نیست.",
            'phone'    => "«{$label}» باید یک شماره موبایل یا تلفن معتبر باشد.",
            'slug'     => "«{$label}» فقط می‌تواند شامل حروف، عدد و خط تیره باشد.",
            'unique'   => "این «{$label}» قبلاً ثبت شده است.",
            'confirmed'=> "تکرار «{$label}» مطابقت ندارد.",
            'boolean'  => "مقدار «{$label}» معتبر نیست.",
            'date'     => "«{$label}» باید یک تاریخ معتبر باشد.",
            'json'     => "«{$label}» باید JSON معتبر باشد.",
            default    => "مقدار «{$label}» نامعتبر است.",
        };

        $this->errors[$field] = $message;
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return (string) (reset($this->errors) ?: '');
    }

    /** @return array<string,mixed> داده‌های معتبر (فیلدهای تعریف‌شده در قواعد) */
    public function validated(): array
    {
        $out = [];
        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $out[$field] = $this->data[$field];
            }
        }
        return $out;
    }
}
