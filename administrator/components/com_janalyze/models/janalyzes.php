<?php
/**
 * @package    janalyze
 *
 * @author     asharikov <your@email.com>
 * @copyright  A copyright
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://your.url.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Janalyzes
 *
 * @package  janalyze
 * @since    1.0.0
 */
class JanalyzeModelJanalyzes extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id',
				'items.id',
				'title',
				'items.title',
				'alias',
				'items.alias',
				'published',
				'items.published',
			];
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		if ($ordering === null)
		{
			$ordering = 'items.title';
		}

		if ($direction === null)
		{
			$direction = 'ASC';
		}

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a \JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  \JDatabaseQuery  A \JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0.0
	 */
	protected function getListQuery()
	{
		$db    = $this->getDbo();
		$query = parent::getListQuery()
			->select(
				$db->quoteName(
					[
						'items.id',
						'items.title',
						'items.alias',
						'items.published',
					]
				)
			)
			->from($db->quoteName('#__janalyze_items', 'items'));

		$search = $this->getState('filter.search');

		if ($search)
		{
			if (strpos($search, ':') !== false)
			{
				$itemId = substr($search, 3);
				$query->where($db->quoteName('items.id') . ' = ' . (int) $itemId);
			}
			else
			{
				$query->where($db->quoteName('items.title') . ' LIKE ' . $db->quote('%' . $search . '%'));
			}
		}

		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('items.published') . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(' . $db->quoteName('items.published') . ' = 0 OR ' . $db->quoteName('items.published') . ' = 1)');
		}

		// Add the list ordering clause.
		$orderCol       = $this->state->get('list.ordering', 'items.title');
		$orderDirection = $this->state->get('list.direction', 'ASC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirection));

		return $query;
	}
}
