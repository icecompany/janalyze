<?php
defined('_JEXEC') or die;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\AdminController;

class JanalyzeControllerSummary extends AdminController
{
    public function execute($task)
    {
        $model = $this->getModel();
        echo new JsonResponse($model->getItems());
    }

    public function getModel($name = 'Summary', $prefix = 'JanalyzeModel', $config = [])
    {
        $familyID = $this->input->getInt('familyID');
        $excludeID = $this->input->get('excludeID');
        $floor = $this->input->getBool('2th_floor');
		return parent::getModel($name, $prefix, ['familyID' => $familyID, 'excludeID' => $excludeID, 'floor' => $floor]);
	}
}
