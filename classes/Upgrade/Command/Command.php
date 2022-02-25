<?php

namespace WCML\Upgrade\Command;

interface Command {
	/**
	 * @return bool|void|null
	 */
	public function run();
}
