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
 * Course routes.
 *
 * @package    webservice_restful
 * @copyright  2017 Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace webservice_restful;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib/include.php');

$routes = [
    [
        'regex' => '/courses',
        'methods' => [

            // Get the courses.
            'GET' => external_api_method('core_course_get_courses'),

            // Create a course.
            'POST' => external_api_method('core_course_create_courses', [
                'argsmapper' => function($args, $request) {
                    return ['courses' => [$request->data]];
                },
                'resultmapper' => function($result) {
                    return reset($result);
                },
                'errorhandler' => function(\Throwable $e, $args, $request, $options) {
                    if ($e instanceof \moodle_exception) {
                        if ($e->errorcode === 'shortnametaken') {
                            return bad_request_from_throwable($e);
                        }
                    }
                    return traditional_response_for_external_api_error_call($e, $args, $request, $options);
                },
            ]),
        ]
    ],

    [
        'regex' => '/courses/([0-9]+)',
        'precheck' => precheck_exists_or_not_found('course', 0),
        'methods' => [

            // Get a course.
            'GET' => external_api_method('core_course_get_courses', [
                'argsmapper' => function($args) {
                    return ['options' => ['ids' => [$args[0]]]];
                },
                'resultmapper' => function($result) {
                    return reset($result) ?: null;
                }
            ]),

            // Delete a course.
            'DELETE' => external_api_method('core_course_delete_courses', [
                'argsmapper' => function($args, $request) {
                    return ['courseids' => [$args[0]]];
                },
                'responsemaker' => function($result) {
                    if (!empty($result['warnings'])) {
                        if ($result['warnings'][0]['warningcode'] === 'unknowncourseidnumber') {
                            return not_found();
                        }
                        return bad_request(reset($result['warnings']));
                    }
                    return no_content();
                }
            ]),

            // Update a course.
            'PATCH' => external_api_method('core_course_update_courses', [
                'argsmapper' => function($args, $request) {
                    return ['courses' => [['id' => $args[0]] + (array) $request->data]];
                },
                'responsemaker' => function($result) {
                    if (!empty($result['warnings'])) {
                        return bad_request(reset($result['warnings']));
                    }
                    return no_content();
                }
            ]),
        ],
    ],

    [
        'regex' => '/courses/([0-9]+)/duplicate',
        'precheck' => precheck_exists_or_not_found('course', 0),
        'methods' => [

            // Duplicate a course.
            'POST' => external_api_method('core_course_duplicate_course', [
                'argsmapper' => function($args, $request) {
                    return ['courseid' => $args[0]] + (array) $request->data;
                }
            ]),
        ]
    ]
];
