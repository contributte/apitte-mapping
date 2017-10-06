<?php

namespace Apitte\Mapping\Decorator;

use Apitte\Mapping\RequestParameterMapping;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestParametersDecorator implements IHandlerRequestDecorator
{

	/** @var RequestParameterMapping */
	protected $mapping;

	/**
	 * @param RequestParameterMapping $mapping
	 */
	public function __construct(RequestParameterMapping $mapping)
	{
		$this->mapping = $mapping;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface|ServerRequestInterface
	 */
	public function decorateHandlerRequest(ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->mapping->map($request, $response);
	}

}
