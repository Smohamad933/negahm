<?php

declare(strict_types=1);

namespace App\Core;

/**
 * فراخوانی هندلر مسیر (آرایه [کلاس، متد] یا کال‌بک) با تزریق وابستگی
 */
final class Handler
{
    /**
     * @param array{0:string,1:string}|callable $handler
     * @param array<string,mixed>               $params
     */
    public static function call(mixed $handler, array $params = []): mixed
    {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            [$class, $method] = $handler;
            $controller       = new $class();
            return self::invokeCallable([$controller, $method], $params);
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $controller       = new $class();
            return self::invokeCallable([$controller, $method], $params);
        }

        if (is_string($handler) && is_callable($handler)) {
            return self::invokeCallable($handler, $params);
        }

        if (is_callable($handler)) {
            return self::invokeCallable($handler, $params);
        }

        throw new HttpException(500, 'هندلر مسیر نامعتبر است');
    }

    /** @param array<string,mixed> $params */
    private static function invokeCallable(callable $callable, array $params): mixed
    {
        $reflection = new \ReflectionFunction(\Closure::fromCallable($callable));
        $args       = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $className = $type->getName();
                if ($className === Request::class) {
                    $args[] = Request::instance();
                    continue;
                }
                if (class_exists($className)) {
                    $args[] = new $className();
                    continue;
                }
            }

            if (array_key_exists($name, $params)) {
                $value = $params[$name];
                if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
                    $args[] = self::cast($value, $type->getName(), $parameter->allowsNull());
                } else {
                    $args[] = $value;
                }
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new HttpException(500, 'پارامتر «' . $name . '» برای هندلر ارسال نشده است');
        }

        return $callable(...$args);
    }

    private static function cast(mixed $value, string $type, bool $nullable): mixed
    {
        if ($value === null) {
            return $nullable ? null : match ($type) {
                'int' => 0,
                'float' => 0.0,
                'string' => '',
                'bool' => false,
                'array' => [],
                default => null,
            };
        }
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'array' => is_array($value) ? $value : [$value],
            default => (string) $value,
        };
    }
}
