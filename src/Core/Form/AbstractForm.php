<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractForm extends AbstractType {
	
	protected TranslatorInterface $translator;
	
	protected array $options;
	
	protected ?string $success = null;
	
	#[Required]
	public function setTranslator(TranslatorInterface $translator): void {
		$this->translator = $translator;
	}
	
	
	public function hasModel(string|array $model): bool {
		if( is_string($model) ) {
			return isset($this->options['models'][$model]);
		}
		
		return !!array_intersect(array_keys($this->options['models']), $model);
	}
	
	public function isModelDisabled(string $model, $default = false): bool {
		// Model is true if enabled, false if disabled
		return !($this->options['models'][$model] ?? !$default);
	}
	
	public function buildView(FormView $view, FormInterface $form, array $options): void {
		parent::buildView($view, $form, $options);
		if( isset($options['invalid_message']) ) {
			$view->vars['invalid_message'] = $options['invalid_message'];
		}
	}
	
	/**
	 * Build choice list matching values and translation by prefix
	 *
	 * @param array $values
	 * @param string|callable $labelGenerator
	 * @param bool|string|null $domain
	 * @return array
	 */
	public function buildChoices(array $values, $labelGenerator, bool|string $domain = null): array {
		if( is_string($labelGenerator) ) {
			$pattern = $labelGenerator;
			$labelGenerator = function ($value) use ($pattern, $domain) {
				return sprintf($pattern, $value ?? 'unknown');
			};
		}
		
		return array_combine(array_map($labelGenerator, $values), $values);
	}
	
	/**
	 * Build choice list using range list
	 *
	 * @param array $values e.g [0, 5, 10, 20, 50] => [0->5, 6->10..., 51+]
	 * @param string $translationKey
	 * @param string|false|null $domain
	 * @param array|null $params
	 * @return array
	 */
	public function buildRangeChoices(array $values, string $translationKey, $domain = null, array $params = []): array {
		$choices = [];
		$last = count($values) - 1;
		// Building ranges (original value is not matching)
		foreach( $values as $rangeIndex => $rangeStart ) {
			if( $rangeIndex === $last ) {
				$value = (object) ['from' => $rangeStart + 1, 'to' => INF];
				$unitValue = $value->from . '+';
			} else {
				$value = (object) ['from' => ($rangeIndex ? $rangeStart + 1 : $rangeStart), 'to' => $values[$rangeIndex + 1]];
				$unitValue = $value->from . '-' . $value->to;
			}
			$valueParams = array_merge([$unitValue], $params ?? []);
			$key = $this->translator->trans($translationKey, $valueParams, $domain);
			$choices[$key] = $rangeIndex;
		}
		
		return $choices;
	}
	
	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}
	
	/**
	 * @param array $options
	 */
	public function setOptions(array $options): void {
		$this->options = $options;
	}
	
}
