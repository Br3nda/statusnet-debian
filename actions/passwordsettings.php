<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Change user password
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Settings
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/accountsettingsaction.php';

/**
 * Change password
 *
 * @category Settings
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class PasswordsettingsAction extends AccountSettingsAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */

    function title()
    {
        return _('Change password');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */

    function getInstructions()
    {
        return _('Change your password.');
    }

    function showScripts()
    {
        parent::showScripts();
        $this->autofocus('oldpassword');
    }

    /**
     * Content area of the page
     *
     * Shows a form for changing the password
     *
     * @return void
     */

    function showContent()
    {
        $user = common_current_user();

        $this->elementStart('form', array('method' => 'POST',
                                          'id' => 'form_password',
                                          'class' => 'form_settings',
                                          'action' =>
                                          common_local_url('passwordsettings')));
        $this->elementStart('fieldset');
        $this->element('legend', null, _('Password change'));
        $this->hidden('token', common_session_token());


        $this->elementStart('ul', 'form_data');
        // Users who logged in with OpenID won't have a pwd
        if ($user->password) {
            $this->elementStart('li');
            $this->password('oldpassword', _('Old password'));
            $this->elementEnd('li');
        }
        $this->elementStart('li');
        $this->password('newpassword', _('New password'),
                        _('6 or more characters'));
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->password('confirm', _('Confirm'),
                        _('Same as password above'));
        $this->elementEnd('li');
        $this->elementEnd('ul');

        $this->submit('changepass', _('Change'));

        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    /**
     * Handle a post
     *
     * Validate input and save changes. Reload the form with a success
     * or error message.
     *
     * @return void
     */

    function handlePost()
    {
        // CSRF protection

        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->showForm(_('There was a problem with your session token. '.
                               'Try again, please.'));
            return;
        }

        $user = common_current_user();
        assert(!is_null($user)); // should already be checked

        // FIXME: scrub input

        $newpassword = $this->arg('newpassword');
        $confirm     = $this->arg('confirm');

        # Some validation

        if (strlen($newpassword) < 6) {
            $this->showForm(_('Password must be 6 or more characters.'));
            return;
        } else if (0 != strcmp($newpassword, $confirm)) {
            $this->showForm(_('Passwords don\'t match.'));
            return;
        }

        if ($user->password) {
            $oldpassword = $this->arg('oldpassword');

            if (!common_check_user($user->nickname, $oldpassword)) {
                $this->showForm(_('Incorrect old password'));
                return;
            }
        }else{
            $oldpassword = null;
        }

        $success = false;
        if(Event::handle('StartChangePassword', array($user, $oldpassword, $newpassword))){
            //no handler changed the password, so change the password internally
            $original = clone($user);

            $user->password = common_munge_password($newpassword, $user->id);

            $val = $user->validate();
            if ($val !== true) {
                $this->showForm(_('Error saving user; invalid.'));
                return;
            }

            if (!$user->update($original)) {
                $this->serverError(_('Can\'t save new password.'));
                return;
            }
            Event::handle('EndChangePassword', array($user));
        }

        $this->showForm(_('Password saved.'), true);
    }
}
