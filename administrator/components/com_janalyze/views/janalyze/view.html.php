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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

/**
 * Janalyze view.
 *
 * @package  janalyze
 * @since    1.0.0
 */
class JanalyzeViewJanalyze extends HtmlView
{
	/**
	 * Form with settings
	 *
	 * @var    Form
	 * @since  1.0.0
	 */
	protected $form;

	/**
	 * The item object
	 *
	 * @var    object
	 * @since  1.0.0
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  1.0.0
	 */
	protected $state;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 *
	 * @see     fetch()
	 */
	public function display($tpl = null)
	{
		/** @var JanalyzesModelJanalyze $model */
		$model       = $this->getModel();
		$this->form  = $model->getForm();
		$this->item  = $model->getItem();
		$this->state = $model->getState();

		// Show the toolbar
		$this->toolbar();

		// Display it all
		return parent::display($tpl);
	}

	/**
	 * Displays a toolbar for a specific page.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	private function toolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$canDo = ContentHelper::getActions('com_janalyze');
		$isNew = ((int) $this->item->id === 0);

		ToolbarHelper::title(Text::_('COM_JANALYZE_TITLE_JANALYZE'));

		// If not checked out, can save the item.
		if ($canDo->get('core.edit') || ($canDo->get('core.create')))
		{
			ToolbarHelper::apply('janalyze.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('janalyze.save', 'JTOOLBAR_SAVE');
		}

		if ($canDo->get('core.create'))
		{
			ToolbarHelper::custom(
				'janalyze.save2new',
				'save-new.png',
				'save-new_f2.png',
				'JTOOLBAR_SAVE_AND_NEW',
				false
			);
		}

		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::custom(
				'janalyze.save2copy',
				'save-copy.png',
				'save-copy_f2.png',
				'JTOOLBAR_SAVE_AS_COPY',
				false
			);
		}

		if (empty($this->item->id))
		{
			ToolbarHelper::cancel('janalyze.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('janalyze.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
