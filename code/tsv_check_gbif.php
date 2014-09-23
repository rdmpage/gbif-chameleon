<?php

require_once(dirname(__FILE__) . '/lib.php');

// check whether record is still in GBIF

//--------------------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}


//--------------------------------------------------------------------------------------------------
$filename = dirname(dirname(__FILE__)) . '/data/endemic_filtered.tsv';
$filename = dirname(dirname(__FILE__)) . '/data/no_coordinates.tsv';
$filename = dirname(dirname(__FILE__)) . '/data/search_results.tsv';

$file = @fopen($filename, "r") or die("couldn't open $filename");

$fieldsEnclosedBy = "";
$fieldsTerminatedBy = "\t";
$ignoreHeaderLines = 1;

$hit = array();
$miss = array();

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
		
		$id = str_replace('http://data.gbif.org/occurrences/', '', $data->GBIF_portal_url);
		$url = 'http://api.gbif.org/v1/occurrence/' . $id;
		
		$json = get($url);
		
		if ($json != '')
		{
			$hit[] = $id;
		}
		else
		{
			$miss[] = $id;
		}
				
	
	}
	
	$row_count++;
	
}

echo $filename . "\n";
echo "Number of records still live: " . count($hit) . "\n";
echo "      Number of records gone: " . count($miss) . "\n";



?>

