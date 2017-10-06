<?php

namespace Apitte\Mapping\Dispatcher;

use Apitte\Core\Dispatcher\CoreDispatcher;
use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Mapping\Decorator\IDecorator;
use Apitte\Mapping\Decorator\IExceptionDecorator;
use Apitte\Mapping\Decorator\IRequestDecorator;
use Apitte\Mapping\Decorator\IResponseDecorator;
use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DecorableDispatcher extends CoreDispatcher
{

	/** @var IRequestDecorator[] */
	protected $requestDecorators = [];

	/** @var IResponseDecorator[] */
	protected $responseDecorators = [];

	/** @var IExceptionDecorator[] */
	protected $exceptionDecorators = [];

	/**
	 * GETTERS/SETTERS *********************************************************
	 */

	/**
	 * @param IRequestDecorator $decorator
	 * @return void
	 */
	public function addRequestDecorator(IRequestDecorator $decorator)
	{
		$this->requestDecorators[] = $decorator;
	}

	/**
	 * @param IResponseDecorator $decorator
	 * @return void
	 */
	public function addResponseDecorator(IResponseDecorator $decorator)
	{
		$this->responseDecorators[] = $decorator;
	}

	/**
	 * @param IExceptionDecorator $decorator
	 * @return void
	 */
	public function addExceptionDecorator(IExceptionDecorator $decorator)
	{
		$this->exceptionDecorators[] = $decorator;
	}

	/**
	 * @param IDecorator[] $decorators
	 * @return void
	 */
	public function addDecorators(array $decorators)
	{
		foreach ($decorators as $decorator) {
			if ($decorator instanceof IRequestDecorator) {
				$this->addRequestDecorator($decorator);
			}
			if ($decorator instanceof IResponseDecorator) {
				$this->addResponseDecorator($decorator);
			}
			if ($decorator instanceof IExceptionDecorator) {
				$this->addExceptionDecorator($decorator);
			}
		}
	}

	/**
	 * API *********************************************************************
	 */

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
	{
		// Create API/HTTP objects
		$request = $this->createApiRequest($request);
		$response = $this->createApiResponse($response);

		// Trigger request decorator
		$request = $this->decorateRequest($request, $response);

		// Dynamic return depends on returned type of beforeHandle
		if ($request instanceof ResponseInterface) return $request;

		// Catch all exceptions and decorate them
		try {
			// Try to route current request
			$response = parent::dispatch($request, $response);
		} catch (Exception $e) {
			$response = $this->decorateException($e, $request, $response);
			if ($response === NULL) throw $e;
		}

		// Trigger response decorator
		$response = $this->decorateResponse($request, $response);

		// Convert ApiResponse to ResponseInterface
		if ($response instanceof ApiResponse) {
			// Get original response
			$response = $response->getOriginalResponse();
		}

		return $response;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface|ServerRequestInterface
	 */
	protected function decorateRequest(ServerRequestInterface $request, ResponseInterface $response)
	{
		foreach ($this->requestDecorators as $decorator) {
			$request = $decorator->decorateRequest($request, $response);

			// Validate if response is returned
			if (!$request) {
				throw new InvalidStateException(sprintf('RequestDecorator "%s" must return request', get_class($decorator)));
			}

			// Validate if response is ApiResponse
			if (!($request instanceof ServerRequestInterface) && !($request instanceof ResponseInterface)) {
				throw new InvalidStateException(sprintf('RequestDecorator returned request must be subtype of %s or %s', ResponseInterface::class, ServerRequestInterface::class));
			}
		}

		return $request;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	protected function decorateResponse(ServerRequestInterface $request, ResponseInterface $response)
	{
		foreach ($this->responseDecorators as $decorator) {
			$response = $decorator->decorateResponse($request, $response);

			// Validate if response is returned
			if (!$response) {
				throw new InvalidStateException(sprintf('ResponseDecorator "%s" must return response', get_class($decorator)));
			}

			// Validate if response is ApiResponse
			if (!($response instanceof ResponseInterface)) {
				throw new InvalidStateException(sprintf('ResponseDecorator returned response must be subtype of %s', ResponseInterface::class));
			}
		}

		return $response;
	}

	/**
	 * @param Exception $exception
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	protected function decorateException(Exception $exception, ServerRequestInterface $request, ResponseInterface $response)
	{
		if (!$this->exceptionDecorators) return NULL;

		foreach ($this->exceptionDecorators as $decorator) {
			$response = $decorator->decorateException($exception, $request, $response);

			// Validate if response is returned
			if (!$response) {
				throw new InvalidStateException(sprintf('ExceptionDecorator "%s" must return response', get_class($decorator)));
			}

			// Validate if response is ApiResponse
			if (!($response instanceof ResponseInterface)) {
				throw new InvalidStateException(sprintf('ExceptionDecorator returned response must be subtype of %s', ResponseInterface::class));
			}
		}

		return $response;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ApiRequest
	 */
	protected function createApiRequest(ServerRequestInterface $request)
	{
		return new ApiRequest($request);
	}

	/**
	 * @param ResponseInterface $response
	 * @return ApiResponse
	 */
	protected function createApiResponse(ResponseInterface $response)
	{
		return new ApiResponse($response);
	}

}
