<?php
defined('_JEXEC') or die;
header("Access-Control-Allow-Origin: https://{$_SERVER['HTTP_HOST']}");
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\AdminController;

class JanalyzeControllerEquipment extends AdminController
{
    public function execute($task)
    {
        $familyID = $this->input->getInt('familyID');
        $projectID = $this->input->get('projectID');
        $pavilionID = $this->input->get('pavilionID');
        $finance_type = $this->input->get('finance_type');
        $equipmentID = $this->input->get('equipmentID');
        $model = $this->getModel('Equipment', 'JanalyzeModel', ['familyID' => $familyID, 'projectID' => $projectID, 'pavilionID' => $pavilionID, 'finance_type' => $finance_type, 'equipmentID' => $equipmentID]);
        $items = $model->getItems();
        echo new JsonResponse($items);
    }

    public function getModel($name = 'Equipment', $prefix = 'JanalyzeModel', $config = [])
    {
		return parent::getModel($name, $prefix, $config);
	}
}
