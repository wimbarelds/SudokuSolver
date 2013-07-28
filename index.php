<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Sudoku solver</title>
	<style type="text/css">
		td {
			min-width: 15px;
			border: solid #000 1px;
			text-align: center;
			font-family: sans-serif;
			position: relative;
		}
		td.big {
			background-color: #999;
		}
		td.small {
			background-color: #FFF;
			cursor: pointer;
		}
		td.changed {
			background-color: #9AB;
		}
		td.small:hover {
			background-color: #BCD;
		}
		.hover {
			display: none;
			position: absolute;
			top: 10px;
			left: 10px;
			width: fit-content;
			border: solid #666 1px;
			background-color: #333;
			color: #DEF;
			white-space: nowrap;
			z-index: 100;
			padding: 1px 3px;
			font-size: 75%;
		}
		td:hover > .hover {
			display: block;
		}
	</style>
	<script type="text/javascript">
		function setData() {
			value = prompt("Value to enter here","");
			if(!isNaN(value)) {
				if(value > 0 && value < 10) {
					this.innerHTML = '<input type="hidden" name="'+this.id+'" value="'+value+'">' + value;
					document.getElementById('sudokuform').submit();
				}
				else {
					alert("Not a number between 1 and 9");
				}
			}
			else {
				alert("Not a number");
			}
		}
		
		function initialize() {
			for(x = 1; x < 10; x++) {
				for(y = 1; y < 10; y++) {
					//alert('r'+x+'c'+y);
					document.getElementById('r'+x+'c'+y).addEventListener('click', setData);
				}
			}
		}
	</script>
