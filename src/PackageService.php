<?php

namespace LawnGnome\PackagistAdoption;

use Predis\ClientInterface;

class PackageService {
	protected $hydrator;

	public function __construct(PackageHydratorInterface $hydrator) {
		$this->hydrator = $hydrator;
	}

	public function getPackage(string $name): Package {
		$package = new Package($name);
		$this->hydrator->hydrate($package);

		return $package;
	}
}

// vim: set noet ts=4 sw=4:
