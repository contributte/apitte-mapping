<?php

namespace Apitte\Mapping\Http\Type;

class IntegerMapper extends AbstractMapper
{

	/**
	 * @param mixed $value
	 * @return int
	 */
	public function normalize($value)
	{
		return intval($value);
	}

}
