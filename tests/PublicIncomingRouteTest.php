<?php

declare(strict_types=1);

use CoquiBot\Coqui\Api\CoreServices;
use CoquiBot\Coqui\Api\Middleware\AuthMiddleware;
use CoquiBot\Coqui\Api\Router;
use CoquiBot\Coqui\Config\OpenClawConfig;
use CoquiBot\Coqui\Config\ProfileDiscovery;
use CoquiBot\Coqui\Storage\SessionStorage;
use CoquiBot\Toolkits\Webhooks\WebhooksFeature;
use React\Http\Message\ServerRequest;

/**
 * Proves the auth split introduced by brief #7 Part 2: the incoming webhook
 * receiver is a PUBLIC route (authenticated by signature, not the API key),
 * while every /api/v1/webhooks management route stays behind AuthMiddleware.
 *
 * The middleware stack mirrors how ApiCommand wires it in production and how
 * coqui's own tests/Unit/Api/PublicRouteMiddlewareStackTest.php builds it:
 * AuthMiddleware receives the configured key plus an isPublic closure bound to
 * Router::isPublicPath, so addPublicRoute registrations are the source of truth.
 */
beforeEach(function () {
    $this->dbPath = sys_get_temp_dir() . '/coqui-webhooks-public-' . bin2hex(random_bytes(8)) . '.db';
    $this->workspacePath = sys_get_temp_dir() . '/coqui-webhooks-public-ws-' . bin2hex(random_bytes(8));
    mkdir($this->workspacePath, 0755, true);

    $this->storage = new SessionStorage($this->dbPath);
    $this->services = new CoreServices(
        $this->storage,
        new ProfileDiscovery($this->workspacePath),
        OpenClawConfig::fromArray([]),
    );

    $this->router = new Router();
    (new WebhooksFeature())->register($this->router, $this->services);
    $this->router->addMiddleware(
        new AuthMiddleware('secret', fn (string $path): bool => $this->router->isPublicPath($path)),
    );
});

afterEach(function () {
    releaseTestObjectProperties($this);
    cleanupSqliteTestDb($this->dbPath);
    cleanupTestTree($this->workspacePath);
});

test('the incoming receiver is reachable without an Authorization header when api.key is configured', function () {
    $response = $this->router->dispatch(
        new ServerRequest('POST', '/api/v1/webhooks/incoming/gh'),
    );

    // The point is that auth does not wall it off: the request reaches the
    // handler (which returns 404 for an unknown webhook name) rather than the
    // 401 AuthMiddleware would emit for a keyed, non-public route.
    expect($response->getStatusCode())->not->toBe(401);
});

test('a management route is 401 without a key, proving incoming public vs management authenticated', function () {
    $response = $this->router->dispatch(
        new ServerRequest('GET', '/api/v1/webhooks'),
    );

    expect($response->getStatusCode())->toBe(401);
});
