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
 * Version info for the Sections learners
 *
 * @package     local_learning_analytics
 * @copyright   2018 Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @author      Marcel Behrmann <behrmann@lfi.rwth-aachen.de>
 * @author      Thomas Dondorf <dondorf@lfi.rwth-aachen.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_learning_analytics\local\outputs\table;
use local_learning_analytics\parameter;
use local_learning_analytics\report_base;
use lareport_learners\query_helper;

class lareport_learners extends report_base {

    public function get_parameter(): array {
        return [
            new parameter('course', parameter::TYPE_COURSE, true, FILTER_SANITIZE_NUMBER_INT),
        ];
    }

    public function run(array $params): array {
        $courseid = (int) $params['course'];

        $learners = query_helper::query_learners($courseid);
        $table = new table();
        $table->set_header_local(['firstname', 'lastname', 'firstaccess', 'lastaccess', 'hits', 'sessions'], 'lareport_learners');

        $maxHits = reset($learners)->hits;
        $maxSessions = 1;
        foreach ($learners as $learner) {
            $maxSessions = max($maxSessions, $learner->sessions);
        }

        foreach ($learners as $learner) {
            $table->add_row([
                $learner->firstname,
                $learner->lastname,
                userdate($learner->firstaccess),
                userdate($learner->lastaccess),
                table::fancyNumberCell(
                    (int) $learner->hits,
                    $maxHits,
                    'green'
                ),
                table::fancyNumberCell(
                    (int) $learner->sessions,
                    $maxSessions,
                    'red'
                )
            ]);
        }

        return [ $table ];
    }

}