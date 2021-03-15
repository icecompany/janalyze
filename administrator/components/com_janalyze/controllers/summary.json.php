<?php
defined('_JEXEC') or die;
header("Access-Control-Allow-Origin: https://{$_SERVER['HTTP_HOST']}");
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\AdminController;

class JanalyzeControllerSummary extends AdminController
{
    public function execute($task)
    {
        $model = $this->getModel('Summary', 'JanalyzeModel', []);
        $items = $model->getItems();
        echo new JsonResponse($items);
    }

    public function getModel($name = 'Summary', $prefix = 'JanalyzeModel', $config = [])
    {
		return parent::getModel($name, $prefix, $config);
	}
}
