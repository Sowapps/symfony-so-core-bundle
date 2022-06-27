<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Form;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Sowapps\SoCore\DataTransformer\EntityTransformer;
use Sowapps\SoCore\Entity\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Entity type with simple id input
 */
class EntityType extends AbstractType {
	
	protected ManagerRegistry $registry;
	
	public function __construct(ManagerRegistry $registry) {
		$this->registry = $registry;
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->addModelTransformer(new EntityTransformer($options['em'], $options['class']));
	}
	
	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		
		$entityManagerNormalizer = function (Options $options, $em) {
			if( null !== $em ) {
				if( $em instanceof ObjectManager ) {
					return $em;
				}
				
				return $this->registry->getManager($em);
			}
			$em = $this->registry->getManagerForClass($options['class']);
			if( null === $em ) {
				throw new RuntimeException(sprintf('Class "%s" seems not to be a managed Doctrine entity. Did you forget to map it?', $options['class']));
			}
			
			return $em;
		};
		
		$resolver->setDefaults([
			'em'       => null,
			'class'    => File::class,
//			'expanded' => false,
		]);
		
		$resolver->setNormalizer('em', $entityManagerNormalizer);
	}
	
	public function getBlockPrefix(): string {
		return 'so_entity';
	}
	
}
