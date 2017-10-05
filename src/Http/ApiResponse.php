<?php

namespace Apitte\Mapping\Http;

use Apitte\Negotiation\Http\AbstractEntity;
use Contributte\Psr7\ResponseWrapper;

/**
 * Tiny wrapper for PSR-7 ResponseInterface
 */
class ApiResponse extends ResponseWrapper
{

	/** @var AbstractEntity */
	protected $entity;

	/**
	 * @return AbstractEntity
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	/**
	 * @param AbstractEntity $entity
	 * @return static
	 */
	public function withEntity(AbstractEntity $entity)
	{
		$new = clone $this;
		$new->entity = $entity;

		return $new;
	}

}
