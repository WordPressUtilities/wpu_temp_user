# WPU Temp User

[![PHP workflow](https://github.com/WordPressUtilities/wpu_temp_user/actions/workflows/php.yml/badge.svg 'PHP workflow')](https://github.com/WordPressUtilities/wpu_temp_user/actions)

## Todo

- [x] Auto Delete Users.
- [ ] Block REST API.
- [ ] Hook for session duration.
- [x] Special User Role.
- [x] Block WP-Admin access,
- [x] Block User toolbar.

## Example

```php
add_action('wp', function () {
    global $WPUTempUser;
    $log_user = $WPUTempUser->log_user('id_12éééeéé34');
    if(!$log_user){
        echo 'Temp User failed';
    }
});
```
