<?php
/*
Plugin Name: Shortbus
Plugin URI: http://wordpress.org/extend/plugins/shortbus/
Description: Quickly and easily manage shortcodes.
Version: 1.2.0
Author: Matt Gibbs
Author URI: http://uproot.us/
License: GPL
Copyright: Matt Gibbs
*/
define('SHORTBUS_VERSION', '1.2.0');

class Shortbus
{
    /*---------------------------------------------------------------------------------------------
     * Verify the database, check Shortbus version, add necessary hooks
     *
     * @author Matt Gibbs
     * @since 1.0
     ---------------------------------------------------------------------------------------------*/
    function init() {
        global $wpdb;

        // Update the wp_option value
        $db_version = get_option('shortbus_version');

        if (false === $db_version) {
            $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}shortbus` (
            `id` int unsigned AUTO_INCREMENT PRIMARY KEY,
            `name` varchar(128),
            `content` mediumtext)");

            // Add the version
            add_option('shortbus_version', SHORTBUS_VERSION);
        }
        elseif (version_compare($db_version, SHORTBUS_VERSION, '<')) {
            update_option('shortbus_version', SHORTBUS_VERSION);
        }

        // Add hooks
        add_action('admin_init', array($this, 'help_box'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_head', array($this, 'admin_scripts'));
        add_shortcode('sb', array($this, 'shortcode'));
        add_shortcode('shortbus', array($this, 'shortcode'));
        add_action('wp_ajax_shortbus', array($this, 'handle_ajax'));
        add_filter('widget_text', 'do_shortcode');
    }

    /*---------------------------------------------------------------------------------------------
     * Retrieve the shortcode content
     *
     * @author Matt Gibbs
     * @since 1.0
     ---------------------------------------------------------------------------------------------*/
    function shortcode($atts) {
        global $wpdb;

        $atts = (object) $atts;
        if (false === empty($atts->name)) {
            $row = $wpdb->get_row("SELECT content FROM `{$wpdb->prefix}shortbus` WHERE name = '$atts->name' LIMIT 1");
            ob_start();
            echo '<div class="shortbus">' . eval('?>' . $row->content) . '</div>';
            return ob_get_clean();
        }
    }

    /*---------------------------------------------------------------------------------------------
     * Add the submenu under "Pages"
     *
     * @author Matt Gibbs
     * @since 1.0
     ---------------------------------------------------------------------------------------------*/
    function admin_menu() {
        add_submenu_page('tools.php', 'Shortcodes', 'Shortcodes', 'manage_options', 'shortbus', array($this, 'admin_page'));
    }

    /*---------------------------------------------------------------------------------------------
     * Standardize ajax responses
     *
     * @author Matt Gibbs
     * @since 1.1.1
     ---------------------------------------------------------------------------------------------*/
    function json_response($status = 'ok', $status_message = null, $data = null) {
        if (empty($status_message)) {
            $status_message = '<p>' . $status_message . '</p>';
        }
        return json_encode(
            array(
                'status' => $status,
                'status_message' => $status_message,
                'data' => $data,
            )
        );
    }

    /*---------------------------------------------------------------------------------------------
     * Save the shortcode data
     *
     * @author Matt Gibbs
     * @since 1.0
     ---------------------------------------------------------------------------------------------*/
    function handle_ajax() {
        include(WP_PLUGIN_DIR . '/shortbus/admin-ajax.php');
    }

    /*---------------------------------------------------------------------------------------------
     * Add javascript to the admin header
     *
     * @author Matt Gibbs
     * @since 1.0
     ---------------------------------------------------------------------------------------------*/
    function admin_scripts() {
        if ('shortbus' == $this->get_var('page')) {
            include(WP_PLUGIN_DIR . '/shortbus/admin-scripts.php');
        }
    }

    /*---------------------------------------------------------------------------------------------
     * Use the contextual help box
     *
     * @author Matt Gibbs
     * @since 1.1.1
     ---------------------------------------------------------------------------------------------*/
    function help_box() {
        ob_start();
?>
        <div><strong>To add a shortcode:</strong></div>
        <div>Enter a shortcode name into the text box and click <strong>Add New</strong>.</div>
        <div style="margin-top:15px"><strong>To edit a shortcode:</strong></div>
        <div>Select an existing shortcode from the dropdown.</div>
        <div style="margin-top:15px"><strong>To use a shortcode:</strong></div>
        <div>Add <span class="code">[sb name="My Shortcode"]</span> within your pages, posts, or widgets.</div>
<?php
        $help_text = ob_get_clean();
        add_contextual_help('tools_page_shortbus', $help_text);
    }

    /*---------------------------------------------------------------------------------------------
     * Build the shortcode management page
     *
     * @author Matt Gibbs
     * @since 1.0
     ---------------------------------------------------------------------------------------------*/
    function admin_page() {
        global $wpdb;
?>
<link href="<?php echo WP_PLUGIN_URL; ?>/shortbus/style.css" rel="stylesheet" type="text/css" />
<link href="<?php echo WP_PLUGIN_URL; ?>/shortbus/js/codemirror/codemirror.css" rel="stylesheet" type="text/css" />
<div class="wrap">
	<div id="icon-edit-pages" class="icon32 shortbus-icon"><br></div>
    <h2>Shortcodes</h2>

    <div id="shortcode-response" class="updated"><p>To get started, click on "Help" at the top right of this screen.</p></div>

    <div style="margin:15px 0">
        <div id="sb-select">
            <div id="sb-select-box">
                <div id="sb-select-value">Select one</div>
            </div>
            <div id="sb-select-popup">
                <div id="sb-filter">
                    <input type="text" id="sb-filter-input" value="" /> Search
                </div>
                <div id="sb-select-options">
                    <div class="sb-option" rel="">Select one</div>
<?php
        $results = $wpdb->get_results("SELECT id, name FROM `{$wpdb->prefix}shortbus` ORDER BY name ASC");
        foreach ($results as $result) {
?>
                    <div class="sb-option" rel="<?php echo $result->id; ?>"><?php echo $result->name; ?></div>
<?php
        }
?>
                </div>
            </div>
        </div>
        - or -
        <input id="shortcode-name" type="text" title="Enter a name for your shortcode" value="" />
        <a id="add-shortcode" class="button">Add New</a>
    </div>
    <div id="shortcode-area" class="hidden">
        <div><textarea id="shortcode-content"></textarea></div>
        <div id="save-area">
            <input type="submit" class="button-primary" id="edit-shortcode" value="Save Changes" />
            or <a id="delete-shortcode" href="javascript:;">Delete Shortcode</a>
        </div>
        <div id="loading-area" class="hidden">
            <span id="loading"></span> Loading, please wait...
        </div>
    </div>
    <div id="shortcode-intro">
        <div><strong>Migrate your shortcodes</strong></div>
        <div><a id="import" href="javascript:;">Import</a> and <a id="export" href="javascript:;">export</a> within seconds.</div>
        <div id="export-area">
            <textarea id="export-content"></textarea>
            <div><input type="submit" class="button-primary" id="do-export" value="Export" /></div>
        </div>
        <div id="import-area">
            <textarea id="import-content"></textarea>
            <div><input type="checkbox" id="import-replace" /> Replace existing shortcodes?</div>
            <div><input type="submit" class="button-primary" id="do-import" value="Import" /></div>
        </div>
        <div style="margin-top:15px"><strong>What do you think of this plugin?</strong></div>
        <div>
            Please
            <a href="http://wordpress.org/extend/plugins/shortbus/" target="_blank">rate it</a>,
            <a href="http://wordpress.org/tags/shortbus?forum_id=10" target="_blank">visit the forum</a>, or
            <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JMVGK3L35X6BU" target="_blank">consider donating</a>.
        </div>
    </div>
</div>
<?php
    }

    /*---------------------------------------------------------------------------------------------
     * Return a GET, POST, or SESSION variable
     *
     * @author Matt Gibbs
     * @since 1.1.4
     ---------------------------------------------------------------------------------------------*/
    function get_var($key, $type = 'get') {
        $type = strtolower($type);
        if ('get' == $type) {
            $out = isset($_GET[$key]) ? $_GET[$key] : null;
        }
        elseif ('post' == $type) {
            $out = isset($_POST[$key]) ? $_POST[$key] : null;
        }
        elseif ('session' == $type) {
            $out = isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        }
        return $out;
    }
}

/*---------------------------------------------------------------------------------------------
 * Initialize Shortbus
 *
 * @author Matt Gibbs
 * @since 1.0
 ---------------------------------------------------------------------------------------------*/
$sb = new Shortbus();
$sb->init();
