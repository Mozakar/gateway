<?php

namespace Mozakar\Gateway;
interface Currency
{
	const RIAL = 'RIAL';
	const TOMAN = 'TOMAN';
	const MILI = 'MILI';

	const ALL = [
		self::RIAL,
		self::TOMAN,
		self::MILI,
	];
}