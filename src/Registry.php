<?php

declare(strict_types=1);

namespace App\AIHub;

final class Registry
{
    /** @var array{providers: array<string, array{path: string, installed_at: string}>} */
    protected array $data = ['providers' => []];

    public function __construct(protected string $path)
    {
        if (is_file($this->path)) {
            $json = file_get_contents($this->path);
            $decoded = is_string($json) ? json_decode($json, true) : null;
            if (is_array($decoded)) {
                $this->data = array_merge(['providers' => []], $decoded);
            }
        }
    }

    public function addProvider(string $name, string $path): void
    {
        $this->data['providers'][$name] = [
            'path' => $path,
            'installed_at' => date('c'),
        ];
        $this->save();
    }

    public function removeProvider(string $name): void
    {
        unset($this->data['providers'][$name]);
        $this->save();
    }

    public function hasProvider(string $name): bool
    {
        return isset($this->data['providers'][$name]);
    }

    public function getProviderPath(string $name): string
    {
        return $this->data['providers'][$name]['path'] ?? '';
    }

    protected function save(): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->path, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
