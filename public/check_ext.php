<?php
echo "Extension loaded: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No');
echo "<br>";
if (function_exists('phpinfo')) {
    phpinfo(INFO_MODULES);
}