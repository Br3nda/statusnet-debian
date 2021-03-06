<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * User administration panel
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
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Administer user settings
 *
 * @category Admin
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Zach Copley <zach@status.net>
 * @author   Sarven Capadisli <csarven@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class UseradminpanelAction extends AdminPanelAction
{
    /**
     * Returns the page title
     *
     * @return string page title
     */

    function title()
    {
        // TRANS: User admin panel title
        return _m('TITLE', 'User');
    }

    /**
     * Instructions for using this form.
     *
     * @return string instructions
     */

    function getInstructions()
    {
        return _('User settings for this StatusNet site.');
    }

    /**
     * Show the site admin panel form
     *
     * @return void
     */

    function showForm()
    {
        $form = new UserAdminPanelForm($this);
        $form->show();
        return;
    }

    /**
     * Save settings from the form
     *
     * @return void
     */

    function saveSettings()
    {
        static $settings = array(
                'profile' => array('biolimit'),
                'newuser' => array('welcome', 'default')
        );

        static $booleans = array(
            'invite' => array('enabled')
        );

        $values = array();

        foreach ($settings as $section => $parts) {
            foreach ($parts as $setting) {
                $values[$section][$setting] = $this->trimmed("$section-$setting");
            }
        }

        foreach ($booleans as $section => $parts) {
            foreach ($parts as $setting) {
                $values[$section][$setting] = ($this->boolean("$section-$setting")) ? 1 : 0;
            }
        }

        // This throws an exception on validation errors

        $this->validate($values);

        // assert(all values are valid);

        $config = new Config();

        $config->query('BEGIN');

        foreach ($settings as $section => $parts) {
            foreach ($parts as $setting) {
                Config::save($section, $setting, $values[$section][$setting]);
            }
        }

        foreach ($booleans as $section => $parts) {
            foreach ($parts as $setting) {
                Config::save($section, $setting, $values[$section][$setting]);
            }
        }

        $config->query('COMMIT');

        return;
    }

    function validate(&$values)
    {
        // Validate biolimit

        if (!Validate::number($values['profile']['biolimit'])) {
            $this->clientError(_("Invalid bio limit. Must be numeric."));
        }

        // Validate welcome text

        if (mb_strlen($values['newuser']['welcome']) > 255) {
            $this->clientError(_("Invalid welcome text. Max length is 255 characters."));
        }

        // Validate default subscription

        if (!empty($values['newuser']['default'])) {
            $defuser = User::staticGet('nickname', trim($values['newuser']['default']));
            if (empty($defuser)) {
                $this->clientError(
                    sprintf(
                        _('Invalid default subscripton: \'%1$s\' is not user.'),
                        $values['newuser']['default']
                    )
                );
            }
        }
    }
}

class UserAdminPanelForm extends AdminForm
{
    /**
     * ID of the form
     *
     * @return int ID of the form
     */

    function id()
    {
        return 'useradminpanel';
    }

    /**
     * class of the form
     *
     * @return string class of the form
     */

    function formClass()
    {
        return 'form_settings';
    }

    /**
     * Action of the form
     *
     * @return string URL of the action
     */

    function action()
    {
        return common_local_url('useradminpanel');
    }

    /**
     * Data elements of the form
     *
     * @return void
     */

    function formData()
    {
        $this->out->elementStart('fieldset', array('id' => 'settings_user-profile'));
        $this->out->element('legend', null, _('Profile'));
        $this->out->elementStart('ul', 'form_data');

        $this->li();
        $this->input('biolimit', _('Bio Limit'),
                     _('Maximum length of a profile bio in characters.'),
                     'profile');
        $this->unli();

        $this->out->elementEnd('ul');
        $this->out->elementEnd('fieldset');

        $this->out->elementStart('fieldset', array('id' => 'settings_user-newusers'));
        $this->out->element('legend', null, _('New users'));
        $this->out->elementStart('ul', 'form_data');

        $this->li();
        $this->input('welcome', _('New user welcome'),
                     _('Welcome text for new users (Max 255 chars).'),
                     'newuser');
        $this->unli();

        $this->li();
        $this->input('default', _('Default subscription'),
                     _('Automatically subscribe new users to this user.'),
                     'newuser');
        $this->unli();

        $this->out->elementEnd('ul');

        $this->out->elementEnd('fieldset');

        $this->out->elementStart('fieldset', array('id' => 'settings_user-invitations'));
        $this->out->element('legend', null, _('Invitations'));
        $this->out->elementStart('ul', 'form_data');

        $this->li();

        $this->out->checkbox('invite-enabled', _('Invitations enabled'),
                              (bool) $this->value('enabled', 'invite'),
                              _('Whether to allow users to invite new users.'));
        $this->unli();

        $this->out->elementEnd('ul');
        $this->out->elementEnd('fieldset');



    }

    /**
     * Utility to simplify some of the duplicated code around
     * params and settings.  Overrided from base class to be
     * more specific about input ids.
     *
     * @param string $setting      Name of the setting
     * @param string $title        Title to use for the input
     * @param string $instructions Instructions for this field
     * @param string $section      config section, default = 'site'
     *
     * @return void
     */

    function input($setting, $title, $instructions, $section='site')
    {
        $this->out->input("$section-$setting", $title, $this->value($setting, $section), $instructions);
    }

    /**
     * Action elements
     *
     * @return void
     */

    function formActions()
    {
        $this->out->submit('submit', _('Save'), 'submit', null, _('Save site settings'));
    }
}
