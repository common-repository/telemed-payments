<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include '../clearent_util.php';

$confirmed = $_POST['confirm'];
if ($confirmed == "true") {
    $plugin_path = $_POST['plugin_dir_path'];
    $cu = new telemed_util();
    $cu->telemed_clearLog($plugin_path);
    $cu->telemed_logMessage("User requested log file clear.", $plugin_path);
}

$redirct_url = $_POST['redirect_url'] . "options-general.php?page=telemed_option_group&tab=debug_log";

header("Location: " . $redirct_url);
die();

?>