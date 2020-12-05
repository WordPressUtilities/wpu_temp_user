# WPU Temp User

## Todo

- [ ] Auto Delete Users.
- [ ] Special User Role.
- [ ] Block WP-Admin access, REST API & User toolbar.
- [ ] Hook for session duration.

## Example

```php
add_action('wp', function () {
    global $WPUTempUser;
    $WPUTempUser->log_user('id_1234');
});
```
