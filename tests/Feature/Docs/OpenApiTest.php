<?php

use Illuminate\Routing\Route as RoutingRoute;
use Symfony\Component\Yaml\Yaml;

/**
 * @return array<string, mixed>
 */
function loadOpenApiSpec(): array
{
    /** @var array<string, mixed> $spec */
    $spec = Yaml::parseFile(base_path('docs/openapi.yaml'));

    return $spec;
}

it('is a valid OpenAPI 3 document', function () {
    $spec = loadOpenApiSpec();

    expect($spec['openapi'])->toStartWith('3.')
        ->and($spec['info']['title'])->toBe('Loyalty & Rewards API')
        ->and($spec['info']['version'])->toBeString()
        ->and($spec['paths'])->toBeArray()->not->toBeEmpty();
});

it('documents every route and no phantom routes', function () {
    /** @var array<string, array<string, mixed>> $paths */
    $paths = loadOpenApiSpec()['paths'];

    $documented = [];
    foreach ($paths as $path => $operations) {
        foreach (array_keys($operations) as $key) {
            $method = strtoupper((string) $key);
            if (in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $documented[] = "{$method} {$path}";
            }
        }
    }
    sort($documented);

    $actual = [];
    foreach (app('router')->getRoutes() as $route) {
        if (! $route instanceof RoutingRoute) {
            continue;
        }

        $uri = $route->uri();
        if ($uri !== '/' && ! str_starts_with($uri, 'api/v1')) {
            continue;
        }

        $path = '/'.ltrim($uri, '/');
        foreach ($route->methods() as $method) {
            $method = strtoupper($method);
            if (in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $actual[] = "{$method} {$path}";
            }
        }
    }
    sort($actual);

    expect($documented)->toBe($actual);
});

it('has no dangling $ref pointers', function () {
    $spec = loadOpenApiSpec();

    $refs = [];
    $collect = function (mixed $node) use (&$collect, &$refs): void {
        if (! is_array($node)) {
            return;
        }

        foreach ($node as $key => $value) {
            if ($key === '$ref' && is_string($value)) {
                $refs[] = $value;
            }

            $collect($value);
        }
    };
    $collect($spec);

    $resolve = function (array $document, string $pointer): bool {
        $node = $document;

        foreach (explode('/', ltrim($pointer, '#/')) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);

            if (! is_array($node) || ! array_key_exists($segment, $node)) {
                return false;
            }

            $node = $node[$segment];
        }

        return true;
    };

    $missing = array_values(array_filter(
        $refs,
        fn (string $ref): bool => ! $resolve($spec, $ref),
    ));

    expect($refs)->not->toBeEmpty()
        ->and($missing)->toBeEmpty();
});

it('ships a valid Postman collection', function () {
    $raw = (string) file_get_contents(base_path('docs/postman_collection.json'));
    $collection = json_decode($raw, true);

    expect(json_last_error())->toBe(JSON_ERROR_NONE)
        ->and($collection['info']['schema'])->toContain('v2.1.0')
        ->and($collection['item'])->toBeArray()->not->toBeEmpty();
});
