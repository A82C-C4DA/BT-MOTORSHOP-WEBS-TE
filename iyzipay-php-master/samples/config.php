<?php
require_once(dirname(__DIR__).'/IyzipayBootstrap.php');

IyzipayBootstrap::init();

class Config
{
    public static function options()
    {	
    	include 'panel/db-ayar.php';
		$iyzico = $db->query("SELECT * FROM iyzico_api LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $options = new \Iyzipay\Options();
        $options->setApiKey($iyzico['setApiKey']);
        $options->setSecretKey($iyzico['setSecretKey']);
        $options->setBaseUrl('https://api.iyzipay.com');

        return $options;
    }
}