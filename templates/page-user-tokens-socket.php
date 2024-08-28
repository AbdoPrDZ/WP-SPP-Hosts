<?php
/**
 * Template Name: User Tokens Page Using Socket IO
 */

if (!is_user_logged_in()) {
  wp_redirect(home_url());
  exit;
}

$current_user = wp_get_current_user();

get_header();

global $wpdb;

$table_name = $wpdb->prefix . 'spp_tokens';
$hosts_table = $wpdb->prefix . 'spp_hosts';
$users_table = $wpdb->prefix . 'users';

// Fetch tokens for the current user
$query = "SELECT `tokens`.*, `hosts`.`name` as `host_name`
    FROM `$table_name` tokens
    LEFT JOIN `$hosts_table` `hosts` ON `tokens`.`host_id` = `hosts`.`id`
    WHERE `tokens`.`user_id` = %d AND
          `tokens`.`status` = 'active' AND
          `tokens`.`expired_at` > NOW()";
$tokens = $wpdb->get_results($wpdb->prepare($query, $current_user->ID));
?>

<div class="wrap">
  <h1>Available Tokens</h1>
  <?php if (!empty($tokens)): ?>
    <div class="connect-loading">
      Loading connection...
      <div class="error"></div>
    </div>
    <div class="watch" style="display: none;">
      <label for="hosts-select">Select a token:</label>

      <select id="hosts-select">
        <option value="">-- Select a token --</option>
        <?php foreach ($tokens as $token): ?>
          <option value="<?php echo esc_attr($token->token); ?>">
            <?php echo esc_html($token->host_name); ?>
          </option>
        <?php endforeach; ?>
      </select><br>

      <div id="watch">
        <div class="loading" style="display: none;">
          Loading...
          <div class="error"></div>
        </div>
        <iframe id="watch-iframe" src=""></iframe>
      </div>
    </div>

    <script>
    function generateJWTToken(target) {
      return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', target, true);
        xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
        xhr.setRequestHeader('Accept', 'application/json;charset=UTF-8');
        xhr.setRequestHeader('X-WP-Nonce', '<?= wp_create_nonce( 'wp_rest' )?>');

        xhr.onreadystatechange = function () {
          if (xhr.readyState === 4) {
            if (xhr.status === 200) {
              const response = JSON.parse(xhr.responseText);
              console.log('response', response);
              if (!response.success) {
                  reject(response.message || 'Failed to generate token');
              } else {
                  resolve(response.token);
              }
            } else {
              reject('Error: ' + xhr.status);
            }
          }
        };

        xhr.send();
      });
    }

    jQuery(document).ready(async ($) => {
      function $on(target, eventName, callback) {
        $(document).on(eventName, target, callback);
      }

      // try {
        const socket = io('http://localhost:3000', {
        // const socket = io('http://<?php echo $_SERVER["SERVER_ADDR"] ?>:3000', {
          auth: {
            token: await generateJWTToken(`?rest_route=/custom/v2/generate-jwt-token`),
          },
          transports: ['websocket']
        });

        socket.on('connect', () => {
          $('.watch').css('display', 'block')
          $('.connect-loading').css('display', 'none')
          console.log('Connected to Socket.IO server');
        });

        socket.on('connect_error', function(error) {
          console.log('Error connecting to Socket.IO server', error);

          $('.watch').css('display', 'none')
          $('.connect-loading').css('display', 'block')
          $('.connect-loading .error').html('Failed to connect to the server')
        })

        socket.on('error', function(error) {
          console.log('Error connecting to Socket.IO server', error);

          $('.watch').css('display', 'none')
          $('.connect-loading').css('display', 'block')
          $('.connect-loading .error').html('Failed to connect to the server')
        })
        socket.on('disconnect', () => {
          console.log('Disconnected from Socket.IO server');

          $('#watch-iframe').attr('src', null)
          $('#watch-iframe').css('display', 'none')
          $('#hosts-select').val('')
          $('.watch').css('display', 'none')
          $('.connect-loading').css('display', 'block')
          $('.connect-loading .error').html('Failed to connect to the server')
        });
      // } catch (err) {
      //   console.error(err)
      //   $('.connect-loading .error').html('Failed to connect to the server')
      // }

      $on('#hosts-select', 'change', async () => {
        $('#watch-iframe').attr('src', null)
        $('#watch-iframe').css('display', 'none')
        $('#watch .loading .error').html('')
        $('#watch .loading').css('display', 'block')

        $('#hosts-select').attr('disabled', true)

        const selectedToken = $('#hosts-select').val();
        if (selectedToken) try {
          const jwtToken = await generateJWTToken(`?rest_route=/custom/v2/generate-host-jwt-token&token=${selectedToken}`);

          socket.emit('select_token', selectedToken, jwtToken)
        } catch (err) {
          console.error(err);
          $('#watch .loading .error').html('Failed to select host')
        } else
          socket.emit('select_token', null);
      });

      socket.on('selected_token', (selectedToken) => {
        console.log('selected_token', selectedToken);

        $('#watch-iframe').attr('src', selectedToken ? `http://${selectedToken}_${<?php echo $current_user->ID ?>}.localhost`: null)
        $('#watch-iframe').css('display', selectedToken ? 'block' : 'none')
        $('#watch .loading').css('display', 'none')
        $('#hosts-select').attr('disabled', false)
      });
    });

    </script>
  <?php else : ?>
    <p>No tokens available.</p>
  <?php endif; ?>
</div>

<style>
  #watch .loading {
    display: block;
  }

  #watch iframe {
    display: none;
    width: 100%;
    height: 500px;
    border: 1px solid #000;
    margin-top: 20px;
  }

  .error {
    color: red;
  }
</style>

<?php
get_footer();
?>
