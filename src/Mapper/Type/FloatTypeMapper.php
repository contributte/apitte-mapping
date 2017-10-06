<?php

namespace Apitte\Mapping\Mapper\Type;

class FloatTypeMapper extends AbstractTypeMapper
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
