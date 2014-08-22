<?php
if (!function_exists('exceptions_error_handler')) {
    function exceptions_error_handler($severity, $message, $filename, $lineno)
    {
        throw new ErrorException($message, 0, $severity, $filename, $lineno);
    }
}

set_error_handler('exceptions_error_handler');

if (!class_exists("dbWrapper_v2")) {
    Class dbWrapper_v2
    {
        protected $_mysqli;
        protected $_debug;

        public function __construct($host, $username, $password, $database, $debug)
        {
            $this->_mysqli = new mysqli($host, $username, $password, $database);
            $this->_debug = (bool)$debug;
            if (mysqli_connect_errno()) {
                if ($this->_debug) {
                    //echo mysqli_connect_error();
                    //debug_print_backtrace();
                    throw new Exception(mysqli_connect_error());
                }
                return false;
            }
            return true;
        }

        public function escape($query)
        {
            return $this->_mysqli->real_escape_string($query);
        }

        public function q($query)
        {
            if ($query = $this->_mysqli->prepare($query)) {
                if (func_num_args() > 1) {
                    $x = func_get_args();
                    $args = array_merge(array(func_get_arg(1)),
                        array_slice($x, 2));
                    $args_ref = array();
                    foreach ($args as $k => &$arg) {
                        $args_ref[$k] = & $arg;
                    }
                    call_user_func_array(array($query, 'bind_param'), $args_ref);
                }
                $query->execute();

                if ($query->errno) {
                    if ($this->_debug) {
                        //echo mysqli_error($this->_mysqli);
                        //debug_print_backtrace();
                        throw new Exception(mysqli_error($this->_mysqli));
                    }
                    return false;
                }

                if ($query->affected_rows > -1) {
                    return $query->affected_rows;
                }
                $params = array();
                $meta = $query->result_metadata();
                while ($field = $meta->fetch_field()) {
                    $params[] = & $row[$field->name];
                }
                call_user_func_array(array($query, 'bind_result'), $params);

                $result = array();
                while ($query->fetch()) {
                    $r = array();
                    foreach ($row as $key => $val) {
                        $r[$key] = $val;
                    }
                    $result[] = $r;
                }
                $query->close();
                return $result;
            } else {
                if ($this->_debug) {
                    //echo $this->_mysqli->error;
                    //debug_print_backtrace();
                    throw new Exception($this->_mysqli->error);
                }
                return false;
            }
        }

        public function handle()
        {
            return $this->_mysqli;
        }

        public function last_index()
        {
            return $this->_mysqli->insert_id;
        }
    }
}