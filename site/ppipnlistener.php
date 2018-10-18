<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ppipnlistener
 * @author     Ted Lowe <lists@creativespirits.org>
 * @copyright  2018 Crea
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Ppipnlistener', JPATH_COMPONENT);
JLoader::register('PpipnlistenerController', JPATH_COMPONENT . '/controller.php');


// Execute the task.
$controller = JControllerLegacy::getInstance('Ppipnlistener');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
