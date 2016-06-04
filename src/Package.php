<?php

namespace LawnGnome\PackagistAdoption;

use DateTime;
use JsonSerializable;

class Package implements JsonSerializable {
	protected $major = [];
	protected $minor = [];
	protected $name;
	protected $time;
	protected $versions = [];

	public function __construct(string $name) {
		$this->name = $name;
	}

	public function addVersion(string $version): Version {
		$this->versions[$version] = new Version;
		return $this->versions[$version];
	}

	public function calculate() {
		$this->major = $this->summariseVersions(1);
		$this->minor = $this->summariseVersions(2);
		uksort($this->versions, 'version_compare');
	}

	public function fromJson(array $data) {
		$mapToVersion = function (array $downloads): Version {
			return new Version($downloads);
		};

		$this->major = array_map($mapToVersion, $data['major']);
		$this->minor = array_map($mapToVersion, $data['minor']);
		$this->time = new DateTime($data['time']);
		$this->versions = array_map($mapToVersion, $data['versions']);
	}

	public function getName(): string {
		return $this->name;
	}

	public function getTime(): DateTime {
		return clone $this->time;
	}

	public function jsonSerialize(): array {
		return [
			'major' => $this->major,
			'minor' => $this->minor,
			'time' => $this->time->format(DateTime::ISO8601),
			'versions' => $this->versions,
		];
	}

	public function setTime(DateTime $time) {
		$this->time = clone $time;
	}

	public function setVersions(array $versions) {
		$this->versions = $versions;
	}

	public function versions() {
		yield from $this->versions;
	}

	protected function summariseVersions(int $elements): array {
		$summary = [];

		foreach ($this->versions as $number => $version) {
			$dots = count_chars($number, 1)[ord('.')] ?? 0;

			// Special case: treat 5.2 as 5.2.0.
			if ($elements == 2 && $dots == 1) {
				$sumver = $number;
			} elseif ($dots >= $elements) {
				$sumver = implode('.', array_slice(explode('.', $number, $elements + 1), 0, $elements));
			} else {
				continue;
			}

			// Other special case: remove prefixed v.
			$sumver = ltrim($sumver, 'v');

			if (!isset($summary[$sumver])) {
				$summary[$sumver] = new Version;
			}

			foreach ($version->all() as $date => $downloads) {
				$summary[$sumver]->add($date, $downloads);
			}
		}

		uksort($summary, 'version_compare');
		return $summary;
	}
}

// vim: set noet ts=4 sw=4:
