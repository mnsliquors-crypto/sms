<div class="card card-outline card-primary shadow">
	<div class="card-header">
		<h3 class="card-title">Git Sync & Database Backup</h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="callout callout-info mb-4">
                        <h5><i class="fas fa-info-circle"></i> GitHub Integration</h5>
                        <p>This module allows you to sync your code and database with your GitHub repository.</p>
                        <p><b>Current Remote:</b> <span id="git-remote" class="text-primary">https://github.com/mnsliquors-crypto/sms.git</span></p>
                    </div>
                </div>
            </div>

            <!-- Configuration Section -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-outline card-secondary shadow-sm">
                        <div class="card-header py-2">
                            <h3 class="card-title"><i class="fas fa-tools mr-2"></i> Path Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form action="" id="git-config-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="git_path" class="small">Git Executable Path</label>
                                            <input type="text" class="form-control form-control-sm" name="git_path" id="git_path" value="<?php echo $_settings->info('git_path') ?: 'C:\Users\USERE\AppData\Local\GitHubDesktop\app-3.5.6\resources\app\git\cmd\git.exe' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="mysql_bin" class="small">MySQL Bin Directory</label>
                                            <input type="text" class="form-control form-control-sm" name="mysql_bin" id="mysql_bin" value="<?php echo $_settings->info('mysql_bin') ?: 'C:\xampp\mysql\bin\\' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="btn btn-secondary btn-sm btn-flat" id="save-config">
                                        <i class="fas fa-save mr-1"></i> Save Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Push to GitHub -->
                <div class="col-md-4">
                    <div class="info-box bg-light border shadow-sm">
                        <span class="info-box-icon bg-primary elevation-1"><i class="fab fa-github"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Push to GitHub</span>
                            <span class="info-box-number small text-muted">Backup everything</span>
                            <button type="button" id="btn-push" class="btn btn-primary btn-sm btn-flat mt-2">
                                <i class="fas fa-upload"></i> Backup Now
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pull Updates -->
                <div class="col-md-4">
                    <div class="info-box bg-light border shadow-sm">
                        <span class="info-box-icon bg-success elevation-1"><i class="fas fa-download"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pull Updates</span>
                            <span class="info-box-number small text-muted">Update code & .sql file</span>
                            <button type="button" id="btn-pull" class="btn btn-success btn-sm btn-flat mt-2">
                                <i class="fas fa-sync"></i> Get Updates
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Restore DB -->
                <div class="col-md-4">
                    <div class="info-box bg-white border shadow-sm">
                        <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-database"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-danger font-weight-bold">Restore Database</span>
                            <span class="info-box-number small text-muted">Overwrite local data</span>
                            <button type="button" id="btn-restore" class="btn btn-outline-danger btn-sm btn-flat mt-2">
                                <i class="fas fa-history"></i> Restore DB
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4 d-none" id="log-container">
                <div class="col-md-12">
                    <div class="card card-dark">
                        <div class="card-header py-1">
                            <h3 class="card-title small">Operation Logs</h3>
                        </div>
                        <div class="card-body p-0">
                            <pre id="git-logs" class="bg-black p-3 mb-0" style="max-height: 300px; overflow-y: auto; color: #00ff00; font-family: 'Courier New', Courier, monospace; font-size: 13px;"></pre>
                        </div>
                    </div>
                </div>
            </div>
		</div>
	</div>
</div>

<script>
    $(document).ready(function(){
        $('#git-config-form').submit(function(e){
            e.preventDefault();
            start_loader();
            $.ajax({
                url: _base_url_ + 'classes/SystemSettings.php?f=update_settings',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                error: function(err){
                    console.log(err);
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        alert_toast("Configuration saved successfully.", 'success');
                    } else {
                        alert_toast("An error occurred.", 'error');
                    }
                    end_loader();
                }
            })
        });

        $('#btn-push').click(function(){
            start_loader();
            $('#log-container').removeClass('d-none');
            $('#git-logs').html('[PUSH] Starting database export and GitHub push...\n');
            
            $.ajax({
                url: _base_url_ + 'classes/GitSync.php?f=push',
                method: 'POST',
                dataType: 'json',
                error: function(err){
                    console.error(err);
                    alert_toast("An error occurred while pushing to Git.", "error");
                    end_loader();
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        $('#git-logs').append('<span class="text-success">[SUCCESS]</span> ' + resp.msg + '\n');
                        alert_toast(resp.msg, "success");
                    } else {
                        $('#git-logs').append('<span class="text-danger">[FAILED]</span> ' + resp.msg + '\n');
                        if(resp.cmd) $('#git-logs').append('Debug: ' + resp.cmd + '\n');
                        alert_toast(resp.msg, "error");
                    }
                    end_loader();
                }
            });
        });

        $('#btn-pull').click(function(){
            start_loader();
            $('#log-container').removeClass('d-none');
            $('#git-logs').html('[PULL] Fetching updates from GitHub...\n');

            $.ajax({
                url: _base_url_ + 'classes/GitSync.php?f=pull',
                method: 'POST',
                dataType: 'json',
                error: function(err){
                    console.error(err);
                    alert_toast("An error occurred while pulling from Git.", "error");
                    end_loader();
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        $('#git-logs').append('<span class="text-success">[SUCCESS]</span> Updates pulled successfully.\n');
                        $('#git-logs').append(resp.msg + '\n');
                        alert_toast("Updates pulled successfully.", "success");
                    } else {
                        $('#git-logs').append('<span class="text-danger">[FAILED]</span> ' + resp.msg + '\n');
                        alert_toast("Failed to pull updates.", "error");
                    }
                    end_loader();
                }
            });
        });

        $('#btn-restore').click(function(){
            _conf("Are you sure you want to restore the database from the GitHub backup? <br><b>WARNING: This will overwrite ALL your current local data!</b>", "restore_db");
        });
    });

    function restore_db(){
        start_loader();
        $('#log-container').removeClass('d-none');
        $('#git-logs').html('[RESTORE] Importing database from backup file...\n');

        $.ajax({
            url: _base_url_ + 'classes/GitSync.php?f=restore',
            method: 'POST',
            dataType: 'json',
            error: function(err){
                console.error(err);
                alert_toast("An error occurred while restoring the database.", "error");
                end_loader();
            },
            success: function(resp){
                if(resp.status == 'success'){
                    $('#git-logs').append('<span class="text-success">[SUCCESS]</span> ' + resp.msg + '\n');
                    alert_toast(resp.msg, "success");
                    setTimeout(function(){
                        location.reload();
                    }, 2000);
                } else {
                    $('#git-logs').append('<span class="text-danger">[FAILED]</span> ' + resp.msg + '\n');
                    if(resp.output) $('#git-logs').append(resp.output + '\n');
                    alert_toast(resp.msg, "error");
                }
                end_loader();
            }
        });
    }
</script>
