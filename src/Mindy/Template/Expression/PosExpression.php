<?php

namespace Mindy\Template\Expression;

class PosExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('+');
    }
}
