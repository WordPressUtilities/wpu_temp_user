<?php
/*
Plugin Name: WPU Temp User
Plugin URI: https://github.com/WordPressUtilities/wpu_temp_user
Description: Lib to handle a temporary user
Version: 0.2.0
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUTempUser {

    private $role_opt = 'wputempuser__role';
    private $role_id = 'wputempuser_temp_user';
    private $role_name = 'Temp User';

    public function __construct() {
        add_action('init', array(&$this, 'user_access'));
        add_action('init', array(&$this, 'create_role'));
        add_action('after_setup_theme', array(&$this, 'remove_admin_bar'));
    }

    public function remove_admin_bar() {
        if ($this->is_current_user_temp_user()) {
            show_admin_bar(false);
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

        /* If user is already logged in */
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if ($user->user_login == $user_name) {
                $this->mark_user_active($user->ID);
                return;
            }

            /* Force Logout */
            wp_logout();
        }

        /* Check user by ID */
        $user_id = username_exists($user_name);

        /* No user id : Create a temp user */
        if (!$user_id) {
            $user_id = $this->generate_temp_user($user_name);
        }

        /* Login as user */
        $this->login_as($user_id);
    }

    public function generate_temp_user($user_name) {
        $user_pass = md5(time() . $user_mail);
        $user_mail = $user_name . '@example.com';
        $user_id = wp_create_user($user_name, $user_pass, $user_mail);
        if (is_wp_error($user_id)) {
            echo '<pre>';
            var_dump($user_id->get_error_message());
            echo '</pre>';
            die;
        }
        return $user_id;
    }

    public function login_as($user_id) {
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

    /* ----------------------------------------------------------
      Helper
    ---------------------------------------------------------- */

    public function is_current_user_temp_user() {
        if (!is_user_logged_in()) {
            return false;
        }
        $user_info = get_userdata(get_current_user_id());
        return is_array($user_info->roles) && in_array($this->role_id, $user_info->roles);
    }

}

$WPUTempUser = new WPUTempUser();
