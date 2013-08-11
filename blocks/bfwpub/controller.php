<?php
/**
 * Copyright (c) 2011 bfwpub.com (R) <http://support.bfwpub.com/>
 *
 * This file is part of bfwpub Moodle LMS integration.
 *
 * bfwpub Sakai LMS integration is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * bfwpub Sakai LMS integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with bfwpub Sakai LMS integration.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: controller.php 1889 2012-08-25 14:54:34Z aaronz $ */

/**
 * Handles controller functions related to the views
 */

require_once (dirname(__FILE__).'/../../config.php');
global $CFG,$db_user,$COURSE;
require_once ('bfwpub_service.php');

class bfwpub_controller {

    const PASSWORD = '_password';
    const LOGIN = '_login';
    const SEPARATOR = '/';
    const PERIOD = '.';
    const XML_HEADER = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
    const SESSION_ID = '_sessionId';
    const COMPENSATE_METHOD = '_method';
    const XML_DATA = '_xml';
    const JSON_DATA = '_json';
    const SHARED_KEY = '_key';

    // class vars

    // REQUEST
    public $method = 'GET';
    public $path = '';
    public $query = '';
    public $body = NULL;

    // RESPONSE
    public $status = 200;
    public $message = '';
    public $headers = array();

    public $results = array(
    );

    public function __construct($getBody = false) {
        // set some headers
        $this->headers['Content-Encoding'] = 'UTF8';
        //header('Content-type: text/plain');
        //header('Cache-Control: no-cache, must-revalidate');
        // get the rest path
        $full_path = me();
        if (!$full_path) {
            $this->path = '';
        } else {
            $path = '';
            $pos = strripos($full_path, '.php');
            if ($pos > 1) {
                $path = substr($full_path, $pos+4);
                $path = trim($path, '/ '); // trim whitespace and slashes
            }
            if (stripos($path, '?')) {
                $qloc = stripos($path, '?');
                $this->query = trim(substr($path, $qloc), '?');
                $path = substr($path, 0, $qloc);
            }
            $this->path = $path;
        }
        // get the body
        if ($getBody) {
            if (function_exists('http_get_request_body')) {
                $this->body = http_get_request_body();
            } else if (defined('STDIN')) {
                $this->body = @stream_get_contents(STDIN);
            } else {
            // Moodlerooms does not allow use of php://input
            //  $this->body = @file_get_contents('php://input');
                // cannot get the body
                $this->setHeader('NO_BODY','Cannot retrieve request body content');
                $this->body = null;
            }
        }
        // allow for method overrides
        $current_method = $_SERVER['REQUEST_METHOD'];
        $comp_method = isset($_REQUEST[self::COMPENSATE_METHOD]) ? $_REQUEST[self::COMPENSATE_METHOD] : null;
        if (! empty($comp_method)) {
            // Allows override to GET or DELETE
            $comp_method = strtoupper(trim($comp_method));
            if ('GET' == $comp_method) {
                $current_method = 'GET';
            } else {
                if ('DELETE' == $comp_method) {
                    $current_method = 'DELETE';
                }
            }
        }
        $this->method = $current_method;
    }

    public function setStatus($status) {
        if ($status) {
            $this->status = $status;
        }
    }

    public function setMessage($msg) {
        $this->message = $msg;
    }

    public function setContentType($mime_type) {
        $this->headers['Content-Type'] = $mime_type;
    }

    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    /**
     * Send the response
     *
     * @param string $content [optional] the content to send
     * @param string $message [optional] the message to send, defaults to "Invalid request"
     */
    public function sendResponse($content = NULL, $message = "Bad Request") {
        $code = $this->status;
        if ($code == 200) {
            $message = 'OK';
        } else {
            if ($code == 401) {
                $message = 'Unauthorized';
            } else {
                if ($code == 403) {
                    $message = 'Forbidden';
                } else {
                    if ($code == 404) {
                        $message = 'Not Found';
                    }
                }
            }
        }
        header("HTTP/1.0 $code ".str_replace("\n", "", $message));
        if ($code >= 400) {
            // force plain text encoding when errors occur
            $this->setContentType('text/plain');
        }
        $headers = $this->headers;
        if (isset($headers) && ! empty($headers)) {
            foreach ($headers as $key=> & $value) {
                header($key.': '.$value, false);
            }
            unset($value);
        }
        // dump the body content
        if (isset($content)) {
            echo $content;
        }
    }


    // XHTML view processors


    // MESSAGING

    var $messages = array(
    );

    const KEY_INFO = "INFO";
    const KEY_ERROR = "ERROR";
    const KEY_BELOW = "BELOW";

    /**
     * Adds a message
     *
     * @param string $key the KEY const
     * @param string $message the message to add
     * @throws Exception if the key is invalid
     */
    public function addMessageStr($key, $message) {
        if ($key == null) {
            throw new Exception("key (".$key.") must not be null");
        }
        if ($message) {
            if (!isset($this->messages[$key])) {
                $this->messages[$key] = array(
                );
            }
            $this->messages[$key][] = $message;
        }
    }

    /**
     * Add an i18n message based on a key
     *
     * @param string $key the KEY const
     * @param string $messageKey the i18n message key
     * @param object $args [optional] args to include
     * @throws Exception if the key is invalid
     */
    public function addMessage($key, $messageKey, $args = NULL) {
        if ($key == null) {
            throw new Exception("key (".$key.") must not be null");
        }
        if ($messageKey) {
            $message = bfwpub_service::msg($messageKey, $args);
            $this->addMessageStr($key, $message);
        }
    }

    /**
     * Get the messages that are currently waiting in this request
     *
     * @param string $key the KEY const
     * @return array the list of messages to display
     * @throws Exception if key is invalid
     */
    public function getMessages($key) {
        if ($key == null) {
            throw new Exception("key (".$key.") must not be null");
        }
        $messages = NULL;
        if (isset($this->messages[$key])) {
            $messages = $this->messages[$key];
            if (!isset($messages)) {
                $messages = array(
                );
            }
        } else {
            $messages = array(
            );
        }
        return $messages;
    }

}
