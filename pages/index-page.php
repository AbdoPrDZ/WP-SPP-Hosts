<?php

$jwt_auth_key = get_option('spp_jwt_auth_key');
$redis_url = get_option('spp_redis_url');
$socket_server_host = get_option('spp_socket_server_host');
$socket_server_port = get_option('spp_socket_server_port');
$socket_server_debug = get_option('spp_socket_server_debug');
$socket_server_log = get_option('spp_socket_server_log');
$socket_server_status = get_option('spp_socket_server_status');

$socket_url = "http://$socket_server_host:$socket_server_port";

$errors = isset($errors) ? $errors : [];

function error($errors, $target) {
  return isset($errors[$target]) ? $errors[$target] : null;
}

?>

<div class="wrap">
  <h1 class="wp-heading-inline">WP SPP Hosts Manager</h1>
  <form method="post">
    <table class="form-table">
      <tr>
        <th><label for="jwt-auth-key">JWT Auth Key</label></th>
        <td>
          <input type="text" name="jwt_auth_key" id="jwt-auth-key" value="<?php echo $jwt_auth_key; ?>" class="regular-text">
          <?php if ($error = error($errors, 'jwt_auth_key')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="redis-url">Redis URL</label></th>
        <td>
          <input type="text" name="redis_url" id="redis-url" value="<?php echo $redis_url; ?>" class="regular-text">
          <?php if ($error = error($errors, 'redis_url')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="socket-server-host">Socket Server Host</label></th>
        <td>
          <input type="text" name="socket_server_host" id="socket-server-host" value="<?php echo $socket_server_host; ?>" class="regular-text">
          <?php if ($error = error($errors, 'socket_server_host')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="socket-server-port">Socket Server Port</label></th>
        <td>
          <input type="text" name="socket_server_port" id="socket-server-port" value="<?php echo $socket_server_port; ?>" class="regular-text">
          <?php if ($error = error($errors, 'socket_server_port')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="socket-server-debug">Socket Server Debug</label></th>
        <td>
          <fieldset id="socket-server-debug">
            <legend class="screen-reader-text"><span>Socket Server Debug</span></legend>
            <label>
              <input type="radio" name="socket_server_debug" value="1" <?php checked($socket_server_debug, 1); ?>>
              Enable
            </label>
            <label>
              <input type="radio" name="socket_server_debug" value="0" <?php checked($socket_server_debug, 0); ?>>
              Disable
            </label>
          </fieldset>
          <?php if ($error = error($errors, 'socket_server_debug')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <th><label for="socket-server-log-file">Socket Server Log</label></th>
        <td>
          <input type="text" name="socket_server_log" id="socket-server-log-file" value="<?php echo $socket_server_log; ?>" class="regular-text">
          <?php if ($error = error($errors, 'socket_server_log')) : ?>
            <p class="description error"><?php echo $error; ?></p>
          <?php endif; ?>
        </td>
      </tr>
      <!-- <tr>
        <th><label for="socket-server-status">Socket Server Status</label></th>
        <td>
          <?php if ($socket_server_status == 'stopped') : ?>
            <button type="submit" name="submit" class="button button-success" id="socket-server-status" value="start_socket_server">Start Server</button>
            <?php if ($error = error($errors, 'start_server')) : ?>
              <p class="description error"><?php echo $error; ?></p>
            <?php endif; ?>
          <?php else: ?>
            <button type="submit" name="submit" class="button button-danger" id="socket-server-status" value="stop_socket_server">Stop Server</button>
            <?php if ($error = error($errors, 'stop_server')) : ?>
              <p class="description error"><?php echo $error; ?></p>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr> -->
    </table>
    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"/>
  </form>
  <hr>

  <h2>Socket Server Status</h2>
  <table class="form-table">
    <tr>
      <th><label for="socket-server-status">Server Status</label></th>
      <td>
        <div id="socket-server-status">
          <button class="button button-primary" id="check-socket-server-status">
            Check Server Status
          </button>
          <pre></pre>
        </div>
      </td>
    </tr>
    <tr>
      <th><label for="socket-server-log">Server Log</label></th>
      <td>
        <pre id="socket-server-log">
<?php
  $log_file = get_option('spp_socket_server_log');
  if (file_exists($log_file)) {
    echo file_get_contents($log_file);
  } else {
    echo 'Log file not found.';
  }
?>
        </pre>
      </td>
    </tr>
    <tr>
      <th><label for="socket-server-start-command">Server Start command</label></th>
      <td>
        <div id="socket-server-start-command">
<?php
$command_args = "\"$jwt_auth_key\"
    --redis-url=\"$redis_url\"
    --host=\"$socket_server_host\"
    --port=\"$socket_server_port\"
    $socket_server_debug
    $socket_server_log
    > /dev/null 2>&1 &";
?>
          <h3>Linux:</h3>
          <pre><?php echo WP_SPP_HOSTS_DIR . "socket.io/bin/server-linux $command_args";?></pre>
          <h3>Windows:</h3>
          <pre><?php echo WP_SPP_HOSTS_DIR . "socket.io/bin/server-win $command_args";?></pre>
          <h3>MacOS:</h3>
          <pre><?php echo WP_SPP_HOSTS_DIR . "socket.io/bin/server-macos $command_args";?></pre>
        </div>
      </td>
    </tr>
    <tr>
      <th><label for="socket-server-stop-command">Server Stop command</label></th>
      <td>
        <div id="socket-server-stop-command">
          <h3>Linux:</h3>
          <pre><?php echo "kill -9 \$(lsof -t -i:$socket_server_port)";?></pre>
          <h3>Windows:</h3>
          <pre><?php echo "netstat -ano | findstr :$socket_server_port | findstr LISTENING | for /F \"tokens=5\" %p in ('more') do taskkill /F /PID %p"; ?></pre>
          <h3>MacOS:</h3>
          <pre><?php echo "kill -9 \$(lsof -t -i:$socket_server_port)";?></pre>
        </div>
      </td>
    </tr>
  </table>

  <script>
    jQuery(document).ready(function($) {
      function getSocketServerStatus(params) {
        $('#check-socket-server-status').attr('disabled', true)
        $('#socket-server-status pre').html('')
        $.ajax({
          url: '<?php echo $socket_url; ?>',
          type: 'GET',
          success: function(response) {
            $('#socket-server-status pre').html(JSON.stringify(response, null, 2));
            $('#check-socket-server-status').attr('disabled', false)
          },
          error: (error) => {
            $('#socket-server-status pre').html(JSON.stringify(error, null, 2));
            $('#check-socket-server-status').attr('disabled', false)
          }
        });
      }
      getSocketServerStatus()
      $('#check-socket-server-status').click(() => getSocketServerStatus());
    });
  </script>
  <style>
    pre {
      max-height: 300px;
      overflow: auto;
      background-color: #f9f9f9;
      border: 1px solid #ccc;
      padding: 10px;
    }
  </style>
</div>
