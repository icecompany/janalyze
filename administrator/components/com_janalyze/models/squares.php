<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class JanalyzeModelSquares extends ListModel
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

		parent::__construct($config);
	}

	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query
            ->select("c.projectID")
            ->select("p.title as project")
            ->select("pi.square_type")
            ->select("ifnull(sum(ci.value),0) as square")
            ->select("ifnull(sum(if(c.currency = 'usd', ci.amount * p.course_usd, if(c.currency = 'eur', ci.amount * p.course_eur, ci.amount))),0) as money")
            ->from($db->qn('#__mkv_contract_items') . ' ci')
            ->leftJoin($db->qn('#__mkv_price_items') . ' pi on ci.itemID = pi.id')
            ->leftJoin($db->qn('#__mkv_contracts') . ' c on ci.contractID = c.id')
            ->leftJoin($db->qn('#__mkv_projects') . ' p on c.projectID = p.id')
            ->where("pi.square_type in (1, 2, 3, 4, 5, 6, 9) and c.is_sponsor != 1 and c.status != 9")
            ->group("c.projectID, pi.square_type")
            ->order("c.projectID, pi.square_type");

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
        $types = [1 => 'pavilion', 2 => 'pavilion', 3 => 'street', 4 => 'street', 5 => 'pavilion', 6 => 'street', 9 => 'pavilion'];
        $square_types = [
            1 => [
                'pavilion' => [
                    1 => 'Экспоместо в павильоне',
                    2 => 'Экспоместо в павильоне (премиум)',
                    5 => 'Экспоместо в павильоне ВПК',
                ],
                'street' => [
                    3 => 'Экспоместо на улице',
                    4 => 'Экспоместо на улице (под застройку)',
                    6 => 'Экспоместо на улице ВПК',
                ],
            ],
            2 => [
                'pavilion' => [
                    1 => 'Экспоместо в павильоне',
                    2 => 'Экспоместо в павильоне (премиум)',
                ],
                'street' => [
                    3 => 'Экспоместо на улице',
                ],
            ],
        ];

        $result = ['projects' => [], 'types' => $square_types[$this->familyID], 'data' => [], 'total' => []];
        foreach ($items as $item) {
            if (!isset($result['projects'][$item->projectID])) $result['projects'][$item->projectID] = $item->project;
        }
        foreach ($items as $item) {
            $square = ($this->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format((float) $item->square, 2, ',', ' '));
            $money = ($this->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format((float) $item->money, 2, ',', ' '));

            if (!isset($result['data'][$types[$item->square_type]][$item->square_type])) {
                foreach($result['projects'] as $projectID => $title) {
                    $result['data'][$types[$item->square_type]][$item->square_type][$projectID] = [
                        'square_clean' => (float)0,
                        'money_clean' => (float)0,
                        'square' => ($this->export) ?: JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format(0, 2, ',', ' ')),
                        'money' => JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format(0, 2, ',', ' ')),
                        'percent_square' => "0%",
                        'percent_money' => "0%",
                    ];
                }
            }

            $result['data'][$types[$item->square_type]][$item->square_type][$item->projectID] = [
                'square_clean' => (float) $item->square,
                'money_clean' => (float) $item->money,
                'square' => $square,
                'money' => $money,
                'percent_square' => "0%",
                'percent_money' => "0%",
            ];

            if (!isset($result['total'][$types[$item->square_type]][$item->projectID])) $result['total'][$types[$item->square_type]][$item->projectID] = [
                'square' => 0,
                'money' => 0,
                'square_clean' => 0,
                'money_clean' => 0,
            ];
            $result['total'][$types[$item->square_type]][$item->projectID]['square'] += $item->square;
            $result['total'][$types[$item->square_type]][$item->projectID]['square_clean'] += $item->square;
            $result['total'][$types[$item->square_type]][$item->projectID]['money'] += $item->money;
            $result['total'][$types[$item->square_type]][$item->projectID]['money_clean'] += $item->money;
        }
        if (!$this->export) {
            foreach (['pavilion', 'street'] as $type) {
                foreach (array_keys($result['projects']) as $projectID) {
                    $result['total'][$type][$projectID]['square'] = JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_SQM', number_format((float)$result['total'][$type][$projectID]['square'], 2, ',', ' '));
                    $result['total'][$type][$projectID]['money'] = JText::sprintf('COM_JANALYZE_HEAD_POSTFIX_RUB', number_format((float)$result['total'][$type][$projectID]['money'], 2, ',', ' '));
                }
            }
        }
        $ids = [];
        $i = 0;
        foreach (array_keys($result['projects']) as $projectID) {
            $ids[$i] = $projectID;
            $i++;
        }
        foreach (['pavilion', 'street'] as $global_type) {
            foreach (['square', 'money'] as $what) {
                foreach ($ids as $i => $projectID) {
                    foreach ($result['data'][$global_type] as $tip => $company) {
                        if (!is_null($ids[$i - 1])) {

                            if ($result['data'][$global_type][$tip][$ids[$i - 1]]["{$what}_clean"] == 0) {
                                if ((float)$result['data'][$global_type][$tip][$projectID]["{$what}_clean"] == 0) {
                                    $result['data'][$global_type][$tip][$projectID]["percent_{$what}"] = "0%";
                                }
                                else {
                                    $result['data'][$global_type][$tip][$projectID]["percent_{$what}"] = "100%";
                                }
                            } else {
                                $result['data'][$global_type][$tip][$projectID]["percent_{$what}"] = round((((float)$result['data'][$global_type][$tip][$projectID]["{$what}_clean"] / (float)$result['data'][$global_type][$tip][$ids[$i - 1]]["{$what}_clean"]) * 100 - 100)) . "%";
                            }
                        }
                    }
                }
                foreach ($result['total'][$global_type] as $prj => $company) {
                    if (!is_null($ids[$i - 1])) {

                        if ($result['total'][$global_type][$ids[$i - 1]]["{$what}_clean"] == 0) {
                            if ((float)$result['total'][$global_type][$prj]["{$what}_clean"] == 0) {
                                $result['total'][$global_type][$prj]["percent_{$what}"] = "0%";
                            }
                            else {
                                $result['total'][$global_type][$prj]["percent_{$what}"] = "100%";
                            }
                        } else {
                            $result['total'][$global_type][$prj]["percent_{$what}"] = round((((float)$result['total'][$global_type][$prj]["{$what}_clean"] / (float)$result['total'][$global_type][$ids[$i - 1]]["{$what}_clean"]) * 100 - 100)) . "%";
                        }
                    }
                }
            }
        }
        return $result;
    }

    private $familyID, $excludeID, $export;
}
