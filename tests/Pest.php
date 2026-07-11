<?php

declare(strict_types=1);

use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Contract\ToolkitInterface;

function toolFromToolkit(ToolkitInterface $toolkit, string $name): ToolInterface
{
    foreach ($toolkit->tools() as $tool) {
        if ($tool->name() === $name) {
            return $tool;
        }
    }

    throw new InvalidArgumentException(sprintf('Tool "%s" not found.', $name));
}

/**
 * @return array<string, mixed>
 */
function assertStructuredToolResult(\CarmeloSantana\PHPAgents\Tool\ToolResult $result): array
{
    expect($result->status)->toBe(\CarmeloSantana\PHPAgents\Enum\ToolResultStatus::Success);
    expect($result->mimeType)->toBe('application/json');
    expect($result->displayHint)->toBe('structured-json');

    $data = json_decode($result->content, true);
    expect($data)->toBeArray();

    return $data;
}

function releaseTestObjectProperties(object $context): void
{
    foreach (array_keys(get_object_vars($context)) as $property) {
        if (is_object($context->{$property})) {
            $context->{$property} = null;
        }
    }

    gc_collect_cycles();
}

function cleanupSqliteTestDb(string $dbPath): void
{
    foreach (['', '-wal', '-shm'] as $suffix) {
        $path = $dbPath . $suffix;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            clearstatcache(true, $path);
            if (!file_exists($path)) {
                break;
            }
            if (@unlink($path)) {
                break;
            }
            usleep(50_000);
        }
    }
}

function cleanupTestTree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
        $path = $file->getPathname();
        $file->isDir() ? @rmdir($path) : @unlink($path);
    }
    @rmdir($dir);
}
