<?php 
class OScanModuleScanPageModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        
        // Logique du scan et ajout au panier ici
        parent::initContent();

        $this->context->smarty->assign(array(
            'module_name' => $this->module->displayName,
            'content' => 'Contenu de la page de scan',
        ));

        $this->setTemplate('module:oscanmodule/views/templates/front/displayScanPage.tpl');
        // die("oii");
       
    }

    // Ajoutez cette méthode dans la classe ScanController
    // public function display()
    // {
    //     $this->setTemplate('module:oscanmodule/views/templates/front/displayScanPage.tpl');
    // }

}

//   /**
//      * @see FrontController::initContent()
//      */
//     public function initContent()
//     {
//         parent::initContent();

//         $this->context->smarty->assign('variables', $this->variables);
//         $this->setTemplate('module:ps_emailsubscription/views/templates/front/subscription_execution.tpl');
//     }
