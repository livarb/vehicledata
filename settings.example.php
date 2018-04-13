<?php
define("TESTMODE", true);

if (TESTMODE) {
	define("WRITECSV", false);
	define("WRITEMETA", false);
	define("DEBUG", true);
	define("DEBUG_LINELENGTH", false);
	define("OUTPUT", true);
	define("OUTPUT_STRUKTUR", true);	
	define("OUTPUTDIR", "outputTest/");

	$input = array(
		array(
			"tekn", // katalognavn
			"struktur_tekn.csv", // struktur-fil
			"tekntest.txt", // data-fil
			"Kjøretøy - enkeltgodkjente" // datasett-tittel (meta.xml)
		), array(
			"typg",
			"struktur_typg.csv",
			"typgtest.txt",
			"Typegodkjenninger for kjøretøy"
		), array(
			"utek",
			"struktur_utek.csv",
			"utektest.txt",
			"Kjøretøy - typegodkjente"
		)
	);

} else {
	define("WRITECSV", true);
	define("WRITEMETA", true);
	define("DEBUG", true);
	define("DEBUG_LINELENGTH", false); // NB! doblar køyretid
	define("OUTPUT", false);
	define("OUTPUT_STRUKTUR", false);
	define("OUTPUTDIR", "output/");

	$input = array(
		array(
			"tekn",
			"struktur_tekn.csv",
			"completeRaw/tekninfo",
			"Kjøretøy - enkeltgodkjente"
		)
		, array(
			"typg",
			"struktur_typg.csv",
			"completeRaw/typginfo",
			"Typegodkjenninger for kjøretøy"
		), array(
			"utek",
			"struktur_utek.csv",
			"completeRaw/utekinfo",
			"Kjøretøy - typegodkjente"
		)
	);
}
?>