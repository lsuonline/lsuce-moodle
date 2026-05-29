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
 * Block sidebar widget for the Backadel admin plugin.
 *
 * @package    block_backadel
 * @category   block
 * @copyright  2016 Louisiana State University - David Elliott, Robert Russo, Chad Mazilly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

/**
 * Backadel admin block sidebar widget.
 *
 * @package block_backadel
 */
class block_backadel extends block_base {

    /**
     * Sets the block title.
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_backadel');
    }

    #[\Override]
    public function applicable_formats(): array {
        return ['site' => true, 'my' => false, 'course' => false];
    }

    #[\Override]
    public function has_config(): bool {
        return true;
    }

    /**
     * Formats a number of seconds as a human-readable elapsed-time string.
     *
     * @param int $secondsrun Elapsed seconds.
     * @return string Human-readable duration (e.g. "2 hours, 5 minutes, 30 seconds").
     */
    public static function seconds2human(int $secondsrun): string {
        $months = (int) floor($secondsrun / 2592000);
        $days   = (int) floor(($secondsrun % 2592000) / 86400);
        $hours  = (int) floor(($secondsrun % 86400) / 3600);
        $mins   = (int) floor(($secondsrun % 3600) / 60);
        $secs   = $secondsrun % 60;

        $parts = [];
        if ($months > 0) {
            $parts[] = $months . ' months';
        }
        if ($days > 0) {
            $parts[] = $days . ' days';
        }
        if ($hours > 0) {
            $parts[] = $hours . ' hours';
        }
        if ($mins > 0) {
            $parts[] = $mins . ' minutes';
        }
        $parts[] = $secs . ' seconds';

        return implode(', ', $parts);
    }

    #[\Override]
    public function get_content(): stdClass {
        global $DB, $OUTPUT, $PAGE, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        if (!is_siteadmin($USER->id)) {
            $this->content = new stdClass();
            $this->content->items = [];
            $this->content->icons = [];
            $this->content->footer = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $numpending = $DB->count_records_select('block_backadel_statuses', "status='SUCCESS'");
        $numfailed  = $DB->count_records_select('block_backadel_statuses', "status='FAIL'");

        $running = get_config('block_backadel', 'running');
        if (!$running) {
            $statustext = get_string('status_not_running', 'block_backadel');
        } else {
            $secondsrun = (int) round(time() - (int) $running);
            $statustext = get_string('status_running', 'block_backadel', self::seconds2human($secondsrun));
        }

        $iconparams = ['class' => 'icon'];
        $data = [
            'links' => [
                [
                    'iconhtml' => $OUTPUT->pix_icon('i/backup', '', 'moodle', $iconparams),
                    'url'      => (new moodle_url('/blocks/backadel/search.php'))->out(false),
                    'label'    => get_string('block_index', 'block_backadel'),
                    'count'    => null,
                    'countkey' => '',
                ],
                [
                    'iconhtml' => $OUTPUT->pix_icon('i/delete', '', 'moodle', $iconparams),
                    'url'      => (new moodle_url('/blocks/backadel/delete.php'))->out(false),
                    'label'    => get_string('block_delete', 'block_backadel'),
                    'count'    => $numpending > 0 ? $numpending : null,
                    'countkey' => 'pending',
                ],
                [
                    'iconhtml' => $OUTPUT->pix_icon('i/risk_xss', '', 'moodle', $iconparams),
                    'url'      => (new moodle_url('/blocks/backadel/failed.php'))->out(false),
                    'label'    => get_string('block_failed', 'block_backadel'),
                    'count'    => $numfailed > 0 ? $numfailed : null,
                    'countkey' => 'failed',
                ],
            ],
            'statustext'     => $statustext,
            'statusiconhtml' => $OUTPUT->pix_icon('i/calendareventtime', '', 'moodle', $iconparams),
        ];

        $this->content->text = $OUTPUT->render_from_template('block_backadel/block_widget', $data);

        // Boot the live-polling AMD module so the widget refreshes every 10 s.
        $PAGE->requires->string_for_js('status_running', 'block_backadel');
        $PAGE->requires->string_for_js('status_not_running', 'block_backadel');
        $PAGE->requires->js_call_amd('block_backadel/migration_status_poll', 'init');

        return $this->content;
    }
}
