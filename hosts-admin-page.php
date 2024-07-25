<?php
global $wpdb;
$table_name = $wpdb->prefix . 'hosts';

// Handle editing a specific host
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Manage Hosts</h1>
    
    <!-- Form for adding or editing a host -->
    <form method="post">
        <input type="hidden" name="action" value="<?php echo isset($item) ? 'edit' : 'add'; ?>">
        <?php if (isset($item)) : ?>
            <input type="hidden" name="id" value="<?php echo esc_attr($item->id); ?>">
        <?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label for="name">Name</label></th>
                <td><input type="text" name="name" id="name" value="<?php echo isset($item) ? esc_attr($item->name) : ''; ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="host">Host Target</label></th>
                <td><input type="text" name="host" id="host" value="<?php echo isset($item) ? esc_attr($item->host) : ''; ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="description">Description</label></th>
                <td><textarea name="description" id="description" class="large-text"><?php echo isset($item) ? esc_textarea($item->description) : ''; ?></textarea></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button button-primary" value="<?php echo isset($item) ? 'Update Host' : 'Add Host'; ?>">
        </p>
    </form>
    
    <!-- Display the list of hosts -->
    <h2>Hosts List</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Host Target</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $hosts = $wpdb->get_results("SELECT * FROM `$table_name`");
            foreach ($hosts as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row->id) . '</td>';
                echo '<td>' . esc_html($row->name) . '</td>';
                echo '<td>' . esc_html($row->host) . '</td>';
                echo '<td>' . esc_html($row->description) . '</td>';
                echo '<td>
                    <a href="' . admin_url('admin.php?page=manage-hosts&edit=' . $row->id) . '" class="button">Edit</a> |
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