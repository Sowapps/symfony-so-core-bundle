<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Twig;

use Sowapps\SoCoreBundle\Contracts\ContextInterface;
use Sowapps\SoCoreBundle\Core\Form\AbstractForm;
use Sowapps\SoCoreBundle\Entity\AbstractEntity;
use Sowapps\SoCoreBundle\Exception\UserException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Environment as TwigService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class TwigExtension extends AbstractExtension {
	
	protected EntrypointLookupInterface $entrypointLookup;
	
	protected TranslatorInterface $translator;
	
	protected TwigService $twig;
	
	protected ContextInterface $contextService;
	
	protected ParameterBagInterface $parameters;
	
	protected string $publicPath;
	
	protected array $uniqueId = [];
	
	/**
	 * AppTwigExtension constructor
	 *
	 * @param EntrypointLookupInterface $entrypointLookup
	 * @param TranslatorInterface $translator
	 * @param TwigService $twig
	 * @param ContextInterface $contextService
	 * @param string $publicPath
	 */
	public function __construct(
		EntrypointLookupInterface $entrypointLookup, TranslatorInterface $translator, ParameterBagInterface $parameters, TwigService $twig,
		ContextInterface          $contextService, string $publicPath
	) {
		$this->entrypointLookup = $entrypointLookup;
		$this->translator = $translator;
		$this->parameters = $parameters;
		$this->twig = $twig;
		$this->contextService = $contextService;
		$this->publicPath = $publicPath;
	}
	
	public function getTests(): array {
		return [
			new TwigTest('object', 'is_object'),
			new TwigTest('array', 'is_array'),
		];
	}
	
	public function getFilters(): array {
		return [
			new TwigFilter('smartDate', [$this, 'formatToSmartDate']),
			new TwigFilter('base64', [$this, 'formatToBase64']),
			new TwigFilter('bool', 'boolval'),
			//			new TwigFilter('price', [$this, 'formatPrice']),
			//			new TwigFilter('smallImage', [$this, 'formatSmallImage']),
			//			new TwigFilter('largeImage', [$this, 'formatLargeImage']),
			//			new TwigFilter('attributes', [$this, 'formatAttributes'], ['is_safe' => ['html']]),
			//			new TwigFilter('labelize', [$this, 'labelize']),
			//			new TwigFilter('paragraphize', [$this, 'paragraphize'], ['is_safe' => ['html']]),
			//			new TwigFilter('theme', [$this, 'applyTheme']),
			//			new TwigFilter('pushTo', [$this, 'pushTo']),
			//			new TwigFilter('normalize', [$this, 'normalize']),
			//			new TwigFilter('json', [$this->normalizerService, 'serialize']),
			//			new TwigFilter('interval', [$this, 'formatInterval']),
			//			new TwigFilter('asMinutes', [$this, 'formatIntervalAsMinutes']),
		];
	}
	
	public function getFunctions(): array {
		return [
			new TwigFunction('bodyClass', [$this, 'getBodyClass']),
			//			new TwigFunction('parameter', [$this, 'getParameter'], ['is_safe' => ['html']]),
			//			new TwigFunction('label', [$this, 'getFieldLabel']),
			//			new TwigFunction('inputAttr', [$this, 'renderInputAttr'], ['is_safe' => ['html']]),
			//			new TwigFunction('inputLabelled', [$this, 'renderInputLabelled'], ['is_safe' => ['html']]),
			//			new TwigFunction('inputDateAttr', [$this, 'renderInputDateAttr'], ['is_safe' => ['html']]),
			//			new TwigFunction('arrayValue', [$this, 'renderArrayValue'], ['is_safe' => ['html']]),
			//			new TwigFunction('arrayChecked', [$this, 'renderArrayChecked'], ['is_safe' => ['html']]),
			//			new TwigFunction('selectOptions', [$this, 'renderSelectOptions'], ['is_safe' => ['html']]),
			//			new TwigFunction('validityCssClass', [$this, 'renderValidityCssClass'], ['is_safe' => ['html']]),
			//			new TwigFunction('getUniqueId', [$this, 'getUniqueId']),
			//			new TwigFunction('date', [$this, 'formatDate']),// 'date' Filter is used by Symfony
			//			new TwigFunction('reports', [$this, 'renderReports'], ['is_safe' => ['html']]),
			//			new TwigFunction('form_success', [$this, 'renderSuccessAlert'], ['is_safe' => ['html']]),
			//			new TwigFunction('successAlert', [$this, 'renderSuccessAlert'], ['is_safe' => ['html']]),
			//			new TwigFunction('errorAlert', [$this, 'renderErrorAlert'], ['is_safe' => ['html']]),
			//			new TwigFunction('repeatString', [$this, 'repeatString'], ['is_safe' => ['html']]),
			//			new TwigFunction('setTheme', [$this, 'setTheme']),
			//			new TwigFunction('theme', [$this, 'getTheme']),
			//			new TwigFunction('encore_entry_css_source', [$this, 'getEncoreEntryCssSource']),
			//			new TwigFunction('translations', [$this, 'getTranslations']),
		];
	}
	
	public function getBodyClass(): string {
		$classes = [];
		if( $this->contextService->isDebug() ) {
			$classes[] = 'mode-debug';
		}
		
		return implode(' ', $classes);
	}
	
	public function getParameter(string $name): ?string {
		return $this->parameters->get($name);
	}
	
	
	/**
	 * Push element to array
	 * /!\ Unable to pass array by reference
	 *
	 * @param $element
	 * @param array $array
	 * @return array
	 */
	public function pushTo($element, array $array): array {
		$array[] = $element;
		
		return $array;
	}
	
	public function getTranslations(string $path, array $keys, ?string $domain = null): array {
		$translations = [];
		foreach( $keys as $key ) {
			$translations[$key] = $this->translator->trans(sprintf('%s.%s', $path, $key), [], $domain);
		}
		
		return $translations;
	}
	
	public function getEncoreEntryCssSource(string $entryName): string {
		$this->entrypointLookup->reset();
		$files = $this->entrypointLookup->getCssFiles($entryName);
		$source = '';
		foreach( $files as $file ) {
			$source .= file_get_contents($this->publicPath . '/' . $file);
		}
		
		return $source;
	}
	
	/**
	 * @return string
	 */
	public function getTheme(): string {
		return $this->theme;
	}
	
	/**
	 * @param string $theme
	 * @return string
	 */
	public function setTheme(string $theme): string {
		$this->theme = $theme;
		
		return $theme;
	}
	
	/**
	 * @param string $value
	 * @return string
	 */
	public function applyTheme(string $value): string {
		return str_replace(['#', '{theme}'], $this->theme, $value);
	}
	
	public function repeatString($char, $times): string {
		return str_repeat($char, $times);
	}
	
	public function labelize(AbstractEntity $entity): string {
		return $entity->__toString();
	}
	
	//	public function paragraphize(?string $text): string {
	//		if( !$text ) {
	//			return '';
	//		}
	//		$parts = preg_split("#\n+#", $text);
	//		$parts = array_map(function ($string) {
	//			return '<p>' . strip_tags($string) . '</p>';
	//		}, $parts);
	//
	//		return join('', $parts);
	//	}
	
	/**
	 * @param array|string|AbstractForm $messages
	 * @return string
	 */
	public function renderErrorAlert($messages, $domain = 'validators'): string {
		return $this->renderAlert('error', $messages, $domain);
	}
	
	/**
	 * @param $type
	 * @param array|string|AbstractForm $messages
	 * @param null $domain
	 * @return string
	 */
	public function renderAlert($type, $messages, $domain = null): string {
		if( !$messages ) {
			return '';
		}
		if( !is_array($messages) ) {
			$messages = [$messages];
		}
		$html = '';
		foreach( $messages as $message ) {
			// Same as AbstractController
			if( $message instanceof UserException ) {
				$report = $message->asArray($domain);
				
			} else {
				$messageDomain = null;
				$params = [];
				if( is_array($message) ) {
					[$message, $params, $messageDomain] = array_pad($message, 3, null);
				}
				$report = ['message' => $message, 'parameters' => $params, 'domain' => $messageDomain ?? $domain];
			}
			$html .= $this->twig->render('component/alert.' . $type . '.' . $this->theme . '.html.twig', $report);
		}
		
		return $html;
	}
	
	/**
	 * @param array|string|AbstractForm $messages
	 * @param string|null $domain
	 * @return string
	 */
	public function renderReports($reports, string $domain = null): string {
		// Render a message saved using AbstractController:consumeSavedReports
		if( !empty($reports['success']) ) {
			return $this->renderAlert('success', $reports['success'], $domain);
		}
		if( !empty($reports['error']) ) {
			return $this->renderAlert('error', $reports['error'], $domain);
		}
		
		return '';
	}
	
	/**
	 * @param array|string|AbstractForm $messages
	 * @param string|null $domain
	 * @return string
	 */
	public function renderSuccessAlert($messages, string $domain = null): string {
		if( $messages instanceof FormView ) {
			// Not set if not using AppForm
			if( !array_key_exists('successes', $messages->vars) ) {
				// Silently ignore compound form widgets
				return '';
			}
			if( !$domain ) {
				$domain = $messages->vars['success_domain'];
			}
			$messageList = $messages->vars['successes'];
			$messages->vars['successes'] = null;
			$messages = $messageList;
		}
		
		return $this->renderAlert('success', $messages, $domain);
	}
	
	public function formatToBase64($url): string {
		if( $url[0] === '/' ) {
			$url = $this->publicPath . $url;
		}
		
		return base64_encode(file_get_contents($url));
	}
	
	//	public function formatSmallImage($image, $ignoreMissing = null, $email = null): string {
	//		if( $ignoreMissing instanceof WrappedTemplatedEmail ) {
	//			$email = $ignoreMissing;
	//			$ignoreMissing = null;
	//		}
	//		$ignoreMissing ??= $image instanceof AbstractEntity;
	//		try {
	//			$image = $this->getFile($image);
	//		} catch( FileNotFoundException $e ) {
	//			if( $ignoreMissing ) {
	//				return '';
	//			}
	//			throw $e;
	//		}
	//		$image = $this->fileService->getAlternativeFile($image, FileService::TYPE_SMALL);
	//
	//		return $this->formatContextImageUrl($image, $email);
	//	}
	
	//	public function isDateInput(object $input): bool {
	//		return $input->value && is_array($input->value) && isset($input->value['year']);
	//	}
	
	//	public function renderSelectOptions(FormView $formView): string {
	//		$input = (object) $formView->vars;
	//		$options = '';
	//		foreach( $input->choices as $choice ) {
	//			/** @var ChoiceView $choice */
	//			$options .= sprintf('<option value="%s" %s>%s</option>', $choice->value, $choice->value === $input->value ? 'selected' : '', $choice->label);
	//		}
	//
	//		return $options;
	//	}
	//
	//	public function renderInputLabelled(FormView $formView, $options = []): string {
	//		$options['placeholder_is_label'] = true;
	//
	//		return $this->renderInputAttr($formView, $options);
	//	}
	//
	//	/**
	//	 * @param FormView $formView
	//	 * @param object|string|null $options
	//	 * @return string
	//	 */
	//	public function renderInputAttr(FormView $formView, $options = null): string {
	//		$input = (object) $formView->vars;
	//		if( isset($input->type) && $input->type === 'date' ) {
	//			return $this->renderInputDateAttr($formView);
	//		}
	//		$options = $options ? (is_string($options) ? ['default_value' => $options] : (array) $options) : [];
	//		$options += ['default_value' => null, 'valued' => true, 'attr' => []];
	//		$options = (object) $options;
	//		$value = $input->value;
	//		if( !empty($input->choices) ) {
	//			$choice = null;
	//			if( isset($input->choices[$input->value]) ) {
	//				$choice = $input->choices[$input->value];
	//			} else {
	//				foreach( $input->choices as $availChoice ) {
	//					if( $availChoice->value == $value ) {
	//						$choice = $availChoice;
	//						break;
	//					}
	//				}
	//			}
	//			// Unknown value is null
	//			$value = $choice ? $choice->label : $options->default_value;
	//		}
	//		$attributes = $options->attr + $input->attr + [
	//				'id'          => isset($options->replace_id_pattern) ? sprintf($options->replace_id_pattern, $input->id) : $input->id,
	//				'name'        => $input->full_name,
	//				'value'       => $options->valued ? $value : null,
	//				'disabled'    => $input->disabled,
	//				'required'    => $input->required,
	//				'checked'     => !empty($input->checked),
	//				'placeholder' => !empty($options->placeholder_is_label) ? $this->getFieldLabel($formView) : null,
	//			];
	//
	//		return $this->formatAttributes($attributes);
	//	}
	//
	//	public function renderInputDateAttr(FormView $formView): string {
	//		$input = (object) $formView->vars;
	//		$value = '';
	//		if( $input->value ) {
	//			if( is_string($input->value) ) {
	//				$value = $input->value;
	//			} elseif( is_array($input->value) && isset($input->value['year']) ) {
	//				$value = sprintf('%d/%d/%d', $input->value['day'], $input->value['month'], $input->value['year']);
	//			}
	//		}
	//
	//		return sprintf('type="text" id="%s" name="%s" value="%s" placeholder="%s"%s%s',
	//			$input->id, $input->full_name, $value, $this->translator->trans('date.format.placeholder_date'),
	//			$input->disabled ? ' disabled' : '', $input->required ? ' required' : '');
	//	}
	//
	//	public function getFieldLabel(FormView $formView): string {
	//		$input = (object) $formView->vars;
	//
	//		return $this->translator->trans(
	//			$input->label ?: ($input->label_format ? strtr($input->label_format, ['%name%' => $input->name, '%id%' => $input->id]) : 'Undefined'),
	//			$input->label_translation_parameters,
	//			$input->translation_domain
	//		);
	//	}
	//
	//	/**
	//	 * @param array|FormView $attributes
	//	 * @param string $prefix
	//	 * @return string
	//	 */
	//	public function formatAttributes($attributes, string $prefix = ''): string {
	//		if( $attributes instanceof FormView ) {
	//			$attributes = $attributes->vars['attr'];
	//		}
	//		$html = '';
	//		foreach( $attributes as $key => $value ) {
	//			if( $value === null || $value === false || $value === '' ) {
	//				continue;
	//			}
	//			$html .= ' ' . $prefix . ($value === true ? $key : $key . '="' . $value . '"');
	//		}
	//
	//		return $html;
	//	}
	//
	//	public function renderArrayValue($values, $name): string {
	//		return $values && isset($values[$name]) ? sprintf('value="%s"', $values[$name]) : '';
	//	}
	//
	//	/**
	//	 * @param string|array $value
	//	 * @param string $name
	//	 * @return string
	//	 */
	//	public function renderArrayChecked($value, $name): string {
	//		// Checkbox => $value is an array
	//		// Radio => $value is a value
	//		// $value is True => Force checked
	//		return $value && ($value === true || (is_array($value) ? !empty($value[$name]) : $value === $name)) ? 'checked' : '';
	//	}
	//
	//	public function renderValidityCssClass(FormView $formView): string {
	//		$input = (object) $formView->vars;
	//
	//		return !$input->valid ? 'is-invalid' : '';
	//	}
	//
	//	public function getUniqueId($subject): string {
	//		if( !isset($this->uniqueId[$subject]) ) {
	//			$this->uniqueId[$subject] = 0;
	//		}
	//
	//		return $subject . (++$this->uniqueId[$subject]);
	//	}
	
	public function getName(): string {
		return 'app_ext';
	}
	
}
