<?php

namespace LawnGnome\PackagistAdoption;

use DateTime;
use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\{Request, Response};
use Predis\ClientInterface as PredisClient;
use RuntimeException;

class InvalidVersionDataException extends RuntimeException {}
class VersionRejectedException extends RuntimeException {}

class PackagistHydrator implements PackageHydratorInterface {
	protected $expiry;
	protected $guzzle;
	protected $predis;

	const PACKAGIST_HOST = 'https://packagist.org';

	public function __construct(GuzzleClient $guzzle, PredisClient $predis, int $expiry = 86400) {
		$this->expiry = $expiry;
		$this->guzzle = $guzzle;
		$this->predis = $predis;
	}

	public function hydrate(Package $package) {
		if ($json = $this->predis->get($package->getName())) {
			$package->fromJson(json_decode($json, true));
			return;
		}

		$pool = new Pool($this->guzzle, $this->getVersionRequests($package), [
			'concurrency' => 5,
			'fulfilled' => function (Response $response, string $version) use ($package) {
				$data = json_decode($response->getBody());
				if (count($data->labels) != count($data->values)) {
					throw new InvalidVersionDataException(sprintf('Mismatch: %d label(s) versus %d value(s)', count($data->labels), count($data->values)));
				}

				$version = $package->addVersion($version);
				for ($i = 0; $i < count($data->labels); $i++) {
					$version->set($data->labels[$i], $data->values[$i]);
				}
			},
			'rejected' => function ($reason, string $version) {
				throw new VersionRejectedException($version);
			},
		]);

		$pool->promise()->wait();

		$package->calculate();
		$this->cache($package);
	}

	protected function cache(Package $package) {
		$this->predis->set($package->getName(), json_encode($package));
		$this->predis->expire($package->getName(), $this->expiry);
	}

	protected function getVersionRequests(Package $package) {
		$response = $this->guzzle->get(self::PACKAGIST_HOST."/packages/{$package->getName()}.json");

		$data = json_decode($response->getBody(), true);
		$package->setTime($time = new DateTime($data['package']['time']));

		foreach (array_keys($data['package']['versions']) as $version) {
			yield $version => new Request('GET', self::PACKAGIST_HOST."/packages/{$package->getName()}/stats/{$version}.json?average=weekly&from={$time->format('Y-m-d')}");
		}
	}
}

// vim: set noet ts=4 sw=4:
