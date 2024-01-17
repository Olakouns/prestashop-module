<?php

use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\ODataClient;

require_once __DIR__ . '/../../src/GlobalInterface.php';

class OdataModuleStockSyncModuleFrontController extends ModuleFrontController implements GlobalInterface
{
    public static $odataClient;
    public $odataServiceUrl = "http://154.73.175.22:35048/MCOTEST/ODataV4/Company('STE METALCO')";

    public function __construct()
    {
        parent::__construct();
        if (!self::$odataClient) {
            $httpProvider = new GuzzleHttpProvider();
            $httpProvider->setExtraOptions( [
                'auth' => ['testwebservice@metalco.local', 'WebS@DMc2024', 'ntlm'],
            ]);
            self::$odataClient = new ODataClient($this->odataServiceUrl, null, $httpProvider);
        }
    }
    
    public function initContent()
    {
        parent::initContent();
        $this->checkWebServiceAuthentication();        
    }

    public function checkWebServiceAuthentication()
    {
        $this->processRequest();
    }

    public function display()
    {       
    }

    public function processRequest()
    {
        $method = Tools::strtolower($_SERVER['REQUEST_METHOD']);
        switch ($method) {
            case 'get':
                $this->processGetRequest();
                break;
            case 'post':
                $this->processPostRequest();
                break;
            default:
                $this->ajaxRender(json_encode(array('error' => 'Méthode non supportée')));
                break;
        }
    }

    /**
     * Logique pour traiter une requête GET (lecture de données)
     */
    public function processGetRequest()
    {
        
        try {
            $code = "ZV012A";
            $existingProductId = Product::getIdByReference($code);
            $existingProductAttributes  = Product::getAttributesInformationsByProduct($existingProductId);

            print_r($existingProductAttributes);

            if (!$existingProductId) {
                throw new Exception("L'article '. $code .' n\'existe pas.");
            }

            StockAvailable::setQuantity($existingProductId, 0 , 200);

            PrestaShopLogger::addLog('Synchronisation du stock terminée : ', 1);
            $this->ajaxRender(json_encode(array('success' => true, 'message' => "Synchronisation effectuee")));
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Erreur lors de la synchronisation : ' . $e->getMessage(), 3);
            $this->ajaxRender(json_encode(array('success' => false, 'message' => "Une erreur s'est produite")));
        }
    }

    public function processPostRequest()
    {
        $this->ajaxRender(json_encode(array('success' => true, 'message' => 'Post request from api request')));
    }

    function replaceSpecialCharacters($inputString) {
        $specialCharacters = ['<', '>', ';', '=', '#', '{', '}'];
        $cleanedString = str_replace($specialCharacters, ' ', $inputString);
        return $cleanedString;
    }
}