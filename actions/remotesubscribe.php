<?php
/**
 * Handler for remote subscription
 *
 * PHP version 5
 *
 * @category Action
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Robin Millette <millette@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 *
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
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
 **/

if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }

require_once INSTALLDIR.'/lib/omb.php';
require_once INSTALLDIR.'/extlib/libomb/service_consumer.php';
require_once INSTALLDIR.'/extlib/libomb/profile.php';

/**
 * Handler for remote subscription
 *
 * @category Action
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Robin Millette <millette@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 */

class RemotesubscribeAction extends Action
{
    var $nickname;
    var $profile_url;
    var $err;

    function prepare($args)
    {
        parent::prepare($args);

        if (common_logged_in()) {
            $this->clientError(_('You can use the local subscription!'));
            return false;
        }

        $this->nickname    = $this->trimmed('nickname');
        $this->profile_url = $this->trimmed('profile_url');

        return true;
    }

    function handle($args)
    {
        parent::handle($args);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            /* Use a session token for CSRF protection. */
            $token = $this->trimmed('token');
            if (!$token || $token != common_session_token()) {
                $this->showForm(_('There was a problem with your session token. '.
                                  'Try again, please.'));
                return;
            }
            $this->remoteSubscription();
        } else {
            $this->showForm();
        }
    }

    function showForm($err=null)
    {
        $this->err = $err;
        $this->showPage();
    }

    function showPageNotice()
    {
        if ($this->err) {
            $this->element('div', 'error', $this->err);
        } else {
            $inst = _('To subscribe, you can [login](%%action.login%%),' .
                      ' or [register](%%action.register%%) a new ' .
                      ' account. If you already have an account ' .
                      ' on a [compatible microblogging site](%%doc.openmublog%%), ' .
                      ' enter your profile URL below.');
            $output = common_markup_to_html($inst);
            $this->elementStart('div', 'instructions');
            $this->raw($output);
            $this->elementEnd('div');
        }
    }

    function title()
    {
        return _('Remote subscribe');
    }

    function showContent()
    {
        /* The id 'remotesubscribe' conflicts with the
           button on profile page. */
        $this->elementStart('form', array('id' => 'form_remote_subscribe',
                                          'method' => 'post',
                                          'class' => 'form_settings',
                                          'action' => common_local_url('remotesubscribe')));
        $this->elementStart('fieldset');
        $this->element('legend', _('Subscribe to a remote user'));
        $this->hidden('token', common_session_token());

        $this->elementStart('ul', 'form_data');
        $this->elementStart('li');
        $this->input('nickname', _('User nickname'), $this->nickname,
                     _('Nickname of the user you want to follow'));
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->input('profile_url', _('Profile URL'), $this->profile_url,
                     _('URL of your profile on another compatible microblogging service'));
        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->submit('submit', _('Subscribe'));
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    function remoteSubscription()
    {
        if (!$this->nickname) {
            $this->showForm(_('No such user.'));
            return;
        }

        $user = User::staticGet('nickname', $this->nickname);

        $this->profile_url = $this->trimmed('profile_url');

        if (!$this->profile_url) {
            $this->showForm(_('No such user.'));
            return;
        }

        if (!common_valid_http_url($this->profile_url)) {
            $this->showForm(_('Invalid profile URL (bad format)'));
            return;
        }

        try {
            $service = new OMB_Service_Consumer($this->profile_url,
                                                common_root_url(),
                                                omb_oauth_datastore());
        } catch (OMB_InvalidYadisException $e) {
            $this->showForm(_('Not a valid profile URL (no YADIS document or ' .
                              'invalid XRDS defined).'));
            return;
        }

        if ($service->getServiceURI(OAUTH_ENDPOINT_REQUEST) ==
            common_local_url('requesttoken') ||
            User::staticGet('uri', $service->getRemoteUserURI())) {
            $this->showForm(_('That’s a local profile! Login to subscribe.'));
            return;
        }

        try {
            $service->requestToken();
        } catch (OMB_RemoteServiceException $e) {
            $this->showForm(_('Couldn’t get a request token.'));
            return;
        }

        /* Create an OMB_Profile from $user. */
        $profile = $user->getProfile();
        if (!$profile) {
            common_log_db_error($user, 'SELECT', __FILE__);
            $this->serverError(_('User without matching profile.'));
            return;
        }

        $target_url = $service->requestAuthorization(
                                   profile_to_omb_profile($user->uri, $profile),
                                   common_local_url('finishremotesubscribe'));

        common_ensure_session();

        $_SESSION['oauth_authorization_request'] = serialize($service);

        /* Redirect to the remote service for authorization. */
        common_redirect($target_url, 303);
    }
}
?>
