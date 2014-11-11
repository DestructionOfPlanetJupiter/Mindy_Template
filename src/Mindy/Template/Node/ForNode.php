<?php

namespace Mindy\Template\Node;

use Mindy\Template\Compiler;
use Mindy\Template\Node;

class ForNode extends Node
{
    protected $seq;
    protected $key;
    protected $value;
    protected $body;
    protected $else;

    public function __construct($seq, $key, $value, $body, $else, $line)
    {
        parent::__construct($line);
        $this->seq = $seq;
        $this->key = $key;
        $this->value = $value;
        $this->body = $body;
        $this->else = $else;
    }

    public function compile(Compiler $compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);

        // Django template engine compatible
        $compiler->pushContext('forloop', $indent);
        // Twig template engine compatible
        $compiler->pushContext('loop', $indent);

        if ($this->key) {
            $compiler->pushContext($this->key, $indent);
        }
        $compiler->pushContext($this->value, $indent);

        $else = false;
        if (!is_null($this->else)) {
            $compiler->raw('if (!Mindy\Template\Helper::is_empty(', $indent);
            $this->seq->compile($compiler);
            $compiler->raw(")) {\n");
            $else = true;
        }

        // Django template engine compatible
        // Twig template engine compatible
        $compiler->raw('$i = 0;');
        $compiler->raw("\n");
        $compiler->raw('foreach (($context[\'forloop\'] = $context[\'loop\'] = $this->iterate($context, ',
            $else ? ($indent + 1) : $indent);
        $this->seq->compile($compiler);

        if ($this->key) {
            $compiler->raw(
                ')) as $context[\'' . $this->key .
                '\'] => $context[\'' . $this->value . '\']) {' . "\n"
            );
        } else {
            $compiler->raw(
                ')) as $context[\'' . $this->value . '\']) {' . "\n"
            );
        }

        $compiler->raw('$context[\'counter0\'] = $context[\'index0\'] = $i;');
        $compiler->raw("\n");
        $compiler->raw('$context[\'counter\'] = $context[\'index\'] = $i + 1;');
        $compiler->raw("\n");

        $this->body->compile($compiler, $else ? ($indent + 2) : ($indent + 1));

        $compiler->raw('$i++;');
        $compiler->raw("\n");

        $compiler->raw("}\n", $else ? ($indent + 1) : $indent);

        if ($else) {
            $compiler->raw("} else {\n", $indent);
            $this->else->compile($compiler, $indent + 1);
            $compiler->raw("}\n", $indent);
        }

        // Django template engine compatible
        $compiler->popContext('forloop', $indent);
        // Twig template engine compatible
        $compiler->popContext('loop', $indent);
        if ($this->key) {
            $compiler->popContext($this->key, $indent);
        }
        $compiler->popContext($this->value, $indent);
    }
}

