<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin capabilities.
 *
 * @package    repository_coursefiles
 * @copyright  2010 Dongsheng Cai
 * @author     Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    // People with this capability can use the resources in their courses.
    /*
     * This means having access to the resource deployment buttons in the sharedresource
     * library interface.
     */
    'repository/sharedresources:use' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'coursecreator' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // People with this capability can view the sharedresource catalog.
    /*
     * this means mainly being able to browse and search in the library and
     * see resource content when possible.
     */
    'repository/sharedresources:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'student' => CAP_ALLOW
        )
    ),

    // People with this capability can view the whole library independantly of access control.
    'repository/sharedresources:accessall' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),

    // People having this capability can manage the sharedresource system.
    /*
     * This means :
     * Being able to edit, delete, bulk import library entries
     * Change global settings
     * Configure schema application profiles
     * configure classificaitons
     */
    'repository/sharedresources:manage' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    // People having this capability sees the schema items that were configured for the author view.
    'repository/sharedresources:authormetadata' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    ),

    // People having this capability sees the schema items that were configured for the librarian view.
    'repository/sharedresources:indexermetadata' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'coursecreator' => CAP_ALLOW
        )
    ),

    // People having this capability sees the schema items that were configured for the admin view.
    'repository/sharedresources:systemmetadata' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    )
);
