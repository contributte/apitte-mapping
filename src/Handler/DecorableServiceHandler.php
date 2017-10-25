<?php

namespace Apitte\Mapping\Handler;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Exception\Runtime\EarlyReturnResponseException;
use Apitte\Core\Handler\IHandler;
use Apitte\Core\Handler\ServiceCallback;
use Apitte\Core\Http\RequestAttributes;
use Apitte\Core\Schema\Endpoint;
use Apitte\Mapping\Decorator\IDecorator;
use Apitte\Mapping\Decorator\IHandlerExceptionDecorator;
use Apitte\Mapping\Decorator\IHandlerRequestDecorator;
use Apitte\Mapping\Decorator\IHandlerResponseDecorator;
use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Apitte\Negotiation\Http\ArrayEntity;
use Exception;
use Nette\DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DecorableServiceHandler implements IHandler
{

	/** @var Container */
	protected $container;

	/** @var IHandlerRequestDecorator[] */
	protected $requestDecorators = [];

	/** @var IHandlerResponseDecorator[] */
	protected $responseDecorators = [];

	/** @var IHandlerExceptionDecorator[] */
	protected $exceptionDecorators = [];

	/** @var IHandlerResponseDecorator[] */
	protected $callbackDecorators = [];

	/**
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

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
		try {
			// Trigger request decorator
			$request = $this->decorateRequest($request, $response);
		} catch (EarlyReturnResponseException $exception) {
			return $exception->getResponse();
		} catch (Exception $e) {
			throw $e;
		}

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

		// Validate if response is returned
		if ($response === NULL) {
			throw new InvalidStateException('Handler returned response cannot be NULL');
		}

		// Validate if response is ResponseInterface
		if (!($response instanceof ResponseInterface)) {
			throw new InvalidStateException(sprintf('Handler returned response must be subtype of %s', ResponseInterface::class));
		}

		// Trigger response decorator
		$response = $this->decorateResponse($request, $response);

		return $response;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ServerRequestInterface
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
			if (!($request instanceof ServerRequestInterface)) {
				throw new InvalidStateException(sprintf('RequestDecorator returned request must be subtype of %s', ServerRequestInterface::class));
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

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ServiceCallback
	 */
	protected function createCallback(ServerRequestInterface $request, ResponseInterface $response)
	{
		$endpoint = $this->getEndpoint($request);

		// Find handler in DI container by class
		$service = $this->getService($endpoint);
		$method = $endpoint->getHandler()->getMethod();

		// Create callback
		$callback = new ServiceCallback($service, $method);
		$callback->setArguments([$request, $response]);

		return $callback;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return Endpoint
	 */
	protected function getEndpoint(ServerRequestInterface $request)
	{
		/** @var Endpoint $endpoint */
		$endpoint = $request->getAttribute(RequestAttributes::ATTR_ENDPOINT);

		// Validate that we have an endpoint
		if (!$endpoint) {
			throw new InvalidStateException(sprintf('Attribute "%s" is required', RequestAttributes::ATTR_ENDPOINT));
		}

		return $endpoint;
	}

	/**
	 * @param Endpoint $endpoint
	 * @return object
	 */
	protected function getService(Endpoint $endpoint)
	{
		return $this->container->getByType($endpoint->getHandler()->getClass());
	}

}
