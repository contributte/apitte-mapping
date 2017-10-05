<?php

namespace Apitte\Mapping\DI;

use Apitte\Core\DI\ApiExtension;
use Apitte\Core\DI\Helpers;
use Apitte\Core\DI\Plugin\AbstractPlugin;
use Apitte\Core\DI\Plugin\PluginCompiler;
use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Mapping\Dispatcher\DecorableDispatcher;
use Apitte\Mapping\Handler\DecorableServiceHandler;
use Apitte\Mapping\Http\RequestParameterMapping;
use Apitte\Mapping\Http\RequestParametersDecorator;
use Apitte\Mapping\Http\Type\FloatMapper;
use Apitte\Mapping\Http\Type\IntegerMapper;
use Apitte\Mapping\Http\Type\StringMapper;

class MappingPlugin extends AbstractPlugin
{

	const PLUGIN_NAME = 'mapping';

	/** @var array */
	protected $defaults = [
		'types' => [
			'int' => IntegerMapper::class,
			'float' => FloatMapper::class,
			'string' => StringMapper::class,
		],
	];

	/**
	 * @param PluginCompiler $compiler
	 */
	public function __construct(PluginCompiler $compiler)
	{
		parent::__construct($compiler);
		$this->name = self::PLUGIN_NAME;
	}

	/**
	 * Register services
	 *
	 * @return void
	 */
	public function loadPluginConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$builder->removeDefinition($this->extensionPrefix('core.handler'));
		$builder->removeDefinition($this->extensionPrefix('core.dispatcher'));

		$builder->addDefinition($this->prefix('dispatcher'))
			->setFactory(DecorableDispatcher::class);

		$builder->addDefinition($this->prefix('handler'))
			->setFactory(DecorableServiceHandler::class);

		if ($config['types']) {
			$builder->addDefinition($this->prefix('decorator.request.parameters'))
				->setFactory(RequestParametersDecorator::class)
				->addTag(ApiExtension::MAPPING_HANDLER_DECORATOR_TAG, ['priority' => 100]);

			$rpm = $builder->addDefinition($this->prefix('request.parameters'))
				->setFactory(RequestParameterMapping::class);

			foreach ($config['types'] as $type => $mapper) {
				$rpm->addSetup('addMapper', [$type, $mapper]);
			}
		}
	}

	/**
	 * Decorate services
	 *
	 * @return void
	 */
	public function beforePluginCompile()
	{
		$builder = $this->getContainerBuilder();

		$this->compileTaggedDecorators();
		$this->compileTaggedHandlerDecorators();
	}

	/**
	 * @return void
	 */
	protected function compileTaggedDecorators()
	{
		$builder = $this->getContainerBuilder();

		// Find all definitions by tag
		$definitions = $builder->findByTag(ApiExtension::MAPPING_DECORATOR_TAG);

		// Ensure we have at least 1 service
		if (!$definitions) {
			throw new InvalidStateException(sprintf('No services with tag "%s"', ApiExtension::MAPPING_DECORATOR_TAG));
		}

		// Sort by priority
		$definitions = Helpers::sort($definitions);

		// Find all services by names
		$decorators = Helpers::getDefinitions($definitions, $builder);

		// Add decorators to dispatcher
		$builder->getDefinition($this->prefix('dispatcher'))
			->addSetup('addDecorators', [$decorators]);
	}

	/**
	 * @return void
	 */
	protected function compileTaggedHandlerDecorators()
	{
		$builder = $this->getContainerBuilder();

		// Find all definitions by tag
		$definitions = $builder->findByTag(ApiExtension::MAPPING_HANDLER_DECORATOR_TAG);

		// Ensure we have at least 1 service
		if (!$definitions) {
			throw new InvalidStateException(sprintf('No services with tag "%s"', ApiExtension::MAPPING_HANDLER_DECORATOR_TAG));
		}

		// Sort by priority
		$definitions = Helpers::sort($definitions);

		// Find all services by names
		$decorators = Helpers::getDefinitions($definitions, $builder);

		// Add decorators to dispatcher
		$builder->getDefinition($this->prefix('handler'))
			->addSetup('addDecorators', [$decorators]);
	}

}
