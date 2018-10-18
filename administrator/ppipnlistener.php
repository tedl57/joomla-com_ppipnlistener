<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ppipnlistener
 * @author     Ted Lowe <lists@creativespirits.org>
 * @copyright  2018 Crea
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_ppipnlistener'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Ppipnlistener', JPATH_COMPONENT_ADMINISTRATOR);
JLoader::register('PpipnlistenerHelper', JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'ppipnlistener.php');

$controller = JControllerLegacy::getInstance('Ppipnlistener');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
