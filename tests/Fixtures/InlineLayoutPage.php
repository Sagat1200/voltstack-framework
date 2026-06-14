<?php

declare(strict_types=1);

namespace VoltStack\Test\Fixtures;

use VoltStack\Runtime\Component\Component;

final class InlineLayoutPage extends Component
{
    public string $title = 'Inline Layout Title';
}

?>
@extends('layouts.app')

@section('content')
<div>
    <h1>{{ $title }}</h1>
</div>
@endsection
