<?php

use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\ODataClient;
require_once __DIR__ . '/../../src/GlobalInterface.php';

class OdataModuleManufacturerSyncModuleFrontController extends ModuleFrontController implements GlobalInterface
{
    private static $odataClient;
    public $odataServiceUrl = "http://154.73.175.22:35048/MCOTEST/ODataV4/Company('STE METALCO')";

    public function __construct()
    {
        parent::__construct();

        // Initialiser $odataClient s'il n'a pas encore été initialisé
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
        // Votre logique d'affichage ici
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

        // Récupérer toutes les entités de l'ensemble d'entités "Category"        
        try {
            $brands = self::$odataClient->from('ListeDesMarques')->get();
         
            Db::getInstance()->execute('START TRANSACTION');

            foreach ($brands as $brand) {
                $brandNew  = new Manufacturer();
                $brandNew->name = $brand->Code;
                $brandNew->active = true;
                $brandNew->description = $brand->Name_WS;
                $brandNew->save();
            }
            Db::getInstance()->execute('COMMIT');
            $this->ajaxRender(json_encode(array('success' => true, 'message' => "Opération terminée avec succès")));
        }catch(Exception $e){
            Db::getInstance()->execute('ROLLBACK');
            PrestaShopLogger::addLog("Erreur : " . $e->getMessage(), 3);
            $this->ajaxRender(json_encode(array('success' => false, 'message' => $e->getMessage())));
        }
    }

    public function processPostRequest()
    {
        // $postData = Tools::file_get_contents('php://input');
        // $data = json_decode($postData, true);
        // $this->ajaxRender(json_encode(array('request' => 'POST REQUEST HERE')));
    }
}