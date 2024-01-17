<?php

use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\ODataClient;
use Spatie\Async\Pool;

require_once __DIR__ . '/../../src/GlobalInterface.php';

class OdataModuleProductSyncModuleFrontController extends ModuleFrontController implements GlobalInterface
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
        // Renvoyer immédiatement une réponse à l'utilisateur
        $this->ajaxRender(json_encode(['success' => true, 'message' => 'Traitement en cours. Veuillez consulter les logs pour les details.']));

        $pageSize = 50;
        $currentPage = 1;
        $pool = Pool::create();
        
        try {
            do {
                $products = self::$odataClient->from('ListeArticle')->take($pageSize)->skip(($currentPage - 1) * $pageSize)->get();

                if (empty($products)) {
                    break;
                }

                foreach ($products as $productData) {
                    if (!isset($productData->No) || empty($productData->No)) {
                        continue;
                    }

                    $pool->add(function () use ($productData) {
                        // Do a thing
                        try {
                            Db::getInstance()->execute('START TRANSACTION');
                            $this->processProduct($productData);
                            Db::getInstance()->execute('COMMIT');
                        } catch (Exception $e) {
                            // En cas d'erreur, annuler toutes les modifications
                            Db::getInstance()->execute('ROLLBACK');
                            PrestaShopLogger::addLog($e->getMessage(), 3);
                        }
                    });  
                    
                    break;
                }

                PrestaShopLogger::addLog('Synchronisation terminée pour la page : ' . $currentPage, 1);

                if($currentPage == 1) {
                    break; 
                }
                $currentPage++;   
                            
            } while (count($products) === $pageSize);
            $pool->wait();
            PrestaShopLogger::addLog('Synchronisation produits terminée : ' . $currentPage, 1);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Erreur lors de la synchronisation : ' . $e->getMessage(), 3);
            //$this->ajaxRender(json_encode(array('success' => false, 'message' => "Erreur lors de la synchronisation : ". $e->getMessage())));
        }

        //$this->ajaxRender(json_encode(array('success' => true, 'message' => "Synchronisation effectuee")));

    }

    /**
     * Traite les données d'un produit.
     * La fonction encapsule la logique de traitement d'un produit, notamment la création du produit lui-même
     * ainsi que des prix spécifiques associés.
     * @param object $productData Les données du produit provenant de la source externe (par exemple, OData).
     * @throws Exception En cas d'erreur lors du traitement du produit, relance l'exception avec un message détaillé.
     */
    private function processProduct($productData)
    {
        try {
            // Logique de traitement du produit ici, par exemple :
            $productId = $this->createProduct($productData);
            $this->createSpecificPrices($productId, $productData);
            // $this->addImageToProduct($productId, "https://internationalpipe.com/wp-content/uploads/2022/01/Stackofsteelpipes.jpg");
        } catch (Exception $e) {
            // Relancez l'exception pour la capturer à un niveau supérieur si nécessaire
            throw new Exception('Erreur lors du traitement du produit : ' . $productData->No . '  - ' . $productData->Description . ' ' . $e->getMessage());
        }
    }

    /**
     * Crée un produit à partir des données fournies.
     * La fonction vérifie d'abord si le produit existe déjà en fonction de sa référence.
     * Si le produit existe, il est mis à jour, sinon un nouveau produit est créé.
     * Les données du produit, telles que le nom, la référence, le prix, etc., sont extraites des données fournies.
     * La catégorie du produit est déterminée à l'aide de la fonction getCategory().
     * @param object $productData Les données du produit provenant de la source externe (par exemple, OData).
     * @return int L'ID du produit créé ou mis à jour.
     */
    private function createProduct($productData)
    {
        $existingProductId = Product::getIdByReference($productData->No);
        $productcategoryId = $this->getCategory($productData);

       
        /*if(!$brandId){
            $brand  = new Manufacturer();
            $brand->name = $productData->Shortcut_Dimension_3_Code;
            $brand->save();
        }*/  

        $product = new Product();

        $brandId = Manufacturer::getIdByName($productData->Shortcut_Dimension_3_Code);
        if ($brandId) {
            # code...
            $product->id_manufacturer = $brandId;
        } else {
            // add some log execption
        }
        
        if ($existingProductId) {
            $product->id =  $existingProductId;
        }
        $product->name = ['1' => $this->replaceSpecialCharacters($productData->Description), '2' =>  $this->replaceSpecialCharacters($productData->Description)];
        $product->manufacturer_name = $productData->Shortcut_Dimension_3_Code;
        $product->description = $productData->Item_Characteristic;
        $product->description_short = $productData->Description_2;
        $product->reference = $productData->No;
        $product->price = $productData->Sales_Price_4;
        $product->id_category_default = $productcategoryId;

        $product->save();
        
        $product->updateCategories([$productcategoryId]);      
        // Retournez l'ID du produit créé
        return $product->id;
    }

    /**
     * Obtient l'identifiant de la catégorie associée aux données du produit.
     * La fonction utilise les informations de catégorie fournies dans les données du produit, en privilégiant
     * d'abord 'Item_Sub_Sub_Category', puis 'Item_Sub_Category', et enfin 'Item_Category_Code' en cas d'absence des précédents.
     * @param object $productData Les données du produit provenant de la source externe (par exemple, OData).
     * @return int L'identifiant de la catégorie dans PrestaShop.
     * @throws Exception En cas d'erreur lors du traitement de la catégorie du produit.
     */
    private function getCategory($productData){
        /* use to get last element of category
        $code = $productData->Item_Category_Code;

        if (isset($productData->Item_Sub_Sub_Category) && !empty($productData->Item_Sub_Sub_Category)) {
            $code = $productData->Item_Sub_Sub_Category;
        } else if(isset($productData->Item_Sub_Category) && !empty($productData->Item_Sub_Category)) {
            $code = $productData->Item_Sub_Category;
        } */

        $code = $productData->Item_Category_Code;

        $categoryId = Db::getInstance()->getValue('
            SELECT id_category
            FROM ' . _DB_PREFIX_ . 'category
            WHERE code = "' . pSQL($code) . '"
        ');                

        if (!$categoryId) {
            throw new Exception('La categorie '. $code .' n\'existe pas.');
        }

        return $categoryId;
    }

    /**
     * Crée ou met à jour les prix spécifiques pour un produit donné.
     * @param int $productId L'identifiant du produit dans PrestaShop.
     * @param object $productData Les données du produit provenant de la source externe (par exemple, OData).
     * @throws Exception En cas d'erreur lors du traitement du produit.
     */
    private function createSpecificPrices($productId, $productData)
    {
        // Logique de création des prix spécifiques ici
        // Utilisez $productId pour lier les prix au produit
        // $myModule = Module::getInstanceByName('odatamodule');
        // $group = Group::searchByName($myModule->customersGroups[0]);

        $pricesArray = [
            'PV1' => 'Sales_Price_1',
            'PV2' => 'Sales_Price_2',
            'PV3' => 'Sales_Price_3',
            'PV4' => 'Sales_Price_4',
        ];

        // Boucle pour parcourir le tableau
        foreach ($pricesArray as $groupKey  => $priceKey) {
            try {
                $group = Group::searchByName($groupKey);

                if (count($group) ==  0) {
                    throw new Exception("Erreur lors du traitement du produit : le groupe $groupKey n'existe pas");
                }
                $specificPrice = new SpecificPrice();
                // todo : check if Specific Price exist and set the id by default
                $existingSpecificPrice = SpecificPrice::exists($productId, 0, 0, $group['id_group'], 0, 0, 0, 1,  SpecificPrice::ORDER_DEFAULT_DATE, SpecificPrice::ORDER_DEFAULT_DATE);
        
                if ($existingSpecificPrice) {
                    // Le prix spécifique existe, mettez à jour l'ID existant
                    $specificPrice->id = $existingSpecificPrice;
                }

                $specificPrice->id_product = $productId;
                $specificPrice->id_product_attribute = 0;
                $specificPrice->id_group = $group['id_group'];
                $specificPrice->id_shop = 0;   // get shop ID
                $specificPrice->id_cart = 0; 
                $specificPrice->id_country = 0; 
                $specificPrice->id_currency = 0; 
                $specificPrice->id_customer = 0; 
                $specificPrice->from_quantity = 1; 
                $specificPrice->reduction_tax = 0; 
                $specificPrice->from = SpecificPrice::ORDER_DEFAULT_DATE; 
                $specificPrice->to =  SpecificPrice::ORDER_DEFAULT_DATE; 
        
                $specificPrice->price = $productData[$priceKey];
                $specificPrice->reduction_type = 'amount';
                $specificPrice->reduction = 0; 
        
                $specificPrice->save();
            } catch (Exception $e) {
                PrestaShopLogger::addLog('Erreur lors de la synchronisation des prix du produit : ' . $productData->No . ' ' . $e->getMessage(), 3);
                throw new Exception('Erreur lors de la synchronisation des prix du produit : ' . $productData->No . ' ' . $e->getMessage());
                break;
            }
        }
    }

    private function addImageToProduct($productId, $imgUrl){
       
        $product = new Product($productId);
        $productCover = Product::getCover($productId);

        if (count($productCover) > 0) {
            # code...
            $product->deleteImages();
        }
       
        $image = new Image();           
        $shops = Shop::getShops(true, null, true);
        $image->id_product = $productId;
        $image->position = Image::getHighestPosition($productId) + 1;
        $image->cover = true;
        
        if (($image->validateFields(false, true)) === true && ($image->validateFieldsLang(false, true)) === true && $image->add()) {
            $image->associateTo($shops);
            if (!$this->uploadImage($productId, $image->id, $imgUrl)) {
                $image->delete();
            }
        }
    }

    private function uploadImage($id_entity, $id_image = null, $imgUrl) {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
        $image_obj = new Image((int)$id_image);
        $path = $image_obj->getPathForCreation();
        $imgUrl = str_replace(' ', '%20', trim($imgUrl));
        // Evaluate the memory required to resize the image: if it's too big we can't resize it.
        if (!ImageManager::checkImageMemoryLimit($imgUrl)) {
            return false;
        }
        if (@copy($imgUrl, $tmpfile)) {
            ImageManager::resize($tmpfile, $path . '.jpg');
            $images_types = ImageType::getImagesTypes('products');
            foreach ($images_types as $image_type) {
                ImageManager::resize($tmpfile, $path . '-' . stripslashes($image_type['name']) . '.jpg', $image_type['width'], $image_type['height']);
                if (in_array($image_type['id_image_type'], $watermark_types)) {
                Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                }
            }
        } else {
            unlink($tmpfile);
            return false;
        }
        unlink($tmpfile);
        return true;
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