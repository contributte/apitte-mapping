<?php

namespace Apitte\Mapping\Http;

use Apitte\Core\Http\ApiResponse as CoreApiResponse;
use Apitte\Negotiation\Http\AbstractEntity;

/**
 * Mapping wrapper for PSR-7 ResponseInterface
 */
class ApiResponse extends CoreApiResponse
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
