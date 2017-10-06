<?php

namespace Apitte\Mapping\Mapper\Type;

interface ITypeMapper
{

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function normalize($value);

}
