<?php


namespace Strud\Template
{
	interface Engine
	{
		public function process(Configuration $configuration, array $entries);
	}
}


