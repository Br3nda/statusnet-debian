#!/usr/bin/env php
<?php
/*
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

define('INSTALLDIR', realpath(dirname(__FILE__) . '/..'));

$shortoptions = 'fi::a';
$longoptions = array('id::', 'foreground', 'all');

$helptext = <<<END_OF_XMPP_HELP
Daemon script for receiving new notices from Jabber users.

    -i --id           Identity (default none)
    -a --all          Handle XMPP for all local sites
                      (requires Stomp queue handler, status_network setup)
    -f --foreground   Stay in the foreground (default background)

END_OF_XMPP_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

require_once INSTALLDIR . '/lib/jabber.php';

class XMPPDaemon extends SpawningDaemon
{
    protected $allsites = false;

    function __construct($id=null, $daemonize=true, $threads=1, $allsites=false)
    {
        if ($threads != 1) {
            // This should never happen. :)
            throw new Exception("XMPPDaemon can must run single-threaded");
        }
        parent::__construct($id, $daemonize, $threads);
        $this->allsites = $allsites;
    }

    function runThread()
    {
        common_log(LOG_INFO, 'Waiting to listen to XMPP and queues');

        $master = new XmppMaster($this->get_id(), $this->processManager());
        $master->init($this->allsites);
        $master->service();

        common_log(LOG_INFO, 'terminating normally');

        return $master->respawn ? self::EXIT_RESTART : self::EXIT_SHUTDOWN;
    }

}

class XmppMaster extends IoMaster
{
    protected $processManager;

    function __construct($id, $processManager)
    {
        parent::__construct($id);
        $this->processManager = $processManager;
    }

    /**
     * Initialize IoManagers for the currently configured site
     * which are appropriate to this instance.
     */
    function initManagers()
    {
        if (common_config('xmpp', 'enabled')) {
            $qm = QueueManager::get();
            $qm->setActiveGroup('xmpp');
            $this->instantiate($qm);
            $this->instantiate(XmppManager::get());
            $this->instantiate($this->processManager);
        }
    }
}

// Abort immediately if xmpp is not enabled, otherwise the daemon chews up
// lots of CPU trying to connect to unconfigured servers
// @fixme do this check after we've run through the site list so we
// don't have to find an XMPP site to start up when using --all mode.
if (common_config('xmpp','enabled')==false) {
    print "Aborting daemon - xmpp is disabled\n";
    exit(1);
}

if (version_compare(PHP_VERSION, '5.2.6', '<')) {
    $arch = php_uname('m');
    if ($arch == 'x86_64' || $arch == 'amd64') {
        print "Aborting daemon - 64-bit PHP prior to 5.2.6 has known bugs in stream_select; you are running " . PHP_VERSION . " on $arch.\n";
        exit(1);
    }
}

if (have_option('i', 'id')) {
    $id = get_option_value('i', 'id');
} else if (count($args) > 0) {
    $id = $args[0];
} else {
    $id = null;
}

$foreground = have_option('f', 'foreground');
$all = have_option('a') || have_option('--all');

$daemon = new XMPPDaemon($id, !$foreground, 1, $all);

$daemon->runOnce();
