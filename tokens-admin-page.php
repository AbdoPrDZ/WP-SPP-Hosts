<?php

global $wpdb;

$table_name = $wpdb->prefix . 'tokens';
$hosts_table = $wpdb->prefix . 'hosts';
$users_table = $wpdb->prefix . 'users';

// Fetch the list of hosts and users
$hosts = $wpdb->get_results("SELECT id, name FROM $hosts_table");
$users = $wpdb->get_results(
    "SELECT `users`.ID, `users`.`display_name`
    FROM `$users_table` `users`
    INNER JOIN `$wpdb->usermeta` `usermeta` ON `users`.`ID` = `usermeta`.`user_id`
    WHERE `usermeta`.`meta_key` = '{$wpdb->prefix}capabilities'
    AND `usermeta`.`meta_value` LIKE '%\"subscriber\"%'"
);

// Handle editing a specific token
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">Manage Tokens</h1>

    <!-- Form for adding or editing a token -->
    <form method="post">
        <input type="hidden" name="action" value="<?php echo isset($item) ? 'edit' : 'add'; ?>">
        <?php if (isset($item)) : ?>
            <input type="hidden" name="id" value="<?php echo esc_attr($item->id); ?>">
        <?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label for="host_id">Host</label></th>
                <td>
                    <select name="host_id" id="host_id">
                        <?php foreach ($hosts as $host) : ?>
                            <option value="<?php echo esc_attr($host->id); ?>" <?php selected(isset($item) && $item->host_id == $host->id); ?>>
                                <?php echo esc_html($host->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="user_id">User</label></th>
                <td>
                    <select name="user_id" id="user_id">
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected(isset($item) && $item->user_id == $user->ID); ?>>
                                <?php echo esc_html($user->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="status">Status</label></th>
                <td>
                    <select name="status" id="status">
						<option value="active" <?php selected(isset($item) && $item->status == "active"); ?>>
							Active
						</option>
						<option value="expired" <?php selected(isset($item) && $item->status == "expired"); ?>>
							Expired
						</option>
						<option value="canceled" <?php selected(isset($item) && $item->status == "canceled"); ?>>
							Canceled
						</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="expired_at">Expiration Date</label></th>
                <td><input type="datetime-local" name="expired_at" id="expired_at" value="<?php echo isset($item) ? esc_attr($item->expired_at) : ''; ?>" class="regular-text"></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button button-primary" value="<?php echo isset($item) ? 'Update Token' : 'Add Token'; ?>">
        </p>
    </form>

    <!-- Display the list of tokens -->
    <h2>Tokens List</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Token</th>
                <th>Host ID</th>
                <th>User ID</th>
                <th>Status</th>
                <th>Expiration Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tokens = $wpdb->get_results("SELECT `tokens`.*, `hosts`.`name` as `host_name`, `users`.`display_name` as `user_name` FROM `$table_name` `tokens`
                                          LEFT JOIN `$hosts_table` `hosts` ON `tokens`.`host_id` = `hosts`.`id`
                                          LEFT JOIN `$users_table` `users` ON `tokens`.`user_id` = `users`.`ID`");
            foreach ($tokens as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row->id) . '</td>';
                echo '<td>' . esc_html($row->token) . '</td>';
                echo '<td>' . esc_html($row->host_name) . '</td>';
                echo '<td>' . esc_html($row->user_name) . '</td>';
                echo '<td>' . esc_html(['active' => 'Active', 'expired' => 'Expired', 'canceled' => 'Canceled'][$row->status]) . '</td>';
                echo '<td>' . esc_html($row->expired_at) . '</td>';
                echo '<td>
                    <a href="' . admin_url('admin.php?page=manage-tokens&edit=' . $row->id) . '" class="button">Edit</a> |
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="' . esc_attr($row->id) . '">
                        <input type="submit" value="Delete" onclick="return confirm(\'Are you sure?\');" class="button">
                    </form>
                </td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>
