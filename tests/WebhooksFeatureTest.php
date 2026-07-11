<?php

declare(strict_types=1);

use CoquiBot\Coqui\Api\CoreServices;
use CoquiBot\Coqui\Api\Router;
use CoquiBot\Coqui\Config\OpenClawConfig;
use CoquiBot\Coqui\Config\ProfileDiscovery;
use CoquiBot\Coqui\Storage\SessionStorage;
use CoquiBot\Toolkits\Webhooks\WebhooksFeature;
use React\Http\Message\ServerRequest;

beforeEach(function () {
    $this->dbPath = sys_get_temp_dir() . '/coqui-webhooks-feature-' . bin2hex(random_bytes(8)) . '.db';
    $this->workspacePath = sys_get_temp_dir() . '/coqui-webhooks-feature-ws-' . bin2hex(random_bytes(8));
    mkdir($this->workspacePath, 0755, true);

    $this->storage = new SessionStorage($this->dbPath);
    $this->services = new CoreServices(
        $this->storage,
        new ProfileDiscovery($this->workspacePath),
        OpenClawConfig::fromArray([]),
    );
});

afterEach(function () {
    releaseTestObjectProperties($this);
    cleanupSqliteTestDb($this->dbPath);
    cleanupTestTree($this->workspacePath);
});

test('register wires the incoming and management routes onto the router', function () {
    $router = new Router();
    (new WebhooksFeature())->register($router, $this->services);

    $response = $router->dispatch(new ServerRequest('GET', '/api/v1/webhooks'));
    $body = json_decode((string) $response->getBody(), true);

    expect($response->getStatusCode())->toBe(200);
    expect($body['webhooks'])->toBe([]);
});
