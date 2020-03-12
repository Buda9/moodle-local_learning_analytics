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
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_learning_analytics\local\outputs\table;
use local_learning_analytics\report_base;
use local_learning_analytics\local\outputs\plot;

const STRINGS = [
    'platform' => [
        'desktop' => 'Desktop Browser',
        'mobile' => 'Mobile Browser',
        'api' => 'App',
        'other' => 'unknown' // TODO replace with language key
    ],
    'os' => [
        'windows' => 'Microsoft Windows',
        'mac' => 'macOS',
        'linux' => 'Linux',
        'other' => 'unknown' // TODO language key
    ],
    'browser' => [
        'chrome' => 'Google Chrome',
        'edge' => 'Microsoft Edge',
        'firefox' => 'Mozilla Firefox',
        'ie' => 'Internet Explorer',
        'opera' => 'Opera',
        'safari' => 'Safari',
        'other' => 'other' // TODO language key
    ],
    'mobile' => [
        'android' => 'Android',
        'ios' => 'iOS',
        'other' => 'unknown' // TODO language key
    ]
];

class lareport_browser_os extends report_base {

    private static $barcolors = [
        '#66b5ab',
        '#F26522',
        '#ffda6e',
        '#A9CF54',
        '#EA030E'
    ];

    private static function createseries($perc, $text, $i) {
        return [
            'y' => ['value'],
            'x' => [$perc],
            'orientation' => 'h',
            'hoverinfo' => 'none',
            'marker' => [
                'color' => (self::$barcolors[$i % count(self::$barcolors)])
            ],
            'name' => $text,
            'type' => 'bar',
            'text' => $text
        ];
    }

    private static function createplot(array $results, string $entrykey) {
        $entries = $results[$entrykey];

        $annotations = [];
        $total = 0;
        foreach ($entries as $value) {
            if ($value >= 1) { // TODO privacy
                $total += $value;
            }
        }

        $plot = new plot();

        $i = 0;
        $percsofar = 0;
        foreach ($entries as $key => $value) {
            if ($value < 1) { // TODO privacy
                continue;
            }
            $perc = round(100 * $value / $total);
            if ($perc > 3) { // Otherwise it might be too small to display.
                $annotations[] = [
                    'x' => ($percsofar + ($perc / 2)),
                    'y' => 'value',
                    'text' => $perc . '%',
                    'font' => ['color' => '#fff', 'size' => 14],
                    'showarrow' => false,
                    'xanchor' => 'center'
                ];
            }
            $annotations[] = [
                'x' => ($percsofar + ($perc / 2)),
                'y' => 'value',
                'yshift' => 16,
                'text' => STRINGS[$entrykey][$key],
                'font' => ['color' => '#000','size' => 16],
                'showarrow' => false,
                'xanchor' => 'center',
                'yanchor' => 'bottom'
            ];
            $percsofar += $perc;
            $series = self::createseries($perc, $key, $i);
            $plot->add_series($series);
            $i += 1;
        }

        $layout = new stdClass();
        $layout->barmode = 'stack';
        $layout->annotations = $annotations;
        $layout->xaxis = ['visible' => false, 'range' => [0, 100], 'fixedrange' => true ];
        $layout->yaxis = ['visible' => false, 'fixedrange' => true];
        $layout->showlegend = false;
        $layout->margin = ['l' => 0, 'r' => 0, 't' => 10, 'b' => 0];

        $plot->set_layout($layout);
        $plot->set_height(70);
        $plot->set_static_plot(true);
        if ($i === 0) {
            return [''];
        }
        return [$plot];
    }

    private function platformplot(int $courseid) {
        global $DB;

        $result = $DB->get_record('lalog_browser_os', ['courseid' => $courseid], '*', MUST_EXIST);

        $results = [
            'platform' => [],
            'os' => [],
            'browser' => [],
            'mobile' => [],
        ];
        foreach ($result as $key => $value) {
            $separatorindex = strpos($key, '_');
            if ($separatorindex === false) {
                continue;
            }
            $prefix = substr($key, 0, $separatorindex);
            $target = substr($key, $separatorindex + 1);
            $results[$prefix][$target] = $value;
        }
        rsort($results['browser']);

        return array_merge(
            self::createplot($results, 'platform'),
            self::createplot($results, 'os'),
            self::createplot($results, 'mobile')
        );
    }

    public function run(array $params): array {
        $courseid = $params['course'];

        return $this->platformplot($courseid);
    }

    public function params(): array {
        return [
            'course' => required_param('course', PARAM_INT)
        ];
    }

}