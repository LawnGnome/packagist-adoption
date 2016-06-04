<?php

namespace LawnGnome\PackagistAdoption;

use DateTime;
use JsonSerializable;

class Version implements JsonSerializable {
	protected $downloads;

	public function __construct(array $downloads = []) {
		$this->downloads = $downloads;
	}

	public function add(string $date, int $downloads) {
		if (isset($this->downloads[$date])) {
			$this->downloads[$date] += $downloads;
		} else {
			$this->set($date, $downloads);
		}
	}

	public function all() {
		yield from $this->downloads;
	}

	public function jsonSerialize(): array {
		return $this->downloads;
	}

	public function set(string $date, int $downloads) {
		$this->downloads[$date] = $downloads;
		ksort($this->downloads);
	}
}

// vim: set noet ts=4 sw=4:
