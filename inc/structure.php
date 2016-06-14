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

    public function test($dbname){
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
                $db->where('TABLE_NAME', $tableName);
                $db->where('TABLE_SCHEMA', $data['database']);
                $columns = $db->get('information_schema.columns',null);
                $return[$tableName]['columns'] = $columns;
            }
        }
        return json_encode($return);
    }

}