<?php
namespace Jlyu;

class TinyCRUD {
    public $id = null;
    private static $db = null;
    protected $changedFields = array();

    public final function __construct($id = null) {
        $id = (int) $id;
        if ($id > 0) {
            $this->get($id);
        }
    }


    public static function setDb(\PDO $db) {
        self::$db = $db;
    }

    public static function getDb() {
        return self::$db;
    }

    public function save() {
        if ($this->id) {
            $this->update();
        } else {
            $this->create();
        }
    }

    protected function update() {
        $fields = array();
        $values = array();
        foreach($this->changedFields as $field => $value) {
            if ($this->$field == $value) {
                continue;
            }

            $fields[] = $field;
            $values[] = $value;
            $this->$field = $value;
        }

        if (!$fields) {
            return;
        }

        $sql = sprintf(
            "update %s set %s where id=%s",
            static::$table,
            join(',', array_map(function ($field) {
                return "`$field` = ?";
            }, $fields)),
            $this->id
        );

        $stmt = self::getDb()->prepare($sql);
        return $stmt->execute($values);
    }

    protected function create() {
        $fields = array();
        $values = array();
        foreach($this->changedFields as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
            $this->$field = $value;
        }
        $sql = sprintf(
            "insert into %s (%s) values (%s)",
            static::$table,
            join(',', array_map(function ($field) {
                return "`$field`";
            }, $fields)),
            join(',', array_fill(0, count($fields), '?'))
        );

        $stmt = self::getDb()->prepare($sql);
        if ($stmt->execute($values)) {
            $this->id  = self::getDb()->lastInsertId('id');
        }
    }

    protected function get($id) {
        $sql = sprintf(
            "select * from %s where id = ?",
            static::$table
        );

        $stmt = self::getDb()->prepare($sql);
        $stmt->execute(array($id));

        $dao = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$dao) {
            throw new \RangeException('dao not exists');
        }

        foreach ($dao as $field => $value) {
            $this->$field = $value;
        }
    }

    public function delete() {
        $sql = sprintf(
            "delete from %s where id = ?",
            static::$table
        );
        $stmt = self::getDb()->prepare($sql);
        return $stmt->execute(array($this->id));
    }

    public function getIds($condations) {
        $sql = "select id from " . static::$table;
        $fields = array();
        $values = array();
        foreach ($condations as $field => $value) {
          if ($value) {
            $fields[] = $field;
            $values[] = $value;
          }
        }
        $condationExpression = join(' and', array_map(function ($field) {
          return "$field = ?";
        }, $fields));

        if ($condationExpression) {
          $sql = "$sql where $condationExpression";
        }
        $stmt = self::getDb()->prepare($sql);
        $stmt->execute($values);

        $daos = array();
        while (false !== ($obj = $stmt->fetch(\PDO::FETCH_ASSOC))) {
          $dao = new static();
          foreach ($obj as $field => $value) {
            $dao->$field = $value;
          }
          $daos[] = $dao;
        }

        return $daos;
    }

    public function __set($name, $value) {
        $this->changedFields[$name] = $value;
    }

    public function __get($name) {
        return $this->changedFields[$name];
    }
}
