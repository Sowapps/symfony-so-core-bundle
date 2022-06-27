<?php

namespace Sowapps\SoCore;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SoCoreBundle extends AbstractBundle {
	
	public function configure(DefinitionConfigurator $definition): void {
		// TODO Add forms ? (like user registration)
		// @formatter:off
		$definition->rootNode()
			->addDefaultsIfNotSet()
			->children()
			
->arrayNode('user')
	->children()
		->scalarNode('class')->defaultValue('\App\Entity\User')->end()
		->arrayNode('activation')
			->children()
				->integerNode('expire_hours')
					->min(1)
					->defaultValue(24)
				->end()
			->end()
		->end()
		->arrayNode('recover')
			->children()
				->integerNode('expire_hours')
					->min(1)
					->defaultValue(24)
				->end()
			->end()
		->end()
	->end()
->end()
->arrayNode('admin')
	->children()
		->arrayNode('auth')
			->children()
				->arrayNode('background')->scalarPrototype()->end()->end()
			->end()
		->end()
	->end()
->end()
->arrayNode('email')
	->children()
		->arrayNode('from')
			->children()
				->scalarNode('name')->end()
				->scalarNode('email')->end()
			->end()
		->end()
		->arrayNode('contact')
			->children()
				->scalarNode('name')->end()
				->scalarNode('email')->end()
			->end()
		->end()
		->arrayNode('online_view')
			->children()
				->integerNode('expire_hours')->min(1)->end()
			->end()
		->end()
	->end()
->end()
->arrayNode('file')
	->children()
		->scalarNode('store_path')->defaultValue('%kernel.project_dir%/var/store/files')->end()
		->scalarNode('public_path')->defaultValue('%kernel.project_dir%/public')->end()
	->end()
->end()
			
			->end();
		// @formatter:on
		
	}
	
	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
		
		$parameters = $container->parameters();
		foreach( $config as $key => $subConfig ) {
			$parameters->set('so_core.' . $key, $subConfig);
		}
		
		// load an XML, PHP or Yaml file
		$container->import('../config/services.yaml');
		
	}
	
}
