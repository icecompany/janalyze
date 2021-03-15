<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class JanalyzeModelSummary extends ListModel
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				's.id',
			];
		}

		parent::__construct($config);

		$this->checked = [
		    '5' => false,
            '11' => true,
            '12' => true,
            '6' => true,
            '30' => true,
        ];
	}

	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query
            ->select("distinct s.pavilionID, pv.title as pavilion, c.projectID, p.title as project, p.familyID, f.title as family")
            ->from($db->qn('#__mkv_stands') . ' s')
            ->leftJoin($db->qn('#__mkv_stand_pavilions') . ' pv on s.pavilionID = pv.id')
            ->leftJoin($db->qn('#__mkv_contract_stands') . ' cs on s.id = cs.standID')
            ->leftJoin($db->qn('#__mkv_contract_items') . ' ci on cs.id = ci.contractStandID')
            ->leftJoin($db->qn('#__mkv_contracts') . ' c on ci.contractID = c.id')
            ->leftJoin($db->qn('#__mkv_projects') . ' p on c.projectID = p.id')
            ->leftJoin($db->qn('#__mkv_project_families') . ' f on p.familyID = f.id')
            ->where("s.pavilionID is not null and c.projectID is not null");

        $this->setState('list.limit', 0);

		return $query;
	}

	public function getItems()
    {
        $items = parent::getItems();
        $result = [];

        foreach ($items as $item) {
            if (!isset($result[$item->familyID])) $result[$item->familyID] = ['title' => $item->family, 'projects' => [], 'pavilions' => []];
            if (!isset($result[$item->familyID]['projects'][$item->projectID])) $result[$item->familyID]['projects'][$item->projectID] = ['title' => $item->project, 'checked' => $this->checked[$item->projectID]];
            if (!isset($result[$item->familyID]['pavilions'][$item->pavilionID])) $result[$item->familyID]['pavilions'][$item->pavilionID] = ['title' => $item->pavilion];
        }

        return $result;
    }

    private $checked;
}
