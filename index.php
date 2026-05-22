<?php

require 'vendor/autoload.php';

$plug = '<?php function plug_plate(string $file, mixed ...$params) {
    echo "here, there should be the component \"$file\" with ";
    
    if (count($params) === 0) {
        echo "no parameters\n";
    } else {
        echo "these parameters: ".json_encode($params)."\n"; }
    }
?>';

echo $plug.LeoNickl\Plate\Plate::file('example.plate');
