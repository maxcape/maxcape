<?php
use \Phalcon\Mvc\View\Engine\Volt;
use \Phalcon\Mvc\ViewBaseInterface;
use \Phalcon\DiInterface;
use \Phalcon\Mvc\View\Engine\Volt\Compiler;

class VoltExtension extends Volt {

    private static $filters = [
        'number_format' => 'number_format',
        'strtotime' 	=> 'strtotime',
        'date' 			=> 'date',
        'chunk' 		=> 'array_chunk',
        'count' 		=> 'count',
        'substr' 		=> 'substr',
        'ucfirst' 		=> 'ucfirst',
        'special_chars' => 'htmlspecialchars',
        'in_array' 		=> 'in_array',
        'array_filter' 	=> 'array_filter',
        'strlen' 		=> 'strlen',
        'explode'       => 'explode',
        'array_keys'    => 'array_keys'
    ];

    private static $functions = [
        'replace'       => 'replace',
        'endsWith'      => 'endsWith',
        'elapsed'       => 'elapsed',
        'limit_string'  => 'limit_string',
        'cleanhtml'     => 'cleanupHtml',
        'modern_number_format' => 'modern_number_format',
        'getFormattedName' => 'getFormattedName',
        'formatRank' => 'getRankFormatted',
        'timeLeft' => 'timeLeft'
    ];

    public function __construct(ViewBaseInterface $view, DiInterface $injector = null) {
        parent::__construct($view, $injector);
    }

    public function addFilters() {
        $compiler = $this->getCompiler();
        foreach (self::$filters as $key => $value) {
            $compiler->addFilter($key, $value);
        }
    }

    public function addFunctions() {
        $compiler = $this->getCompiler();
        foreach (self::$functions as $key => $value) {
            $compiler->addFunction($key, function($key) use ($value) {
                return "Functions::{$value}({$key})";
            });
        }
    }



}