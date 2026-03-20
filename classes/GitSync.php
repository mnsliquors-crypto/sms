<?php
require_once __DIR__ . '/../config.php';

Class GitSync extends DBConnection {
    private $settings;
    
    public function __construct(){
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
    }
    
    public function __destruct(){
        parent::__destruct();
    }

    /**
     * Export database to database/sms_db.sql and push to GitHub
     */
    public function push_to_git(){
        $git_path = $this->settings->info('git_path') ?: 'git';
        $mysql_bin = $this->settings->info('mysql_bin') ?: 'C:\xampp\mysql\bin\\';
        $db_name = DB_NAME;
        $db_user = DB_USERNAME;
        $db_pass = DB_PASSWORD;
        $repo_path = base_app;
        $sql_file = $repo_path . 'database/sms_db.sql';

        // 1. Export Database
        $dump_cmd = "\"{$mysql_bin}mysqldump\" -u {$db_user} " . (!empty($db_pass) ? "-p{$db_pass} " : "") . "{$db_name} > \"{$sql_file}\"";
        exec($dump_cmd, $output, $return_var);

        if($return_var !== 0){
            return json_encode(['status' => 'failed', 'msg' => 'Database export failed. Check MySQL Bin path.', 'cmd' => $dump_cmd]);
        }

        // 2. Git Operations
        chdir($repo_path);
        
        $commands = [
            "\"{$git_path}\" add .",
            "\"{$git_path}\" commit -m \"Auto-sync: " . date('Y-m-d H:i:s') . "\"",
            "\"{$git_path}\" push origin main"
        ];

        $results = [];
        foreach($commands as $cmd){
            exec($cmd . " 2>&1", $cmd_out, $cmd_ret);
            $results[] = [
                'command' => $cmd,
                'output' => implode("\n", $cmd_out),
                'return' => $cmd_ret
            ];
            unset($cmd_out);
        }

        return json_encode(['status' => 'success', 'msg' => 'Push complete.', 'details' => $results]);
    }

    /**
     * Pull updates from GitHub (Code and .sql file)
     */
    public function pull_updates(){
        $git_path = $this->settings->info('git_path') ?: 'git';
        $repo_path = base_app;

        chdir($repo_path);
        $cmd = "\"{$git_path}\" pull origin main 2>&1";
        exec($cmd, $output, $return_var);

        return json_encode([
            'status' => ($return_var === 0) ? 'success' : 'failed',
            'msg' => implode("\n", $output),
            'return' => $return_var
        ]);
    }

    /**
     * Restore database from database/sms_db.sql
     */
    public function restore_db(){
        $mysql_bin = $this->settings->info('mysql_bin') ?: 'C:\xampp\mysql\bin\\';
        $db_name = DB_NAME;
        $db_user = DB_USERNAME;
        $db_pass = DB_PASSWORD;
        $sql_file = base_app . 'database/sms_db.sql';

        if(!is_file($sql_file)){
            return json_encode(['status' => 'failed', 'msg' => 'Backup file not found in database/ folder.']);
        }

        $restore_cmd = "\"{$mysql_bin}mysql\" -u {$db_user} " . (!empty($db_pass) ? "-p{$db_pass} " : "") . "{$db_name} < \"{$sql_file}\"";
        exec($restore_cmd, $output, $return_var);

        if($return_var !== 0){
            return json_encode(['status' => 'failed', 'msg' => 'Database restoration failed. Check MySQL Bin path.', 'output' => implode("\n", $output)]);
        }

        return json_encode(['status' => 'success', 'msg' => 'Database successfully restored from GitHub backup.']);
    }
}

// Handler for AJAX requests
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$git_sync = new GitSync();
switch ($action) {
	case 'push':
		echo $git_sync->push_to_git();
		break;
	case 'pull':
		echo $git_sync->pull_updates();
		break;
	case 'restore':
		echo $git_sync->restore_db();
		break;
	default:
		break;
}
