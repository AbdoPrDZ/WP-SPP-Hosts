# WP Smart Proxy Hosts

WP Smart Proxy Hosts is a powerful extension for WordPress that allows you to create and manage hosts and sessions for users using JWT tokens.

## Features

- Create and manage hosts: With WP Smart Proxy Hosts, you can easily create and manage hosts within your WordPress site. This allows you to provide access to specific resources or services to your users.

- Secure access with JWT tokens: The extension utilizes JWT (JSON Web Tokens) for secure authentication and authorization. Users can access hosts by providing a valid JWT token, ensuring only authorized users can access the resources.

- Session management: WP Smart Proxy Hosts provides session management capabilities, allowing you to control the duration and expiration of user sessions. This ensures that access to hosts is limited to the specified time frame.

- User-friendly interface: The extension comes with a user-friendly interface that makes it easy to create, manage, and monitor hosts and sessions. You can quickly set up and configure the extension to meet your specific requirements.

## Installation

1. Clone the extension into your wordpress directory.

    ```shell
    cd /var/www/wordpress
    git clone https://github/AbdoPrDZ/WP-SPP-Hosts.git
    ```

2. Include the config file `wordpress/WP-SPP-Hosts/wp-config.php` into your root `wordpress/wp-config.php`.

    file: `wordpress/wp-config.php`

    ```php
    /* Other config lines */

    /** Included WP-SPP-Hosts files. */
    require_once ABSPATH . 'WP-SPP-Hosts/wp-config.php';

    /** Sets up WordPress vars and included files. */
    require_once ABSPATH . 'wp-settings.php';
    ```

3. Include the functions file `WP-SPP-Hosts/functions.php` into your theme functions file (e.g: `wordpress/wp-content/themes/freesia-empire/functions.php`)

    ```php
    /* Other lines */
    
    /* Include the WP-SPP-Hosts functions file. */
    require_once WP_SPP_HOSTS_DIR .'functions.php';
    ```

4. Link the templates pages into your theme directory.

    ```shell
    sudo ln -s /var/www/wordpress/WP-SPP-Hosts/templates /var/www/wordpress/wp-content/themes/freesia-empire/wp-spp-hosts-templates
    ```
