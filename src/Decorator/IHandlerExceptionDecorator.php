<?php

namespace Apitte\Mapping\Decorator;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
interface IHandlerExceptionDecorator extends IDecorator
{

	/**
	 * @param Exception $exception
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function decorateHandlerException(Exception $exception, ServerRequestInterface $request, ResponseInterface $response);

}
