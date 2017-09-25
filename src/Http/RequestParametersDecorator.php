<?php

namespace Apitte\Mapping\Http;

use Apitte\Mapping\Handler\Decorator\IRequestDecorator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestParametersDecorator implements IRequestDecorator
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
	public function decorateRequest(ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->mapping->map($request, $response);
	}

}
