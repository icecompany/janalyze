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
				'id',
			];
		}

		$this->familyID = $config['familyID'] ?? null;
		$this->excludeID = $config[$this->excludeID] ?? null;
		$this->export = $config[$this->export] ?? false;
		$this->floor = $config[$this->floor] ?? false;

		parent::__construct($config);
	}

	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query
            ->select("c.projectID")
            ->select("p.title as project")
            ->select("if(c.status = 9, 'non_commercial', if(c.is_sponsor = 1, 'sponsor', if (pi.square_type in (1, 2, 5, 9), 'pavilion', if(pi.square_type in (3, 4, 6), 'street', if (pi.square_type in (7, 8), '2th_floor', 'other'))))) as tip")
            ->select("ifnull(sum(ci.value),0) as square")
            ->select("ifnull(sum(if(c.currency = 'usd', ci.amount * p.course_usd, if(c.currency = 'eur', ci.amount * p.course_eur, ci.amount))),0) as money")
            ->from($db->qn('#__mkv_contract_items') . ' ci')
            ->leftJoin($db->qn('#__mkv_price_items') . ' pi on ci.itemID = pi.id')
            ->leftJoin($db->qn('s7vi9_mkv_contracts') . ' c on ci.contractID = c.id')
            ->leftJoin($db->qn('s7vi9_mkv_projects') . ' p on c.projectID = p.id')
            ->where("pi.square_type is not null")
            ->group("c.projectID, tip")
            ->order("c.projectID");

		$floor = (!$this->floor) ? 'not' : '';
		$query->where("pi.square_type {$floor} in (7, 8)");

		if (is_numeric($this->familyID)) $query->where("p.familyID = {$db->q($this->familyID)}");
		if (!empty($this->excludeID)) {
		    if (is_numeric($this->excludeID)) {
		        $query->where("c.projectID != {$db->q($this->excludeID)}");
            }
		    if (is_array($this->excludeID) && count($this->excludeID) > 1) {
		        $exclude = explode(', ', $this->excludeID);
		        if (!empty($exclude)) $query->where("c.projectID not in ({$exclude})");
            }
        }

		return $query;
	}

	public function getItems()
    {
        $items = parent::getItems();
        $types['2th_floor' ]= JText::sprintf('COM_JANALYZE_SUMMARY_TYPE_2TH_FLOOR');
        if (!$this->floor) {
            $types['pavilion'] = JText::sprintf('COM_JANALYZE_SUMMARY_TYPE_PAVILION');
            $types['street'] = JText::sprintf('COM_JANALYZE_SUMMARY_TYPE_STREET');
            $types['sponsor'] = JText::sprintf('COM_JANALYZE_SUMMARY_TYPE_SPONSOR');
            $types['non_commercial'] = JText::sprintf('COM_JANALYZE_SUMMARY_TYPE_NON_COMMERCIAL');
            unset($types['2th_floor']);
        }
        $result = ['projects' => [], 'types' => $types, 'data' => [], 'total' => []];
        foreach ($items as $item) {
            if (!isset($result['projects'][$item->projectID])) $result['projects'][$item->projectID] = $item->project;
            $square = ($item->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format((float) $item->square, 2, ',', ' '));
            $money = ($item->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format((float) $item->money, 2, ',', ' '));
            $result['data'][$item->tip][$item->projectID] = ['square' => $square, 'money' => $money];
            if (!isset($result['total'][$item->projectID])) $result['total'][$item->projectID] = ['square' => 0, 'money' => 0];
            $result['total'][$item->projectID]['square'] += $item->square;
            $result['total'][$item->projectID]['money'] += $item->money;
        }
        if (!$this->export) {
            foreach (array_keys($result['total']) as $projectID) {
                $result['total'][$projectID]['square'] = JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format((float) $result['total'][$projectID]['square'], 2, ',', ' '));
                $result['total'][$projectID]['money'] = JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format((float) $result['total'][$projectID]['money'], 2, ',', ' '));
            }
        }
        return $result;
    }

    private $familyID, $excludeID, $floor, $export;
}
