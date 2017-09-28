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
 * Server class.
 *
 * @package    webservice_restful
 * @copyright  2017 Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace webservice_restful;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/lib.php');
require_once(__DIR__ . '/../lib/include.php');

/**
 * Server class.
 *
 * @package    webservice_restful
 * @copyright  2017 Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class server extends \webservice_base_server {

    protected $token;
    protected $functionname;
    protected $wsname = 'restful';
    protected $verbose;
    protected $request;
    protected $response;
    protected $route;
    protected $routeargs;
    protected $routes;
    protected $baseurl;

    /**
     * Constructor.
     *
     * @param array $routes [description]
     */
    public function __construct(array $routes) {
        global $CFG;
        parent::__construct(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN);

        $this->baseurl = new \moodle_url('/webservice/restful/index.php');
        $this->routes = $routes;
        $this->verbose = $CFG->debugdeveloper ? true : false;
    }

    /**
     * Parse the request.
     *
     * @return void
     */
    protected function parse_request() {
        $request = make_request_object($this->baseurl);
        list($route, $routeargs) = resolve_route_from_request($this->routes, $request);

        $this->token = extract_token_from_request($request);

        // Have we found a route?
        if (!$route) {
            $this->early_bail(not_found());
        }

        // Is the method covered?
        if (!isset($route['methods'][$request->verb])) {
            $this->early_bail(method_not_allowed());
        }

        // Check things first.
        if (isset($route['precheck'])) {
            $precheck = $route['precheck']($routeargs);
            if ($precheck !== null) {
                $this->early_bail($precheck);
            }
            unset($precheck);
        }

        // Extract the method.
        $method = get_method_from_route($route, $request->verb);

        // Persist request information.
        $this->request = $request;
        $this->route = $route;
        $this->routeargs = $routeargs;
        $this->method = $method;

        // At this time we can only handle methods for an external function.
        $functionname = $method['meta']['external_function'] ?? null;
        if (!$functionname) {
            $this->early_bail(internal_server_error_empty());
        }

        // This allows the parent class to make some additional checks on the function to be called.
        $this->functionname = $functionname;
    }

    /**
     * Early bail. Oh my!
     *
     * @param array $response The response.
     * @return void
     */
    protected function early_bail($response) {
        $this->response = $response;
        $this->send_response();

        // Not sure if die is a great thing, but we cannot leave the execution happening.
        die();
    }

    /**
     * Execute.
     *
     * @return void
     */
    protected function execute() {
        $handler = $this->method['handler'];
        $this->response = $handler($this->routeargs, $this->request, [
            'verbose' => $this->verbose
        ]);
    }

    /**
     * Send the response.
     *
     * @return void
     */
    protected function send_response() {
        $response = $this->response;
        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $response['code'] . ' ' . $response['text']);
        header('Content-Type: application/json');
        if (array_key_exists('body', $response)) {
            echo json_encode($response['body']);
        }
    }

    /**
     * Send an error.
     *
     * @param Throwable $e The error.
     * @return void
     */
    protected function send_error($e = null) {
        $response = internal_server_error_from_throwable($e, $this->verbose);
        if ($e instanceof \moodle_exception) {
            if ($e->errorcode == 'invalidtoken') {
                $response = forbidden_from_throwable($e, $this->verbose);
            }
        }

        $this->response = $response;
        $this->send_response();
    }
}
