<?php
// Test på konvertering av nytt format

// http://php.net/manual/en/function.microtime.php
function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

$time_start = microtime(true);

$write = true;
// $filename = "AKD-38_eksempel_16.04.20_10000rader.dsv";
// $filename = "AKD-38_eksempel_16.04.20.dsv";
// $filename = "AKD38_TEKNISKE_DATA_27.05.20.dsv";
// $filenameOutput = "dataset.csv";

if (count($argv) == 4) {
    // print("Got right number of arguments!\n");
    $dir = $argv[1];
    $filename = $argv[2];
    $filenameOutput = $argv[3];
    // print("$dir\n$filename\n$filenameOutput\n\n");
} else {
    print("Wrong number of arguments!\n");
    print_r($argv);
    exit(1);
}

$fileFullpath = $dir . $filename;

if ($write) $fpw = fopen($dir . $filenameOutput, "w");
if ($fpw === false) {
    print("Could not open file for writing: $dir$filenameOutput\n");
    exit(2);
}

$datasetFP = fopen($fileFullpath, 'r');
if ($datasetFP === false) {
    print("Could not open input-file for reading: $fileFullpath\n");
    exit(3);
}

$lineNr = 1;
$numFields = -1;
$errors = false;
while ($lineRaw = fgets($datasetFP)) {
    $line = utf8_encode($lineRaw);
    $lineSplit = explode("¤", $line);

    for ($i = 0; $i < count($lineSplit); $i++) {
        if (strstr($lineSplit[$i], "\"") || strstr($lineSplit[$i], ";")) {
            $lineSplit[$i] = '"' . str_replace('"', '\"', $lineSplit[$i]) . '"';
        } 
    }

    $lineCSV = implode(";", $lineSplit);

    if ($lineNr == 1) {
        $lineCSV = strtolower($lineCSV);
        $numFields = count($lineSplit);

        print("Rådata: overskriftsrad\n");
        print($line . "\n");
        
        // print($lineCSV);
        foreach ($lineSplit as $colName) {
            print(strtolower($colName) . "\n");
        }

        print("Fields: $numFields\n\n");
    } else {
        if (count($lineSplit) !== $numFields) {
            print("Wrong number of fields!\n");
            print($line);
            exit(5);
        }
    }

    if ($write) fwrite($fpw, $lineCSV);

    $lineNr++;
    // if ($lineNr === 10) {
    //     break;
    // }
}
fclose($datasetFP);
if ($write) fclose($fpw);

if ($errors) {
    print("There were errors during conversion\n");
    exit(4);
}

$time_end = microtime(true);
$time = number_format($time_end - $time_start, 2);
print("Linjer: " . $lineNr . "\n");
print("Køyretid konvertering: " . $time . " sekund\n");

?>