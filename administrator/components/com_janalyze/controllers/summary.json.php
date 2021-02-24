<?php
defined('_JEXEC') or die;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\AdminController;

class JanalyzeControllerSummary extends AdminController
{
    public function execute($task)
    {
        $familyID = $this->input->getInt('familyID');
        $projectID = $this->input->get('projectID');
        $model = $this->getModel('Summary', 'JanalyzeModel', ['familyID' => $familyID, 'projectID' => $projectID, 'floor' => false]);
        $summary = $model->getItems();
        $model = $this->getModel('Summary', 'JanalyzeModel', ['familyID' => $familyID, 'projectID' => $projectID, 'floor' => true]);
        $floor = $model->getItems();
        $model = $this->getModel('Squares', 'JanalyzeModel', ['familyID' => $familyID, 'projectID' => $projectID]);
        $squares = $model->getItems();

        $json = ['summary' => $summary, 'floor' => $floor, 'squares' => $squares];
        echo new JsonResponse($json);
    }

    public function getModel($name = 'Summary', $prefix = 'JanalyzeModel', $config = [])
    {
		return parent::getModel($name, $prefix, $config);
	}
}
