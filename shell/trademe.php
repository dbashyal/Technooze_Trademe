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
        $model = Mage::getModel('trademe/trademeApiRequest');
        $credentials = $model->getOauthCredentials();

        var_dump($credentials);
    }
}

$trademe = new Mage_Shell_Trademe();
$trademe->run();