<?php

namespace Octoper\Lighthouse\Test;

use Octoper\Lighthouse\Lighthouse;

class MockLighthouse extends Lighthouse
{
    public function getCategories()
    {
        return $this->categories;
    }
}
