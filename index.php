<?php
// header('Content-Type: text/html; charset=utf-8');

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

?>

<style>
td {
	width: 50%;
	vertical-align: top;
}
</style>

<?php
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
	$cardataRaw = file($dataFile);

	print("<span id=\"" . $files[0] 
		. "\"><b>" . $files[0] . "</b></span><br/>\n");



	foreach ($cardataRaw as $line) {
		$car = array();
		foreach ($data as $attribute) {
			$value = substr(
				$line, 
				($attribute["startpos"]-1), 
				$attribute["lengde"]
			);
			if ($attribute["type"] == "NUM") {
				$value = intval($value, 10);
			}
			$car[$attribute["kortnamn"]] = $value;
		}
		$cardata[] = $car;
	}

	print("<table><tr><td><u>Data</u><br/>\n<pre>\n");
	print_r($cardata);
	print("</pre></td>\n");

	print("<td><u>Struktur</u><br/>\n<pre>\n");
	print_r($data);
	print("</pre></td></tr></table>\n\n");

?>
<pre>
<?php
print_r($cardata);
?>
</pre>
<?php
} ?>