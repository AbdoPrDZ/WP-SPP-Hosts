<?php
/**
 * Template Name: User Tokens Page Using JWT
 */

if (!is_user_logged_in()) {
  wp_redirect(home_url());
  exit;
}

$current_user = wp_get_current_user();

get_header();

global $wpdb;

$table_name = $wpdb->prefix . 'tokens';
$hosts_table = $wpdb->prefix . 'hosts';
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
  <?php if (!empty($tokens)) : ?>
    <label for="hosts-select">Select a token:</label>
    <select id="hosts-select">
      <option value="">-- Select a token --</option>
      <?php foreach ($tokens as $token) : ?>
        <option value="<?php echo esc_attr($token->id); ?>">
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const hostsSelect = document.getElementById('hosts-select');
  const loading = document.querySelector('#watch .loading');
  const iframe = document.getElementById('watch-iframe');
  const errorDiv = document.querySelector('#watch .loading .error');

  hostsSelect?.addEventListener('change', async function () {
    iframe.style.display = 'none';
    errorDiv.textContent = '';

    const selectedToken = this.value;
    if (selectedToken) try {
      loading.style.display = 'block';
      const token = await generateJWTToken(selectedToken);
      iframe.src = `http://${token.replaceAll('.', '-')}.localhost`;
      iframe.style.display = 'block';
      loading.style.display = 'none';
    } catch (err) {
      errorDiv.textContent = err;
      loading.style.display = 'none';
    }
  });

  function generateJWTToken(token_id) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', '?rest_route=/custom/v2/generate-jwt-token', true);
      xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
      xhr.setRequestHeader('Accept', 'application/json;charset=UTF-8');

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

      xhr.send(JSON.stringify({ token_id: token_id }));
    });
  }
});

</script>

<?php
get_footer();
?>
