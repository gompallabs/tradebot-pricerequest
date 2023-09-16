<?php

declare(strict_types=1);

namespace App\Domain;

final class File
{
    private string $name;
    private string $extension;
    private string $path;

    public function __construct(string $name, string $extension, string $path)
    {
        $this->name = $name;
        $this->extension = $extension;
        $this->path = $path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'extension' => $this->getExtension(),
            'path' => $this->getPath(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            extension: $data['extension'],
            path: $data['path']
        );
    }
}
