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

$helptext = <<<END_OF_HELP
Script for converting mail messages into notices. Takes message body
as STDIN.

END_OF_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';
require_once INSTALLDIR.'/lib/mailhandler.php';

if (common_config('emailpost', 'enabled')) {
    $mh = new MailHandler();
    $mh->handle_message(file_get_contents('php://stdin'));
}
