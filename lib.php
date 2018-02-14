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
 * This plugin is used to access coursefiles repository
 *
 * @since 2.0
 * @package    repository_sharedresources
 * @copyright  2010 Dongsheng Cai {@link http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot.'/repository/sharedresources/filebrowser/file_browser.php');
require_once($CFG->dirroot.'/local/sharedresources/classes/navigator.class.php');
require_once($CFG->dirroot.'/mod/sharedresource/lib.php');

/**
 * repository_sharedresources class is used to browse sharedresources
 *
 * @since 2.0
 * @package    repository_sharedresources
 * @copyright  2013 Valery Fremaux {@link http://www.mylearningfactory.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_sharedresources extends repository {

    /**
     * sharedresources plugin doesn't require login, so list all files
     *
     * @return mixed
     */
    public function print_login() {
        return $this->get_listing();
    }

    /**
     * sharedresources plugin doesn't require login, so list all files
     *
     * @return mixed
     */
    public function check_login() {
        return true;
    }

    /**
     * Get file listing as an array of nodes.
     *
     *  $node = array(
     *      'title' => $child->get_visible_name(),
     *      'datemodified' => $child->get_timemodified(),
     *      'datecreated' => $child->get_timecreated(),
     *      'path' => $encodedpath,
     *      'children' => array(),
     *      'thumbnail' => $OUTPUT->pix_url(file_folder_icon(90))->out(false)
     *  );
     *
     *   $node = array(
     *      'title' => $child->get_visible_name(),
     *      'size' => $child->get_filesize(),
     *      'author' => $child->get_author(),
     *      'license' => $child->get_license(),
     *      'datemodified' => $child->get_timemodified(),
     *      'datecreated' => $child->get_timecreated(),
     *      'source'=> $encodedpath,
     *      'isref' => $child->is_external_file(),
     *      'thumbnail' => $OUTPUT->pix_url(file_file_icon($child, 90))->out(false)
     *  );
     *
     *      $node['originalmissing'] = true;
     *
     * @param string $encodedpath
     * @return mixed
     */
    public function get_listing($encodedpath = '', $page = '') {
        global $CFG, $USER, $OUTPUT, $DB;

        $fs = get_file_storage();

        $ret = array();
        $ret['dynload'] = true;
        $ret['nosearch'] = true;
        $ret['nologin'] = true;
        $list = array();

        $config = get_config('sharedresource');
        $plugin = sharedresource_get_plugin($config->schema);

        $taxonomies = \local_sharedresources\browser\navigation::get_taxonomies(true);

        if (!empty($encodedpath)) {
            /*
             * filepath contains a virtual path in the sharedresource library, as :
             * /taxonomyname/tokenid/tokenid/tokenid
             * filename contains the resource identifier or a single dot when a directory.
             */
            $params = unserialize(base64_decode($encodedpath));
            if (is_array($params)) {
                $taxonomy = array_shift($params);
                $filename = array_pop($params);
                $taxonomypath = $params; // Tokenids that remain.
            }
        } else {
            $taxonomy = '';
            $filename = '.';
            $taxonomypath = array();
        }

        // debug_trace("Requires level for $taxonomy/".implode('/', $taxonomypath)). "/$filename ";

        $context = context_system::instance();

        if ($taxonomy == '') {
            // Start with taxonomy list.
            foreach ($taxonomies as $tx) {

                if (mod_sharedresource_supports_feature('taxonomy/accessctl')) {
                    $navigator = new \local_sharedresources\browser\navigation($tx);
                    if (!$navigator->can_use()) {
                        continue;
                    }
                }

                $path = array($tx->shortname, '.');
                $encodedpath = base64_encode(serialize($path));
                $node = array(
                   'title' => format_string($tx->name),
                   /*
                   'datemodified' => $child->get_timemodified(),
                   'datecreated' => $child->get_timecreated(),
                   */
                   'path' => $encodedpath,
                   'children' => array(),
                   'thumbnail' => $OUTPUT->pix_url('classification', 'repository_sharedresources')->out(false)
               );
               $list[] = $node;
            }
        } else {

            $taxonomy = $DB->get_record('sharedresource_classif', array('shortname' => $taxonomy));
            $navigator = new \local_sharedresources\browser\navigation($taxonomy);

            // Build item path starting with taxonomy root.
            $pathelms = array($taxonomy->shortname);
            $taxpath = base64_encode(serialize($pathelms));
            $ret['path'] = array(array('path' => base64_encode(serialize(array())), 'name' => get_string('library', 'local_sharedresources')));
            $ret['path'][] = array('path' => $taxpath, 'name' => format_string($taxonomy->name));

            // continue entering taxonomy tokens
            $lastcatid = 0;
            if (!empty($taxonomypath)) {
                foreach ($taxonomypath as $tokenid) {
                    // We make the breadcrumb array.
                    $token = $navigator->get_token_info($tokenid);
                    $pathelms[] = $tokenid;
                    $taxpath = base64_encode(serialize(array_merge($pathelms, array('.'))));
                    $ret['path'][] = array('path' => $taxpath, 'name' => format_string($token->value));
                    $lastcatid = $tokenid;
                }
            }
            $pathelms[] = '.'; // Always terminate folder paths with dot.

            // Now Build file tree.

            $children = $navigator->get_children($lastcatid);
            if ($children) {
                foreach ($children as $child) {
                    $entryelms = $pathelms;
                    array_pop($entryelms); // Remove ending dot.
                    $entryelms[] = $child->id;
                    $entryelms[] = '.';
                    $encodedpath = base64_encode(serialize($entryelms));
                    $node = array(
                        'title' => format_string($child->name),
                        /*
                        'datemodified' => $child->get_timemodified(),
                        'datecreated' => $child->get_timecreated(),
                        */
                        'path' => $encodedpath,
                        'readablepath' => implode('/', $entryelms),
                        'children' => array(),
                        'thumbnail' => $OUTPUT->pix_url(file_folder_icon(90))->out(false)
                    );
                    $list[] = $node;
                }
            }

            $entries = $navigator->get_entries($lastcatid);
            foreach ($entries as $entry) {
                $shrentry = \mod_sharedresource\entry::read_by_id($entry->id);

                if (mod_sharedresource_supports_feature('entry/accessctl')) {
                    if (!$shrentry->has_access()) {
                        continue;
                    }
                }

                $entryelm = $pathelms;
                $entryelm[] = $entry->identifier;
                $encodedpath = base64_encode(serialize($entryelm));
                if (!empty($entry->file)) {
                    // Only physical files can be shown.
                    $mainfile = $fs->get_file_by_id($entry->file);

                    if (!$mainfile) {
                        $node = array();
                        $node['title'] = format_string($entry->title);
                        $node['originalmissing'] = true;
                        $list[] = $node;
                        continue;
                    }

                    $mainfilearr = array(
                        'component' => $mainfile->get_component(),
                        'contextid' => $mainfile->get_contextid(),
                        'filearea' => $mainfile->get_filearea(),
                        'itemid' => $mainfile->get_itemid(),
                        'filepath' => $mainfile->get_filepath(),
                        'filename' => $mainfile->get_filename(),
                    );

                    $node = array(
                        'title' => $entry->title,
                        'size' => $mainfile->get_filesize(),
                        /*
                        'author' => $entry->get_author(),
                        'license' => $entry->get_license(),
                        */
                        'datemodified' => $mainfile->get_timemodified(),
                        'datecreated' => $mainfile->get_timecreated(),
                        'source'=> base64_encode(json_encode($mainfilearr)),
                        'isref' => false,
                        'thumbnail' => $OUTPUT->pix_url(file_file_icon($mainfile, 90))->out(false)
                    );

                    if ($imageinfo = $mainfile->get_imageinfo()) {
                        $fileurl = new moodle_url($mainfile->get_url());
                        $node['realthumbnail'] = $fileurl->out(false, array('preview' => 'thumb', 'oid' => $mainfile->get_timemodified()));
                        $node['realicon'] = $fileurl->out(false, array('preview' => 'tinyicon', 'oid' => $mainfile->get_timemodified()));
                        $node['image_width'] = $imageinfo['width'];
                        $node['image_height'] = $imageinfo['height'];
                    }
                    $list[] = $node;
                } else {
                    // This is an URL type resource, including a network resource.
                }
            }
        }
        $ret['list'] = array_filter($list, array($this, 'filter'));
        return $ret;
    }

    public function get_link($encoded) {
        $info = array();

        // the final file
        $params = unserialize(base64_decode($encoded));
        $contextid  = clean_param($params['contextid'], PARAM_INT);
        $fileitemid = clean_param($params['itemid'], PARAM_INT);
        $filename = clean_param($params['filename'], PARAM_FILE);
        $filepath = clean_param($params['filepath'], PARAM_PATH);;
        $filearea = clean_param($params['filearea'], PARAM_AREA);
        $component = clean_param($params['component'], PARAM_COMPONENT);
        $context = context::instance_by_id($contextid);

        $file_info = $browser->get_file_info($context, $component, $filearea, $fileitemid, $filepath, $filename);
        return $file_info->get_url();
    }

    /**
     * Return is the instance is visible
     * (is the type visible ? is the context enable ?)
     *
     * @return boolean
     */
    /*
    public function is_visible() {
        return parent::is_visible();
    }
    */

    public function get_name() {
        list($context, $course, $cm) = get_context_info_array($this->context->id);
        if (!empty($course)) {
            $prefix = get_string('sharedresources', 'repository_sharedresources');
            return $prefix.format_string($course->shortname, true, array('context' => $context->get_course_context(true)));
        } else {
            return get_string('sharedresources', 'repository_sharedresources');
        }
    }

    public function supported_returntypes() {
        return (FILE_INTERNAL | FILE_REFERENCE);
    }

    public static function get_type_option_names() {
        return array();
    }

    /**
     * Does this repository used to browse moodle files?
     *
     * @return boolean
     */
    public function has_moodle_files() {
        return true;
    }

    /**
     * Return reference file life time
     *
     * @param string $ref
     * @return int
     */
    public function get_reference_file_lifetime($ref) {
        // This should be realtime.
        return 0;
    }

    /**
     * Prepare file reference information. Source can be local or remote depending on the 'remoterepo'
     * information.
     *
     * @param string $source source of the file, returned by repository as 'source' and received back from user (not cleaned)
     * @return string file reference, ready to be stored
     */
    public function get_file_reference($source) {
        if ($source && $this->has_moodle_files()) {
            $params = @json_decode(base64_decode($source), true);

            if (!is_array($params) ||
                (empty($params['contextid']) && empty($params['remoterepo'])) ||
                (!empty($params['remoterepo']) && empty($params['url'])) ) {
                throw new repository_exception('invalidparams', 'repository');
            }

            if (empty($params['remoterepo'])) {
                $params = array(
                    'component' => empty($params['component']) ? ''   : clean_param($params['component'], PARAM_COMPONENT),
                    'filearea'  => empty($params['filearea'])  ? ''   : clean_param($params['filearea'], PARAM_AREA),
                    'itemid'    => empty($params['itemid'])    ? 0    : clean_param($params['itemid'], PARAM_INT),
                    'filename'  => empty($params['filename'])  ? null : clean_param($params['filename'], PARAM_FILE),
                    'filepath'  => empty($params['filepath'])  ? null : clean_param($params['filepath'], PARAM_PATH),
                    'contextid' => clean_param($params['contextid'], PARAM_INT)
                );
                // Check if context exists.
                if (!context::instance_by_id($params['contextid'], IGNORE_MISSING)) {
                    throw new repository_exception('invalidparams', 'repository');
                }
                return file_storage::pack_reference($params);
            } else {
                // TODO : See how to report remote reference.
            }
        }
        return $source;
    }

    /**
     * Repository method to make sure that user can access particular file.
     *
     * This is checked when user tries to pick the file from repository to deal with
     * potential parameter substitutions is request
     *
     * @param string $source source of the file, returned by repository as 'source' and received back from user (not cleaned)
     * @return bool whether the file is accessible by current user
     */
    public function file_is_accessible($source) {
        if ($this->has_moodle_files()) {
            $reference = $this->get_file_reference($source);
            try {
                $params = file_storage::unpack_reference($reference, true);
            } catch (file_reference_exception $e) {
                return false;
            }
            $browser = new sharedresource_file_browser();
            $context = context::instance_by_id($params['contextid']);
            $file_info = $browser->get_file_info($context, $params['component'], $params['filearea'],
                    $params['itemid'], $params['filepath'], $params['filename']);
            return !empty($file_info);
        }
        return true;
    }
}
