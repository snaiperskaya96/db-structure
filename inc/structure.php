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
        mysqli_report(MYSQLI_REPORT_STRICT);
        if(file_exists($prefix.'config/databases.php'))
            require_once($prefix.'config/databases.php');
        else
            die("Missing /config/databases.php config file.");

        require_once('vendor/autoload.php');
        $this->configs = $databases; // Included from config/databases.php
    }

    public function getDatabases(){
        $dbs = array('available' => array(),'unavailable' => array());
        foreach($this->configs as $name => $config){
            try {
                $db = new MysqliDb($config['host'],$config['username'],$config['password'],$config['database']);
                $db->ping();
                $dbs['available'][$name] = $config;
            } catch (Exception $e){
                $dbs['unavailable'][$name] = $e->getMessage();
            }
        }
        return $dbs;
    }

    public function getConfigs(){
        return $this->configs;
    }

    public function get($fileName){
        $file = '/tmp/' . preg_replace('[\\\/\*\[\]\(\)\@\~]','',$fileName);
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
    }
    private function write($phpWord, $filename, $writers)
    {
        $result = '';
        // Write documents
        foreach ($writers as $format => $extension) {
            $result .= date('H:i:s') . " Write to {$format} format";
            if (null !== $extension) {
                $targetFile = "/tmp/{$filename}.{$extension}";
                $phpWord->save($targetFile, $format);
            } else {
                $result .= ' ... NOT DONE!';
            }
            $result .= "\n";
        }
        return $targetFile;
    }

    private function cleanText($text){
        $text = htmlspecialchars($text);
        $text = str_replace('&', '&amp;', $text);
        return $text;
    }

    public function toDoc($json){

        $data = json_decode($json);

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $header = array('size' => 16, 'bold' => true, 'align' => 'center');
        $subHeader = array('size' => 7, 'align' => 'right');
        $phpWord->addParagraphStyle('center-paragraph', array('alignment' => \PhpOffice\PhpWord\Style\TextBox::POS_CENTER, 'spaceAfter' => 100));
        $phpWord->addFontStyle('center', array('align' => 'center'));
        $phpWord->addFontStyle('right', array('align' => 'right'));
        $phpWord->addFontStyle('left', array('align' => 'left'));
        $section->addText("{$data->name} ({$data->dbname})", $header, array('align' => 'center'));
        $section->addTextBreak();
        $section->addText("Data Lengths marked with an asterisk (*) indicate default minimum length in bytes required by MySql",$subHeader,array('align' => 'right'));
        $section->addTextBreak();
        $tableStyle = array('borderSize' => 6, 'borderColor' => '000000', 'width' => 100 * 100, 'unit' => 'pct');
        foreach($data->tables as $table){
            $t = $section->addTable($tableStyle);
            $t->addRow(800);
            $t->addCell(1750, array('gridSpan' => '1','valign' => 'center', 'bgColor' => 'B3B3B3', 'bold' => true))->addText($table->name);
            $t->addCell(1750, array('gridSpan' => '6','valign' => 'center', 'alignment' => 'center'))->addText($table->comment);
            $t->addRow(400);
            foreach($table->headers as $header) {
                $width = 1750;
                if($header == 'Description') $width = 3500;
                if($header == 'Type') $width = 1200;
                if($header == 'Length') $width = 800;
                if($header == 'Null') $width = 800;
                if($header == 'Default') $width = 800;
                $t->addCell($width, array('gridSpan' => '1', 'valign' => 'center', 'bgColor' => 'D9D9D9', 'bold' => true))->addText($header);
            }
            foreach($table->fields as $field){
                $t->addRow(300);
                foreach($field as $index => $cell) {
                    $pStyle = array('align' => 'center');
                    if($index == 3 || $index == 5)
                        $t->addCell(1, array('gridSpan' => '1', 'valign' => 'center',))->addText($this->cleanText($cell),'right',array('align' => 'right'));
                    elseif($index == 0 || $index == 6)
                        $t->addCell(1, array('gridSpan' => '1', 'valign' => 'center'))->addText($this->cleanText($cell),'left',array('align' => 'left'));
                    else
                        $t->addCell(1, array('gridSpan' => '1', 'valign' => 'center',))->addText($this->cleanText($cell),'center',array('align' => 'center'));
                }
            }

            $t->addRow(300);
            $t->addCell(1750, array('gridSpan' => '1','valign' => 'center', 'bgColor' => 'D9D9D9','bold' => true))->addText("Primary Key");
            $t->addCell(1750, array('gridSpan' => '6','valign' => 'center', 'alignment' => 'center'))->addText($table->keys->primary);
            $t->addRow(300);
            $t->addCell(1750, array('gridSpan' => '1','valign' => 'center', 'bgColor' => 'D9D9D9','bold' => true))->addText("Unique Keys");
            $t->addCell(1750, array('gridSpan' => '6','valign' => 'center', 'alignment' => 'center'))->addText($table->keys->unique);
            $t->addRow(300);
            $t->addCell(1750, array('gridSpan' => '1','valign' => 'center', 'bgColor' => 'D9D9D9','bold' => true))->addText("Foreign Keys");
            $t->addCell(1750, array('gridSpan' => '6','valign' => 'center', 'alignment' => 'center'))->addText($table->keys->foreign);
            $t->addRow(300);
            $t->addCell(1750, array('gridSpan' => '1','valign' => 'center', 'bgColor' => 'D9D9D9','bold' => true))->addText("SQL Code");
            $t->addCell(1750, array('gridSpan' => '6','valign' => 'center', 'alignment' => 'center'))->addText(SqlFormatter::format($table->sql, false));
            $t->addRow(300);
            $t->addCell(1750, array('gridSpan' => '1','valign' => 'center', 'bgColor' => 'D9D9D9','bold' => true))->addText("Notes");
            $t->addCell(1750, array('gridSpan' => '6','valign' => 'center', 'alignment' => 'center'))->addText("");
            $section->addTextBreak(3);
        }

        $writers = array('Word2007' => 'docx');
        $file = $this->write($phpWord, basename(__FILE__, '.php'), $writers);

        echo json_encode(array('status' => 'ok', 'source' => basename($file)));
    }

    public function parseTable($dbname){
        $return = array();
        $data = $this->configs[$dbname];
        $db = @new MysqliDb($data['host'],$data['username'],$data['password'],$data['database']);
        $result = $db->rawQuery('SHOW TABLES FROM `' .$data['database'] .'`');
        foreach($result as $row => $value){
            $tableName = array_values($value)[0];
            $db->where('TABLE_SCHEMA',$data['database']);
            $db->where('TABLE_NAME',$tableName);
            $table = $db->getOne('information_schema.tables', null);
            if(!empty($table)){
                $return[$tableName] = $table;
                $createQuery = $db->rawQuery('SHOW CREATE TABLE '.$tableName);
                $return[$tableName]['sql'] = SqlFormatter::format(array_values(array_values($createQuery)[0])[1]);
                $db->join('information_schema.key_column_usage s','c.TABLE_SCHEMA=s.TABLE_SCHEMA AND c.TABLE_NAME=s.TABLE_NAME AND c.COLUMN_NAME=s.COLUMN_NAME', 'LEFT');
                $db->where('c.TABLE_NAME', $tableName);
                $db->where('c.TABLE_SCHEMA', $data['database']);
                $db->orderBy('c.ORDINAL_POSITION','ASC'); // Ordering by original position
                $columnNames = 'c.CHARACTER_MAXIMUM_LENGTH,'
                .'c.CHARACTER_OCTET_LENGTH,c.CHARACTER_SET_NAME,c.COLLATION_NAME,c.COLUMN_COMMENT,c.COLUMN_DEFAULT,'
                .'c.COLUMN_KEY,c.COLUMN_NAME,c.COLUMN_TYPE,c.DATA_TYPE,c.DATETIME_PRECISION,c.EXTRA,c.IS_NULLABLE,'
                .'c.NUMERIC_PRECISION,c.NUMERIC_SCALE,c.ORDINAL_POSITION,c.PRIVILEGES,c.TABLE_CATALOG,c.TABLE_NAME,'
                .'c.TABLE_SCHEMA,s.CONSTRAINT_NAME as FK_CONSTRAINT_NAME,s.REFERENCED_TABLE_NAME as FK_REFERENCED_TABLE_NAME,'
                .'s.REFERENCED_COLUMN_NAME as FK_REFERENCED_COLUMN_NAME';
                $columns = $db->get('information_schema.columns c',null,$columnNames);
                $return[$tableName]['columns'] = $columns;
                $columnArray = array();
                foreach ($columns as $column) {
                    if(isset($column['COLUMN_NAME'])){
                        $columnArray[] = 'MAX(`'.$column['COLUMN_NAME'].'`) as "'.$column['COLUMN_NAME'].'"';
                    }
                }
                $columnString = implode(',',$columnArray);
                $allValues = $db->getOne($tableName,$columnString);
                $return[$tableName]['examples'] = $allValues;
            }
        }
        return json_encode($return);
    }
}
