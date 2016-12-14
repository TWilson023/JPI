<?php


/**
 * Class DBInterface
 *
 * Used for interacting with a MySQL database without manually writing queries
 */
class DBInterface {

    public $mysqli;

    /**
     * DBInterface constructor.
     * @param $mysqli mysqli MySQLi database variable
     */
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }


    /**
     * @param string $table Name of the table in the database
     * @param array $criteria Associative array of fields and values (for matching)
     * @param string $types String of types of values (e.g. "ids" for an integer, a double, and a string)
     * @return boolean Whether the query was successfully executed
     */
    public function delete($table, $criteria, $types) {
        $where = "";
        $i = 0;
        $length = count($criteria);

        if ($length > 0) {
            $where .= " WHERE ";

            foreach ($criteria as $field => $value) {
                $where .= "`" . $field . "` = ? ";
                if ($i != $length - 1)
                    $where .= "AND ";

                $i++;
            }
        }

        $sql = "DELETE FROM `$table`" . $where;

        if ($statement = $this->mysqli->prepare($sql)) {
            if (count($criteria) > 0) {
                $params = array_values($criteria);

                array_unshift($params, $types);
                call_user_func_array(array($statement, 'bind_param'), $this->referenceArray($params));
            }
            return $statement->execute();
        }

        return null;
    }


    /**
     * @param string $table Name of the table in the database
     * @param array $fields Associative array of field names and values
     * @param array $criteria Associative array of fields and values (for matching)
     * @param string $types String of types of values (fields before criteria) (e.g. "ids" for an integer, a double, and a string)
     * @return boolean Whether the query was successfully executed
     */
    public function update($table, $fields, $criteria, $types) {
        $set = " SET ";
        $i = 0;
        $length = count($fields);

        if ($length > 0) {
            foreach ($fields as $field => $value) {
                $set .= "`" . $field . "`=?";

                if ($i != $length - 1)
                    $set .= ",";

                $i++;
            }
        }

        $where = "";
        $i = 0;
        $length = count($criteria);

        if ($length > 0) {
            $where .= " WHERE ";

            foreach ($criteria as $field => $value) {
                $where .= "`" . $field . "` = ? ";

                if ($i != $length - 1)
                    $where .= "AND ";

                $i++;
            }
        }

        $sql = "UPDATE `$table`" . $set . $where;
        if ($statement = $this->mysqli->prepare($sql)) {
            $params = array_values($fields);

            array_unshift($params, $types);
            $params = array_merge($params, array_values($criteria));
            call_user_func_array(array($statement, 'bind_param'), $this->referenceArray($params));

            return $statement->execute();
        }

        return false;
    }


    /**
     * @param string $table Name of the table in the database
     * @param array $fields Associative array of field names and values
     * @param string $types String of types of values (e.g. "ids" for an integer, a double, and a string)
     * @return boolean Whether the query was successfully executed
     */
    public function insert($table, $fields, $types) {
        $values = "";
        $i = 0;
        $length = count($fields);

        if ($length > 0) {
            foreach ($fields as $field => $value) {
                $values .= "?";

                if ($i != $length - 1)
                    $values .= ",";

                $i++;
            }
        }

        $sql = "INSERT INTO `$table` (" . implode(",", array_keys($fields)) . ") VALUES ($values)";

        if ($statement = $this->mysqli->prepare($sql)) {
            $params = array_values($fields);

            array_unshift($params, $types);
            call_user_func_array(array($statement, 'bind_param'), $this->referenceArray($params));
            return $statement->execute();
        }

        return false;
    }

    public function select_query($sql, $types) {
        if ($statement = $this->mysqli->prepare($sql)) {
            if (strlen($types) > 0)
                $statement->bind_param($types);

            $statement->execute();
            $result = $statement->get_result();

            $output = array();

            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }

            return $output;
        }

        return null;
    }

    /**
     * @param string $table Name of the table in the database
     * @param array $fields Fields to be selected/returned
     * @param array $criteria Associative array of fields and values (for matching)
     * @param string $types String of types of criteria (e.g. "ids" for an integer, a double, and a string)
     * @param array $order Associative array of fields to order by (e.g. ["name"=>"DESC"])
     * @return array|null An array of associative arrays of results (or null if none were found)
     */
    public function select($table, $fields, $criteria = array(), $types = "", $order = array()) {
        $where = "";
        $i = 0;
        $length = count($criteria);

        if ($length > 0) {
            $where .= "WHERE ";

            foreach ($criteria as $field => $value) {
                $where .= "`" . $field . "` = ? ";

                if ($i != $length - 1)
                    $where .= "AND ";

                $i++;
            }
        }

        $by = "";

        if (count($order) > 0) {
            $by .= "ORDER BY ";

            foreach ($order as $field => $direction) {
                $by .= "`" . $field . "`" . $direction;
            }
        }

        $sql = "SELECT " . implode(",", (array)$fields) . " FROM `" . $table . "` " . $where . $by;

        if ($statement = $this->mysqli->prepare($sql)) {
            if (count($criteria) > 0) {
                $params = array_values($criteria);

                array_unshift($params, $types);
                call_user_func_array(array($statement, 'bind_param'), $this->referenceArray($params));

            }

            $statement->execute();

            /*
             * Can't use $statement->get_result() for compatibility with systems without mysqlnd.
             * (I'm looking at you, Hostgator...)
            */
            $meta = $statement->result_metadata();
            $meta_fields = array();

            $results = array();

            while ($field = $meta->fetch_field()) {
                $var = $field->name;

                $$var = null;
                $meta_fields[$var] = &$$var;
            }

            call_user_func_array(array($statement, 'bind_result'), $meta_fields);

            $i = 0;

            while ($statement->fetch()) {
                $results[$i] = array();

                foreach ($meta_fields as $k => $v)
                    $results[$i][$k] = $v;

                $i++;
            }

            return $results;
        }

        return null;
    }

    /**
     * Creates an array of references to satisfy call_user_func_array
     * @param array $a The array to be referencified
     * @return array An array of references
     */
    private function referenceArray($a) {
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = array();

            foreach ($a as $key => $value)
                $refs[$key] = &$a[$key];

            return $refs;
        }

        return $a;
    }

}