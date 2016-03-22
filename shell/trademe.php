<?php
require_once 'abstract.php';
class Mage_Shell_Trademe extends Mage_Shell_Abstract
{
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f trademe.php
  help                  This help

USAGE;
    }

    public function run()
    {
    }
}

$trademe = new Mage_Shell_Trademe();
$trademe->run();