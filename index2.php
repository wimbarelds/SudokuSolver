<?php

	include("static/head.html");
	
	// Fill in the fields
	for($x = 1; $x <= 9; $x++) {
		for($y = 1; $y <= 9; $y++) {
			// See if this value was posted
			if(!empty($_POST["r".$y."c".$x])) {
				$fields[$x][$y] = $_POST["r".$y."c".$x];
			}
			// If not posted, default it
			else {
				$fields[$x][$y] = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
			}
		}
	}
	
	/**
	 * @param int $x
	 * @param int $y
	 * @return array($bx, $by) Returns the starting x and y from current block
	 */
	function get_block_xy($x, $y) {
		$bx = ((int) (($x - 1) / 3)) * 3;
		$by = ((int) (($y - 1) / 3)) * 3;
		
		return array($bx, $by);
	}
	
	/**
	 * @param int $x
	 * @param int $y
	 * Returns false if value not yet found
	 */
	function get_value($x, $y) {
		$v = get_values($x, $y);
		if(is_array($v)) {
			return false;
		}
		else {
			return $v;
		}
	}
	
	/**
	 * @param int $x
	 * @param int $y
	 * Returns value of range of possible values for a location
	 */
	function get_values($x, $y) {
		return $GLOBALS["fields"][$x][$y];
	}
	
	/**
	 * Eliminates values from location that are already found either on its x axis, y axis, or in its block
	 * @param type $x
	 * @param type $y
	 * @return Value or array with possible values
	 */
	function basic_elimination($x, $y) {
		// Retrieve value
		$values = get_values($x, $y);
		
		// Check if value is already found
		if(!is_array($values)) {
			return $values;
		}
		
		// Check horizontal and vertical axis'
		for($i = 1; $i <= 9; $i++) {
			// Horitontal
			if($i != $x) { // No need to check your own location
				$v = get_value($i, $y);
				if($v && in_array($v, $values)) {
					$values = array_diff($values, array($v));
				}
			}
			// Vertical
			if($i != $y) { // No need to check your own location
				$v = get_value($x, $i);
				if($v && in_array($v, $values)) {
					$values = array_diff($values, array($v));
				}
			}
		}
		
		// Check current block
		list($bx, $by) = get_block_xy($x, $y);
		for($a = 1; $a <= 3; $a++) {
			for($b = 1; $b <= 3; $b++) {
				$v = get_value($bx + $a, $by + $b);
				if($v && in_array($v, $values)) {
					$values = array_diff($values, array($v));
				}
			}
		}
		
		if(count($values) === 1) {
			return reset($values);
		}
		return $values;
	}
	
	/**
	 * For each remaining possible value, this function checks the x axis, y axis, and block- to see if that value can still be placed anywhere else
	 * @param type $x
	 * @param type $y 
	 * @return Found value or starting value
	 */
	function basic_only_option($x, $y) {
		$values = get_values($x, $y);
		if(is_numeric($values)) return $values;
		if(count($values) === 1) return reset($values);
		
		foreach($values as $v) {
			// Check Horizontal axis for alternative locations
			$found = false;
			for($i = 1; $i <= 9; $i++) {
				if($i == $x) continue;
				
				$cv = get_values($i, $y);
				if(is_array($cv) && in_array($v, $cv) || $cv == $v) {
					$found = true;
					break;
				}
			}
			if(!$found) {
				return $v;
			}
			
			// Check Vertical axis for alternative locations
			$found = false;
			for($i = 1; $i <= 9; $i++) {
				if($i == $y) continue;
				
				$cv = get_values($x, $i);
				if(is_array($cv) && in_array($v, $cv) || $cv == $v) {
					$found = true;
					break;
				}
			}
			if(!$found) {
				return $v;
			}
			
			// Check block for alternative locations
			$found = false;
			list($bx, $by) = get_block_xy($x, $y);
			for($a = 1; $a <= 3; $a++) {
				for($b = 1; $b <= 3; $b++) {
					if($bx + $a == $x && $by + $b == $y) continue;
					
					$cv = get_values($bx + $a, $by + $b);
					if(is_array($cv) && in_array($v, $cv) || $cv == $v) {
						$found = true;
						break;
					}
				}
			}
			if(!$found) {
				return $v;
			}
		}
		return $values;
	}
	
	$changed = array();
	if(!empty($_POST["solve"])) {
		ob_start();
		
		echo "Attempting to solve...<br />\n";
		
		// Go through all locations
		$found = 0;
		$basic_elimination = 0;
		$basic_only_option = 0;
		$consecutive = 0;
		
		while(true) {
			$changes = 0;
			
			// Apply elimination
			for($x = 1; $x <= 9; $x++) {
				for($y = 1; $y <= 9; $y++) {
					$cv = $fields[$x][$y];
					$nv = basic_elimination($x, $y);
					if($cv != $nv) {
						$fields[$x][$y] = $nv;
						$changes++;
						if(is_numeric($nv)) {
							$found++;
							$basic_elimination++;
							$changed[] = "r{$y}c{$x}";
							if($_POST["solve"] == "1 step"){ break 3; }
						}
					}
				}
			}
			
			// Apply basic only option
			for($x = 1; $x <= 9; $x++) {
				for($y = 1; $y <= 9; $y++) {
					$cv = $fields[$x][$y];
					$nv = basic_only_option($x, $y);
					if($cv != $nv) {
						$fields[$x][$y] = $nv;
						$changes++;
						if(is_numeric($nv)) {
							$found++;
							$basic_only_option++;
							$changed[] = "r{$y}c{$x}";
							if($_POST["solve"] == "1 step"){ break 3; }
						}
					}
				}
			}
			
			if($changes === 0) {
				$consecutive++;
				if($consecutive > 2)
				break;
			}
		}
		echo "Found ".$found." new values, ";
		echo $basic_elimination." through basic elimination and ";
		echo $basic_only_option." through basic only option.<br />\n";
	}
	
	// Create output table
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
	
	include("static/foot.html");

?>
