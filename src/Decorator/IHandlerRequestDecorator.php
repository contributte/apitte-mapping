<?php

namespace Apitte\Mapping\Decorator;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
interface IHandlerRequestDecorator extends IDecorator
{

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface|ServerRequestInterface
	 */
	public function decorateHandlerRequest(ServerRequestInterface $request, ResponseInterface $response);

}
