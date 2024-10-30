<?php
/** 
 * HNGamers Core
 * 
 * @package HNGamers Atavism Core
 * @author thevisad
 * @copyright 2022 HNGamers
 * @license GPL 
 * 
 * @wordpress-plugin 
 * Plugin Name: HNGamers Atavism Core
 * Plugin URI: https://hngamers.com/courses/atavism-wordpress-cms/
 * Description: These are the Core Atavism Settings and are used in each of the addon plugins that will be coming.
 * Version: 0.0.9
 * Author: thevisad
 * Author URI: https://hngamers.com/
 * Text Domain: hngamers-atavism-core
 * License: GPL 
 **/
class hngamers_atavism_core {

    public $user_characters;
    public $server_characters;
    
    public function __construct() {
        register_activation_hook(__FILE__,  array( $this,'hngamers_core_atavism_defaults'));
        register_deactivation_hook( __FILE__, array( $this,'hngamers_core_atavism_remove') );
        add_action( 'admin_menu', array( $this,'hngamers_core_admin_menu') );
        add_action('admin_init', array( $this,'hngamers_core_admin_init'));
        add_action('wp_login', array( $this, 'hngamers_core_get_user_characters'), 10, 2);
	add_action('admin_enqueue_scripts', array( $this, 'hngamers_core_admin_styles'), 10, 2);

    }
    
    // Check server and port availability
    function hngamers_core_check_server_port($host, $port) {
        $connection = @fsockopen($host, $port, $errno, $errstr, 5); // Try for 5 seconds
        if ($connection) {
            fclose($connection);
            return true;
        } else {
            echo "<font color='red'><b>Failure!</b></font><p>Error checking server " . esc_html($host) . " at port " . esc_html($port) . ": " . esc_html($errstr) . " (" . esc_html($errno) . ")</p>";

            error_log("Error checking server port: $errstr (" . esc_html($errno) . ")");
            return false;
        }
    }
    
