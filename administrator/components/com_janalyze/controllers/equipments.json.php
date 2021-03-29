<?php
defined('_JEXEC') or die;
header("Access-Control-Allow-Origin: https://{$_SERVER['HTTP_HOST']}");
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\AdminController;

class JanalyzeControllerEquipments extends AdminController
{
    public function execute($task)
    {
        $familyID = $this->input->getInt('familyID');
        $projectID = $this->input->get('projectID');
        $pavilionID = $this->input->get('pavilionID');
        $model = $this->getModel('Equipments', 'JanalyzeModel', ['familyID' => $familyID, 'projectID' => $projectID, 'pavilionID' => $pavilionID]);
        $items = $model->getItems();
        echo new JsonResponse($items);
    }

    public function getModel($name = 'Equipments', $prefix = 'JanalyzeModel', $config = [])
    {
		return parent::getModel($name, $prefix, $config);
	}
}
