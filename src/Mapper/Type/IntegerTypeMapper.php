<?php

namespace Apitte\Mapping\Mapper\Type;

class IntegerTypeMapper extends AbstractTypeMapper
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
