<?php

/**
 * Created by PhpStorm.
 * User: skaya
 * Date: 14/06/16
 * Time: 14.38
 */
class Structure{

    private $configs;

    public function __construct($prefix = ''){
        $this->init($prefix);
    }

    private function init($prefix){
        if(file_exists($prefix.'config/databases.php'))
            require_once($prefix.'config/databases.php');
        else
            die("Missing /config/databases.php config file.");

        require_once ('mysqlidb.php');

        $this->configs = $databases; // Included from config/databases.php
    }

    public function getDatabases(){
        $dbs = array('available','unavailable');
        foreach($this->configs as $name => $config){
            $db = new MysqliDb($config['host'],$config['username'],$config['password'],$config['database']);
            try {
                if ($db->ping())
                    $dbs['available'][$name] = $config;
                else
                    $dbs['unavailable'][$name] = $db->getLastError();
            } catch (Exception $e){
                $dbs['unavailable'][$name] = $e->getMessage();
            }
        }
        return $dbs;
    }

    public function getConfigs(){
        return $this->configs;
    }

    public function parseTable($dbname){
        $return = array();
        $data = $this->configs[$dbname];
        $db = new MysqliDb($data['host'],$data['username'],$data['password'],$data['database']);
        $result = $db->rawQuery('SHOW TABLES FROM ' .$data['database']);
        foreach($result as $row => $value){
            $tableName = array_values($value)[0];
            $db->where('TABLE_SCHEMA',$data['database']);
            $db->where('TABLE_NAME',$tableName);
            $table = $db->getOne('information_schema.tables', null);
            if(!empty($table)){
                $return[$tableName] = $table;
                $db->join('information_schema.key_column_usage s','c.TABLE_NAME=s.TABLE_NAME AND c.COLUMN_NAME=s.COLUMN_NAME', 'LEFT');
                $db->where('c.TABLE_NAME', $tableName);
                $db->where('c.TABLE_SCHEMA', $data['database']);
                $db->groupBy('c.COLUMN_NAME'); // There may be multiple columns
                $db->orderBy('c.ORDINAL_POSITION','ASC'); // Ordering by original position
                $columnNames = 'c.CHARACTER_MAXIMUM_LENGTH,'
                .'c.CHARACTER_OCTET_LENGTH,c.CHARACTER_SET_NAME,c.COLLATION_NAME,c.COLUMN_COMMENT,c.COLUMN_DEFAULT,'
                .'c.COLUMN_KEY,c.COLUMN_NAME,c.COLUMN_TYPE,c.DATA_TYPE,c.DATETIME_PRECISION,c.EXTRA,c.IS_NULLABLE,'
                .'c.NUMERIC_PRECISION,c.NUMERIC_SCALE,c.ORDINAL_POSITION,c.PRIVILEGES,c.TABLE_CATALOG,c.TABLE_NAME,'
                .'c.TABLE_SCHEMA,s.CONSTRAINT_NAME as FK_CONSTRAINT_NAME,s.REFERENCED_TABLE_NAME as FK_REFERENCED_TABLE_NAME,'
                .'s.REFERENCED_COLUMN_NAME as FK_REFERENCED_COLUMN_NAME';
                $columns = $db->get('information_schema.columns c',null,$columnNames);
                $return[$tableName]['columns'] = $columns;
            }
        }
        return json_encode($return);
    }

}