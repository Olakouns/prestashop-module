<?php 

if (!defined('_PS_VERSION_')) {
    exit;
}

// not required here
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}


class ORealSyncModule extends Module
{


    public function __construct()
    {
        $this->name = 'orealsyncmodule';
        $this->displayName = 'OReal SyncModule';
        $this->version = '1.1.0';
        $this->author = 'Razacki';
        $this->description = 'Demo module of how to mak real synch in prestashop';
        $this->need_instance = 0;
        $this->bootstrap = false;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        parent::__construct();

        $this->displayName = $this->l('ORealSync');
        $this->description = $this->l('ORealSync module description');
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install() && $this->registerHook('actionPresentProduct');
    }



    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

   
    public function hookActionPresentProduct($params) {
        //die(print_r($params));
       // die(Tools::getValue('id_product'));

        $product = new Product(Tools::getValue('id_product'));
        $product->price = 12;
        $product->save();
        PrestaShopLogger::addLog('Product presentation : ', 1);
    }
}