<?php

class GoogleAdsEnv
{
    private string $path;
    private array $values = [];

    public function __construct(?string $path = null)
    {
        $this->path = $path ?: $this->resolveDefaultPath();
        $this->load();
    }

    public function path(): string
    {
        return $this->path;
    }

    public function get(string $key, string $default = ''): string
    {
        return array_key_exists($key, $this->values) ? (string)$this->values[$key] : $default;
    }

    public function set(string $key, string $value): void
    {
        $this->values[$key] = $value;
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
        $this->persist();
    }

    public function required(array $keys): array
    {
        $missing = [];
        foreach ($keys as $key) {
            if (trim($this->get($key)) === '') {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    public function load(): void
    {
        $this->values = [];
        if (!is_file($this->path)) {
            return;
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (($this->startsWith($value, '"') && $this->endsWith($value, '"')) || ($this->startsWith($value, "'") && $this->endsWith($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if ($key !== '') {
                $this->values[$key] = $value;
                $_ENV[$key] = $value;
                putenv($key . '=' . $value);
            }
        }
    }

    private function persist(): void
    {
        $existingLines = is_file($this->path) ? (file($this->path, FILE_IGNORE_NEW_LINES) ?: []) : [];
        $seen = [];
        $output = [];

        foreach ($existingLines as $line) {
            if (trim($line) === '' || strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                $output[] = $line;
                continue;
            }
            [$key] = explode('=', $line, 2);
            $key = trim($key);
            if ($key !== '' && array_key_exists($key, $this->values)) {
                $output[] = $key . '=' . $this->escapeValue((string)$this->values[$key]);
                $seen[$key] = true;
            } else {
                $output[] = $line;
            }
        }

        foreach ($this->values as $key => $value) {
            if (!isset($seen[$key])) {
                $output[] = $key . '=' . $this->escapeValue((string)$value);
            }
        }

        file_put_contents($this->path, implode(PHP_EOL, $output) . PHP_EOL, LOCK_EX);
    }

    private function escapeValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|=|"|\'/', $value)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }
        return $value;
    }

    private function resolveDefaultPath(): string
    {
        $candidates = [
            dirname(__DIR__, 4) . '/.env',
            dirname(__DIR__, 3) . '/.env',
            dirname(__DIR__, 2) . '/.env',
            dirname(__DIR__) . '/.env',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            $dir = dirname($candidate);
            if (is_dir($dir) && is_writable($dir)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    private function startsWith(string $value, string $prefix): bool
    {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }

    private function endsWith(string $value, string $suffix): bool
    {
        if ($suffix === '') return true;
        return substr($value, -strlen($suffix)) === $suffix;
    }
}
