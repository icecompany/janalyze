<?php
defined('_JEXEC') or die;
header("Access-Control-Allow-Origin: https://{$_SERVER['HTTP_HOST']}");
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\AdminController;

class JanalyzeControllerItems extends AdminController
{
    public function execute($task)
    {
        $familyID = $this->input->getInt('familyID');
        $projectID = $this->input->get('projectID');
        $model = $this->getModel('Items', 'JanalyzeModel', ['familyID' => $familyID, 'projectID' => $projectID]);
        $items = $model->getItems();
        echo new JsonResponse($items);
    }

    public function getModel($name = 'Items', $prefix = 'JanalyzeModel', $config = [])
    {
		return parent::getModel($name, $prefix, $config);
	}
}
