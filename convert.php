<?php
// Test på konvertering av nytt format

// http://php.net/manual/en/function.microtime.php
function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

$time_start = microtime(true);

$write = true;
// $dir = "E:\svv\";
// $filename = "DATA_NORGE_11.08.2020.dsv";
// $filenameOutput = "dataset.csv";

if (count($argv) == 4) {
    $dir = $argv[1];
    $filename = $argv[2];
    $filenameOutput = $argv[3];
    print("Køyrer converteringsscript.\n\tKatalog: $dir\n\tInput-filnamn: $filename\n\tOutput-filnamn: $filenameOutput\n\n");
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
while ($line = fgets($datasetFP)) {
    // $line = utf8_encode($lineRaw); // kun dersom input-fil er ISO-teiknsett. Endra til UTF8 frå august 2020
    $lineSplit = explode("¤", $line);

    for ($i = 0; $i < count($lineSplit); $i++) {
        if (strstr($lineSplit[$i], "\"") || strstr($lineSplit[$i], ";")) {
            $lineSplit[$i] = '"' . str_replace('"', '\"', $lineSplit[$i]) . '"';
        } 
    }

    // Fjernar første kolonne. Tomt felt der understellsnummer var før.
    unset($lineSplit[0]);

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
            print("Wrong number of fields! $lineNr\n");
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