<?php

namespace Apitte\Mapping\Handler;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Handler\ServiceHandler;
use Apitte\Mapping\Decorator\IDecorator;
use Apitte\Mapping\Decorator\IHandlerExceptionDecorator;
use Apitte\Mapping\Decorator\IHandlerRequestDecorator;
use Apitte\Mapping\Decorator\IHandlerResponseDecorator;
use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Apitte\Negotiation\Http\ArrayEntity;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DecorableServiceHandler extends ServiceHandler
{

	/** @var IHandlerRequestDecorator[] */
	protected $requestDecorators = [];

	/** @var IHandlerResponseDecorator[] */
	protected $responseDecorators = [];

	/** @var IHandlerExceptionDecorator[] */
	protected $exceptionDecorators = [];

	/** @var IHandlerResponseDecorator[] */
	protected $callbackDecorators = [];

	/**
	 * GETTERS/SETTERS *********************************************************
	 */

	/**
	 * @param IHandlerRequestDecorator $decorator
	 * @return void
	 */
	public function addRequestDecorator(IHandlerRequestDecorator $decorator)
	{
		$this->requestDecorators[] = $decorator;
	}

	/**
	 * @param IHandlerResponseDecorator $decorator
	 * @return void
	 */
	public function addResponseDecorator(IHandlerResponseDecorator $decorator)
	{
		$this->responseDecorators[] = $decorator;
	}

	/**
	 * @param IHandlerExceptionDecorator $decorator
	 * @return void
	 */
	public function addExceptionDecorator(IHandlerExceptionDecorator $decorator)
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
			if ($decorator instanceof IHandlerRequestDecorator) {
				$this->addRequestDecorator($decorator);
			}
			if ($decorator instanceof IHandlerResponseDecorator) {
				$this->addResponseDecorator($decorator);
			}
			if ($decorator instanceof IHandlerExceptionDecorator) {
				$this->addExceptionDecorator($decorator);
			}
		}
	}

	/**
	 * API *********************************************************************
	 */

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request, ResponseInterface $response)
	{
		// @todo validate ApiRequest & ApiResponse

		// Trigger request decorator
		$request = $this->decorateRequest($request, $response);

		// Dynamic return depends on returned type of beforeHandle
		if ($request instanceof ResponseInterface) return $request;

		// Catch all exceptions and decorate them
		try {
			// Create service callback
			$callback = $this->createCallback($request, $response);
			$result = $callback($request, $response);

			// Handle returned value from callback
			if (is_array($result)) {
				$response = $response->withEntity(new ArrayEntity($result));
			} else if ($result instanceof ResponseInterface) {
				$response = $result;
			} else {
				throw new InvalidStateException(sprintf('Unsupported returned type %s', gettype($result)));
			}
		} catch (Exception $e) {
			$response = $this->decorateException($e, $request, $response);
			if ($response === NULL) throw $e;
		}

		// Validate response
		$response = $this->finalize($response);

		// Trigger response decorator
		$response = $this->decorateResponse($request, $response);

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
			$request = $decorator->decorateHandlerRequest($request, $response);

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
			$response = $decorator->decorateHandlerResponse($request, $response);

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
			$response = $decorator->decorateHandlerException($exception, $request, $response);

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

}
