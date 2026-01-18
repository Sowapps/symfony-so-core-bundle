<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class SoAppService {
	public function __construct(
		private TranslatorInterface $translator,
		#[Autowire('%so_core.app%')]
		protected array             $configApp
	) {
	}
	
	public function getAppName(): string {
		return $this->translator->trans($this->configApp['label_tk']);
	}
}
