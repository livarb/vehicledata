<?php
// header('Content-Type: text/html; charset=utf-8');

require("settings.php");

// http://php.net/manual/en/function.microtime.php
function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

$time_start = microtime(true);

// http://stackoverflow.com/a/10473026
function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
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
	return str_getcsv($array, ";", '"', "\\");
}

function createFieldsText($fields) {
	$d = "  "; // delimiter
	$text = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
	$text .= "<datasetFields>\n";
	$text .= "$d<fields>\n";

	foreach($fields as $f) {
		$searchable = empty($f["searchable"]) ? "false" : $f["searchable"];
		$groupable = empty($f["groupable"]) ? "false" : $f["groupable"];

		$text .= ($d.$d . "<field>\n");
		$text .= ($d.$d.$d . "<name>" . $f["felt"] . "</name>\n");
		$text .= ($d.$d.$d . "<shortName>" . $f["kortnamn"] . "</shortName>\n");
		$text .= ($d.$d.$d . "<groupable>" . $groupable . "</groupable>\n");
		$text .=($d.$d.$d . "<searchable>" . $searchable . "</searchable>\n");
		$text .=($d.$d.$d . "<content>" . $f["kommentar"] . "</content>\n");	
		$text .=($d.$d . "</field>\n");
	}
	$text .= "$d</fields>\n</datasetFields>\n";
	return $text;
}

function createMetaText($title) {
	$text = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

	$text .= "<metadata>\n"
		. "\t<name>$title</name>\n"
		. "\t<updated>" . time() . "</updated>\n"
		. "\t<active>false</active>\n"
		. "</metadata>\n";
	return $text;
}

function prepareOutputDir($dir) {
	if (!is_dir($dir)) {
		mkdir($dir);
	}
}

function getFilePointer($filename) {
	$fp = fopen($filename, "w");
	if (!$fp) {
		die("Kunne ikkje opne skrivetilgang til fil: $fp");
	}
	return $fp;
}

function printStructError($msg, $value, $structFile, $lineNum) {
	print("$structFile line $lineNum: $msg — «" . $value . "»<br/>\n");
}

