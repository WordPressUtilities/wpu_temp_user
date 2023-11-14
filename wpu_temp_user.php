<?php
/*
Plugin Name: WPU Temp User
Plugin URI: https://github.com/WordPressUtilities/wpu_temp_user
Update URI: https://github.com/WordPressUtilities/wpu_temp_user
Description: Lib to handle a temporary user
Version: 1.0.2
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wpu_temp_user
Requires at least: 6.0
Requires PHP: 8.0
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUTempUser {

    private $role_opt = 'wputempuser__role';
    private $role_id = 'wputempuser_temp_user';
    private $role_name = 'Temp User';

    public function __construct() {
        add_action('init', array(&$this, 'user_access'));
        add_action('init', array(&$this, 'create_role'));
        add_action('after_setup_theme', array(&$this, 'remove_admin_bar'));
        /* Clean up */
        add_action('init', array(&$this, 'do_fake_cron'));
        add_action('admin_init', array(&$this, 'trigger_fake_cron'));
    }

    public function remove_admin_bar() {
        if ($this->is_current_user_temp_user()) {
            show_admin_bar(false);
            add_filter('show_admin_bar', '__return_false');
        }
    }

    /* ----------------------------------------------------------
      Role
    ---------------------------------------------------------- */

    public function user_access() {
        $is_temp_user = $this->is_current_user_temp_user();
        if (is_admin() && $is_temp_user) {
            return false;
        }
        if ($is_temp_user) {
            $this->mark_user_active(get_current_user_id());
        }
    }

    public function create_role() {
        /* Start on subscriber role */
        $subscriber_role = get_role('subscriber');
        $role_details = $subscriber_role->capabilities;

        /* Register role if changed */
        $role_version = md5($this->role_id . $this->role_name . json_encode($role_details));
        if (get_option($this->role_opt) != $role_version) {
            if (get_role($this->role_id)) {
                remove_role($this->role_id);
            }
            add_role($this->role_id, $this->role_name, $role_details);
            update_option($this->role_opt, $role_version);
        }
    }

    /* ----------------------------------------------------------
      Log
    ---------------------------------------------------------- */

    public function log_user($user_name) {

        /* Ensure format is correct */
        $user_name = sanitize_title($user_name);

        /* Ensure user name is not too long */
        $user_name = substr($user_name, 0, 60);

        /* If user is already logged in */
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if ($user->user_login == $user_name) {
                $this->mark_user_active($user->ID);
                return $user->ID;
            }

            /* Force Logout */
            wp_logout();
        }

        /* Check user by ID */
        $user_id = $this->username_exists($user_name);

        /* No user id : Create a temp user */
        if (!$user_id) {
            $user_id = $this->generate_temp_user($user_name);
        }

        /* Login as user */
        $this->login_as($user_id);
        return $user_id;
    }

    public function username_exists($user_name) {
        global $wpdb;
        $val = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_login=%s AND NOW()", $user_name));
        if (is_numeric($val)) {
            return $val;
        }
        return false;
    }

    public function generate_temp_user($user_name) {
        $user_mail = $user_name . '@example.com';
        $user_pass = md5(time() . $user_mail);
        $user_id = wp_create_user($user_name, $user_pass, $user_mail);
        if (is_wp_error($user_id)) {
            error_log('WPU Temp User : ' . $user_id->get_error_message());
            return false;
        }
        return $user_id;
    }

    public function login_as($user_id) {
        if (!$user_id) {
            return false;
        }

        $user = get_user_by('id', $user_id);
        $user->remove_role('subscriber');
        $user->add_role($this->role_id);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        do_action('wp_login', $user->user_login, $user);
        $this->mark_user_active($user_id);
    }

    public function mark_user_active($user_id) {
        update_user_meta($user_id, '_last_action', time());
    }

    public function kill_temp_user($user_id) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
        if (get_current_user_id() == $user_id) {
            wp_logout();
        }
    }

    /* ----------------------------------------------------------
      Clean
    ---------------------------------------------------------- */

    public function do_fake_cron() {
        if (!defined('DOING_CRON') || !DOING_CRON) {
            return;
        }
        $this->trigger_fake_cron();
    }

    public function trigger_fake_cron() {
        /* Call every 3600 sec */
        $t_id = 'wpu_temp_user_last_cron';
        if (get_transient($t_id)) {
            return;
        }
        set_transient($t_id, '1', 3600);
        $this->delete_old_users();
    }

    public function delete_old_users() {
        $max_last_action = time() - apply_filters('wpu_temp_user__user_delete_after', 86400);
        $users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => '_last_action',
                    'value' => $max_last_action,
                    'compare' => "<=",
                    'type' => 'numeric'
                )
            )
        ));
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ($users as $user) {
            wp_delete_user($user->ID);
        }
    }

    /* ----------------------------------------------------------
      Helper
    ---------------------------------------------------------- */

    public function is_current_user_temp_user() {
        if (!is_user_logged_in()) {
            return false;
        }
        $user_info = get_userdata(get_current_user_id());
        return is_object($user_info) && is_array($user_info->roles) && in_array($this->role_id, $user_info->roles);
    }

}

$WPUTempUser = new WPUTempUser();
