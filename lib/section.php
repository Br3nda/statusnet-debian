<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Base class for sections (sidebar widgets)
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
 * @category  Widget
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/widget.php';

/**
 * Base class for sections
 *
 * These are the widgets that show interesting data about a person
 * group, or site.
 *
 * @category Widget
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class Section extends Widget
{
    /**
     * Show the form
     *
     * Uses a recipe to output the form.
     *
     * @return void
     * @see Widget::show()
     */

    function show()
    {
        $this->out->elementStart('div',
                                 array('id' => $this->divId(),
                                       'class' => 'section'));

        $this->out->element('h2', null,
                            $this->title());

        $have_more = $this->showContent();

        if ($have_more) {
            $this->out->elementStart('p');
            $this->out->element('a', array('href' => $this->moreUrl(),
                                      'class' => 'more'),
                           $this->moreTitle());
            $this->out->elementEnd('p');
        }

        $this->out->elementEnd('div');
    }

    function divId()
    {
        return 'generic_section';
    }

    function title()
    {
        return _('Untitled section');
    }

    function showContent()
    {
        $this->out->element('p', null,
                            _('(None)'));
        return false;
    }

    function moreUrl()
    {
        return null;
    }

    function moreTitle()
    {
        return _('More...');
    }
}
