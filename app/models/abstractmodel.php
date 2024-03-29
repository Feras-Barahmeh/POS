<?php

namespace APP\Models;
use APP\Lib\Database\DatabaseHandler;
use http\Params;
use PDO;
use function APP\pr;


class AbstractModel
{
    const DATA_TYPE_BOOL = \PDO::PARAM_BOOL;
    const DATA_TYPE_INT = \PDO::PARAM_INT;
    const DATA_TYPE_STR = \PDO::PARAM_STR;
    const DATA_TYPE_DECIMAL = 4;
    const DATA_TYPE_DATE = 5;
    protected $_info = [];

    private function bindParams(\PDOStatement &$stmt): void
    {
        foreach (static::$tableSchema as $columnName => $type)
        {
            if ($type != 4)
            {
                $stmt->bindValue(":{$columnName}", $this->$columnName, $type);
            }
            else
            {
                $sanitize = filter_var($this->$columnName, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $stmt->bindValue(":{$columnName}", $sanitize);
            }
        }
    }

    private static function buildNameParamSQL(): string
    {
        $query  = '';
        foreach (static::$tableSchema as $columnName => $type) {
            $query .= $columnName . " = :" . $columnName .  ", ";
        }

        return trim($query, ", ");
    }
    private function insert(): bool
    {
        $query = "INSERT INTO " . static::$tableName . " SET " . self::buildNameParamSQL() ;
        $stmt = DatabaseHandler::factory()->prepare($query);
        $this->bindParams($stmt);
        
        if ($stmt->execute()) {
            $this->{static::$primaryKey} = DatabaseHandler::lastInsertID();
            return true;
        }
        return false;
    }
    private function update()
    {
        $query = "UPDATE " . static::$tableName . " SET " . self::buildNameParamSQL() . " WHERE " . static::$primaryKey . " = " . $this->{static::$primaryKey} ;
        $stmt = DatabaseHandler::factory()->prepare($query);
        $this->bindParams($stmt);
        return $stmt->execute();
    }

    /**
     * @param $query string the query you want execute
     * @return mixed
     */
    public static function executeQuery(string $query): mixed
    {
        $stmt = DatabaseHandler::factory()->prepare($query);
        return $stmt->execute();
    }
    /**
     * @param bool $isSubProcess if you want to use save method to model subset from another model such as user model
     * and subset info model (check if set primary key manual)
     * @return bool
     */
    public function save(bool $isSubProcess=true): bool
    {
        if (! $isSubProcess) {
            return $this->insert();
        }
        if ($this->{static::$primaryKey} === null) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }

    public function delete()
    {

        $query = "DELETE FROM " . static::$tableName . " WHERE " . static::$primaryKey . " = " . $this->{static::$primaryKey} ;
        $stmt = DatabaseHandler::factory()->prepare($query);
        return $stmt->execute();
    }



    public static function getAll(): bool|\ArrayIterator
    {
        $query = "SELECT * FROM " . static::$tableName;
        $stmt = DatabaseHandler::factory()->prepare($query);
        $stmt->execute();


        // Get All
        if (method_exists(get_called_class(), "__construct")) {
            $results = $stmt->fetchAll(
                \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE,
                get_called_class(),
                array_keys(static::$tableSchema)
            );
        }
        else {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS , get_called_class());
        }


        if ((is_array($results) && !empty($results)))  {
            return new \ArrayIterator($results);
        }

        return false;
    }

    public static function getByPK($pk)
    {
        $query = "SELECT * FROM " . static::$tableName . " WHERE " . static::$primaryKey . " = '" . $pk . "'";

        $stmt = DatabaseHandler::factory()->prepare($query);

        if ($stmt->execute() === true)
        {
            if (method_exists(get_called_class(), "__construct")) {
                $results = $stmt->fetchAll(
                    \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE,
                    get_called_class(),
                    array_keys(static::$tableSchema)
                );
            }
            else {
                $results = $stmt->fetchAll(\PDO::FETCH_CLASS , get_called_class());
            }

            return !empty($results) ? array_shift($results) : false ;
        }
        return false;
    }


    public static function getBy($columns, $options = []): false|\ArrayIterator
    {
        $whereClauseColumns = array_keys($columns);
        $whereClauseValues = array_values($columns);
        $whereClause = []; $numberConditions = count($whereClauseColumns);

        // Connection keys whit values.
        for ( $i = 0, $ii = $numberConditions; $i < $ii; $i++ ) {
            $whereClause[] = $whereClauseColumns[$i] . ' = "' . $whereClauseValues[$i] . '"';
        }

        // Bind all conditions
        $whereClause = implode(' AND ', $whereClause);

        $sql = 'SELECT * FROM ' . static::$tableName . '  WHERE ' . $whereClause;


        return (new UserGroupPrivilegeModel)->get($sql, $options);
    }

    public function get($query, $options=[]): false|\ArrayIterator
    {
        $stmt = DatabaseHandler::factory()->prepare($query);
        $stmt->execute();
        if(method_exists(get_called_class(), '__construct')) {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, get_called_class(), array_keys(static::$tableSchema));
        } else {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS, get_called_class());
        }

        if ((is_array($results) && !empty($results))) {
            return new \ArrayIterator($results);
        }
        return false;
    }

    public function getRow($sql)
    {
        $row = static::get($sql);
        return ! $row ? 0 : $row->current();
    }

    public static function getTableName()
    {
        return static::$tableName;
    }

    /**
     * Method to check if value exist in db or not get column and value this column
     * @author Feras Barahmeh
     * @version 1.0.0
     *
     * @param string $column select the column you want count
     * @param string $value value column you want search it
     * @return false|\ArrayIterator false if value not exist and values to this column otherwise
     *
     */
    public static function countRow(string $column, string $value): false|\ArrayIterator
    {
        $calledClass = get_called_class();
        return (new $calledClass)->get("SELECT " . $column . " FROM " . static::$tableName . " WHERE " . $column . " = '$value'");

    }
    public function allLazy(array $options = null): \Generator
    {
        $query = "SELECT * FROM " . static::$tableName;
        if ($options != null) {
            foreach ($options as $key => $val) {
                $query .= " " . $key . " " . $val;
            }
        }

        $records = $this->get($query);
        foreach ($records as $record) {
            yield $record;
        }
    }
    /**
     * return count primary key
     * @return int|mixed
     * @version 1.0
     * @author Feras Barahemeh
     */
    public static function enumerate(): mixed
    {
        $sql = "
            SELECT 
                COUNT(" . static::$primaryKey . ") AS count
            FROM 
                " . static::$tableName ."
        ";

        return (new AbstractModel)->getRow($sql)->count;
    }

    /**
     * method to return enumerate transaction dayle
     * @version 1.0
     * @author Feras Barahmeh
     *
     * @return int|mixed return 0 if no value returned else return count today transaction
     */
    public static function countTransactionsToday(): mixed
    {
        $sql = "
            SELECT
                COUNT(". static::$primaryKey .") AS count
            FROM 
                ". static::$tableName ." 
            WHERE
                DATE(Created) = (SELECT CURRENT_DATE())
        ";
        return (new AbstractModel())->getRow($sql)->count;
    }

    /**
     * get sum columns value
     * @version 1.0
     * @author Feras Barahmeh
     * @param string $column the column you want get sum
     * @param string|null $where if it has a condition
     * @return mixed
     */
    public static function sum(string $column, string $where=null): mixed
    {
        $sql = "
            SELECT 
                SUM($column) AS S
            FROM
                ". static::$tableName  ."
        ";

        return (new AbstractModel())->getRow($sql)->S;
    }

}