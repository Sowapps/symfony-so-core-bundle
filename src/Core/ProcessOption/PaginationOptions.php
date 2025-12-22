<?php

namespace Sowapps\SoCore\Core\ProcessOption;

class PaginationOptions {

    public function __construct(
        private readonly int $page = 1,
        private int          $pageLimit = 50
    ) {
    }

    public function getPage(): int {
        return $this->page;
    }

    public function getPageLimit(): int {
        return $this->pageLimit;
    }

    public function setPageLimit(int $pageLimit): void {
        $this->pageLimit = $pageLimit;
    }

}
