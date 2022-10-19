<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Api;

interface ApiCallsInterface
{
    public function sendFile(string $fileContent, string $fileName, string $source, bool $attachInstruction = true): void;
    public function getWorkProgress(array $files = [], string $targetLocale = '', ?int $skip = null, ?int $count = null): array;
    public function getFile(array $file): string;
    public function reportSuccess(array $files = [], string $target = ''): array;
    public function resetInstructions(): void;
    public function setToken(string $token): void;
    public function getToken(): string;
    public function setDeadline($time = null): void;
    public function setLocales(array $locales): void;
    public function setMetaData(array $metaData): void;
    public function getLastError(): string;
}
