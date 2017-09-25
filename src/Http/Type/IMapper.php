<?php

namespace Apitte\Mapping\Http\Type;

interface IMapper
{

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function normalize($value);

}
