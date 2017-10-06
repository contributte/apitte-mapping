<?php

namespace Apitte\Mapping\Mapper\Type;

class StringTypeMapper extends AbstractTypeMapper
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
