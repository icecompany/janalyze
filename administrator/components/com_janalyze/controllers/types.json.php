<?php
defined('_JEXEC') or die;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\AdminController;

class JanalyzeControllerTypes extends AdminController
{
    public function execute($task)
    {
        $familyID = $this->input->getInt('familyID');
        $excludeID = $this->input->get('excludeID');
        $commercial = $this->input->getString('commercial', 'commercial');
        $square_type = $this->input->getInt('square_type');
        $model = $this->getModel('Types', 'JanalyzeModel', ['familyID' => $familyID, 'excludeID' => $excludeID, 'square_type' => $square_type, 'commercial' => $commercial]);

        $json = $model->getItems();
        echo new JsonResponse($json);
    }

    public function getModel($name = 'Types', $prefix = 'JanalyzeModel', $config = [])
    {
		return parent::getModel($name, $prefix, $config);
	}
}
