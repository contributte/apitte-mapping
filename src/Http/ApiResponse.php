<?php

namespace Apitte\Mapping\Http;

use Apitte\Core\Http\ApiResponse as CoreApiResponse;
use Apitte\Core\Schema\Endpoint;
use Apitte\Negotiation\Http\AbstractEntity;

/**
 * Mapping wrapper for PSR-7 ResponseInterface
 */
class ApiResponse extends CoreApiResponse
{

	/** @var AbstractEntity */
	protected $entity;

	/** @var Endpoint */
	protected $endpoint;

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

	/**
	 * @return Endpoint
	 */
	public function getEndpoint()
	{
		return $this->endpoint;
	}

	/**
	 * @param Endpoint $endpoint
	 * @return static
	 */
	public function withEndpoint(Endpoint $endpoint)
	{
		$new = clone $this;
		$new->endpoint = $endpoint;

		return $new;
	}

}
