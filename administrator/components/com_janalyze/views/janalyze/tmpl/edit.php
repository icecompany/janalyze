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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var JanalyzeViewJanalyze $this */

HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen');

Factory::getDocument()->addScriptDeclaration(<<<JS
		Joomla.submitbutton = function(task)
		{
			if (task === 'janalyze.cancel' || document.formvalidator.isValid(document.getElementById('adminForm')))
			{
				Joomla.submitform(task, document.getElementById('adminForm'));
			}
		};
JS
);
?>
<form action="<?php echo Route::_('index.php?option=com_janalyze&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" enctype="multipart/form-data" id="adminForm" class="form-validate">
	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<hr/>

	<div class="row-fluid">
		<div class="span9">
			<?php echo $this->form->getInput('description'); ?>
		</div>
		<div class="span3">
			<?php echo LayoutHelper::render('joomla.edit.global', $this); ?>
		</div>
	</div>

	<input type="hidden" name="task" value=""/>
	<?php echo $this->form->getInput('id'); ?>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
