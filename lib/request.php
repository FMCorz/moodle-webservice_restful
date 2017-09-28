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
 * Request related functions.
 *
 * @package    webservice_restful
 * @copyright  2017 Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace webservice_restful;
defined('MOODLE_INTERNAL') || die();

/**
 * Extract token from request.
 *
 * @param object $request The request.
 * @return string|null
 */
function extract_token_from_request($request) {
    $headers = $request->headers;
    if (!isset($headers['Authorization'])) {
        return null;
    }
    list($type, $token) = explode(' ', $headers['Authorization']);
    return $token;
}

/**
 * Get the requested path.
 *
 * @param moodle_url $baseurl The base URL.
 * @param string $param The query string param to use as fallback.
 * @return string
 */
function get_requested_path(\moodle_url $baseurl, $param = '_r') {
    global $SCRIPT;

    $relativepath = false;
    $hasforcedslashargs = false;
    $routepath = $baseurl->get_path();

    if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
        // Checks whether $_SERVER['REQUEST_URI'] contains '.../index.php/' instead of '.../index.php?'.
        if ((strpos($_SERVER['REQUEST_URI'], $routepath . '/') !== false)
                && isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
            $hasforcedslashargs = true;
        }
    }

    if (!$hasforcedslashargs) {
        $relativepath = optional_param($param, false, PARAM_PATH);
    }

    // Did we have a relative path yet?
    if ($relativepath !== false and $relativepath !== '') {
        return $relativepath;
    }
    $relativepath = false;

    // Then try extract file from the slasharguments.
    if (stripos($_SERVER['SERVER_SOFTWARE'], 'iis') !== false) {
        if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '') {
            if (strpos($_SERVER['PATH_INFO'], $SCRIPT) === false) {
                $relativepath = clean_param(urldecode($_SERVER['PATH_INFO']), PARAM_PATH);
            }
        }
    } else {
        // All other apache-like servers depend on PATH_INFO.
        if (isset($_SERVER['PATH_INFO'])) {
            if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['PATH_INFO'], $_SERVER['SCRIPT_NAME']) === 0) {
                $relativepath = substr($_SERVER['PATH_INFO'], strlen($_SERVER['SCRIPT_NAME']));
            } else {
                $relativepath = $_SERVER['PATH_INFO'];
            }
            $relativepath = clean_param($relativepath, PARAM_PATH);
        }
    }

    if (empty($relativepath) || $relativepath[0] !== '/') {
        return '/';
    }

    return $relativepath;
}

/**
 * Make the request object.
 *
 * @return object
 */
function make_request_object(\moodle_url $baseurl) {
    $headers = getallheaders();
    $body = file_get_contents('php://input');
    $isjson = isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json';
    return (object) [
        'verb' => $_SERVER['REQUEST_METHOD'],
        'body' => $body,
        'data' => $isjson ? json_decode($body, true) : null,
        'path' => get_requested_path($baseurl),
        'query' => $_GET,
        'headers' => getallheaders()
    ];
}

/**
 * Resolve a route from a request.
 *
 * @param array $routes The routes.
 * @param object $request The request.
 * @return array Of nulls, or with the route, and its arguments.
 */
function resolve_route_from_request($routes, $request) {
    $route = null;
    $routeargs = [];

    foreach ($routes as $candidate) {
        $matches = [];
        $regex = '~^' . $candidate['regex'] . '$~';

        if (preg_match($regex, $request->path, $matches)) {
            $route = $candidate;
            $routeargs = array_slice($matches, 1);
            break;
        }
    }

    return [$route, $routeargs];
}
