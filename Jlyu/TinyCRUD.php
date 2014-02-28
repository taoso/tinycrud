<?php
namespace Jlyu;

class TinyCRUD {
    public static $pkName = 'id';
    private static $db = null;
    protected $changedFields = array();
    private $isCreate = false;

    public function __construct($pk = null) {
        if ($pk) {
            return $this->get($pk);
        }
    }

    public static function setDb(\PDO $db) {
        self::$db = $db;
    }

    public static function getDb() {
        return self::$db;
    }

    public function save() {
        if ($this->isCreate) {
            $this->create();
        } else {
            $this->update();
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
            "update %s set %s where `%s` = ?",
            static::$table,
            join(',', array_map(function ($field) {
                return "`$field` = ?";
            }, $fields)),
            static::$pkName
        );

        $values[] = $this->{static::$pkName};

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
        $stmt->execute($values);
    }

    protected function get($id) {
        $sql = sprintf(
            "select * from `%s` where `%s` = ?",
            static::$table,
            static::$pkName
        );

        $stmt = self::getDb()->prepare($sql);
        $stmt->execute(array($id));

        $dao = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$dao) {
            return null;
        }

        foreach ($dao as $field => $value) {
            $this->$field = $value;
        }
    }

    public function delete() {
        $sql = sprintf(
            "delete from `%s` where `%s` = ?",
            static::$table,
            static::$pkName
        );
        $stmt = self::getDb()->prepare($sql);
        return $stmt->execute(array($this->id));
    }

    public function getIds($condations) {
        $sql = "select `" . static::$pkName . "` from " . static::$table;
        $fields = array();
        $values = array();
        foreach ($condations as $field => $value) {
            if ($value) {
                $fields[] = $field;
                $values[] = $value;
            }
        }
        $condationExpression = join(' and', array_map(function ($field) {
            return "`$field` = ?";
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

    public function setPk($pk) {
        $this->changedFields[static::$pkName] = $pk;
        $this->isCreate = true;
    }
}
