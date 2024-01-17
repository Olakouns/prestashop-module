<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class OScanModule extends Module
{
    public function __construct()
    {
        $this->name = 'oscanmodule';
        $this->displayName = 'OScanModule';
        $this->version = '1.1.0';
        $this->author = 'Razacki';
        $this->description = 'Demo module of how to scan';
        $this->need_instance = 0;
        $this->bootstrap = false;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        parent::__construct();

        $this->displayName = $this->l('Module de Scan d\'Articles');
        $this->description = $this->l('Ajoutez la fonctionnalité de scan de codes-barres pour ajouter des articles au panier.');
    }

    // Ajoutez ces méthodes dans la classe ScanModule
    public function install()
    {
        return parent::install() &&
        $this->registerHook('displayHeader') &&
        $this->registerHook('moduleRoutes') && // Nouveau hook pour la page de scan
        Configuration::updateValue('oscanmodule', 'my module');
    }

    public function hookDisplayHeader()
    {
      
        $this->context->controller->addJS($this->_path . 'views/js/quagga.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/scanmodule.js');
        $this->context->controller->addCSS($this->_path . 'views/css/style.css');

        
    }

    public function hookModuleRoutes($params)
    {
        return array(
            'module-scanmodule-scanpage' => array(
                'controller' => 'scanpage',
                'rule' => 'scanpage',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'oscanmodule',
                ),
            ),
        );
    }

}
