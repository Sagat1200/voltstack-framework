<?php

declare(strict_types=1);

namespace VoltStack\Test\Fixtures;

use VoltStack\Runtime\Component\Component;

final class InlineTemplatePage extends Component
{
    public string $title = 'Inline Title';
}

?>
<div>
    <h1>{{ $title }}</h1>
</div>