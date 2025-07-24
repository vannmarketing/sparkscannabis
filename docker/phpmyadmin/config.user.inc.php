<?php
/**
 * phpMyAdmin custom configuration
 * This file contains custom settings for phpMyAdmin
 */

// Increase memory and time limits
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);

// Upload settings
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');

// Custom configuration
$cfg['DefaultLang'] = 'en';
$cfg['ServerDefault'] = 1;
$cfg['UploadDir'] = '';
$cfg['SaveDir'] = '';

// Session settings
$cfg['LoginCookieValidity'] = 3600; // 1 hour
$cfg['LoginCookieStore'] = 0;
$cfg['LoginCookieDeleteAll'] = true;

// Security settings
$cfg['ForceSSL'] = false; // Set to true if using HTTPS
$cfg['CheckConfigurationPermissions'] = false;

// Interface settings
$cfg['ThemeDefault'] = 'pmahomme';
$cfg['DefaultTabServer'] = 'main.php';
$cfg['DefaultTabDatabase'] = 'structure.php';
$cfg['DefaultTabTable'] = 'browse.php';

// Export settings
$cfg['Export']['format'] = 'sql';
$cfg['Export']['compression'] = 'gzip';
$cfg['Export']['charset'] = 'utf-8';

// Import settings
$cfg['Import']['charset'] = 'utf-8';

// MySQL settings specific to Laravel
$cfg['Servers'][1]['extension'] = 'mysqli';
$cfg['Servers'][1]['compress'] = false;
$cfg['Servers'][1]['AllowNoPassword'] = false;

// Hide databases
$cfg['Servers'][1]['hide_db'] = '^(information_schema|performance_schema|mysql|sys)$';

// Custom theme (optional)
// $cfg['ThemeDefault'] = 'custom';

?>