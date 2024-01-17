<?php 

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}


class OdataModule extends Module
{

    public $customersGroups = ['PV1', 'PV2', 'PV3', 'PV4', 'PV5'];

    public function __construct()
    {
        $this->name = 'odatamodule';
        $this->displayName = 'ODATA Module';
        $this->version = '1.1.0';
        $this->author = 'Razacki';
        $this->description = 'Demo module of how to add odata in prestashop';
        $this->need_instance = 0;
        $this->bootstrap = false;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        parent::__construct();

        $this->displayName = $this->l('Odata');
        $this->description = $this->l('Odata module description');
    }

    /**
     * @return bool
     */
    public function install()
    {
        // Ajouter la colonne 'code' à la table 'category' si elle n'existe pas encore
        $result = Db::getInstance()->executeS("SHOW COLUMNS FROM " . _DB_PREFIX_ . "category LIKE 'code'");
        $testValue = empty($result);
        if ($testValue == 1) {
            // La colonne 'code' n'existe pas, l'ajouter
            Db::getInstance()->execute("ALTER TABLE " . _DB_PREFIX_ . "category ADD `code` VARCHAR(255) DEFAULT NULL");
        }

        if (!$this->createGroups()) {
            return false;
        }

        return parent::install() && $this->registerHook('moduleRoutes') && $this->registerHook('actionPresentProduct');
    }

    /**
     * @return bool
     */
    public function createGroups()
    {
        Db::getInstance()->execute('START TRANSACTION');
        try {
            foreach ($this->customersGroups as $groupName) {
                $group = new Group();
                $group->name = ['1' => $groupName, '2' => $groupName];
                $group->reduction = 0;
                $group->price_display_method = 0;
                $group->add();
            }
            Db::getInstance()->execute('COMMIT');
        }catch(Exception $e){
            Db::getInstance()->execute('ROLLBACK');
            Tools::displayError('Impossible de creer les groupes pour les clients');
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    private function deleteGroups()
    {
        Db::getInstance()->execute('START TRANSACTION');
        try {
            foreach ($this->customersGroups as $groupName) {
                $groupId = Group::searchByName($groupName);

                if (count($groupId) > 0) {
                    $groupObj = new Group($groupId['id_group']);
                    $groupObj->delete();
                }
            }
            Db::getInstance()->execute('COMMIT');
        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            Tools::displayError('Impossible de supprimer les groupes pour les clients ' . $e->getMessage());
        }
        return true;
    }
    /**
     * @return bool
     */
    public function uninstall()
    {
        // Supprimer la colonne 'code' de la table 'category' si elle existe
        $result = Db::getInstance()->executeS("SHOW COLUMNS FROM " . _DB_PREFIX_ . "category LIKE 'code'");
        $testValue = !empty($result);
        if ($testValue == 1) {
            // La colonne 'code' existe, la supprimer
            Db::getInstance()->execute("ALTER TABLE " . _DB_PREFIX_ . "category DROP `code`");
        }

        if (!$this->deleteGroups()) {
            return false;
        }

        //$this->deleteGroups();
        // Appeler la méthode uninstall de la classe parente pour compléter la désinstallation
        return parent::uninstall();
    }

    public function hookModuleRoutes($params)
    {
        return array(
            'odata_module_api_category' => array(
                'controller' => 'apiCategory',
                'rule' => 'odata-module/api/category',
                'keywords' => array(
                    'category_id' => array('regexp' => '[0-9]+', 'param' => 'category_id'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'OdataModule',
                ),
            ),
            'sync_data' => array(
                'controller' => 'CategorySync',
                'rule' => 'category-sync',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'OdataModule'
                ),
            ),
            'product_sync' => array(
                'controller' => 'ProductSync',
                'rule' => 'product-sync',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'OdataModule'
                ),
            ),
            'brand_sync' => array(
                'controller' => 'ManufacturerSync',
                'rule' => 'brand-sync',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'OdataModule'
                ),
            ),
            'stock_sync' => array(
                'controller' => 'StockSync',
                'rule' => 'stock-sync',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'OdataModule'
                ),
            )
        );
    }

    public function hookActionPresentProduct($params) {
        PrestaShopLogger::addLog('Product presentation : ', 1);
    }
}