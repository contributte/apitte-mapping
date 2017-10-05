<?php

namespace Apitte\Mapping\Decorator;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
interface IExceptionDecorator extends IDecorator
{

	/**
	 * @param Exception $exception
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function decorateException(Exception $exception, ServerRequestInterface $request, ResponseInterface $response);

}
