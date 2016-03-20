<?php
require_once APPPATH . 'third_party/vendor/autoload.php';

class CsvParser
{
    public function parse($file)
    {
        //TODO: Validations
        $parser = \KzykHys\CsvParser\CsvParser::fromFile($file);
        return $parser->parse();
    }
}