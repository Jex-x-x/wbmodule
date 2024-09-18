<?
namespace Wbs24\Wbapi;

class Db
{
    protected $connection;

    public function __construct($objects = [])
    {
        global $DB;

        $this->connection = $objects['DB'] ?? $DB;
    }

    public function query($sql)
    {
        $result = $this->connection->Query($sql);
        $returnResult = [];
        while ($fields = $result->Fetch()) {
            $returnResult[] = $fields;
        }

        return $returnResult;
    }

    public function set($table, $data)
    {
        $data = $this->escapeOfQuotes($data);

        $fields = array_keys($data);
        $fieldsAsSql = "`".implode("`, `", $fields)."`";
        $valuesAsSql = "'".implode("', '", $data)."'";
        $sql = "INSERT INTO `${table}` (${fieldsAsSql}) VALUES (${valuesAsSql})";

        $valuesAsSql = $this->getValuesAsSql($data, [$key]);
        $sql .= " ON DUPLICATE KEY UPDATE ${valuesAsSql}";

        $this->query($sql);

        return true;
    }

    public function get($table, $where = [], $param = [])
    {
        $whereFiledsAsSql = $this->getWhereAsSql($where);
        $sql = "SELECT * FROM `${table}`";

        if ($where) $sql .= " WHERE ${whereFiledsAsSql}";

        $order = $param['order'] ?? false;
        if ($order) $sql .= " ORDER BY ${order}";

        $limit = $param['limit'] ?? false;
        if ($limit) $sql .= " LIMIT ${limit}";

        return $this->query($sql);
    }

    public function getSingle($table, $where)
    {
        $result = $this->get($table, $where);
        $resultSingle = $result[0] ?? false;

        return $resultSingle;
    }

    public function clear($table, $where = [])
    {
        $sql = "DELETE FROM `${table}`";
        $whereFiledsAsSql = $this->getWhereAsSql($where);
        if ($where) $sql .= " WHERE ${whereFiledsAsSql}";
        $this->query($sql);
    }

    public function escapeOfQuotes($data)
    {
        foreach ($data as $k => $v) {
            $data[$k] = str_replace("'", "\\'", $v);
        }

        return $data;
    }

    public function getValuesAsSql($data, $exclude = [])
    {
        $setFieldsSql = "";
        foreach ($data as $field => $value) {
            if (in_array($field, $exclude)) continue;
            if ($setFieldsSql) $setFieldsSql .= ', ';
            $setFieldsSql .= "`${field}` = '${value}'";
        }

        return $setFieldsSql;
    }

    public function getWhereAsSql($where)
    {
        $whereSql = "";
        $allowedOperators = ["<", ">"];

        foreach ($where as $field => $value) {
            if ($whereSql) $whereSql .= ' AND ';

            $operator = "=";
            $quotes = "'";
            $firstSymbol = substr($field, 0, 1);
            if (in_array($firstSymbol, $allowedOperators)) {
                $operator = $firstSymbol;
                $field = substr($field, 1);
                $quotes = "";
            }

            $whereSql .= "`${field}` ${operator} ${quotes}${value}${quotes}";
        }

        return $whereSql;
    }
}
