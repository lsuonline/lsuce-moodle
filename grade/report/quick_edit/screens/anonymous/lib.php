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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/uilib.php');

class quick_edit_anonymous extends quick_edit_tablelike
    implements selectable_items, item_filtering {

    private static $supported;

    public static function is_supported() {
        global $COURSE;

        if (is_null(self::$supported)) {
            if (class_exists('grade_anonymous')) {
                self::$supported = grade_anonymous::is_supported($COURSE);
            } else {
                self::$supported = false;
            }
        }

        return self::$supported;
    }

    public function description() {
        return get_string('anonymousitem', 'grades');
    }

    public function options() {
        if (!self::is_supported()) {
            return array();
        }

        global $DB;

        $sql = 'SELECT gi.id, gi.itemname
            FROM {grade_items} gi, {grade_anon_items} anon
            WHERE gi.id = anon.itemid
              AND gi.courseid = ' . $this->courseid;

        return $DB->get_records_sql_menu($sql);
    }

    public function item_type() {
        return 'anonymous';
    }

    public function original_definition() {
        $defaults = array('finalgrade');

        if ($this->item->is_completed()) {
            $defaults[] = 'adjust_value';
            $defaults[] = 'exclude';
        }

        return $defaults;
    }

    public function additional_headers($line) {
        if ($this->item->is_completed()) {
            array_unshift($line, '');
            $line[1] = get_string('firstname') . ' (' . get_string('alternatename') . ') ' . get_string('lastname');
            $line[] = get_string('anonymousadjusts', 'grades');
            $line[] = $this->make_toggle_links('exclude');
        }

        return $line;
    }

    public static function filter($item) {
        if (!self::is_supported()) {
            return true;
        }

        $anonid = grade_anonymous::fetch(array('itemid' => $item->id));

        return empty($anonid);
    }

    /**
     * Load a valid list of users for this gradebook as the screen "items".
     * @return array $users A list of enroled users.
     */
    protected function load_users() {
        global $CFG;

        // Create a graded_users_iterator because it will properly check the groups etc.
        require_once($CFG->dirroot.'/grade/lib.php');
        $gui = new \graded_users_iterator($this->course, null, $this->groupid);
        $gui->require_active_enrolment(true);
        $gui->init();

        // Flatten the users.
        $users = array();
        while ($user = $gui->next_user()) {
            $users[$user->user->id] = $user->user;
        }
        return $users;
    }

    public function init($selfitemisempty = false) {
        $graded = get_config('moodle', 'gradebookroles');

        if ($selfitemisempty) {
            return;
        }

        $this->item = grade_anonymous::fetch(array('itemid' => $this->itemid));

        $mainuserfields = user_picture::fields('u', array('id'), 'userid');

        $this->students = array();
        $this->coursestudents = array();
        $suspended = array('status', '0');

        if (count(explode(',', $graded)) > 1) {
            $roleids = explode(',', $graded);
            foreach ($roleids as $roleid) {
                $this->students = $this->students + get_role_users(
                    $roleid, $this->context, false, '',
                    'u.id, u.lastname, u.firstname', null, $this->groupid, null, null, null, $suspended
                );
                $this->coursestudents = $this->coursestudents + get_role_users(
                    $roleid, $this->context, false, '',
                    'u.id, u.lastname, u.firstname', null, '0', null, null, null, $suspended
                );
            }
        } else {
            $this->students = get_role_users($graded, $this->context, false, '',
                'u.id, u.lastname, u.firstname', null, $this->groupid, null, null, null, $suspended);
            $this->coursestudents = get_role_users($graded, $this->context, false, '',
                'u.id, u.lastname, u.firstname', null, '0', null, null, null, $suspended);
        }

        $this->items = $this->item->is_completed() ? $this->load_users() : grade_anonymous::anonymous_users($this->students);

        $this->definition = $this->original_definition();
        $this->headers = $this->original_headers();
    }

    public function original_headers() {
        return $this->additional_headers(array(
            get_string('anonymous', 'grades'),
            get_string('range', 'grades'),
            get_string('grade', 'grades')
        ));
    }

    public function format_line($user) {
        global $OUTPUT;

        $grade = $this->fetch_grade_or_default($this->item, $user->id);

        if ($this->item->is_completed()) {
            if (!empty($user->alternatename)) {
                $user->imagealt = $user->alternatename . ' (' . $user->firstname . ') ' . $user->lastname;
            } else {
                $user->imagealt = $user->firstname . ' ' . $user->lastname;
            }
            $line = array(
                $OUTPUT->user_picture($user),
                $this->format_link('user', $user->id, $user->imagealt)
            );
        } else {
            $line = array($user->data);
        }

        $line[] = $this->item_range();

        return $this->format_definition($line, $grade);
    }

    public function item_range() {
        if (empty($this->range)) {
            $this->range = $this->factory()
                ->create('range')
                ->format($this->item->load_item());
        }

        return $this->range;
    }

    public function heading() {
        return $this->item->get_name();
    }

    public function fetch_grade_or_default($item, $userid) {
        return $this->item->load_grade($userid);
    }

    public function factory() {
        if (empty($this->_factory)) {
            $this->_factory = new anonymous_ui_factory();
        }

        return $this->_factory;
    }

    public function bulk_insert() {
        return '';
    }

    public function process($data) {
        $warnings = parent::process($data);

        $anon = $this->item;

        if (empty($warnings) and !$anon->is_completed()) {
            $anon->set_completed($anon->check_completed($this->coursestudents, $this->students));
        }

        return $warnings;
    }
}