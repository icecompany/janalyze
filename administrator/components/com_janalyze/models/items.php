<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class JanalyzeModelItems extends ListModel
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
        $this->projectID = $config['projectID'] ?? null;
        $this->export = $config['export'] ?? false;
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
        $this->places = [
            'pavilion' => 'В павильоне',
            'street' => 'На улице',
            '2th_floor' => 'Второй этаж',
        ];
        $this->finance_types = [
            'commerce' => 'Коммерческая',
            'non_commerce' => 'Некоммерческая',
        ];
        $this->square_types = [
            1 => 'Экспоместо в павильоне',
            2 => 'Экспоместо в павильоне (премиум)',
            3 => 'Экспоместо на открытой площадке для демонстрации экспонатов',
            4 => 'Экспоместо на открытой площадке под застройку',
            5 => 'Экспоместо в павильоне ВПК',
            6 => 'Экспоместо на открытой площадке для демонстрации экспонатов у павильона ВПК',
            7 => 'Площадь второго этажа (премиум)',
            8 => 'Площадь второго этажа',
            'non_commerce' => 'Некоммерческая',
            'sponsor' => 'Спонсорская',
        ];

		parent::__construct($config);
	}

	protected function _getListQuery()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select("c.projectID, p.title as project")
            ->select("if (pi.square_type in (1, 2, 5, 9), 'pavilion', if(pi.square_type in (3, 4, 6), 'street', if (pi.square_type in (7, 8), '2th_floor', 'other'))) as place")
            ->select("if (c.status = 9, 'non_commerce', 'commerce') as finance_type")
            ->select("if (c.is_sponsor = 1, 'sponsor', pi.square_type) as object_type")
            ->select("c.companyID, e.title as company")
            ->select("ifnull(sum(ci.value),0) as square")
            ->select("ifnull(sum(if(c.currency = 'usd', ci.amount * p.course_usd, if(c.currency = 'eur', ci.amount * p.course_eur, ci.amount))),0) as money")
            ->from("`#__mkv_contract_items` ci")
            ->leftJoin("`#__mkv_price_items` pi on ci.itemID = pi.id")
            ->leftJoin("`#__mkv_contracts` c on ci.contractID = c.id")
            ->leftJoin("`#__mkv_projects` p on c.projectID = p.id")
            ->leftJoin("`#__mkv_companies` e on c.companyID = e.id")
            ->where("pi.square_type is not null")
            ->where("c.status not in (7, 8)")
            ->group("c.projectID, place, finance_type, object_type, c.companyID")
            ->order("c.projectID");

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
        $result = $this->init($items);
        foreach ($items as $item) {
            $result['data'][$item->companyID][$item->projectID][$item->place][$item->finance_type][$item->object_type]['square'] = (float) $item->square;
            $result['data'][$item->companyID][$item->projectID][$item->place][$item->finance_type][$item->object_type]['money'] = (float) $item->money;
            $result['total']['by_companies'][$item->companyID][$item->place][$item->finance_type][$item->object_type]['square'] += (float) $item->square;
            $result['total']['by_companies'][$item->companyID][$item->place][$item->finance_type][$item->object_type]['money'] += (float) $item->money;
            $result['total']['by_squares'][$item->projectID][$item->place][$item->finance_type][$item->object_type]['square'] += (float) $item->square;
            $result['total']['by_squares'][$item->projectID][$item->place][$item->finance_type][$item->object_type]['money'] += (float) $item->money;
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
                foreach ($result['places'] as $place_id => $place) {
                    foreach ($result['finance_types'] as $finance_type_id => $finance_type) {
                        foreach ($result['square_types'] as $square_type_id => $square_type) {
                            foreach (['square', 'money'] as $what) {
                                if (!isset($result['data'][$companyID][$projects[$i]][$place_id][$finance_type_id][$square_type_id][$what])) continue;
                                $result['data'][$companyID][$projects[$i]][$place_id][$finance_type_id][$square_type_id]["percent_{$what}"] = $this->dynamic((float) $result['data'][$companyID][$projects[$i-1]][$place_id][$finance_type_id][$square_type_id][$what], (float) $result['data'][$companyID][$projects[$i]][$place_id][$finance_type_id][$square_type_id][$what]);
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    private function dynamic(float $a, float $b): float
    {
        if ($a == 0) {
            $result = ($b == 0) ? 0 : 1;
        }
        else {
            $result = $b / $a;
        }
        return round(($result * 100 - 100));
    }

    private function init($items): array
    {
	    $result = [
	        'companies' => $this->listOfCompanies($items),
            'projects' => JanalyzeHelper::getAllProjects($this->familyID, $this->projectID ?? []),
            'places' => $this->places,
            'finance_types' => $this->finance_types,
            'square_types' => $this->square_types,
            'structure' => $this->getStructure(),
            'data' => [],
            'total' => ['by_squares' => []]
        ];
        foreach ($result['companies'] as $companyID => $company) {
            $i = 0;
            foreach ($result['projects'] as $projectID => $project) {
                $structure_for_data = $this->getStructure(true, $i);
                $result['data'][$companyID][$projectID] = $structure_for_data;
                $result['total']['by_companies'][$companyID] = $structure_for_data;
                $result['total']['by_squares'][$projectID] = $structure_for_data;
                $i++;
            }
        }
        return $result;
    }

    private function getStructure($for_data = false, $i = 0): array
    {
        $result = [];
        foreach (array_keys($this->places) as $place) {
            foreach (array_keys($this->finance_types) as $finance_type) {
                foreach (array_keys($this->square_types) as $square_type) {
                    if ($this->is_continue($place, $finance_type, $square_type)) continue;
                    if (!$for_data) {
                        $result[$place][$finance_type][] = $square_type;
                    }
                    else {
                        $result[$place][$finance_type][$square_type] = $this->values[($i > 0) ? 'with_percents' : 'without_percents'];
                    }
                }
            }
        }
        return $result;
    }

    private function is_continue(string $place, string $finance_type, string $square_type): bool
    {
        if ($place == '2th_floor' && $finance_type == 'non_commerce') return true;
        if ($finance_type == 'non_commerce' && $square_type == 'sponsor') return true;
        if ($place == 'street' && (
                $square_type != 3 && $square_type != 4 && $square_type != 6 && $square_type != 'sponsor'
            )) return true;
        if ($place == 'pavilion' && (
                $square_type != 1 && $square_type != 2 && $square_type != 5 && $square_type != 'sponsor'
            )) return true;
        if ($place == '2th_floor' &&
            ($square_type != 7 && $square_type != 8)
        ) return true;
        if ($place != '2th_floor' &&
            ($square_type == 7 || $square_type == 8)) return true;
        return false;
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

    private $familyID, $projectID, $values, $export, $places, $finance_types, $square_types;
}
