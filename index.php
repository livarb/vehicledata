<?php
// header('Content-Type: text/html; charset=utf-8');

define("TESTMODE", true);

if (TESTMODE) {
	define("WRITECSV", false);	
	define("DEBUG", true);
	define("DEBUG_LINELENGTH", false);	
	define("OUTPUT", true);
	define("OUTPUTDIR", "outputTest/");

	$input = array(
		array(
			"tekn",
			"struktur_tekn.csv",
			"tekntest.txt"
		), array(
			"typg",
			"struktur_typg.csv",
			"typgtest.txt"
		), array(
			"utek",
			"struktur_utek.csv",
			"utektest.txt"
		)
	);

} else {
	define("WRITECSV", false);	
	define("DEBUG", true);
	define("DEBUG_LINELENGTH", false);
	define("OUTPUT", false);
	define("OUTPUTDIR", "output/");

	$input = array(
		array(
			"tekn",
			"struktur_tekn.csv",
			"completeRaw/tekninfo.txt"
		)
		, array(
			"typg",
			"struktur_typg.csv",
			"completeRaw/typginfo.txt"
		), array(
			"utek",
			"struktur_utek.csv",
			"completeRaw/utekinfo.txt"
		)
	);	
}


function parseCSV($data) {
	$csv = array_map('str_getcsv_custom', $data);
	array_walk($csv, function(&$a) use ($csv) {
	  $a = array_combine($csv[0], $a);
	});
	array_shift($csv); # remove column header
	return $csv;
}

function str_getcsv_custom($array) {
	return str_getcsv($array, ";");
}

// http://php.net/manual/en/function.microtime.php
function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

$time_start = microtime(true);

?>

<style>
td {
	width: 50%;
	vertical-align: top;
}
</style>

<?php

foreach ($input as $files) {
	$filtype = $files[0];
	print ("<a href=\"#$filtype\">$filtype</a> &nbsp; ");
}
print ("<br/><br/>\n");

foreach ($input as $files) {
	$structFile = $files[1];
	$dataFile = $files[2];

	$dataRaw = file($structFile);
	$data = parseCSV($dataRaw);

	$cardata = array();
	$cardataRawFP = fopen($dataFile, "r");
	if (!$cardataRawFP) {
		die("Kunne ikkje lese rådatafil: $dataFile");
	}

	if (WRITECSV) {
		// Open CSV-output-file
		$csvFileDir = OUTPUTDIR . $files[0];
		if (!is_dir($csvFileDir)) {
			mkdir($csvFileDir);
		}
		$csvOutputFile = $csvFileDir . "/dataset.csv";
		$csvOutputFP = fopen($csvOutputFile, "w");
		if (!$csvOutputFP) {
			die("Kunne ikkje opne skrivetilgang til CSV-fil: $csvOutputFile");	
		}

		// Write CSV-header-line
		$headerColumns = array();
		foreach($data as $column) {
			$headerColumns[] = $column["kortnamn"];
		}
		$headerLine = implode(";", $headerColumns);
		fwrite($csvOutputFP, $headerLine . "\n");
	}

	print("<span id=\"" . $files[0] 
		. "\"><b>" . $files[0] . "</b></span><br/>\n");

	print("<table><tr><td><u>Data</u><br/>\n<pre>\n");

	$lineNum = 1;
    while (($line = fgets($cardataRawFP)) !== false) {

		// $lineNum++;
		// continue;

		$lineLen = strlen($line);

		if (DEBUG_LINELENGTH) {
			$arrCheck = array_fill(0, ($lineLen+1), '0');
		}

		$lineErrors = false;

		$car = array();
		foreach ($data as $attribute) {
			// hentar ut rå verdi
			$value = substr(
				$line, 
				($attribute["startpos"]-1), 
				$attribute["lengde"]
			);

			// Fyller sjekk-array for linja
			if (DEBUG_LINELENGTH) {
				for ($i = $attribute["startpos"]; $i < ($attribute["startpos"] + $attribute["lengde"]); $i++) {
					$arrCheck[$i] = '1';
				}
			}

			// Kvalitetssjekk
			if (DEBUG && $attribute["type"] == "NUM") {
				if (!is_numeric($value)) {
					// print("$lineNum: " 
					// 	. $attribute["kortnamn"] 
					// 	. " er ikkje numerisk! $value\n"
					// 	// . $line . "\n"
					// );
					$lineErrors = true;
				}
			}

			// Konverterer data
			if ($attribute["type"] == "NUM") {
				if ($attribute["kortnamn"] == "motorytelse") {
					$heilDel = intval(substr($value, 0, -2));
					$desimaler = substr($value, -2);
					if ($desimaler == "00") {
						$value = $heilDel;
					} else {
						$value = $heilDel . "," . $desimaler;
					}
				} else {
					$value = intval($value, 10);
				}
			} else {
				$value_trimmed = trim($value);
				if (strlen($value_trimmed) > 0) {
					$value_trimmed = str_replace('"', '\"', $value_trimmed);
					$value = '"' . $value_trimmed . '"';
				} else {
					$value = "";
				}
			}
			$car[$attribute["kortnamn"]] = $value;

		}
		if (DEBUG && $lineErrors) {
			// print("XX: " . $line . "\n");
			print($lineNum . "\n");
			flush();
		}

		// Write to CSV
		if (WRITECSV && !$lineErrors) {
			$csvLine = implode(";", $car);
			fwrite($csvOutputFP, $csvLine . "\n");
		}

		if (OUTPUT) print_r($car);

		if (DEBUG_LINELENGTH) {		
			$arrCheckImploded = implode($arrCheck);
			$zeroes = substr_count($arrCheckImploded, "0");
			$ones = substr_count($arrCheckImploded, "1");

			if ($zeroes != 3) {
				print($lineNum . ": Dekningssjekk. Linjelengede: $lineLen , 0: $zeroes, 1: $ones\n" . $arrCheckImploded . "\n");
				print($line . "<br/>\n");
			}
		}

		// if ($lineNum == 20) break;
		
		$lineNum++;
	}
	fclose($cardataRawFP);

	if (WRITECSV) {
		fclose($csvOutputFP);
	}

	print( number_format($lineNum, 0, ",", " ") . " linjer" );

	// print_r($cardata);
	print("</pre></td>\n");

	print("<td><u>Struktur</u><br/>\n<pre>\n");
	// print_r($data);
	print("</pre></td></tr></table><br/><br/>\n\n");
} 

$time_end = microtime(true);
$time = number_format($time_end - $time_start, 2);
print("K&oslash;yretid: " . $time . " sekund\n");
?>