<?php

namespace WCML\Utilities;

class InMemoryLock {

	/** @var bool $locked */
	private $locked = false;

	public function lock() {
		$this->locked = true;
	}

	public function release() {
		$this->locked = false;
	}

	/**
	 * @return bool
	 */
	public function isLocked() {
		return $this->locked;
	}

	public function run( callable $function ) {
		$this->lock();
		$function();
		$this->release();
	}
}
