<?php
use Flow\Loader;

/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 01/08/14.08.2014 13:51
 */

class BaseTest extends TestCase
{
    protected function getTemplate()
    {
        return new Loader([
            'source' => __DIR__ . '/../templates',
            'target' => __DIR__ . '/../cache',
        ]);
    }

    public function providerLoadFromString()
    {
        return [
            ['hello {{ world }}', 'hello world', ['world' => 'world']],
            ['hello {{ world }}', 'hello ', []],
            ['{% block content %}hello {{ world }}{% endblock %}', 'hello world', ['world' => 'world']],

            ['{% extends "base.html" %}', '1', []],
            ['{% extends "base.html" %}{% block content %}2{% endblock %}', '2', []],
        ];
    }

    /**
     * @dataProvider providerLoadFromString
     */
    public function testLoadFromString($template, $result, $data)
    {
        $tpl = $this->getTemplate()->loadFromString($template);
        $this->assertEquals($tpl->render($data), $result);
    }

    public function providerLoad()
    {
        return [
            ['main.html', '1', []],
            ['main.html', '12', ['data' => 2]],
        ];
    }

    /**
     * @dataProvider providerLoad
     */
    public function testLoad($template, $result, $data)
    {
        $tpl = $this->getTemplate()->load($template);
        $this->assertEquals($tpl->render($data), $result);
    }
}