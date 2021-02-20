<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class JanalyzeModelTypes extends ListModel
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
		$this->excludeID = $config['excludeID'] ?? null;
		$this->export = $config['export'] ?? false;
		$this->square = $config['square_type'] ?? false;
		$this->commercial = $config['commercial'] ?? false;

		parent::__construct($config);
	}

	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query
            ->select("c.projectID, c.companyID")
            ->select("e.title as company")
            ->select("p.title as project")
            ->select("ifnull(sum(ci.value),0) as square")
            ->select("ifnull(sum(if(c.currency = 'usd', ci.amount * p.course_usd, if(c.currency = 'eur', ci.amount * p.course_eur, ci.amount))),0) as money")
            ->from($db->qn('#__mkv_contract_items') . ' ci')
            ->leftJoin($db->qn('#__mkv_price_items') . ' pi on ci.itemID = pi.id')
            ->leftJoin($db->qn('#__mkv_contracts') . ' c on ci.contractID = c.id')
            ->leftJoin($db->qn('#__mkv_projects') . ' p on c.projectID = p.id')
            ->leftJoin($db->qn('#__mkv_companies') . ' e on c.companyID = e.id')
            ->group("c.projectID, c.companyID")
            ->order("c.projectID, e.title");

		if (is_numeric($this->square)) {
		    $query->where("pi.square_type = {$db->q($this->square)}");
        }

		if (!empty($this->commercial)) {
		    if ($this->commercial === 'commercial') {
		        $query
                    ->where("c.status != 9")
                    ->where("c.is_sponsor != 1");
            }
		    if ($this->commercial === 'sponsor') {
		        $query
                    ->where("c.is_sponsor = 1");
            }
		    if ($this->commercial === 'non_commercial') {
		        $query
                    ->where("c.status = 9");
            }
        }

		if (is_numeric($this->familyID)) $query->where("p.familyID = {$db->q($this->familyID)}");

		if (!empty($this->excludeID)) {
		    if (is_numeric($this->excludeID)) {
		        $query->where("c.projectID != {$db->q($this->excludeID)}");
            }
		    if (is_array($this->excludeID)) {
		        $exclude = implode(', ', $this->excludeID);
		        if (!empty($exclude)) $query->where("c.projectID not in ({$exclude})");
            }
        }

        $this->setState('list.limit', 0);

		return $query;
	}

	public function getItems()
    {
        $items = parent::getItems();

        $result = ['projects' => [], 'companies' => [], 'data' => [], 'total' => []];
        foreach ($items as $item) {
            if (!isset($result['projects'][$item->projectID])) $result['projects'][$item->projectID] = $item->project;
            if (!isset($result['companies'][$item->companyID])) $result['companies'][$item->companyID] = $item->company;
            $square = ($this->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format((float) $item->square, 2, ',', ' '));
            $money = ($this->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format((float) $item->money, 2, ',', ' '));
            $result['data'][$item->companyID][$item->projectID] = ['square' => $square, 'money' => $money];
            if (!isset($result['total'][$item->projectID])) $result['total'][$item->projectID] = ['square' => 0, 'money' => 0];
            $result['total'][$item->projectID]['square'] += $item->square;
            $result['total'][$item->projectID]['money'] += $item->money;
        }
        if (!$this->export) {
            foreach (array_keys($result['projects']) as $projectID) {
                $result['total'][$projectID]['square'] = JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format((float)$result['total'][$projectID]['square'], 2, ',', ' '));
                $result['total'][$projectID]['money'] = JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format((float)$result['total'][$projectID]['money'], 2, ',', ' '));
            }
        }
        natcasesort($result['companies']);
        return $result;
    }

    private $familyID, $excludeID, $export, $square, $commercial;
}
