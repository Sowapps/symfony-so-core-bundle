<?php

namespace Sowapps\SoCore;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SoCoreBundle extends AbstractBundle {
	
	public const ASSET_PACKAGE = '@sowapps/so-core';

	public function configure(DefinitionConfigurator $definition): void {
		// Load bundle configuration definition
		$definition->import('../config/definition.php');
	}
	
	public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void {
		if( !$this->isAssetMapperAvailable($builder) ) {
			return;
		}
		
		// Add the path "assets/" to loaded resources of the bundle
		// It allows the app to use assets in this folder
		$builder->prependExtensionConfig('framework', [
			'asset_mapper' => [
				'paths' => [
					__DIR__ . '/../assets' => self::ASSET_PACKAGE,
				],
			],
		]);
	}
	
	/**
	 * @param array $config
	 * @param ContainerConfigurator $container Seems to declare services/parameters for all bundles+app
	 * @param ContainerBuilder $builder Seems to declare services/parameters for this bundle only
	 * @return void
	 */
	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
		// Load bundle services
		$container->import('../config/services.yaml');
		
		// We keep it hardcoded to get back the parameters' name
		$container->parameters()
			->set('so_core.app', $config['app'])
			->set('so_core.user', $config['user'])
			->set('so_core.admin', $config['admin'])
			->set('so_core.email', $config['email'])
			->set('so_core.file', $config['file'])
			->set('so_core.routing', $config['routing']);
	}
	
	private function isAssetMapperAvailable(ContainerBuilder $builder): bool {
		if( !interface_exists(AssetMapperInterface::class) ) {
			return false;
		}
		
		// check that FrameworkBundle 6.3 or higher is installed
		$bundlesMetadata = $builder->getParameter('kernel.bundles_metadata');
		if( !isset($bundlesMetadata['FrameworkBundle']) ) {
			return false;
		}
		
		return is_file($bundlesMetadata['FrameworkBundle']['path'] . '/Resources/config/asset_mapper.php');
	}
	
}
