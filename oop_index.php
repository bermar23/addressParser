<?php
set_time_limit(0);
include("address_parser_class_lib.php");
include("functions.php");

$address = new address($_GET['keyword']);

echo '<b>Address:</b><br/>';
echo $address->get_address().'<br/>';

echo '--------------------------------------<br/><b>Address Array:</b><br/>';
echo '<pre>';
print_r($address->get_address_array());
echo '</pre>';

echo '--------------------------------------<br/><b>Area:</b><br/>';
echo '<pre>';
print_r($address->get_area());
echo '</pre>';

echo '--------------------------------------<br/><b>Street Part:</b><br/>';
echo '<pre>';
print_r($address->get_street_part());
echo '</pre>';

echo '--------------------------------------<br/><b>Street:</b><br/>';
echo '<pre>';
print_r($address->get_street());
echo '</pre>';

echo '--------------------------------------<br/><b>Building:</b><br/>';
echo '<pre>';
print_r($address->get_building());
echo '</pre>';

echo '--------------------------------------<br/><b>Building Part:</b><br/>';
echo '<pre>';
print_r($address->get_building_part());
echo '</pre>';

echo '--------------------------------------<br/><b>GET ADDRESS MAP</b><br/>';
echo '<pre>';
print_r($address->get_property_map());
echo '</pre>';

echo '--------------------------------------<br/><b>STREET VERIFICATION RESULT:</b><br/>';

foreach($address->get_street() as $street_for_verify){
$verified_street_result[] = verify_street($street_for_verify,$address->get_address_array(),171,FALSE);
}
if(isset($verified_street_result)){
    echo '<pre>';
    print_r($verified_street_result);    
    echo '</pre>';
}

echo '--------------------------------------<br/><b>TESTING DROP EXCESS STREET</b><br/>';

if(isset($verified_street_result)){
    $address->update_matches($verified_street_result);
    echo '<pre>';
    print_r($address->get_street());
    echo '</pre>';
}

echo '--------------------------------------<br/><b>TESTING APPEND/PREPEND EXCESS STREET</b><br/>';

if(isset($verified_street_result)){
    $address->prepend_exceptions($verified_street_result);
    echo '<pre>';
    print_r($address->get_street_part());
    echo '</pre>';
    echo '<pre>';
    print_r($address->get_building_part());
    echo '</pre>';
}

echo '--------------------------------------<br/><b>BUILDING VERIFICATION RESULT:</b><br/>';

foreach($address->get_building() as $building_for_verify){
$verified_building_result[] = verify_building($building_for_verify,$address->get_address_array());
}
if(isset($verified_building_result)){
    echo '<pre>';
    print_r($verified_building_result);    
    echo '</pre>';
}

echo '--------------------------------------<br/><b>TESTING DROP EXCESS BUILDING</b><br/>';

if(isset($verified_building_result)){
    $address->update_matches($verified_building_result);
    echo '<pre>';
    print_r($address->get_building());
    echo '</pre>';
}

echo '--------------------------------------<br/><b>TESTING APPEND/PREPEND EXCESS BUILDING</b><br/>';

if(isset($verified_building_result)){
    $address->prepend_exceptions($verified_building_result);
    echo '<pre>';
    print_r($address->get_street_part());
    echo '</pre>';
    echo '<pre>';
    print_r($address->get_building_part());
    echo '</pre>';
}

echo '--------------------------------------<br/><b>UPDATED ADDRESS MAP</b><br/>';

echo '<pre>';
print_r($address->get_property_map());
echo '</pre>';

echo '--------------------------------------<br/><b>BRGY VERIFICATION</b><br/>';

echo '<pre>';
print_r(identify_brgy($address->get_area(),'137405000',$address->get_address_array()));
echo '</pre>';



?>