</head>
<body onload="initialize();">
<form method="post" action="index.php" id="sudokuform">
<table border="0">
<?php
	
	$fields = array();
	foreach($_POST as $k => $v){
		if(preg_match('/^r([1-9])c([1-9])$/', $k, $matches)) {
			$fields[$matches[2]][$matches[1]] = $v;
		}
	}
	
	function getValue($x, $y) {
		$value = $GLOBALS["fields"][$x][$y];
		if(is_numeric($value)) return $value;
		return false;
	}
	
	// This function determines what values can be placed in a specific field
	function getValues($x, $y, $recursive = true) {
		$values = $GLOBALS["fields"][$x][$y];
		
		if(is_numeric($values)) { return $values; }
		if(count($values) === 1) { return reset($values); }
		if(!$recursive) { return $values; }
		
		// Eliminate values from this block
		$bx = ((int) (($x - 1) / 3)) * 3;
		$by = ((int) (($y - 1) / 3)) * 3;
		
		for($i = 1; $i <= 3; $i++) {
			for($j = 1; $j <= 3; $j++) {
				$cx = $bx + $j;
				$cy = $by + $i;
				
				$v = getValue($cx, $cy);
				if($v && in_array($v, $values)) {
					$values = array_diff($values, array($v));
				}
			}
		}
		
		for($i = 1; $i <= 9; $i++) {
			// Eliminate values from this row
			$v = getValue($i, $y);
			if($v && in_array($v, $values)) {
				$values = array_diff($values, array($v));
			}
			// Eliminate values from this col
			$v = getValue($x, $i);
			if($v && in_array($v, $values)) {
				$values = array_diff($values, array($v));
			}
		}
		
		if(count($values) === 1) {
			return reset($values);
		}
		
		// Check for each possible values if it could possibly go anywhere else
		foreach($values as $v) {
			// Check x axis for alternative places for this value
			$found = false;
			for($i = 1; $i <= 9; $i++) {
				if($i == $x) continue;
				$placeValues = getValues($i, $y, false);
				if(!is_array($placeValues)) continue;		// If this is isn't an array, ignore it
				
				if(in_array($v, $placeValues)) {
					$found = true;
					break;
				}
			}
			// Value was not found as an option in any alternative location
			if(!$found){
				echo "Value ".$v." was placed at (".--$i.",".$y.") because it could not be placed anywhere else on it's x axis<br />\n";
				return $v;
			}

			// Check y axis for alternative places for this value
			$found = false;
			for($i = 1; $i <= 9; $i++) {
				if($i == $y) continue;
				$placeValues = getValues($x, $i, false);
				if(!is_array($placeValues)) continue;		// If this is isn't an array, ignore it

				if(in_array($v, $placeValues)) {
					$found = true;
					break;
				}
			}
			// Value was not found as an option in any alternative location
			if(!$found){
				echo "Value ".$v." was placed at (".$x.",".--$i.") because it could not be placed anywhere else on it's y axis<br />\n";
				return $v;
			}

			// Check block for alternative places for this value
			$found = false;
			for($i = 0; $i < 9; $i++) {
				$a = $bx + ($i % 3) + 1;
				$b = $by + ((int) ($i / 3)) + 1;
				
				if($a == $x && $b == $y) continue;			// Don't eliminate the numbers from our own current location
				$placeValues = getValues($a, $b, false);
				
				if(!is_array($placeValues)) continue;		// If this is isn't an array, ignore it

				if(in_array($v, $placeValues)) {
					$found = true;
					break;
				}
			}
			// Value was not found as an option in any alternative location
			if(!$found){
				echo "Value ".$v." was placed at (".$a.",".$b.") because it could not be placed anywhere else in it's block<br />\n";
				return $v;
			}
		}
		
		return $values;
	}
	
	ob_start();
	
	if(isset($_POST["solve"])) {
		echo "trying to solve...<br \>\n";
		
		$found = 0;
		for($x = 1; $x <= 9; $x++) {
			for($y = 1; $y <= 9; $y++) {
				// Default value
				if(!isset($fields[$x][$y])) {
					$fields[$x][$y] = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
				}
			}
		}
		
		$changed = array();
		
		$consecutive = 0;	// number of times in a row no new numbers were found
		while(true) {
			$changes = 0;	// number of numbers found in this check-through
			
			for($x = 1; $x <= 9; $x++) {
				for($y = 1; $y <= 9; $y++) {
					// Not yet found
					if(is_array($fields[$x][$y])) {
						$return = getValues($x, $y);
						
						if(!empty($return)) {
							$fields[$x][$y] = $return;
						}
						
						if(is_numeric($return)) {
							$found++;
							$changes++;
							$changed[] = "r".$y."c".$x;
							if($_POST["solve"] == "1 step"){ break 3; }
						}
					}
				}
			}
			
			if($changes === 0) {
				$consecutive++;
			}
			if($consecutive > 2) break;
		}
		
		if($found < 81 && $_POST["solve"] != "1 step") {
			echo "Filled in ".$found." new numbers, but can not proceed any further.";
		}
		elseif($found == 0 && $_POST["solve"] === "1 step") {
			echo "Unable to find any further steps to proceed.";
		}
	}
	
	$message = ob_get_clean();
	
	for($y1 = 1; $y1 <= 3; $y1++) {
		echo "	<tr>\n";
		for($x1 = 1; $x1 <= 3; $x1++) {
			echo "		<td class=\"big\">\n";
			echo "			<table border=\"0\">\n";
			
			for($y2 = 1; $y2 <= 3; $y2++) {
				echo "				<tr>\n";
				for($x2 = 1; $x2 <= 3; $x2++) {
					$x = ($x2 +(($x1 - 1) * 3));
					$y = ($y2 +(($y1 - 1) * 3));
					$id = "r".$y."c".$x;
					if(in_array($id, $changed)) {
						echo "					<td class=\"small changed\" id=\"".$id."\">";
					}
					else {
						echo "					<td class=\"small\" id=\"".$id."\">";
					}
					if(empty($fields[$x][$y])) {
						echo "&nbsp;";
					}
					elseif(is_array($fields[$x][$y])) {
						echo "&nbsp;<span class=\"hover\">".implode(", ", $fields[$x][$y])."</span>";
					}
					else {
						echo "<input type=\"hidden\" name=\"r{$y}c{$x}\" value=\"{$fields[$x][$y]}\" \>{$fields[$x][$y]}";
					}
					echo "</td>";
				}
				echo "				</tr>\n";
			}
			echo "			</table>\n";
			echo "		</td>\n";
		}
		echo "	</tr>\n";
	}
	

?>
</table>
<input type="submit" name="solve" value="solve" />
<input type="submit" name="solve" value="1 step" />
</form>
<?php
	echo $message;
?>
</body>
</html>