<?php
/*
Plugin Name: WPU Temp User
Plugin URI: https://github.com/WordPressUtilities/wpu_temp_user
Description: Lib to handle a temporary user
Version: 0.1.0
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUTempUser {
    public function __construct() {
    }

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
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        do_action('wp_login', $user->user_login, $user);
        $this->mark_user_active($user_id);
    }

    public function mark_user_active($user_id) {
        update_user_meta($user_id, '_last_action', time());
    }

}

$WPUTempUser = new WPUTempUser();
