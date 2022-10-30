<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Form;

use Sowapps\SoCore\Entity\File;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageType extends EntityType {
	
	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		
		$resolver->setDefaults([
			'class'          => File::class,
			'preview_width'  => '100px',
			'preview_height' => null,
		]);
	}
	
	public function buildView(FormView $view, FormInterface $form, array $options) {
		$view->vars['preview_width'] = $options['preview_width'];
		$view->vars['preview_height'] = $options['preview_height'];
	}
	
	public function getBlockPrefix(): string {
		return 'so_image';
	}
	
	//	public function getParent(): string {
	//		return FileType::class;
	//	}
	
}
