<?php

namespace Sowapps\SoCore\Core\ProcessOption;

use App\Entity\AbstractEntity;
use App\Service\TextFormatter;
use Closure;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormatOptions {

    private ?Closure $formatter = null;

    public function __construct(
        private readonly array $formats,
        private readonly ?TextFormatter $textFormatter = null
    ) {
    }

    public function format(AbstractEntity $entity) {
        return $entity->asArray($this) + ($this->formatter ? call_user_func($this->formatter, $entity) : []);
    }

    public function isRestricted(): bool {
        // If anything is common, the format allow to display restricted data
        return !!array_intersect([AbstractEntity::FORMAT_PRIVATE, AbstractEntity::FORMAT_ADMIN], $this->formats);
    }

    public function isAdmin(): bool {
        // If anything is common, the format allow to display restricted data
        return $this->hasFormat(AbstractEntity::FORMAT_ADMIN);
    }

    public function isRelation(): bool {
        return $this->hasFormat(AbstractEntity::FORMAT_RELATION);
    }

    public function hasFormat(string $format): bool {
        return in_array($format, $this->formats, true);
    }

    public static function default(): static {
        return new static([AbstractEntity::FORMAT_PUBLIC]);
    }

    public function getFormatter(): ?Closure {
        return $this->formatter;
    }

    public function setFormatter(?Closure $formatter): void {
        $this->formatter = $formatter;
    }

    public function getTextFormatter(): ?TextFormatter {
        return $this->textFormatter;
    }

}
