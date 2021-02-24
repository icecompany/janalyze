<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class JanalyzeModelTypes extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
            ];
        }

        $this->export = $config['export'] ?? false;
        $this->square = $config['square_type'] ?? false;
        $this->commercial = $config['commercial'] ?? false;
        $this->projectID = $config['projectID'] ?? false;
        $this->familyID = $config['familyID'] ?? false;

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

        if (!empty($this->projectID)) {
            if (is_numeric($this->projectID)) {
                $query->where("c.projectID = {$db->q($this->projectID)}");
            }
            if (is_array($this->projectID)) {
                $project = implode(', ', $this->projectID);
                if (!empty($project)) $query->where("c.projectID in ({$project})");
            }
        }
        else {
            $query->where("p.familyID = {$db->q($this->familyID)}");
        }

        $this->setState('list.limit', 0);

        return $query;
    }

    public function getItems()
    {
        $items = parent::getItems();

        $result = ['projects' => JanalyzeHelper::getAllProjects($this->familyID, $this->projectID ?? []), 'companies' => [], 'data' => [], 'total' => []];
        foreach ($items as $item) {
            $item->companyID = $item->companyID . " ";
            if (!isset($result['companies'][$item->companyID])) $result['companies'][$item->companyID] = $item->company;
            $square = ($this->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format((float)$item->square, 2, ',', ' '));
            $money = ($this->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format((float)$item->money, 2, ',', ' '));
            if (!isset($result['data'][$item->companyID])) {
                foreach ($result['projects'] as $projectID => $title) {
                    $result['data'][$item->companyID][$projectID] = [
                        'square_clean' => (float)0,
                        'money_clean' => (float)0,
                        'square' => ($this->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format(0, 2, ',', ' ')),
                        'money' => JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format(0, 2, ',', ' ')),
                        'percent_square' => "0%",
                        'percent_money' => "0%",
                    ];
                }
            }
            $result['data'][$item->companyID][$item->projectID] = [
                'square_clean' => (float)$item->square,
                'money_clean' => (float)$item->money,
                'square' => $square,
                'money' => $money,
                'percent_square' => "0%",
                'percent_money' => "0%",
            ];
            if (!isset($result['total'][$item->projectID])) $result['total'][$item->projectID] = ['square' => 0, 'money' => 0];
            $result['total'][$item->projectID]['square_clean'] += $item->square;
            $result['total'][$item->projectID]['square'] += $item->square;
            $result['total'][$item->projectID]['money_clean'] += $item->money;
            $result['total'][$item->projectID]['money'] += $item->money;
        }
        if (!$this->export) {
            foreach (array_keys($result['projects']) as $projectID) {
                $result['total'][$projectID]['square'] = JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format((float)$result['total'][$projectID]['square'], 2, ',', ' '));
                $result['total'][$projectID]['money'] = JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format((float)$result['total'][$projectID]['money'], 2, ',', ' '));
            }
        }
        $ids = [];
        $i = 0;
        foreach (array_keys($result['projects']) as $projectID) {
            $ids[$i] = $projectID;
            $i++;
        }
        foreach (['square', 'money'] as $what) {
            foreach ($ids as $i => $projectID) {
                foreach ($result['data'] as $companyID => $company) {
                    if (!is_null($ids[$i - 1])) {

                        if ($result['data'][$companyID][$ids[$i - 1]]["{$what}_clean"] == 0) {
                            if ((float)$result['data'][$companyID][$projectID]["{$what}_clean"] == 0) {
                                $result['data'][$companyID][$projectID]["percent_{$what}"] = "0%";
                            }
                            else {
                                $result['data'][$companyID][$projectID]["percent_{$what}"] = "100%";
                            }
                        } else {
                            $result['data'][$companyID][$projectID]["percent_{$what}"] = round((((float)$result['data'][$companyID][$projectID]["{$what}_clean"] / (float)$result['data'][$companyID][$ids[$i - 1]]["{$what}_clean"]) * 100 - 100)) . "%";
                        }
                    }
                }
                foreach ($result['total'] as $companyID => $company) {
                    if (!is_null($ids[$i - 1])) {

                        if ($result['total'][$ids[$i - 1]]["{$what}_clean"] == 0) {
                            if ((float)$result['total'][$projectID]["{$what}_clean"] == 0) {
                                $result['total'][$projectID]["percent_{$what}"] = "0%";
                            }
                            else {
                                $result['total'][$projectID]["percent_{$what}"] = "100%";
                            }
                        } else {
                            $result['total'][$projectID]["percent_{$what}"] = round((((float)$result['total'][$projectID]["{$what}_clean"] / (float)$result['total'][$ids[$i - 1]]["{$what}_clean"]) * 100 - 100)) . "%";
                        }
                    }
                }
            }
        }
        asort($result['companies']);
        return $result;
    }

    private $projectID, $familyID, $export, $square, $commercial;
}
