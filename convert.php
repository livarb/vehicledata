<?php
// Test på konvertering av nytt format

// http://php.net/manual/en/function.microtime.php
function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

// https://www.php.net/manual/en/function.array-diff.php#120821
function arrayDiff($A, $B) {
    $intersect = array_intersect($A, $B);
    return array_merge(array_diff($A, $intersect), array_diff($B, $intersect));
}

$time_start = microtime(true);

$write = true;
// $dir = "E:\svv\";
// $filename = "DATA_NORGE_11.08.2020.dsv";
// $filenameOutput = "dataset.csv";

if (count($argv) == 5) {
    $dir = $argv[1];
    $filename = $argv[2];
    $filenameOutput = $argv[3];
    $filenameFields = $argv[4];
    print("Køyrer converteringsscript.\n\tKatalog: $dir\n\tInput-filnamn: $filename\n\tOutput-filnamn: $filenameOutput\n\tFields-filnamn: $filenameFields\n\n");
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

// Read fields.xml
$fields = array();
$fieldsLines = file($dir . $filenameFields);
if ($fieldsLines === false) {
    print("Could not read fields-file for reading: $dir$filenameFields\n");
    exit(5);
}
foreach ($fieldsLines as $fieldsLine) {
    if (strpos($fieldsLine, "<shortName>") !== false) {
        $fields[] = trim(str_replace(array("<shortName>", "</shortName>"), "", $fieldsLine));
    }
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

        // Sjekk mot fields.xml
        $lineSplitLower = array_map('strtolower', $lineSplit);
        $lineSplitLowerTrim = array_map('trim', $lineSplitLower);
        $differanse = arrayDiff($lineSplitLowerTrim, $fields);
        if (count($differanse) !== 0) {
            print("Mismatch mellom CSV og fields.xml\n");
            print(count($fields) . " felt i fields.xml — " . count($lineSplit) . " felt i CSV\n");
            print("Følgande felt er ikkje i begge: " . implode(", ", $differanse) . "\n");
            exit(6);
        }

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