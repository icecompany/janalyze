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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Janalyze helper.
 *
 * @package  janalyze
 * @since    1.0.0
 */
class JanalyzeHelper
{
	/**
	 * Render submenu.
	 *
	 * @param   string  $vName  The name of the current view.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function addSubmenu($vName)
	{
		HTMLHelper::_(
			'sidebar.addEntry',
			Text::_('COM_JANALYZE'),
			'index.php?option=com_janalyze&view=janalyzes',
			$vName === 'janalyzes'
		);
	}
}
