<?php

namespace Apitte\Mapping\Http\Type;

class StringMapper extends AbstractMapper
{

	/**
	 * @param mixed $value
	 * @return int
	 */
	public function normalize($value)
	{
		return strval($value);
	}

}
