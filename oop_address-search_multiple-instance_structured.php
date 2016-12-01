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
                $query ="SELECT * FROM address_list where address like '%San Juan%' order by address limit 200;";
                
                $values = $search->query($query);
                        
		echo "<table border='all' style='border-collapse:collapse;'>";
		//echo "<tr style='font-weight:bold;'><td>No.</td><td><b>ADDRESS</b></td><td>Text Array</td><td><b>Street Found</b></td><td><b>Street Found Result</b></td><td><b>House No. Found</b></td><td><b>House No. Found Result</b></td><td><b>Building Found</b></td><td><b>Building Found Result</b></td><td><b>Building Part Found</b></td><td><b>Building Part Found Result</b></td><td><b>Area Found</b></td><td><b>Area Found Result</b></td><td><b>Verified street</b></td><td><b>Un Verified street</b></td></tr>";            
		echo "<tr style='font-weight:bold;'><td>No.</td><td><b>ADDRESS</b></td><td>Text Array</td><td><b>Street Found</b></td><td><b>Street Found Result</b></td><td><b>Street Part Found</b></td><td><b>Street Part Found Result</b></td><td><b>Address Map Before</b></td><td><b>Verified street</b></td><td><b>Address Map After</b></td><td></td><td></td></tr>";            
		
		while($row = $values->fetch_assoc())
		{
		    $counter++;
		    //echo $counter."<br/>";
		    $keywordForSearch = $row['address'];			    
		    $address = new address($keywordForSearch);
		    
			echo "<tr valign='top'><td><b>$counter.</b></td><td>".$address->get_address()."</td><td>";
			echo '<pre>';
			print_r($address->get_address_array());
			echo '</pre>';
			echo "</td><td>";
			if(count($address->get_street()) != 0){
			    echo 'Street found!<br/>';
			}
			else{
			    echo 'NO Street found!<br/>';
			}
			echo "</td><td>";
			echo '<pre>';
			print_r($address->get_street());
			echo '</pre>';
			echo "</td><td>";
			if(count($address->get_street_part()) != 0){
			    echo 'House No. found!<br/>';
			}
			else{
			    echo 'NO House No. found!<br/>';
			}
			echo "</td><td>";
			echo '<pre>';
			print_r($address->get_street_part());
			echo '</pre>';
			echo "</td><td>";
			//if(count($address->get_building()) != 0){
			//    echo 'Building found!<br/>';
			//}
			//else{
			//    echo 'NO Building found!<br/>';
			//}
			//echo "</td><td>";
			//echo '<pre>';
			//print_r($address->get_building());
			//echo '</pre>';
			//echo "</td><td>";
			//if(count($address->get_building_part()) != 0){
			//    echo 'Building Part found!<br/>';
			//}
			//else{
			//    echo 'NO Building Part found!<br/>';
			//}
			//echo "</td><td>";
			//echo '<pre>';
			//print_r($address->get_building_part());
			//echo '</pre>';
			//echo "</td><td>";
			if(count($address->get_area()) != 0){
			    echo 'Area found!<br/>';
			}
			else{
			    echo 'NO Area found!<br/>';
			}
			echo "</td><td>";			
			echo '<pre>';
			print_r($address->get_area());
			echo '</pre>';			
			echo "</td><td>";
			echo '<pre>';
			print_r($address->get_property_map());
			echo '</pre>';
			echo "</td><td>";
			$verified_street_result = array();
			foreach($address->get_street() as $street_for_verify){
			$verified_street_result[] = verify_street($street_for_verify,$address->get_address_array(),171,TRUE);			
			}
			
			if( ! count($verified_street_result) == 0){
			echo '<pre>';
			print_r($verified_street_result);			
			echo '</pre>';
			
			$address->update_matches($verified_street_result);
			$address->prepend_exceptions($verified_street_result);
			}
			echo "</td><td>";
			echo '<pre>';
			print_r($address->get_property_map());
			echo '</pre>';
			
			/////////
			echo "</td><td>";
			$verified_building_result = array();
			foreach($address->get_building() as $building_for_verify){
			$verified_building_result[] = verify_building($building_for_verify,$address->get_address_array());			
			}
			
			if( ! count($verified_building_result) == 0){
			echo '<pre>';
			print_r($verified_building_result);			
			echo '</pre>';
			
			$address->update_matches($verified_building_result);
			$address->prepend_exceptions($verified_building_result);
			}
			echo "</td><td>";
			echo '<pre>';
			print_r($address->get_property_map());
			echo '</pre>';
			/////////
			
			echo "</td></tr>";
		    
		$address = '';
		}
		echo "</table>";
?>
