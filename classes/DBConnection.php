<?php
if(!defined('DB_SERVER')){
    require_once(__DIR__ . "/../initialize.php");
}
class DBConnection{

    private $host = DB_SERVER;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $database = DB_NAME;
    
    public $conn;
    
    public function __construct(){

        if (!isset($this->conn)) {
            
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if (!$this->conn) {
                echo 'Cannot connect to database server';
                exit;
            }            
        }    
        
    }
    /**
     * Prepare and execute a statement with optional types and parameters.
     *
     * @param string $sql   SQL query with ? placeholders
     * @param string $types A string of types (i, s, d, b) corresponding to $params
     * @param array  $params Values to bind to the statement
     * @return mixed        mysqli_result on SELECT, true/false on other queries
     */
    public function queryPrepared($sql, $types = '', $params = []){
        $stmt = $this->conn->prepare($sql);
        if(!$stmt){
            error_log('Prepare failed: '.$this->conn->error);
            return false;
        }
        if($types !== '' && count($params) > 0){
            // mysqli_stmt::bind_param requires parameters by reference
            $refs = [];
            foreach($params as $key => $value){
                $refs[$key] = &$params[$key];
            }
            array_unshift($refs, $types);
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        if(!$stmt->execute()){
            error_log('Execute failed: '.$stmt->error);
            return false;
        }
        $result = $stmt->get_result();
        if($result !== false){
            return $result;
        }
        return true;
    }

    /**
     * Simple wrapper to delete a row by its integer primary key.
     *
     * @param string $table Table name (will be escaped)
     * @param int    $id    Primary key value
     * @return bool         true on success
     */
    public function deleteById($table, $id){
        // note: backticks around table name, but avoid injection by restricting allowed characters
        if(!preg_match('/^[A-Za-z0-9_]+$/',$table)){
            throw new InvalidArgumentException('Invalid table name');
        }
        $sql = "DELETE FROM `".$table."` WHERE id = ?";
        return $this->queryPrepared($sql, 'i', [$id]);
    }
    /**
     * Update or insert a record in the centralized search index.
     */
    public function update_search_index($table, $id, $type, $title, $subtitle = '', $search_data = '', $amount = 0.00, $date = null, $view_url = '', $edit_url = ''){
        $sql = "INSERT INTO `search_index` 
                (`source_table`, `source_id`, `type`, `title`, `subtitle`, `search_data`, `amount`, `transaction_date`, `view_url`, `edit_url`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                `type` = VALUES(`type`), 
                `title` = VALUES(`title`), 
                `subtitle` = VALUES(`subtitle`), 
                `search_data` = VALUES(`search_data`), 
                `amount` = VALUES(`amount`), 
                `transaction_date` = VALUES(`transaction_date`), 
                `view_url` = VALUES(`view_url`), 
                `edit_url` = VALUES(`edit_url`)";
        
        $types = "sissssds ss"; // note: s = string, i = int, d = double. Spaced for readability.
        $types = str_replace(' ', '', $types);
        $params = [$table, $id, $type, $title, $subtitle, $search_data, $amount, $date, $view_url, $edit_url];
        
        return $this->queryPrepared($sql, $types, $params);
    }

    /**
     * Remove a record from the centralized search index.
     */
    public function delete_from_index($table, $id){
        $sql = "DELETE FROM `search_index` WHERE `source_table` = ? AND `source_id` = ?";
        return $this->queryPrepared($sql, 'si', [$table, $id]);
    }

    public function __destruct(){
        $this->conn->close();
    }
}
?>