    function hngamers_core_get_user_characters($user_login, $user) {
        $db_test_options = get_option('hngamers_core_options');
        
        if (!$this->hngamers_core_check_server_port($db_test_options['hngamers_atavism_master_db_hostname_string'], $db_test_options['hngamers_atavism_master_db_port_string'])) {
            error_log("Unable to connect to the database server on the specified port.");
            return; // Exit the function to prevent further execution
        }
        
        if ($this->hngamers_core_check_server_port($db_test_options['hngamers_atavism_master_db_hostname_string'], $db_test_options['hngamers_atavism_master_db_port_string'])) {
            $mysqli_conn = new mysqli(
                $db_test_options['hngamers_atavism_master_db_hostname_string'],
                $db_test_options['hngamers_atavism_master_db_user_string'],
                $db_test_options['hngamers_atavism_master_db_pass_string'],
                $db_test_options['hngamers_atavism_master_db_schema_string'],
                $db_test_options['hngamers_atavism_master_db_port_string']
            );
        } else {
            echo "<font color='red'><b>Failure!</b></font><p>Unable to connect to the server on the specified port.</p>";
        }
        
        if (!$mysqli_conn) {
            error_log('Database connection failed in hngamers_core_get_user_characters: ' . $mysqli_conn->connect_error);
        } else {
			$stmt = $mysqli_conn->prepare("SELECT
				`master`.account_character.character_id,
				`master`.world.world_name,
				admin.account_character.characterName,
				`master`.account.id
				FROM
				`master`.account
				INNER JOIN `master`.account_character ON `master`.account_character.account_id = `master`.account.id
				INNER JOIN `master`.world ON `master`.account_character.world_server_id = `master`.world.world_id
				INNER JOIN admin.account_character ON `master`.account_character.character_id = admin.account_character.characterId
				WHERE `master`.account.id = ?");
			$stmt->bind_param("i", $user_id);
			$stmt->execute();
			$result = $stmt->get_result();
            
            while($row = $result->fetch_assoc()) {
                $this->user_characters[$row['characterName']] = $row['character_id'];
                $this->server_characters[$row['world_name']] = $row['character_id'];
            }
        }
    }
    
    function hngamers_plugin_section_text() {
        echo '<p>These are the Core Atavism Settings and are used in each of the addon plugins that will be coming.</p>';
    }

    function hngamers_core_admin_menu() {
		add_menu_page('HNGamers Atavism Core', 'HNGamers', 'manage_options', 'hngamers-core-admin', array($this, 'hngamers_core_about_page'));
        add_submenu_page('hngamers-core-admin', 'Default Settings', 'Default Settings', 'manage_options', 'hngamers-core-admin-default', array($this, 'hngamers_core_default_page'));
        add_submenu_page('hngamers-core-admin', 'Database Settings', 'Database', 'manage_options', 'hngamers-core-admin-database', array($this, 'hngamers_core_options_page'));
    }

    function hngamers_core_default_page() {
        ?>
        <div class="wrap">
            <h1>Default Settings for HNGamers Core</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('hngamers_core_options'); // Use the same settings group as other settings
                do_settings_sections('hngamers_core_default'); // This will handle the default section
                submit_button('Save Default Settings');
                ?>
            </form>
        </div>
        <?php
    }

    function hngamers_core_about_page() {
        ?>
        <div class="wrap">
            <h1>HNGamers Core Gateway About</h1>
            <p>This plugin integrates your WooCommerce store with Xsolla, enabling seamless transactions for Atavism Online game services hosted by users. It's an essential tool for Atavism Online license holders to control their game servers efficiently.</p>
            <p>Explore our range of plugins designed to enhance your Atavism Online experience:</p>
			<h2>Links to Plugins and Documentation</h2>
            <ul>
                <li><a href="https://hngamers.com/" target="_blank">HNGamers</a></li>
                <li><a href="https://wordpress.org/plugins/hngamers-atavism-core/" target="_blank">HNGamers Atavism Core</a></li>
                <li><a href="https://wordpress.org/plugins/hngamers-atavism-user-verification/" target="_blank">HNGamers Atavism User Verification</a></li>
                <li><a href="https://hngamers.com/product/hngamers-atavism-item-to-woocommerce-plugin/" target="_blank">HNGamers Atavism Item to WooCommerce</a></li>
                <li><a href="https://hngamers.com/product/atavism-store-woocommerce-xsolla-gateway/" target="_blank">HNGamers Atavism WooCommerce Xsolla Gateway</a></li>
                <li><a href="https://hngamers.com/product/atavism-store-integration/" target="_blank">HNGamers Atavism Store Integration</a></li>
                <li><a href="https://hngamers.com/courses/atavism-wordpress-cms/" target="_blank">Atavism WordPress CMS Documentation</a></li>
            </ul>
        </div>
        <?php
    }

    function hngamers_core_options_page() {
        $thisOption = get_option('hngamers_core_options');
        $server_count = $thisOption['hngamers_atavism_gameserver_count'] ?? 1;
        ?>
        <div class="wrap">
            <h2>HNGamers Core Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('hngamers_core_options'); ?>
                <h2 class="nav-tab-wrapper">
                    <a href="#master-db" class="nav-tab">Master DB</a>
                    <?php for ($i = 1; $i <= $server_count; $i++): ?>
                        <a href="#server-<?php echo $i; ?>" class="nav-tab">Server <?php echo $i; ?></a>
                    <?php endfor; ?>
                    <a href="#connection-status" class="nav-tab">Connection Status</a>
                </h2>
                <div id="master-db" class="tab-content" style="display: none;">
                    <h3>Master Database Settings</h3>
                    <?php do_settings_sections('hngamers_core_admin_master'); ?>
                </div>

                <?php for ($i = 1; $i <= $server_count; $i++): ?>
                    <div id="server-<?php echo $i; ?>" class="tab-content" style="display: none;">
                        <h3>Server <?php echo $i; ?> Settings</h3>
                        <h4>Admin Database Settings</h4>
                        <?php do_settings_sections('hngamers_core_admin_server_' . $i); ?>
                        <h4>Atavism Database Settings</h4>
                        <?php do_settings_sections('hngamers_core_atavism_server_' . $i); ?>
                        <h4>World Content Database Settings</h4>
                        <?php do_settings_sections('hngamers_core_worldcontent_server_' . $i); ?>
                    </div>
                <?php endfor; ?>
                <div id="connection-status" class="tab-content" style="display: none;">
                    <h3>Master Database Connection Status</h3>
                    <?php $this->hngamers_core_database_connectivity(); ?>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.nav-tab').click(function(e) {
                    e.preventDefault();
                    $('.nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    $('.tab-content').hide();
                    $('#' + $(this).attr('href').substring(1)).show();
                });
                $('.nav-tab').first().click(); // Open the first tab by default
            });
        </script>
        <?php
    }
	function hngamers_core_admin_styles() {
		wp_enqueue_style('hngamers-admin-styles', plugin_dir_url(__FILE__) . 'hngamers-admin-styles.css');
	}
	
	function hngamers_core_image_size_fn() {
		$options = get_option('hngamers_core_options');
		$value = isset($options['hngamers_image_size']) ? esc_attr($options['hngamers_image_size']) : '16';
		echo "<input type='number' id='hngamers_image_size' name='hngamers_core_options[hngamers_image_size]' value='$value' min='16' max='64' />";
	}

	function hngamers_core_image_set_fn() {
		$options = get_option('hngamers_core_options');
		$value = isset($options['hngamers_image_set']) ? esc_attr($options['hngamers_image_set']) : '0';
		echo "<input type='number' id='hngamers_image_set' name='hngamers_core_options[hngamers_image_set]' value='$value' min='0' max='9' />";
	}

	function hngamers_core_database_connectivity() {
		$db_test_options = get_option('hngamers_core_options');  
		$plugin_url = plugin_dir_url(__FILE__);
		$image_size = isset($db_test_options['hngamers_image_size']) ? intval($db_test_options['hngamers_image_size']) : 16;
		$image_set = isset($db_test_options['hngamers_image_set']) ? intval($db_test_options['hngamers_image_set']) : 0;

		// Master Database Connection
		$hostname = sanitize_text_field($db_test_options['hngamers_atavism_master_db_hostname_string'] ?? '');
		$port = intval($db_test_options['hngamers_atavism_master_db_port_string'] ?? 3306);
		$user = sanitize_text_field($db_test_options['hngamers_atavism_master_db_user_string'] ?? '');
		$password = sanitize_text_field($db_test_options['hngamers_atavism_master_db_pass_string'] ?? '');
		$schema = sanitize_text_field($db_test_options['hngamers_atavism_master_db_schema_string'] ?? '');

		echo '<div class="hngamers-admin-box">';
		echo '<h3>Master Database Connection Status</h3>';

		if ($this->hngamers_core_check_server_port($hostname, $port)) {
			$mysqli_conn = new mysqli($hostname, $user, $password, $schema, $port);
			if ($mysqli_conn && $mysqli_conn->connect_errno) {
				echo "<p class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Failure! " . esc_html($mysqli_conn->connect_error, $domain = 'default') . "</p>";
			} else {
				echo "<p class='hngamers-status-connected'><img src='" . $plugin_url . "images/online$image_set.png' alt='Online' width='$image_size' height='$image_size'> Connected!</p>";
			}
			$mysqli_conn->close();
		} else {
			echo "<p class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Unable to connect to the server on the specified port.</p>";
		}

		echo '</div>';

		// Servers Connection
		$server_count = $db_test_options['hngamers_atavism_gameserver_count'] ?? 1;
		for ($count = 1; $count <= $server_count; $count++) {
			echo '<div class="hngamers-admin-box">';
			echo '<h3>Server ' . esc_html($count, $domain = 'default') . ' Connection Status</h3>';

			// Admin Database
			$admin_hostname = $db_test_options['hngamers_atavism_admin_db' . $count . '_hostname_string'] ?? '';
			$admin_port = $db_test_options['hngamers_atavism_admin_db' . $count . '_port_string'] ?? '';
			$admin_user = $db_test_options['hngamers_atavism_admin_db' . $count . '_user_string'] ?? '';
			$admin_password = $db_test_options['hngamers_atavism_admin_db' . $count . '_pass_string'] ?? '';
			$admin_schema = $db_test_options['hngamers_atavism_admin_db' . $count . '_schema_string'] ?? '';

			if ($this->hngamers_core_check_server_port($admin_hostname, $admin_port)) {
				echo '<p>Admin Database: ';
				$mysqli_conn = new mysqli($admin_hostname, $admin_user, $admin_password, $admin_schema, $admin_port);
				if ($mysqli_conn && $mysqli_conn->connect_errno) {
					echo "<span class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Failure! " . esc_html($mysqli_conn->connect_error, $domain = 'default') . "</span></p>";
				} else if (!$mysqli_conn) {
					echo "<span class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Database Connection Failure!</span></p>";
				} else {
					echo "<span class='hngamers-status-connected'><img src='" . $plugin_url . "images/online$image_set.png' alt='Online' width='$image_size' height='$image_size'> Connected!</span></p>";
                    $mysqli_conn->close();
				}
			} else {
				echo "<p class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Unable to connect to the server on the specified port.</p>";
			}

			// Atavism Database
			$atavism_hostname = $db_test_options['hngamers_atavism_atavism_db' . $count . '_hostname_string'] ?? '';
			$atavism_port = $db_test_options['hngamers_atavism_atavism_db' . $count . '_port_string'] ?? '';
			$atavism_user = $db_test_options['hngamers_atavism_atavism_db' . $count . '_user_string'] ?? '';
			$atavism_password = $db_test_options['hngamers_atavism_atavism_db' . $count . '_pass_string'] ?? '';
			$atavism_schema = $db_test_options['hngamers_atavism_atavism_db' . $count . '_schema_string'] ?? '';

			if ($this->hngamers_core_check_server_port($atavism_hostname, $atavism_port)) {
				echo '<p>Atavism Database: ';
				$mysqli_conn = new mysqli($atavism_hostname, $atavism_user, $atavism_password, $atavism_schema, $atavism_port);
				if ($mysqli_conn && $mysqli_conn->connect_errno) {
					echo "<span class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Failure! " . esc_html($mysqli_conn->connect_error, $domain = 'default') . "</span></p>";
				} else if (!$mysqli_conn) {
					echo "<span class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Database Connection Failure!</span></p>";
				} else {
					echo "<span class='hngamers-status-connected'><img src='" . $plugin_url . "images/online$image_set.png' alt='Online' width='$image_size' height='$image_size'> Connected!</span></p>";
                    $mysqli_conn->close();
				}
				
			} else {
				echo "<p class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Unable to connect to the server on the specified port.</p>";
			}

			// World Content Database
			$worldcontent_hostname = $db_test_options['hngamers_atavism_worldcontent_db' . $count . '_hostname_string'] ?? '';
			$worldcontent_port = $db_test_options['hngamers_atavism_worldcontent_db' . $count . '_port_string'] ?? '';
			$worldcontent_user = $db_test_options['hngamers_atavism_worldcontent_db' . $count . '_user_string'] ?? '';
			$worldcontent_password = $db_test_options['hngamers_atavism_worldcontent_db' . $count . '_pass_string'] ?? '';
			$worldcontent_schema = $db_test_options['hngamers_atavism_worldcontent_db' . $count . '_schema_string'] ?? '';

			if ($this->hngamers_core_check_server_port($worldcontent_hostname, $worldcontent_port)) {
				echo '<p>World Content Database: ';
				$mysqli_conn = new mysqli($worldcontent_hostname, $worldcontent_user, $worldcontent_password, $worldcontent_schema, $worldcontent_port);
				if ($mysqli_conn && $mysqli_conn->connect_errno) {
					echo "<span class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Failure! " . esc_html($mysqli_conn->connect_error, $domain = 'default') . "</span></p>";
				} else if (!$mysqli_conn) {
					echo "<span class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Database Connection Failure!</span></p>";
				} else {
					echo "<span class='hngamers-status-connected'><img src='" . $plugin_url . "images/online$image_set.png' alt='Online' width='$image_size' height='$image_size'> Connected!</span></p>";
                    $mysqli_conn->close();
				}
			} else {
				echo "<p class='hngamers-status-failure'><img src='" . $plugin_url . "images/offline$image_set.png' alt='Offline' width='$image_size' height='$image_size'> Unable to connect to the server on the specified port.</p>";
			}

			echo '</div>';
		}
	}


    function hngamers_core_atavism_remove() {
        delete_option('hngamers_recaptcha_apikey_pub_string');
        delete_option('hngamers_recaptcha_apikey_priv_string');
        delete_option('hngamers_atavism_selected_server');    
        delete_option('hngamers_atavism_master_db_hostname_string');        
        delete_option('hngamers_atavism_master_db_port_string');
        delete_option('hngamers_atavism_master_db_schema_string');
        delete_option('hngamers_atavism_master_db_user_string');
        delete_option('hngamers_atavism_master_db_pass_string');

        $thisOption = get_option('hngamers_core_options');        

        for($count = 1; $count <= $thisOption['hngamers_atavism_gameserver_count']; $count++) {
            delete_option('hngamers_atavism_gameworld'. strval( $count ) .'_name_string');
            delete_option('hngamers_atavism_admin_db'.strval($count).'_hostname_string');
            delete_option('hngamers_atavism_admin_db'.strval($count).'_port_string');
            delete_option('hngamers_atavism_admin_db' . strval($count) . '_schema_string');
            delete_option('hngamers_atavism_admin_db'.strval($count).'_user_string');
            delete_option('hngamers_atavism_admin_db'.strval($count).'_pass_string');

            delete_option('hngamers_atavism_atavism_db'.strval($count).'_hostname_string');
            delete_option('hngamers_atavism_atavism_db'.strval($count).'_port_string');
            delete_option('hngamers_atavism_atavism_db'.strval($count).'_schema_string');
            delete_option('hngamers_atavism_atavism_db'.strval($count).'_user_string');
            delete_option('hngamers_atavism_atavism_db'.strval($count).'_pass_string');

            delete_option('hngamers_atavism_worldcontent_db'.strval($count).'_hostname_string');
            delete_option('hngamers_atavism_worldcontent_db'.strval($count).'_port_string');
            delete_option('hngamers_atavism_worldcontent_db'.strval($count).'_schema_string');
            delete_option('hngamers_atavism_worldcontent_db'.strval($count).'_user_string');
            delete_option('hngamers_atavism_worldcontent_db'.strval($count).'_pass_string');
        }

        delete_option('hngamers_atavism_gameserver_count');
        delete_option('hngamers_core_options');
    }

    function hngamers_core_selected_server_dropdown_fn() {
        $options = get_option('hngamers_core_options');
        echo '<select name="hngamers_core_options[hngamers_atavism_selected_server]">';
        $server_count = isset($options['hngamers_atavism_gameserver_count']) ? (int) $options['hngamers_atavism_gameserver_count'] : 1;
        $selected_server = isset($options['hngamers_atavism_selected_server']) ? (int) $options['hngamers_atavism_selected_server'] : 1;

        for ($i = 1; $i <= $server_count; $i++) {
            $selected_attr = selected($selected_server, $i, false);
            echo '<option value="' . esc_attr($i) . '"' . $selected_attr . '>Server ' . esc_html($i) . '</option>';
        }
        echo '</select>';
    }

    function hngamers_core_number_servers_dropdown_fn() {
        $thisOption = get_option('hngamers_core_options');
        $serverCount = $thisOption['hngamers_atavism_gameserver_count'] ?? 1; // Default to 1 if not set

        echo "<select name='hngamers_core_options[hngamers_atavism_gameserver_count]'>";
        for ($i = 1; $i <= 10; $i++) {
            $selected = ($i == $serverCount) ? 'selected' : '';
            echo "<option value='$i' $selected>$i</option>";
        }
        echo "</select>";
    }

    function hngamers_core_admin_init() {
		register_setting('hngamers_core_options', 'hngamers_core_options', array($this, 'hngamers_core_options_validate'));

        // Default settings section for global settings like number of servers
        add_settings_section('hngamers_core_default', 'Global Settings', function() { echo "<p>Manage global settings for HNGamers Core.</p>"; }, 'hngamers_core_default');
        add_settings_field('hngamers_atavism_gameserver_count', 'Number of Servers', array($this, 'hngamers_core_number_servers_dropdown_fn'), 'hngamers_core_default', 'hngamers_core_default');
		add_settings_field('hngamers_image_size', 'Size of Online/Offline Images', array($this, 'hngamers_core_image_size_fn'), 'hngamers_core_default', 'hngamers_core_default');
		add_settings_field('hngamers_image_set', 'Image Set to Use', array($this, 'hngamers_core_image_set_fn'), 'hngamers_core_default', 'hngamers_core_default');

        // Master DB settings
        add_settings_section('hngamers_core_master', 'Master Database Settings', function() {
            echo "<p>Manage master database settings for HNGamers Core.</p>";
        }, 'hngamers_core_admin_master');

        $master_fields = [
            'hngamers_atavism_master_db_hostname_string' => 'Master DB Hostname',
            'hngamers_atavism_master_db_port_string' => 'Master DB Port',
            'hngamers_atavism_master_db_user_string' => 'Master DB User',
            'hngamers_atavism_master_db_pass_string' => 'Master DB Password',
            'hngamers_atavism_master_db_schema_string' => 'Master DB Schema'
        ];

        foreach ($master_fields as $key => $label) {
            add_settings_field($key, $label, array($this, 'hngamers_core_plugin_setting_string_master'), 'hngamers_core_admin_master', 'hngamers_core_master', ['label_for' => $key, 'key' => $key]);
        }

        $thisOption = get_option('hngamers_core_options');
        $server_count = $thisOption['hngamers_atavism_gameserver_count'] ?? 1;

        for ($i = 1; $i <= $server_count; $i++) {
            // Admin DB settings
            add_settings_section('hngamers_core_server_' . $i, 'Admin Database Settings for Server ' . $i, function() use ($i) { echo "<p>Admin Database Settings for Server $i.</p>"; }, 'hngamers_core_admin_server_' . $i);
            $admin_fields = ['_hostname_string' => 'Admin Hostname', '_port_string' => 'Admin Port', '_schema_string' => 'Admin Database Schema', '_user_string' => 'Admin Username', '_pass_string' => 'Admin Password'];
            foreach ($admin_fields as $key => $label) {
                add_settings_field("hngamers_atavism_admin_db{$i}{$key}", $label, array($this, 'hngamers_core_plugin_setting_string_admin'), 'hngamers_core_admin_server_' . $i, 'hngamers_core_server_' . $i, ['label_for' => "hngamers_atavism_admin_db{$i}{$key}", 'id' => $i, 'key' => $key]);
            }

            // Atavism DB settings
            add_settings_section('hngamers_core_atavism_server_' . $i, 'Atavism Database Settings for Server ' . $i, function() use ($i) { echo "<p>Atavism Database Settings for Server $i.</p>"; }, 'hngamers_core_atavism_server_' . $i);
            $atavism_fields = ['_hostname_string' => 'Atavism Hostname', '_port_string' => 'Atavism Port', '_schema_string' => 'Atavism Database Schema', '_user_string' => 'Atavism Username', '_pass_string' => 'Atavism Password'];
            foreach ($atavism_fields as $key => $label) {
                add_settings_field("hngamers_atavism_atavism_db{$i}{$key}", $label, array($this, 'hngamers_core_plugin_setting_string_atavism'), 'hngamers_core_atavism_server_' . $i, 'hngamers_core_atavism_server_' . $i, ['label_for' => "hngamers_atavism_atavism_db{$i}{$key}", 'id' => $i, 'key' => $key]);
            }

            // World Content DB settings
            add_settings_section('hngamers_core_worldcontent_server_' . $i, 'World Content Database Settings for Server ' . $i, function() use ($i) { echo "<p>World Content Database Settings for Server $i.</p>"; }, 'hngamers_core_worldcontent_server_' . $i);
            $worldcontent_fields = ['_hostname_string' => 'World Content Hostname', '_port_string' => 'World Content Port', '_schema_string' => 'World Content Database Schema', '_user_string' => 'World Content Username', '_pass_string' => 'World Content Password'];
            foreach ($worldcontent_fields as $key => $label) {
                add_settings_field("hngamers_atavism_worldcontent_db{$i}{$key}", $label, array($this, 'hngamers_core_plugin_setting_string_worldcontent'), 'hngamers_core_worldcontent_server_' . $i, 'hngamers_core_worldcontent_server_' . $i, ['label_for' => "hngamers_atavism_worldcontent_db{$i}{$key}", 'id' => $i, 'key' => $key]);
            }
        }
    }

	function hngamers_core_plugin_setting_string_admin($args) {
		$options = get_option('hngamers_core_options');
		$id = $args['id'];
		$key = $args['key'];
		$value_admin = isset($options["hngamers_atavism_admin_db{$id}{$key}"]) ? esc_attr($options["hngamers_atavism_admin_db{$id}{$key}"]) : '';

		if ($key === '_pass_string') {
			echo "<input type='password' id='hngamers_atavism_admin_db{$id}{$key}' name='hngamers_core_options[hngamers_atavism_admin_db{$id}{$key}]' value='$value_admin' />";
		} else {
			echo "<input type='text' id='hngamers_atavism_admin_db{$id}{$key}' name='hngamers_core_options[hngamers_atavism_admin_db{$id}{$key}]' value='$value_admin' />";
		}
	}

	function hngamers_core_plugin_setting_string_atavism($args) {
		$options = get_option('hngamers_core_options');
		$id = $args['id'];
		$key = $args['key'];
		$value_atavism = isset($options["hngamers_atavism_atavism_db{$id}{$key}"]) ? esc_attr($options["hngamers_atavism_atavism_db{$id}{$key}"]) : '';

		if ($key === '_pass_string') {
			echo "<input type='password' id='hngamers_atavism_atavism_db{$id}{$key}' name='hngamers_core_options[hngamers_atavism_atavism_db{$id}{$key}]' value='$value_atavism' />";
		} else {
			echo "<input type='text' id='hngamers_atavism_atavism_db{$id}{$key}' name='hngamers_core_options[hngamers_atavism_atavism_db{$id}{$key}]' value='$value_atavism' />";
		}
	}

	function hngamers_core_plugin_setting_string_worldcontent($args) {
		$options = get_option('hngamers_core_options');
		$id = $args['id'];
		$key = $args['key'];
		$value_worldcontent = isset($options["hngamers_atavism_worldcontent_db{$id}{$key}"]) ? esc_attr($options["hngamers_atavism_worldcontent_db{$id}{$key}"]) : '';

		if ($key === '_pass_string') {
			echo "<input type='password' id='hngamers_atavism_worldcontent_db{$id}{$key}' name='hngamers_core_options[hngamers_atavism_worldcontent_db{$id}{$key}]' value='$value_worldcontent' />";
		} else {
			echo "<input type='text' id='hngamers_atavism_worldcontent_db{$id}{$key}' name='hngamers_core_options[hngamers_atavism_worldcontent_db{$id}{$key}]' value='$value_worldcontent' />";
		}
	}
	function hngamers_core_plugin_setting_string_master($args) {
		$options = get_option('hngamers_core_options');
		$key = $args['key'];
		$value = isset($options[$key]) ? esc_attr($options[$key]) : '';

		if ($key === 'hngamers_atavism_master_db_pass_string') {
			echo "<input type='password' id='$key' name='hngamers_core_options[$key]' value='$value' />";
		} else {
			echo "<input type='text' id='$key' name='hngamers_core_options[$key]' value='$value' />";
		}
	}

	function hngamers_core_options_validate($input) {
		$existing_options = get_option('hngamers_core_options');
		
		// Sanitize new input

		// Ensure the server count is a positive integer
		if (isset($input['hngamers_atavism_gameserver_count']) && is_numeric($input['hngamers_atavism_gameserver_count']) && $input['hngamers_atavism_gameserver_count'] > 0) {
			$input['hngamers_atavism_gameserver_count'] = intval($input['hngamers_atavism_gameserver_count']);
		} else {
			$input['hngamers_atavism_gameserver_count'] = isset($existing_options['hngamers_atavism_gameserver_count']) ? $existing_options['hngamers_atavism_gameserver_count'] : 1;
		}

		if (isset($input['hngamers_atavism_recaptcha_apikey_pub_string'])) {
			$input['hngamers_atavism_recaptcha_apikey_pub_string'] = wp_filter_nohtml_kses($input['hngamers_atavism_recaptcha_apikey_pub_string']);
		}
		if (isset($input['hngamers_atavism_recaptcha_apikey_priv_string'])) {
			$input['hngamers_atavism_recaptcha_apikey_priv_string'] = wp_filter_nohtml_kses($input['hngamers_atavism_recaptcha_apikey_priv_string']);
		}
		if (isset($input['hngamers_atavism_master_db_hostname_string'])) {
			$input['hngamers_atavism_master_db_hostname_string'] = wp_filter_nohtml_kses($input['hngamers_atavism_master_db_hostname_string']);
		}
		if (isset($input['hngamers_atavism_master_db_user_string'])) {
			$input['hngamers_atavism_master_db_user_string'] = wp_filter_nohtml_kses($input['hngamers_atavism_master_db_user_string']);
		}
		if (isset($input['hngamers_atavism_master_db_pass_string'])) {
			$input['hngamers_atavism_master_db_pass_string'] = wp_filter_nohtml_kses($input['hngamers_atavism_master_db_pass_string']);
		}
		if (isset($input['hngamers_atavism_master_db_schema_string'])) {
			$input['hngamers_atavism_master_db_schema_string'] = wp_filter_nohtml_kses($input['hngamers_atavism_master_db_schema_string']);
		}
		if (isset($input['hngamers_atavism_master_db_port_string'])) {
			$input['hngamers_atavism_master_db_port_string'] = wp_filter_nohtml_kses($input['hngamers_atavism_master_db_port_string']);
		}

		// Merge new input with existing options
		$merged_options = array_merge($existing_options, $input);

		// Loop through servers and sanitize inputs
		$server_count = intval($merged_options['hngamers_atavism_gameserver_count']);
		for ($count = 1; $count <= $server_count; $count++) {
			$merged_options["hngamers_atavism_gameworld{$count}_name_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_gameworld{$count}_name_string"] ?? $existing_options["hngamers_atavism_gameworld{$count}_name_string"] ?? '');

			// Admin Database
			$merged_options["hngamers_atavism_admin_db{$count}_hostname_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_admin_db{$count}_hostname_string"] ?? $existing_options["hngamers_atavism_admin_db{$count}_hostname_string"] ?? 'localhost');
			$merged_options["hngamers_atavism_admin_db{$count}_port_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_admin_db{$count}_port_string"] ?? $existing_options["hngamers_atavism_admin_db{$count}_port_string"] ?? '3306');
			$merged_options["hngamers_atavism_admin_db{$count}_schema_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_admin_db{$count}_schema_string"] ?? $existing_options["hngamers_atavism_admin_db{$count}_schema_string"] ?? 'admin_schema');
			$merged_options["hngamers_atavism_admin_db{$count}_user_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_admin_db{$count}_user_string"] ?? $existing_options["hngamers_atavism_admin_db{$count}_user_string"] ?? 'root');
			$merged_options["hngamers_atavism_admin_db{$count}_pass_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_admin_db{$count}_pass_string"] ?? $existing_options["hngamers_atavism_admin_db{$count}_pass_string"] ?? 'password');

			// Atavism Database
			$merged_options["hngamers_atavism_atavism_db{$count}_hostname_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_atavism_db{$count}_hostname_string"] ?? $existing_options["hngamers_atavism_atavism_db{$count}_hostname_string"] ?? 'localhost');
			$merged_options["hngamers_atavism_atavism_db{$count}_port_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_atavism_db{$count}_port_string"] ?? $existing_options["hngamers_atavism_atavism_db{$count}_port_string"] ?? '3306');
			$merged_options["hngamers_atavism_atavism_db{$count}_schema_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_atavism_db{$count}_schema_string"] ?? $existing_options["hngamers_atavism_atavism_db{$count}_schema_string"] ?? 'atavism_schema');
			$merged_options["hngamers_atavism_atavism_db{$count}_user_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_atavism_db{$count}_user_string"] ?? $existing_options["hngamers_atavism_atavism_db{$count}_user_string"] ?? 'root');
			$merged_options["hngamers_atavism_atavism_db{$count}_pass_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_atavism_db{$count}_pass_string"] ?? $existing_options["hngamers_atavism_atavism_db{$count}_pass_string"] ?? 'password');

			// World Content Database
			$merged_options["hngamers_atavism_worldcontent_db{$count}_hostname_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_worldcontent_db{$count}_hostname_string"] ?? $existing_options["hngamers_atavism_worldcontent_db{$count}_hostname_string"] ?? 'localhost');
			$merged_options["hngamers_atavism_worldcontent_db{$count}_port_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_worldcontent_db{$count}_port_string"] ?? $existing_options["hngamers_atavism_worldcontent_db{$count}_port_string"] ?? '3306');
			$merged_options["hngamers_atavism_worldcontent_db{$count}_schema_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_worldcontent_db{$count}_schema_string"] ?? $existing_options["hngamers_atavism_worldcontent_db{$count}_schema_string"] ?? 'world_content_schema');
			$merged_options["hngamers_atavism_worldcontent_db{$count}_user_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_worldcontent_db{$count}_user_string"] ?? $existing_options["hngamers_atavism_worldcontent_db{$count}_user_string"] ?? 'root');
			$merged_options["hngamers_atavism_worldcontent_db{$count}_pass_string"] = wp_filter_nohtml_kses($input["hngamers_atavism_worldcontent_db{$count}_pass_string"] ?? $existing_options["hngamers_atavism_worldcontent_db{$count}_pass_string"] ?? 'password');
		}

		return $merged_options;
	}


    function hngamers_core_atavism_defaults() {
        global $thisOption_array;
        $thisOption_array = array(
            "hngamers_atavism_recaptcha_apikey_priv_string"        => "YOUR_RECAPTHA_PRIVATE_KEY",
            "hngamers_atavism_recaptcha_apikey_pub_string"         => "YOUR_RECAPTCHA_PUBLIC_KEY",            
            "hngamers_atavism_selected_server"         => "1",
            "hngamers_atavism_gameserver_count"         => "1",
            "hngamers_atavism_master_db_hostname_string"           => "localhost",
            "hngamers_atavism_master_db_port_string"               => "3306",
            "hngamers_atavism_master_db_schema_string"             => "master",
            "hngamers_atavism_master_db_user_string"               => "root",
            "hngamers_atavism_master_db_pass_string"               => "test",
        );
        
        for ($count = 1; $count <= $thisOption_array['hngamers_atavism_gameserver_count']; $count++) {
            $array2 = array(
                "hngamers_atavism_gameworld". strval( $count ) ."_name_string" => "Server ". strval( $count ) . " Name",
                "hngamers_atavism_admin_db". strval( $count ) ."_hostname_string" => "localhost",
                "hngamers_atavism_admin_db". strval( $count ) ."_port_string" => "3306",
                "hngamers_atavism_admin_db". strval( $count ) ."_schema_string" => "admin",
                "hngamers_atavism_admin_db". strval( $count ) ."_user_string" => "root",
                "hngamers_atavism_admin_db". strval( $count ) ."_pass_string" => "test",
                "hngamers_atavism_atavism_db". strval( $count ) ."_hostname_string" => "localhost",
                "hngamers_atavism_atavism_db". strval( $count ) ."_port_string" => "3306",
                "hngamers_atavism_atavism_db". strval( $count ) ."_schema_string" => "atavism",
                "hngamers_atavism_atavism_db". strval( $count ) ."_user_string" => "root",
                "hngamers_atavism_atavism_db". strval( $count ) ."_pass_string" => "test",
                "hngamers_atavism_worldcontent_db". strval( $count ) ."_hostname_string" => "localhost",
                "hngamers_atavism_worldcontent_db". strval( $count ) ."_port_string" => "3306",
                "hngamers_atavism_worldcontent_db". strval( $count ) ."_schema_string" => "world_content",
                "hngamers_atavism_worldcontent_db". strval( $count ) ."_user_string" => "root",
                "hngamers_atavism_worldcontent_db". strval( $count ) ."_pass_string" => "test",
            );
            $thisOption_array = array_merge($thisOption_array, $array2);
        }
        update_option('hngamers_core_options', $thisOption_array);

        return;
    }
}

// Initialize plugin
$hngamers_atavism_core = new hngamers_atavism_core();
