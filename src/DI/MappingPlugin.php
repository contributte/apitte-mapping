<?php

namespace Apitte\Mapping\DI;

use Apitte\Core\DI\Plugin\AbstractPlugin;
use Apitte\Core\DI\Plugin\PluginCompiler;
use Apitte\Mapping\Handler\DecorableHandler;
use Apitte\Mapping\Handler\Decorator\IDecorator;
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

		$handler = $builder->getDefinition($this->extensionPrefix('core.handler'));
		$handler->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('handler'))
			->setFactory(DecorableHandler::class, [$handler]);

		if ($config['types']) {
			$builder->addDefinition($this->prefix('decorator.request.parameters'))
				->setFactory(RequestParametersDecorator::class);

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

		$decorators = $builder->findByType(IDecorator::class);
		$builder->getDefinition($this->prefix('handler'))
			->addSetup('addDecorators', [$decorators]);
	}

}
