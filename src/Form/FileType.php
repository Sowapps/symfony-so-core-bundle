<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Form;

use Sowapps\SoCore\Entity\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileType extends EntityType {
	
	public function configureOptions(OptionsResolver $resolver): void {
		parent::configureOptions($resolver);
		
		$resolver->setDefaults([
			'class' => File::class,
		]);
	}
	
	public function getBlockPrefix(): string {
		return 'so_file';
	}
	
}
