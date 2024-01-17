<?php

use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\ODataClient;
require_once __DIR__ . '/../../src/GlobalInterface.php';

class OdataModuleCategorySyncModuleFrontController extends ModuleFrontController implements GlobalInterface
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

        // if (!$this->context->employee || !$this->isEmployeeAuthenticated()) {
        if (!$this->isEmployeeAuthenticated()) {
            $this->sendUnauthorizedHeader();
            exit;
        }

        $this->processRequest();
    }

    private function isEmployeeAuthenticated()
    {
        $username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
        $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

        if (empty($username) || empty($password)) {
            return false;
        }
        $employee = new Employee();
        $result = $employee->getByEmail($username, $password); 
        if($result){
            $this->context->employee = $result;
        }      
        return $result ? true : false;
    }


    private function sendUnauthorizedHeader()
    {
        header('WWW-Authenticate: Basic realm="MyModule"');
        header('HTTP/1.0 401 Unauthorized');
        //$this->ajaxRender(json_encode(['success' => true, 'message' => 'Accès refusé. Veuillez fournir des identifiants valides pour accéder à cette ressource.']));
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
        // Renvoyer immédiatement une réponse à l'utilisateur
        $this->ajaxRender(json_encode(['success' => true, 'message' => 'Processing in progress. Please see logs for details.']));

        // Récupérer toutes les entités de l'ensemble d'entités "Category"
        try {

            PrestaShopLogger::addLog("Synchronization startup", 1, 200, "Category");
            $categories = self::$odataClient->from('Categories')->get();
            $hasError = false;
            

            foreach ($categories as $categoryData) {
                // Extraire les informations pertinentes
                try {
                    $code = $categoryData['Code'];
                    $description = $categoryData['Description'];
                    // Vérifier si la catégorie existe déjà dans la base de données
                    $categoryId = Db::getInstance()->getValue('
                        SELECT id_category
                        FROM ' . _DB_PREFIX_ . 'category
                        WHERE code = "' . pSQL($code) . '"
                    ');                
        
                    if (!$categoryId) {
                        $category = new Category();
                        $category->name = ['1' => $description, '2' => $description];
                        $category->id_parent = 2;
                        $category->link_rewrite = ['1' => Tools::str2url($description), '2' => Tools::str2url($description)];
                        $category->add();
        
                        // Récupérer l'identifiant de la catégorie ajoutée
                        $categoryId = $category->id;    
                        Db::getInstance()->update('category', ['code' => pSQL($code)], 'id_category = ' . (int)$categoryId);
                    } else {
                        $category = new Category((int)$categoryId);
                        $category->id_parent = 2;
                        $category->name = ['1' => $description, '2' => $description];
                        $category->update();
                    }
                } catch (Exception $e) {
                    $hasError = true;
                    PrestaShopLogger::addLog($categoryData['Description'] . " Balloon category synchronization failure", 3, null, "Category", $categoryData['Code']);
                }              
    
            }
            PrestaShopLogger::addLog("Synchronization complete", $hasError ? 2 : 1, $hasError ? 400 : 200, "Category");
        }catch(Exception $e){
            PrestaShopLogger::addLog("Error : " . $e->getMessage(), 3, null, "Category");
        }
    }

    public function processPostRequest()
    {
        // $postData = Tools::file_get_contents('php://input');
        // $data = json_decode($postData, true);
        // $this->ajaxRender(json_encode(array('request' => 'POST REQUEST HERE')));
    }
}