<?php
//to resolve issue when entering ampersand

set_time_limit(0);
include("address_parser_class_lib.php");
include("functions.php");
    
    $counter = 0;
    
    $search = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            if ($search->connect_errno)
            {
                $search->close();
            }
                $query ="SELECT * FROM address_list order by address;";//database where to get the address, make sure the 'address' field exists.
                
                $values = $search->query($query);
                        
		echo "<table border='all' style='border-collapse:collapse;'>";
		echo "<tr style='font-weight:bold;'><td>No.</td><td><b>ID</b></td><td><b>COMPANY NAME</b></td><td><b>ADDRESS</b></td><td><b>Street 1 House No.</b></td><td><b>Street 1</b></td><td><b>Street 2 House No.</b></td><td><b>Street 2</b></td><td><b></b></td><td><b></b></td><td><b>Building Found Result</b></td><td><b>Building Part Found Result</b></td><td><b>Barangay</b></td><td><b>City</b></td><td><b>Other Area (Complex, Center, Hills, Villa)</b></td><td><b>Corner</b></td></tr>";            
		
		
		while($row = $values->fetch_assoc())
		{
		    $counter++;		    
		    $keywordForSearch = $row['address'];			    
		    $address = new address($keywordForSearch);
		    
		    $street = array();
		    $street_part = array();		    
		    
		    $final_street_part = $address->get_street_part();
		    
			echo "<tr valign='top'><td><b>$counter.</b></td><td>" . $row['id'] . "</td><td>" . $row['company_name'] . "</td><td>".$address->get_address()."</td>";
			
			foreach($final_street_part as $final_street_part_key => &$streetpart_array_lvl1){
			    $find_SP = FALSE;
			    foreach($final_street_part as $final_street_part_comp_key => &$streetpart_array_lvl1_comp){
				
				if(($streetpart_array_lvl1['endPosition'] + 1) == $streetpart_array_lvl1_comp['startPosition']){
				    unset($final_street_part[$final_street_part_comp_key]);
				    $find_SP = TRUE;
				}
			    }
			    
			    if($find_SP){
				$streetpart_array_lvl1 = array('street_part' => text_builder($streetpart_array_lvl1_comp['endPosition'], $streetpart_array_lvl1['startPosition'], $address->get_address_array()), 'startPosition' => $streetpart_array_lvl1['startPosition'], 'endPosition' => $streetpart_array_lvl1_comp['endPosition']);
			    }
			    unset($streetpart_array_lvl1_comp);
			}
			unset($streetpart_array_lvl1);			
			
			//Separate parsed street
			$street_array = $address->get_street();
			$street_part_array = $final_street_part;			
			foreach($street_array as $street_array_check){
			    $street[] = $street_array_check['street'];
			    
			    $street_matched = FALSE;
			    $street_part_matched = '';
			    foreach($street_part_array as $street_part_array_check){				
				if(($street_part_array_check['endPosition'] + 1) == $street_array_check['startPosition']){
				    $street_matched = TRUE;
				    $street_part_matched = $street_part_array_check['street_part'];
				}
			    }
			    if($street_matched){
				$street_part[] = $street_part_matched;
			    }
			    else{
				$street_part[] = '';
			    }
			    unset($street_part_array_check);
			}
			unset($street_array_check);
			
			echo "<td>";
			if(isset($street_part[0])){
			    echo $street_part[0];
			}
			echo "</td><td>";
			if(isset($street[0])){
			    echo $street[0];
			}
			echo "</td><td>";
			if(isset($street_part[1])){
			    echo $street_part[1];
			}
			echo "</td><td>";
			if(isset($street[1])){
			    echo $street[1];
			}
			echo "</td><td>";
			if(isset($street_part[2])){
			    echo $street_part[2];
			}
			echo "</td><td>";
			if(isset($street[2])){
			    echo $street[2];
			}			
			echo "</td><td>";
			$building = '';
			foreach($address->get_building() as $building_check){			    
			    $building = $building . ', ' . $building_check['building'];
			}
			echo trim($building, ', ');
			echo "</td><td>";
			$building_part = '';
			foreach($address->get_building_part() as $building_part_check){			    
			    $building_part = $building_part . ', ' . $building_part_check['building_part'];
			}
			echo trim($building_part, ', ');
			echo "</td><td>";
			$area = '';
			foreach($address->get_area() as $area_check){
			    if(strtolower($area_check['category']) == strtolower('Barangay')){
				$area = $area . ', ' . $area_check['area'];
			    }
			}
			echo trim($area, ', ');			
			echo "</td><td>";
			$area = '';
			foreach($address->get_area() as $area_check){
			    if(strtolower($area_check['category']) == strtolower('City')){
				$area = $area . ', ' . $area_check['area'];
			    }
			}
			echo trim($area, ', ');
			echo "</td><td>";
			$area = '';
			foreach($address->get_area() as $area_check){
			    if(strtolower($area_check['category']) != strtolower('City') && strtolower($area_check['category']) != strtolower('Barangay')){
				$area = $area . ', ' . $area_check['area'];
			    }
			}
			echo trim($area, ', ');
			echo "</td><td>";
			$corner = '';
			foreach($address->get_corner_reference() as $corner_check){			    
			    $corner = $corner . ', ' . $corner_check['corner_reference'];			
			}
			echo trim($corner, ', ');
			echo "</td></tr>";
		    
		$address = '';
		}
		echo "</table>";
?>
