<?php
 $linesFile = 'lines.json';

 $linesData = [];
if (file_exists($linesFile)) {
    $linesData = json_decode(file_get_contents($linesFile), true);
}

header('Content-Type: application/json');
echo json_encode($linesData);
?>