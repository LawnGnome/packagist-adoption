<?php

namespace LawnGnome\PackagistAdoption;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use Predis\Silex\ClientServiceProvider as SilexServiceProvider;
use Silex\Application;
use Symfony\Component\HttpFoundation\{Request, Response};
use Throwable;

require __DIR__.'/../vendor/autoload.php';

$app = new Application;

// Set up services.
$app->register(new SilexServiceProvider(), [
	'predis.parameters' => 'tcp://redis:6379',
]);

$app['guzzle'] = function (Application $app): GuzzleClient {
	return new GuzzleClient;
};

$app['hydrator'] = function (Application $app): PackageHydratorInterface {
	return new PackagistHydrator($app['guzzle'], $app['predis']);
};

$app['package'] = function (Application $app): PackageService {
	return new PackageService($app['hydrator']);
};

// Define routes.
$app->get('/{packager}/{package}', function (Application $app, string $packager, string $package): Response {
	return $app->json($app['package']->getPackage("$packager/$package"));
});

// Error handling.
$app->error(function (Throwable $e, Request $request, $code) use ($app): Response {
	return $app->json($e->getMessage(), ($e instanceof GuzzleClientException) ? $e->getCode() : 500);
});

$app->run();

// vim: set noet ts=4 sw=4:
