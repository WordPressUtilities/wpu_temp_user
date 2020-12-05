# WPU Temp User

## Example

```php
add_action('wp', function () {
    global $WPUTempUser;
    $WPUTempUser->log_user('id_1234');
});
``
