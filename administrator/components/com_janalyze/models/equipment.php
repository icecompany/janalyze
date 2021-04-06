<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class JanalyzeModelEquipment extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
            ];
        }

        $this->familyID = $config['familyID'] ?? null;
        $this->projectID = $config['projectID'] ?? null;
        $this->export = $config['export'] ?? false;
        $this->pavilionID = $config['pavilionID'] ?? false;
        $this->finance_type = $config['finance_type'] ?? null;
        $this->equipmentID = $config['equipmentID'] ?? null;

        $this->values = [
            'with_percents' => [
                'square' => 0,
                'money' => 0,
                'percent_square' => 0,
                'percent_money' => 0,
            ],
            'without_percents' => [
                'square' => 0,
                'money' => 0,
            ]
        ];

        parent::__construct($config);
    }

    protected function _getListQuery()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select("c.projectID, p.title as project")
            ->select("if (c.status = 9, 'non_commerce', if(c.is_sponsor = 1, 'sponsor', 'commerce')) as finance_type")
            ->select("c.companyID, e.title as company")
            ->select("ifnull(sum(ci.value),0) as square")
            ->select("ifnull(sum(if(c.currency = 'usd', ci.amount * p.course_usd, if(c.currency = 'eur', ci.amount * p.course_eur, ci.amount))),0) as money")
            ->from("`#__mkv_contract_items` ci")
            ->leftJoin("`#__mkv_price_items` pi on ci.itemID = pi.id")
            ->leftJoin("`#__mkv_contracts` c on ci.contractID = c.id")
            ->leftJoin("`#__mkv_projects` p on c.projectID = p.id")
            ->leftJoin("`#__mkv_companies` e on c.companyID = e.id")
            ->where("c.status not in (7, 8)")
            ->group("c.projectID, c.companyID, finance_type")
            ->order("c.projectID");

        if (!empty($this->projectID)) {
            if (is_numeric($this->projectID)) {
                $query->where("c.projectID = {$db->q($this->projectID)}");
            }
            if (is_array($this->projectID)) {
                $project = implode(', ', $this->projectID);
                if (!empty($project)) $query->where("c.projectID in ({$project})");
            }
        } else {
            $query->where("p.familyID = {$db->q($this->familyID)}");
        }
        if (!empty($this->pavilionID)) {
            $query
                ->leftJoin("#__mkv_contract_stands cs on ci.contractStandID = cs.id")
                ->leftJoin("#__mkv_stands s on cs.standID = s.id")
                ->where("s.pavilionID = {$db->q($this->pavilionID)}");
        }
        if ($this->finance_type == 'commerce' || $this->finance_type == 'non_commerce' || $this->finance_type == 'sponsor') {
            $query->having("finance_type = {$db->q($this->finance_type)}");
        }
        if (is_numeric($this->equipmentID)) {
            $query->where("pi.equipmentID = {$db->q($this->equipmentID)}");
        }

        $this->setState('list.limit', 0);

        return $query;
    }

    public function getItems()
    {
        $items = parent::getItems();
        $result = $this->init($items);
        foreach ($items as $item) {
            $result['data'][$item->companyID][$item->projectID]['square'] = (float)$item->square;
            $result['data'][$item->companyID][$item->projectID]['money'] = (float)$item->money;
            $result['total'][$item->projectID]['square'] += (float)$item->square;
            $result['total'][$item->projectID]['money'] += (float)$item->money;
        }
        $result = $this->getDynamic($result);

        return $result;
    }

    private function getDynamic($result = []): array
    {
        if (empty($result)) return [];
        $projects = array_keys($result['projects']);
        foreach ($projects as $i => $projectID) {
            if ($i == 0) continue;
            foreach ($result['companies'] as $companyID => $company) {
                foreach (['square', 'money'] as $what) {
                    if (!isset($result['data'][$companyID][$projects[$i]][$what])) continue;
                    $dynamic = $this->dynamic((float)$result['data'][$companyID][$projects[$i - 1]][$what], (float)$result['data'][$companyID][$projects[$i]][$what]);
                    $result['data'][$companyID][$projects[$i]]["percent_{$what}"] = $dynamic;
                }
            }
        }
        return $result;
    }

    private function dynamic(float $previous, float $current): float
    {
        if ($previous == 0) {
            return ($current == 0) ? 0 : 100;
        } else {
            $result = $current / $previous;
        }
        return round($result * 100 - 100);
    }

    private function init($items): array
    {
        $result = [
            'companies' => $this->listOfCompanies($items),
            'projects' => JanalyzeHelper::getAllProjects($this->familyID, $this->projectID ?? []),
            'data' => [],
            'total' => []
        ];
        foreach ($result['companies'] as $companyID => $company) {
            $i = 0;
            foreach ($result['projects'] as $projectID => $project) {
                $structure_for_data = $this->values[($i > 0) ? 'with_percents' : 'without_percents'];
                $result['data'][$companyID][$projectID] = $structure_for_data;
                $result['total'][$projectID] = $structure_for_data;
                $i++;
            }
        }
        return $result;
    }

    private function listOfCompanies(array $items): array
    {
        $result = [];
        if (empty($items)) return $result;
        foreach ($items as $item) {
            if (!isset($result[$item->companyID])) $result[$item->companyID] = $item->company;
        }
        return $result;
    }

    protected function populateState($ordering = null, $direction = null)
    {
        parent::populateState($ordering, $direction);
    }

    private $familyID, $projectID, $pavilionID, $values, $export, $finance_type, $equipmentID;
}
