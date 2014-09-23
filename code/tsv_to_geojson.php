<?php

//--------------------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}


//--------------------------------------------------------------------------------------------------
$filename = dirname(dirname(__FILE__)) . '/data/endemic_filtered.tsv';

$file = @fopen($filename, "r") or die("couldn't open $filename");

$fieldsEnclosedBy = "";
$fieldsTerminatedBy = "\t";
$ignoreHeaderLines = 1;

$geojson = new stdclass;
$geojson->type = 'FeatureCollection';
$geojson->features = array();


$row_count = 0;

$headers = array();
$header_index = array();

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$row = fgetcsv(
		$file_handle, 
		0, 
		translate_quoted($fieldsTerminatedBy),
		(translate_quoted($fieldsEnclosedBy) != '' ? translate_quoted($fieldsEnclosedBy) : '"') 
		);

	$go = is_array($row);
	
	if ($go && ($row_count == 0) && ($ignoreHeaderLines == 1))
	{
		$go = false;
		
		// headers
		$headers = $row;
		
		$count = 0;
		foreach ($row as $header)
		{
			$header_index[$header] = $count++;
		}
		
		
	}
	
	if ($go)
	{
		$data = new stdclass;
		
		for ($i = 0; $i < count($row); $i++)
		{
			if ($row[$i] != '')
			{
				$key = str_replace(' ', '_', $headers[$i]);
				$data->{$key} = $row[$i];
			}
		}
		
		//print_r($data);
		
		if (isset($data->Latitude) && isset($data->Longitude))
		{
			$feature = new stdclass;
			$feature->type = 'Feature';
		
			$feature->properties = new stdclass;
			
			// Specimen
			$feature->properties->publisher = $data->Data_publisher;
			$feature->properties->catalogueNumber = $data->Catalogue_No;
			$feature->properties->updatedName = $data->updated_name;
			$feature->properties->scientificName = $data->Scientific_name;

			// Locality
			$feature->properties->country = $data->Country;
			
			if (isset($feature->properties->locality))
			{
				$feature->properties->locality = $data->Locality;
			}
			$feature->properties->latitude = $data->Latitude;
			$feature->properties->longitude = $data->Longitude;
			
			// GBIF			
			$feature->properties->gbif = str_replace('http://data.gbif.org/occurrences/', 'http://www.gbif.org/occurrence/', $data->GBIF_portal_url);
			
			$feature->geometry = new stdclass;
			$feature->geometry->type = 'Point';
			$feature->geometry->coordinates = array();
			$feature->geometry->coordinates[] = (Double)$data->Longitude;
			$feature->geometry->coordinates[] = (Double)$data->Latitude;
			
			//print_r($feature);
			
			$geojson->features[] = $feature;
		
		}
		
		//if ($row_count == 2) exit();
				
	
	}
	
	$row_count++;
	
}

$json_filename = str_replace('.tsv', '.geojson', $filename);

file_put_contents($json_filename, json_encode($geojson, JSON_PRETTY_PRINT));



?>