function validateStructData($data) {
	// Check structure-file
	for ($i = 0; $i < count($data); $i++) {
		$row = $data[$i];

		if (!preg_match('/^[a-z0-9_]*$/', $row["kortnamn"])) { 
			printStructError("invalid «kortnamn».", $row["kortnamn"], $structFile, $i);
		}

		// «felt» is not evaluated

		if (!ctype_digit($row["lengde"])) {
			printStructError("«lengde» is not number", $row["lengde"], $structFile, $i);
		}

		if (!ctype_digit($row["startpos"])) {
			printStructError("«startpos» is not number", $row["startpos"], $structFile, $i);
		}

		if (startsWith($row["type"], "DEC")) {
			$lastPart = substr($row["type"], 3);
			if (!ctype_digit($lastPart)) {
				printStructError("Type is DEC but has invalid or missing number.", $row["type"], $structFile, $i);
			}
		} else if ($row["type"] !== "NUM" && $row["type"] !== "AN") {
			printStructError("invalid type", $row["type"], $structFile, $i);
		}

		if ($row["searchable"] !== "true" 
			&& $row["searchable"] !== "false"
			&& $row["searchable"] !== "") {
			printStructError("«searchable» invalid.", $row["searchable"], $structFile, $i);			
		}

		if ($row["groupable"] !== "true" 
			&& $row["groupable"] !== "false"
			&& $row["groupable"] !== "") {
			printStructError("«groupable» invalid.", $row["searchable"], $structFile, $i);			
		}

		// «kommentar» is not evaluated

		// TODO: check that there are no gaps in character-positions covered in a line
	}
}

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

	validateStructData($data, $structFile);

	$cardata = array();
	$cardataRawFP = fopen($dataFile, "r");
	if (!$cardataRawFP) {
		die("Kunne ikkje lese rådatafil: $dataFile");
	}

	if (WRITECSV) {
		// Open CSV-output-file
		$csvFileDir = OUTPUTDIR . $files[0];
		prepareOutputDir($csvFileDir);

		$csvOutputFile = $csvFileDir . "/dataset.csv";
		$csvOutputFP = getFilePointer($csvOutputFile);

		// Write CSV-header-line
		$headerColumns = array();
		foreach($data as $column) {
			$headerColumns[] = $column["kortnamn"];
		}
		$headerLine = implode(";", $headerColumns);
		fwrite($csvOutputFP, $headerLine . "\n");
	}

	if (WRITEMETA) {
		$fileDir = OUTPUTDIR . $files[0];
		prepareOutputDir($fileDir);

		$metaXmlOutputFile = $fileDir . "/meta.xml";
		$metaXmlOutputFP = getFilePointer($metaXmlOutputFile);
		$metaXmlData = createMetaText($files[3]);
		fwrite($metaXmlOutputFP, $metaXmlData);
		fclose($metaXmlOutputFP);

		$fieldsXmlOutputFile = $fileDir . "/fields.xml";
		$fieldsXmlOutputFP = getFilePointer($fieldsXmlOutputFile);
		$fieldsXmlData = createFieldsText($data);
		fwrite($fieldsXmlOutputFP, $fieldsXmlData);
		fclose($fieldsXmlOutputFP);
	}

	print("<span id=\"" . $files[0] 
		. "\"><b>" . $files[0] . "</b></span><br/>\n");

	print("<table><tr><td><u>Data</u><br/>\n<pre>\n");

	$lineNum = 1;
    while (($line = fgets($cardataRawFP)) !== false) {

    	if (!(DEBUG || WRITECSV || DEBUG_LINELENGTH)) {
    		$lineNum++;
    		continue;
    	}

		if ($lineNum % 100000 == 0) {
			// renew PHP-script timeout to keep going
	    	set_time_limit(30);
		}

		$lineLen = strlen($line);

		if (DEBUG_LINELENGTH) {
			// minus 1 linelength
			$arrCheck = array_fill(0, ($lineLen-1), '0');
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

			if ($value === FALSE) {
				$lineErrors = true;
				print("The line was too short!\n");
				print("Length: " . strlen($line) . " Attempted startpos: " . ($attribute["startpos"] - 1) . "\n");
				break;
			}

			// Fyller sjekk-array for linja
			if (DEBUG_LINELENGTH) {
				for ($i = $attribute["startpos"] - 1; $i < ($attribute["startpos"] + $attribute["lengde"]); $i++) {
					$arrCheck[$i] = '1';
				}
			}

			// Kvalitetssjekk
			if (DEBUG && 
				($attribute["type"] == "NUM"
				|| startsWith($attribute["type"], "DEC")
			)) {
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
				$value = intval($value, 10);
			} else if (startsWith($attribute["type"], "DEC")) {
				$numDecimals = intval(
					substr($attribute["type"], 3)
				);

				$heilDel = intval(substr($value, 0, -($numDecimals)));
				$desimalDel = substr($value, -($numDecimals));
				if (preg_match("/^[0]++$/", $desimalDel)) {
					$value = $heilDel;
				} else {
					$value = $heilDel . "," . $desimalDel;
				}
			} else {
				$value_trimmed = trim($value);
				if (strlen($value_trimmed) > 0) {
					$value_trimmed = str_replace('"', '\"', $value_trimmed);
					$value = '"' 
						// utf8_encode for æøå
						. utf8_encode($value_trimmed)
						. '"';
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
			ob_flush();
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

			if ($zeroes > 0) {
				$ones = substr_count($arrCheckImploded, "1");
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

	if (OUTPUT_STRUKTUR) {
		print("<td><u>Struktur</u><br/>\n<pre>\n");
		print_r($data);
		print("</pre></td>");
	}
	print("</tr></table><br/><br/>\n\n");
	flush();
	ob_flush();
} 

$time_end = microtime(true);
$time = number_format($time_end - $time_start, 2);
print("K&oslash;yretid: " . $time . " sekund\n");
?>