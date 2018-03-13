# vehicledata
3 datasets - together they represent technical data on all vehicles in Norway. Registry is managed by the Norwegian Road Authority (Statens vegvesen).

Given a specific tag-id (skiltnummer), a vehicle is either type-approved. You find the entry for the vehicle in one table with a reference to the type-approval in another table. Given a vehicle which is not type-approved, the entire record for that vehicle is in the third table.

## Files from the Road Authority
- beskrivelse tekniskefiler.txt : description of the data formats
- tekntest.txt, typgtest.txt, utektest.txt - test data (50 records) from the registry, in mainframe-computer-format.

## Metadata-files
struktur_tekn.csv, struktur_typg.csv, struktur_utek.csv
One file for each dataset, based on "besrkvielse tekniskefiler.txt"

## index.php
See output from script here: https://livarbergheim.no/vi/

The script reads the data-files, using the struktur...-files for a definition, and transforms the data to CSV-files compatible with the data hotel (hotell.difi.no).

## Datahotel-version of datasets
The script (index.php) write CSV-files and metadata for the data hotel (meta.xml, fields.xml).
Output from the sample data can be found in the folder "outputTest".
