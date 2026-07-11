<?php

declare(strict_types=1);

use CoquiBot\Coqui\Storage\SessionStorage;
use CoquiBot\Toolkits\Webhooks\WebhookToolkit;

beforeEach(function () {
    $this->dbPath = sys_get_temp_dir() . '/coqui-webhook-toolkit-context-' . bin2hex(random_bytes(8)) . '.db';
    $this->storage = new SessionStorage($this->dbPath);
});

afterEach(function () {
    releaseTestObjectProperties($this);
    cleanupSqliteTestDb($this->dbPath);
});

test('fromCoquiContext builds a working toolkit from storage and activeProfile', function () {
    $toolkit = WebhookToolkit::fromCoquiContext([
        'storage' => $this->storage,
        'activeProfile' => 'default',
    ]);

    expect($toolkit)->toBeInstanceOf(WebhookToolkit::class);

    $tool = toolFromToolkit($toolkit, 'webhook_create');

    $result = $tool->execute([
        'name' => 'context-hook',
        'prompt_template' => 'Handle {{event_type}}',
        'source' => 'github',
    ]);

    $data = assertStructuredToolResult($result);
    expect($data['name'])->toBe('context-hook');
    expect($data['source'])->toBe('github');
    expect($data['profile'])->toBe('default');
    expect($data['secret'])->not->toBe('');
});

test('fromCoquiContext throws when storage is missing', function () {
    expect(fn () => WebhookToolkit::fromCoquiContext([]))->toThrow(RuntimeException::class);
});

test('fromCoquiContext throws when storage is not a SessionStorage', function () {
    expect(fn () => WebhookToolkit::fromCoquiContext(['storage' => new stdClass()]))->toThrow(RuntimeException::class);
});
