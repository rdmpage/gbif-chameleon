<?php

//--------------------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}

//--------------------------------------------------------------------------------------------------
function cross_product($p1, $p2, $p3)
{
	return ($p2[0] - $p1[0]) * ($p3[1] - $p1[1]) - ($p3[0] - $p1[0]) * ($p2[1] - $p1[1]);
}

//--------------------------------------------------------------------------------------------------
// From http://en.wikipedia.org/wiki/Graham_scan
function convex_hull($points)
{
	// Find pivot point (has lowest y-value)
	$minX = 180.0;
	$minY = 90;
	$pivot = 0;

	$n = count($points);

	for ($i=0;  $i < $n; $i++)
	{	
		if ($points[$i][1] <= $minY)
		{
			if ($points[$i][1] < $minY)
			{
				$pivot = $i;
				$minY = $points[$i][1];
				$minX = $points[$i][0];
			}
			else
			{
				if ($points[$i][0] < $minX)
				{
					$pivot = $i;
					$minX = $points[$i][0];
				}
			}
		}
	}

	$angle = array();
	$distance = array();

	// Compute tangents
	for ($i=0;  $i < $n; $i++)
	{	
		if ($i != $pivot)
		{
			$o = $points[$i][1] - $points[$pivot][1];
			$a = $points[$i][0] - $points[$pivot][0];		
			$h = sqrt($a*$a + $o*$o); 
		
			array_push($angle, rad2deg(atan2($o, $a)));
			array_push($distance, $h);
		}
		else
		{
			array_push($angle, 0.0);
			array_push($distance, 0.0);
		}
	}

	// Sort array of points by angle, then distance
	array_multisort($angle, SORT_ASC, $distance, SORT_DESC, $points);

	// Fnd hull
	$stack = array();
	array_push($stack, $points[0]);
	array_push($stack, $points[1]);

	for ($i = 2; $i < $n; $i++)
	{
		$stack_count = count($stack);
		$cp = cross_product($stack[$stack_count-2], $stack[$stack_count-1], $points[$i]);
		while ($cp <= 0 && $stack_count >= 2)
		{
			array_pop($stack);
			$stack_count = count($stack);
			$cp = cross_product($stack[$stack_count-2], $stack[$stack_count-1], $points[$i]);
		}
		array_push($stack, $points[$i]);
	}

	return $stack;
}


//--------------------------------------------------------------------------------------------------
$filename = dirname(dirname(__FILE__)) . '/data/endemic_filtered.tsv';

$file = @fopen($filename, "r") or die("couldn't open $filename");

$fieldsEnclosedBy = "";
$fieldsTerminatedBy = "\t";
$ignoreHeaderLines = 1;


$taxa = array();

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
			$accept = true;
			
			$pt = array((Double)$data->Longitude, (Double)$data->Latitude);
			
			// fudge for negative long
			if ($data->Longitude < 0)
			{
				$pt[0] = -$data->Longitude;
				$accept = false;
			}
			
			if ($data->Latitude > 5)
			{
				$pt[1] = -$data->Latitude;
				$accept = true;
			}
			
			
			if ($accept)
			{
				
				$name = $data->updated_name;
				
				if (!isset($taxa[$name]))
				{
					$taxa[$name] = array();
				}
				if (!in_array($pt, $taxa[$name]))
				{
					$taxa[$name][] = $pt;
				}
			}
		}
		//if ($row_count == 2) exit();
				
	
	}
	
	$row_count++;
	
}

/*
foreach ($taxa as $k => $v)
{
	echo "k=$k\n";
	$taxa[$k] = array_unique(array_values($v));
}
*/
print_r($taxa);

$geojson = new stdclass;
$geojson->type = 'FeatureCollection';
$geojson->features = array();

foreach ($taxa as $k => $v)
{
	$feature = new stdclass;
	$feature->type = 'Feature';
	
	$feature->properties = new stdclass;
	$feature->properties->updatedName = $k;

	$num_points = count($v);
	
	switch ($num_points)
	{
		case 1:
			$feature->geometry = new stdclass;
			$feature->geometry->type = 'Point';
			$feature->geometry->coordinates = $v[0];
			
			$geojson->features[] = $feature;
			break;
			
		case 2:
			break;
		
		default:
			$s = convex_hull($v);
			print_r($s);
			
			$s[] = $s[0];
			
			$feature->geometry = new stdclass;
			$feature->geometry->type = 'Polygon';
			$feature->geometry->coordinates = array();
			$feature->geometry->coordinates[] = $s;
			
			$geojson->features[] = $feature;
			break;
	
	}
}

$json_filename = str_replace('.tsv', '.polygon.geojson', $filename);

file_put_contents($json_filename, json_encode($geojson, JSON_PRETTY_PRINT));


?>

