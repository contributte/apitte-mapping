<?php

namespace Apitte\Mapping\Handler;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Handler\IHandler;
use Apitte\Mapping\Handler\Decorator\IDecorator;
use Apitte\Mapping\Handler\Decorator\IRequestDecorator;
use Apitte\Mapping\Handler\Decorator\IResponseDecorator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DecorableHandler implements IHandler
{

	/** @var IRequestDecorator[] */
	protected $requestDecorators = [];

	/** @var IResponseDecorator[] */
	protected $responseDecorators = [];

	/** @var IHandler */
	private $handler;

	/**
	 * @param IHandler $handler
	 */
	public function __construct(IHandler $handler)
	{
		$this->handler = $handler;
	}

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
	public function handle(ServerRequestInterface $request, ResponseInterface $response)
	{
		// Trigger request decorator
		$request = $this->beforeHandle($request, $response);

		// Dynamic return depends on returned type of beforeHandle
		if ($request instanceof ResponseInterface) return $request;

		// Handle request
		$response = $this->handler->handle($request, $response);

		// Trigger response decorator
		$response = $this->afterHandle($request, $response);

		return $response;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface|ServerRequestInterface
	 */
	protected function beforeHandle(ServerRequestInterface $request, ResponseInterface $response)
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
	protected function afterHandle(ServerRequestInterface $request, ResponseInterface $response)
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
}
