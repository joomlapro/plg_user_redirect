<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  User.redirect
 * @copyright   Copyright (C) 2013 AtomTech, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('JPATH_BASE') or die;

/**
 * Redirect User plugin
 *
 * @package     Bolsas
 * @subpackage  User.redirect
 * @since       3.0
 */
class PlgUserRedirect extends JPlugin
{
	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe.
	 * @param   array   $config    An array that holds the plugin configuration.
	 *
	 * @access  protected
	 * @since   3.0
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * This method should handle any login logic and report back to the subject.
	 *
	 * @param   array  $user     Holds the user data
	 * @param   array  $options  Array holding options (remember, autoregister, group)
	 *
	 * @return  boolean	True on success
	 *
	 * @since   3.0
	 */
	public function onUserLogin($user, $options = array())
	{
		// Initialiase variables.
		$app = JFactory::getApplication();

		if ($app->isAdmin() || JDEBUG)
		{
			return;
		}

		$instance = $this->_getUser($user, $options);

		// If _getUser returned an error, then pass it back.
		if ($instance instanceof Exception)
		{
			return false;
		}

		if (!in_array(8, $instance->groups))
		{
			foreach ($params->get('access_group') as $i => $group)
			{
				if (in_array($group, $instance->groups))
				{
					// Redirect to the cpanel.
					$app->setUserState('users.login.form.return', JRoute::_(BolsasHelperRoute::getCpanelRoute(), false));
				}
			}
		}

		return true;
	}

	/**
	 * This method will return a user object.
	 *
	 * If options['autoregister'] is true, if the user doesn't exist yet he will be created.
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Array holding options (remember, autoregister, group).
	 *
	 * @return  object  A JUser object.
	 *
	 * @since   3.0
	 */
	protected function _getUser($user, $options = array())
	{
		// Initialiase variables.
		$instance = JUser::getInstance();
		$id       = (int) JUserHelper::getUserId($user['username']);

		if ($id)
		{
			$instance->load($id);
			return $instance;
		}

		// TODO: move this out of the plugin.
		$config = JComponentHelper::getParams('com_users');

		// Default to Registered.
		$defaultUserGroup = $config->get('new_usertype', 2);

		$instance->set('id',             0);
		$instance->set('name',           $user['fullname']);
		$instance->set('username',       $user['username']);
		$instance->set('password_clear', $user['password_clear']);

		// Result should contain an email (check).
		$instance->set('email',          $user['email']);
		$instance->set('groups',         array($defaultUserGroup));

		// If autoregister is set let's register the user.
		$autoregister = isset($options['autoregister']) ? $options['autoregister'] :  $this->params->get('autoregister', 1);

		if ($autoregister)
		{
			if (!$instance->save())
			{
				return JError::raiseWarning('SOME_ERROR_CODE', $instance->getError());
			}
		}
		else
		{
			// No existing user and autoregister off, this is a temporary user.
			$instance->set('tmp_user', true);
		}

		return $instance;
	}
}
