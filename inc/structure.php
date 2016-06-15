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

        require_once('vendor/mysqlidb.php');
        require_once('vendor/PHPWord.php');
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

    public function test(){
        $phpWord = new PHPWord();
        $section = $phpWord->createSection();
        $header = array('size' => 16, 'bold' => true);
// 1. Basic table
        $rows = 10;
        $cols = 5;
        $section->addText("Basic table", $header);
        $table = $section->addTable();
        for($r = 1; $r <= 8; $r++) {
            $table->addRow();
            for($c = 1; $c <= 5; $c++) {
                $table->addCell(1750)->addText("Row $r, Cell $c");
            }
        }
// 2. Advanced table
        $section->addTextBreak(1);
        $section->addText("Fancy table", $header);
        $styleTable = array('borderSize' => 6, 'borderColor' => '006699', 'cellMargin' => 80);
        $styleFirstRow = array('borderBottomSize' => 18, 'borderBottomColor' => '0000FF', 'bgColor' => '66BBFF');
        $styleCell = array('valign' => 'center');
        $fontStyle = array('bold' => true, 'align' => 'center');
        $phpWord->addTableStyle('Fancy Table', $styleTable, $styleFirstRow);
        $table = $section->addTable('Fancy Table');
        $table->addRow(900);
        $table->addCell(2000, $styleCell)->addText('Row 1', $fontStyle);
        $table->addCell(2000, $styleCell)->addText('Row 2', $fontStyle);
        $table->addCell(2000, $styleCell)->addText('Row 3', $fontStyle);
        $table->addCell(2000, $styleCell)->addText('Row 4', $fontStyle);
        $table->addCell(500, $styleCell)->addText('Row 5', $fontStyle);
        $table->addCell(2000, $styleCell)->addText('Row 5', $fontStyle);
        for($i = 1; $i <= 8; $i++) {
            $table->addRow();
            $table->addCell(2000)->addText("Cell $i");
            $table->addCell(2000)->addText("Cell $i");
            $table->addCell(2000)->addText("Cell $i");
            $table->addCell(2000)->addText("Cell $i");
            $text = ($i % 2 == 0) ? 'X' : '';
            $table->addCell(500)->addText($text);
        }
// Save file
        $export = new PHPWord_Writer_Word2007($phpWord);
        $export->save('/tmp/asd2.docx');
    }
    public function toDoc($json){
        $this->test(); return;
        $data = json_decode($json);
        $phpWord = new PHPWord();
        $section = $phpWord->createSection();
        $section->addTitle("{$data->name} ({$data->dbname})");
        
        foreach($data->tables as $table){
            $t = $section->addTable(array('width' => 50 * 50, 'unit' => 'pct', 'align' => 'center'));
            $t->addRow(1);
            $t->addCell(1, array('gridSpan' => '1'))->addText($table->name);
            $t->addCell(1, array('gridSpan' => '1'))->addText($table->comment);
            $t->addRow(1);
            foreach($table->headers as $header)
                $t->addCell(1, array('gridSpan' => '1'))->addText($header);
            $index = 3;
            foreach($table->fields as $field){
                $t->addRow(1);
                foreach($field as $cell)
                    $t->addCell(1, array('gridSpan' => '1'))->addText($cell);
                $index ++;
            }
            $t->addRow(1);
            $t->addCell(1, array('gridSpan' => '1'))->addText("Primary Key");
            $t->addCell(1, array('gridSpan' => '1'))->addText($table->keys->primary);
        }

        $export = new PHPWord_Writer_Word2007($phpWord);
        $export->save('/tmp/asd.docx');

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
                $createQuery = $db->rawQuery('SHOW CREATE TABLE '.$tableName);
                $return[$tableName]['sql'] = array_values(array_values($createQuery)[0])[1];
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