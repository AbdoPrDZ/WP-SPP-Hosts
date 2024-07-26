<?php
/**
 * Template Name: User Tokens Page
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
  const hostsSelect = document.getElementById('hosts-select')
  const loading = document.querySelector('#watch .loading')
  const iframe = document.getElementById('watch-iframe')

  hostsSelect?.addEventListener('change', function () {
    iframe.style.display = 'none'

    const selectedToken = this.value
    if (selectedToken) {
      loading.style.display = 'block'
      iframe.src = `http://${selectedToken}.localhost`
      iframe.style.display = 'block'
      loading.style.display = 'none'
    }
  })
})

</script>

<?php
get_footer();
?>
