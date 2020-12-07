# WPU Temp User

## Todo

- [ ] Auto Delete Users.
- [ ] Block REST API.
- [ ] Hook for session duration.
- [x] Special User Role.
- [x] Block WP-Admin access,
- [x] Block User toolbar.

## Example

```php
add_action('wp', function () {
    global $WPUTempUser;
    $WPUTempUser->log_user('id_1234');
});
```
