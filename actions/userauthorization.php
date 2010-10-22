<?php
/**
 * Let the user authorize a remote subscription request
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
 */

if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }

require_once INSTALLDIR.'/lib/omb.php';
require_once INSTALLDIR.'/extlib/libomb/service_provider.php';
require_once INSTALLDIR.'/extlib/libomb/profile.php';
define('TIMESTAMP_THRESHOLD', 300);

class UserauthorizationAction extends Action
{
    var $error;
    var $params;

    function handle($args)
    {
        parent::handle($args);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            /* Use a session token for CSRF protection. */
            $token = $this->trimmed('token');
            if (!$token || $token != common_session_token()) {
                $srv = $this->getStoredParams();
                $this->showForm($srv->getRemoteUser(), _('There was a problem ' .
                                        'with your session token. Try again, ' .
                                        'please.'));
                return;
            }
            /* We've shown the form, now post user's choice. */
            $this->sendAuthorization();
        } else {
            if (!common_logged_in()) {
                /* Go log in, and then come back. */
                common_set_returnto($_SERVER['REQUEST_URI']);

                common_redirect(common_local_url('login'));
                return;
            }

            $user    = common_current_user();
            $profile = $user->getProfile();
            if (!$profile) {
                common_log_db_error($user, 'SELECT', __FILE__);
                $this->serverError(_('User without matching profile.'));
                return;
            }

            /* TODO: If no token is passed the user should get a prompt to enter
               it according to OAuth Core 1.0. */
            try {
                $this->validateOmb();
                $srv = new OMB_Service_Provider(
                        profile_to_omb_profile($user->uri, $profile),
                        omb_oauth_datastore());

                $remote_user = $srv->handleUserAuth();
            } catch (Exception $e) {
                $this->clearParams();
                $this->clientError($e->getMessage());
                return;
            }

            $this->storeParams($srv);
            $this->showForm($remote_user);
        }
    }

    function showForm($params, $error=null)
    {
        $this->params = $params;
        $this->error  = $error;
        $this->showPage();
    }

    function title()
    {
        return _('Authorize subscription');
    }

    function showPageNotice()
    {
        $this->element('p', null, _('Please check these details to make sure '.
                                    'that you want to subscribe to this ' .
                                    'user’s notices. If you didn’t just ask ' .
                                    'to subscribe to someone’s notices, '.
                                    'click “Reject”.'));
    }

    function showContent()
    {
        $params = $this->params;

        $nickname = $params->getNickname();
        $profile  = $params->getProfileURL();
        $license  = $params->getLicenseURL();
        $fullname = $params->getFullname();
        $homepage = $params->getHomepage();
        $bio      = $params->getBio();
        $location = $params->getLocation();
        $avatar   = $params->getAvatarURL();

        $this->elementStart('div', 'entity_profile vcard');
        $this->elementStart('dl', 'entity_depiction');
        $this->element('dt', null, _('Photo'));
        $this->elementStart('dd');
        if ($avatar) {
            $this->element('img', array('src' => $avatar,
                                        'class' => 'photo avatar',
                                        'width' => AVATAR_PROFILE_SIZE,
                                        'height' => AVATAR_PROFILE_SIZE,
                                        'alt' => $nickname));
        }
        $this->elementEnd('dd');
        $this->elementEnd('dl');

        $this->elementStart('dl', 'entity_nickname');
        $this->element('dt', null, _('Nickname'));
        $this->elementStart('dd');
        $hasFN = ($fullname !== '') ? 'nickname' : 'fn nickname';
        $this->elementStart('a', array('href' => $profile,
                                       'class' => 'url '.$hasFN));
        $this->raw($nickname);
        $this->elementEnd('a');
        $this->elementEnd('dd');
        $this->elementEnd('dl');

        if (!is_null($fullname)) {
            $this->elementStart('dl', 'entity_fn');
            $this->elementStart('dd');
            $this->elementStart('span', 'fn');
            $this->raw($fullname);
            $this->elementEnd('span');
            $this->elementEnd('dd');
            $this->elementEnd('dl');
        }
        if (!is_null($location)) {
            $this->elementStart('dl', 'entity_location');
            $this->element('dt', null, _('Location'));
            $this->elementStart('dd', 'label');
            $this->raw($location);
            $this->elementEnd('dd');
            $this->elementEnd('dl');
        }

        if (!is_null($homepage)) {
            $this->elementStart('dl', 'entity_url');
            $this->element('dt', null, _('URL'));
            $this->elementStart('dd');
            $this->elementStart('a', array('href' => $homepage,
                                                'class' => 'url'));
            $this->raw($homepage);
            $this->elementEnd('a');
            $this->elementEnd('dd');
            $this->elementEnd('dl');
        }

        if (!is_null($bio)) {
            $this->elementStart('dl', 'entity_note');
            $this->element('dt', null, _('Note'));
            $this->elementStart('dd', 'note');
            $this->raw($bio);
            $this->elementEnd('dd');
            $this->elementEnd('dl');
        }

        if (!is_null($license)) {
            $this->elementStart('dl', 'entity_license');
            $this->element('dt', null, _('License'));
            $this->elementStart('dd', 'license');
            $this->element('a', array('href' => $license,
                                      'class' => 'license'),
                           $license);
            $this->elementEnd('dd');
            $this->elementEnd('dl');
        }
        $this->elementEnd('div');

        $this->elementStart('div', 'entity_actions');
        $this->elementStart('ul');
        $this->elementStart('li', 'entity_subscribe');
        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'userauthorization',
                                          'class' => 'form_user_authorization',
                                          'name' => 'userauthorization',
                                          'action' => common_local_url(
                                                         'userauthorization')));
        $this->hidden('token', common_session_token());

        $this->submit('accept', _('Accept'), 'submit accept', null,
                      _('Subscribe to this user'));
        $this->submit('reject', _('Reject'), 'submit reject', null,
                      _('Reject this subscription'));
        $this->elementEnd('form');
        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->elementEnd('div');
    }

    function sendAuthorization()
    {
        $srv = $this->getStoredParams();

        if (is_null($srv)) {
            $this->clientError(_('No authorization request!'));
            return;
        }

        $accepted = $this->arg('accept');
        try {
            list($val, $token) = $srv->continueUserAuth($accepted);
        } catch (Exception $e) {
            $this->clientError($e->getMessage());
            return;
        }
        if ($val !== false) {
            common_redirect($val, 303);
        } elseif ($accepted) {
            $this->showAcceptMessage($token);
        } else {
            $this->showRejectMessage();
        }
    }

    function showAcceptMessage($tok)
    {
        common_show_header(_('Subscription authorized'));
        $this->element('p', null,
                       _('The subscription has been authorized, but no '.
                         'callback URL was passed. Check with the site’s ' .
                         'instructions for details on how to authorize the ' .
                         'subscription. Your subscription token is:'));
        $this->element('blockquote', 'token', $tok);
        common_show_footer();
    }

    function showRejectMessage()
    {
        common_show_header(_('Subscription rejected'));
        $this->element('p', null,
                       _('The subscription has been rejected, but no '.
                         'callback URL was passed. Check with the site’s ' .
                         'instructions for details on how to fully reject ' .
                         'the subscription.'));
        common_show_footer();
    }

    function storeParams($params)
    {
        common_ensure_session();
        $_SESSION['userauthorizationparams'] = serialize($params);
    }

    function clearParams()
    {
        common_ensure_session();
        unset($_SESSION['userauthorizationparams']);
    }

    function getStoredParams()
    {
        common_ensure_session();
        $params = unserialize($_SESSION['userauthorizationparams']);
        return $params;
    }

    function validateOmb()
    {
        $listener = $_GET['omb_listener'];
        $listenee = $_GET['omb_listenee'];
        $nickname = $_GET['omb_listenee_nickname'];
        $profile  = $_GET['omb_listenee_profile'];

        $user = User::staticGet('uri', $listener);
        if (!$user) {
            throw new Exception(sprintf(_('Listener URI ‘%s’ not found here.'),
                                        $listener));
        }

        if (strlen($listenee) > 255) {
            throw new Exception(sprintf(_('Listenee URI ‘%s’ is too long.'),
                                        $listenee));
        }

        $other = User::staticGet('uri', $listenee);
        if ($other) {
            throw new Exception(sprintf(_('Listenee URI ‘%s’ is a local user.'),
                                        $listenee));
        }

        $remote = Remote_profile::staticGet('uri', $listenee);
        if ($remote) {
            $sub             = new Subscription();
            $sub->subscriber = $user->id;
            $sub->subscribed = $remote->id;
            if ($sub->find(true)) {
                throw new Exception('You are already subscribed to this user.');
            }
        }

        if ($profile == common_profile_url($nickname)) {
            throw new Exception(sprintf(_('Profile URL ‘%s’ is for a local user.'),
                                        $profile));

        }

        $license      = $_GET['omb_listenee_license'];
        $site_license = common_config('license', 'url');
        if (!common_compatible_license($license, $site_license)) {
            throw new Exception(sprintf(_('Listenee stream license ‘%1$s’ is not ' .
                                          'compatible with site license ‘%2$s’.'),
                                        $license, $site_license));
        }

        $avatar = $_GET['omb_listenee_avatar'];
        if ($avatar) {
            if (!common_valid_http_url($avatar) || strlen($avatar) > 255) {
                throw new Exception(sprintf(_('Avatar URL ‘%s’ is not valid.'),
                                            $avatar));
            }
            $size = @getimagesize($avatar);
            if (!$size) {
                throw new Exception(sprintf(_('Can’t read avatar URL ‘%s’.'),
                                            $avatar));
            }
            if (!in_array($size[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG,
                                          IMAGETYPE_PNG))) {
                throw new Exception(sprintf(_('Wrong image type for avatar URL '.
                                              '‘%s’.'), $avatar));
            }
        }
    }
}
