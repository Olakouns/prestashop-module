<?php
// http://localhost/prestashoptest/odata-module/api/category?category_id=1
use SaintSystems\OData\ODataClient;

class OdataModuleApiCategoryModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        //echo "here i'm started";
        $response = $this->processApiRequest();
        header('Content-Type: application/json');
        //echo json_encode($response);
        exit;
    }

    protected function processApiRequest()
    {

        $context=$this->context;

        if ($this->context->cookie->id_cart)
		{
            echo $this->context->cookie->id_cart;
			// $cart = new Cart($this->context->cookie->id_cart);
		}else {
            echo "Nothing here";
        }

        $categoryId = (int)Tools::getValue('category_id');
        //echo $categoryId;

        //var_dump("olakouns");
        $this->lauchODATAREquest();

        // $newData = array('name' => 'New Category Name');
        // $this->updateCategoryData($categoryId, $newData);
        return array('success' => true, 'message' => 'Category updated successfully');
    }

    protected function updateCategoryData($categoryId, $newData)
    {
        // Construire la requête SQL avec DbQuery
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('category');
        $sql->where('`id_category` = ' . (int)$categoryId);

        // Exécuter la requête et récupérer les données de la catégorie
        $categoryData = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        echo json_encode($categoryData);
        // Vérifier si la catégorie existe
        if (!$categoryData) {
            // Gérer le cas où la catégorie n'existe pas
            echo json_encode(array('error' => 'Category not found'));
            exit;
        }
    }

    protected function lauchODATAREquest()
    {
        $odataServiceUrl = 'https://services.odata.org/V4/TripPinService';

		$odataClient = new ODataClient($odataServiceUrl);
        

		// Retrieve all entities from the "People" Entity Set
		$people = $odataClient->from('People')->get();
		// Or retrieve a specific entity by the Entity ID/Key
		try {
			$person = $odataClient->from('People')->find('russellwhyte');
            echo $person->UserName;
            echo $person->FirstName;
            echo $person->LastName;
            echo $person->Emails[0];
            echo $person->Emails[1];
            echo '\n';
			echo "Hello, I am $person->FirstName ";
		} catch (Exception $e) {
			echo $e->getMessage();
		}

		// Want to only select a few properties/columns?
		// $people = $odataClient->from('People')->select('FirstName','LastName')->where('FirstName','=','Russell')->get();
		//$people = $odataClient->from('People')->select('FirstName','LastName')->get();

        //print_r(count($people));
        //print_r($people);
    }
}
