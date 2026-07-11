<?php

declare(strict_types=1);

namespace CoquiBot\Toolkits\Webhooks;

use CoquiBot\Coqui\Api\CoreServices;
use CoquiBot\Coqui\Api\Router;
use CoquiBot\Coqui\Contract\ApiFeatureInterface;

/**
 * Registers the incoming-webhook receiver and the webhook management CRUD API.
 */
final class WebhooksFeature implements ApiFeatureInterface
{
    public function register(Router $router, CoreServices $services): void
    {
        $store = new WebhookStore($services->pdo());
        $dispatcher = new WebhookDispatchService($store, $services->sessionStorage());

        (new WebhookHandler($store, $services->sessionStorage(), new WebhookVerifierRegistry(), $dispatcher))
            ->register($router);
        (new WebhookManagementHandler($store, $services->profileDiscovery(), $services->sessionStorage(), $dispatcher))
            ->register($router);
    }
}
