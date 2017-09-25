<?php

namespace Apitte\Mapping\Http\Type;

class FloatMapper extends AbstractMapper
{

	/**
	 * @param mixed $value
	 * @return int
	 */
	public function normalize($value)
	{
		return floatval($value);
	}

}
