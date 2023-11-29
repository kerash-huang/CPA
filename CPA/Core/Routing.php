<?php

namespace CPA\Core;

use Exception;

class Routing {

    private $post_routing = array();
    private $get_routing = array();
    private $put_routing = array();

    public function check($method, $controller, $action) {
        $var = $method . "_routing";
        if (isset($this->{$var}[$controller])) {
            if (in_array($action, $this->{$var}[$controller])) {
                return true;
            } else {
                throw new Exception("Routing not support", 405);
            }
        } else {
            throw new Exception("Method not allowed", 405);
        }
    }

    function add_route($method, $controller, $action) {
        $var = $method . "_routing";
        if (isset($this->{$var})) {
            if (!isset($this->{$var}[$controller])) {
                $this->{$var}[$controller] = array();
            }
            if (is_array($action)) {
                foreach ($action as $single) {
                    if (!in_array($single, $this->{$var}[$controller])) {
                        $this->{$var}[$controller][] = $single;
                    }
                }
            } else {

                if (!in_array($action, $this->{$var}[$controller])) {
                    $this->{$var}[$controller][] = $action;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function list_all_route() {
        return array('get' => $this->get_routing, 'post' => $this->post_routing);
    }

    public function list_controller_route($controller) {
        return array('get' => $this->get_routing[$controller], 'post' => $this->post_routing[$controller]);
    }
}
