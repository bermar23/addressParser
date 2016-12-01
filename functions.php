<?php
/**
 * Contains functions Extentions of Address class
*/

/**
 * Function that Verify street in the database and return array results grouped as 'matches' with 'exceptions' for excess
 * 
 * @param       array(street,startPosition,endPosition)
 * @param       address array() delimited by space (Object property)
 * @param       city_id int() to specify city for street search
 * @param       alias boolean() DEFAULT = FALSE, specify if to consider aliad for address searching
 * @return      array results grouped by 'matches' and 'exceptions' 
 * 
*/
    
function verify_street($array_input, $address_array, $city_id = 171, $alias = FALSE){
    
    $result_array = array();
    $dbase_param = array('table' => 'tbl_street_list', 'field' => 'street');
    
    $search = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if( $search->connect_errno)
    {
            echo "Failed to connect to MySQL: (" . $search->connect_errno . ") " . $search->connect_error;
            $search->close();
    }
    
    //Create combinations from start and end position of an array
    $array_search_items = array();        
    for ($j = $array_input['startPosition']; $j <= $array_input['endPosition']; $j++)
    {
        for ($k = $array_input['startPosition']; $k <= $array_input['endPosition']; $k++ ){
            if( $j <= $k ){
                $array_search_items[] = array($j, $k);
            }
        }
    }
    
    //sort combinations
    $loop = $array_input['endPosition'] - $array_input['startPosition'];
    while( $loop >= 0 ){
        foreach( $array_search_items as $array_search_items_check ){
            if( $loop == ($array_search_items_check[1] - $array_search_items_check[0]) ){
                $array_search_sort_items[] = $array_search_items_check;
            }
        }
        $loop--;
    }        
    foreach( $array_search_sort_items as $array_search_item)
    {            
        $dbase_param['search_value'] = text_builder($array_search_item[1], $array_search_item[0], $address_array);         
        $query = "SELECT * FROM " . $dbase_param['table'] . " WHERE LOWER(" . $dbase_param['field'] . ")='" . strtolower($dbase_param['search_value']) . "' and city_id=" . $city_id . ";";
        
        if( $alias == TRUE ){
            $in_values = '';
            $init = 0;                
            foreach( get_alias($dbase_param['search_value']) as $in_concat ){            
                $in_values = $in_values . ", '" . strtolower($in_concat) . "'";
            }        
            $in_values = trim($in_values, ', ');
            $query = "SELECT * FROM " . $dbase_param['table'] . " WHERE LOWER(" . $dbase_param['field'] . ") IN (" . $in_values . ") and city_id=" . $city_id . ";";
        }
        
        $values = $search->query($query);        
        
        if( $values->num_rows != 0)
        {
            if( isset($result_array['matches']['match']) ){
                $find_in_matched = FALSE;
                foreach( $result_array['matches']['match'] as $result_matched_check)
                {
                    if( $array_search_item[0] >= $result_matched_check['startPosition'] && $array_search_item[1] <= $result_matched_check['endPosition'] ){                    
                        $find_in_matched = TRUE;
                    }
                }
                if( ! $find_in_matched ){
                    $result_array['matches']['match'][] = array('street' => $dbase_param['search_value'], 'startPosition' => $array_search_item[0], 'endPosition' => $array_search_item[1], 'comment' => 'street found');
                }
            }
            else{
                $result_array['matches']['match'][] = array('street' => $dbase_param['search_value'], 'startPosition' => $array_search_item[0], 'endPosition' => $array_search_item[1], 'comment' => 'street found');
            }
        }
    }
    
    //Create a final $result_array['exceptions']        
    $loop_check = $array_input['startPosition'];
    while( $loop_check <= $array_input['endPosition'])
    {
        if( isset($result_array['matches']))
        {
            $find_in_matched = FALSE;
            $end_pos = FALSE;
            $start_pos = FALSE;
            $pos = '';
            
            foreach( $result_array['matches']['match'] as $result_matched_check)
            {                
                if( $loop_check >= $result_matched_check['startPosition'] && $loop_check <= $result_matched_check['endPosition'] ){                    
                    $find_in_matched = TRUE;
                }
                
                //determine position
                if( $loop_check < $result_matched_check['startPosition'] && $loop_check < $result_matched_check['endPosition'] ){
                    $start_pos = TRUE;                            
                }
                if( $loop_check > $result_matched_check['startPosition'] && $loop_check > $result_matched_check['endPosition'] ){
                    $end_pos = TRUE;                            
                }
            }
            if( ! $find_in_matched ){
                if( $end_pos == TRUE && $start_pos == TRUE ){
                    $pos = 'middle';
                }
                else{
                    if( $end_pos === TRUE ){
                        $pos = 'end';                                
                    }
                    if( $start_pos === TRUE ){
                        $pos = 'start';
                    }
                }
                $final_exceptions[] = array('street' => text_builder($loop_check, $loop_check, $address_array), 'startPosition' => $loop_check, 'endPosition' => $loop_check, 'comment' => 'excess', 'pos' => $pos);
            }                    
        }
        else
        {
            $final_exceptions[] = array('street' => text_builder($loop_check, $loop_check, $address_array), 'startPosition' => $loop_check, 'endPosition' => $loop_check, 'comment' => 'excess', 'pos' => 'none');
        }
        $loop_check++;
    }
        
    if( isset($final_exceptions) ){
        $result_array['matches']['exceptions'] = $final_exceptions;
    }
    return $result_array;    
}
//--------------------------------------------------

/**
 * Function that Verify building in the database and return array results grouped as 'matches' with 'exceptions' for excess
 * 
 * @param       array(building,startPosition,endPosition)
 * @param       address array() delimited by space (Object property)
 * @param       city_code int() to specify city for building search
 * @return      array results grouped by 'matches' and 'exceptions' 
 * 
*/
    
function verify_building($array_input, $address_array, $city_code = ''){
    $result_array = array();
    $dbase_param = array('table' => 'tbl_poi', 'field' => 'name');
    
    $search = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    //Create combinations from start and end position of an array
    $array_search_items = array();        
    for ($j = $array_input['startPosition']; $j <= $array_input['endPosition']; $j++)
    {
        for ($k = $array_input['startPosition']; $k <= $array_input['endPosition']; $k++ ){
            if( $j <= $k ){
                $array_search_items[] = array($j, $k);
            }
        }
    }
    
    //sort combinations
    $loop = $array_input['endPosition'] - $array_input['startPosition'];
    while( $loop >= 0 ){
        foreach( $array_search_items as $array_search_items_check ){
            if( $loop == ($array_search_items_check[1] - $array_search_items_check[0]) ){
                $array_search_sort_items[] = $array_search_items_check;
            }
        }
        $loop--;
    }
    
    foreach( $array_search_sort_items as $array_search_item)
    {
        $dbase_param['search_value'] = text_builder($array_search_item[1], $array_search_item[0], $address_array);            
        $query ="SELECT * FROM " . $dbase_param['table'] . " WHERE LOWER(" . $dbase_param['field'] . ")='" . strtolower($dbase_param['search_value']) . "';";
        $values = $search->query($query);        
        
        if( $values->num_rows != 0 )
        {
            if( isset($result_array['matches']['match']) ){
                $find_in_matched = FALSE;
                foreach( $result_array['matches']['match'] as $result_matched_check)
                {
                    if( $array_search_item[0] >= $result_matched_check['startPosition'] && $array_search_item[1] <= $result_matched_check['endPosition'] ){                    
                        $find_in_matched = TRUE;
                    }
                }
                if( ! $find_in_matched ){
                    $result_array['matches']['match'][] = array('building' => $dbase_param['search_value'], 'startPosition' => $array_search_item[0], 'endPosition' => $array_search_item[1], 'comment' => 'building found');
                }
            }
            else{
                $result_array['matches']['match'][] = array('building' => $dbase_param['search_value'], 'startPosition' => $array_search_item[0], 'endPosition' => $array_search_item[1], 'comment' => 'building found');
            }
        }
    }
    
    //Create a final $result_array['exceptions']        
    $loop_check = $array_input['startPosition'];
    while( $loop_check <= $array_input['endPosition'] )
    {
        if( isset($result_array['matches']) ) 
        {
            $find_in_matched = FALSE;
            $end_pos = FALSE;
            $start_pos = FALSE;
            $pos = '';
            
            foreach( $result_array['matches']['match'] as $result_matched_check)
            {                
                if( $loop_check >= $result_matched_check['startPosition'] && $loop_check <= $result_matched_check['endPosition'] ){                    
                    $find_in_matched = TRUE;
                }
                
                //determine position
                if( $loop_check < $result_matched_check['startPosition'] && $loop_check < $result_matched_check['endPosition'] ){
                    $start_pos = TRUE;                            
                }
                if( $loop_check > $result_matched_check['startPosition'] && $loop_check > $result_matched_check['endPosition'] ){
                    $end_pos = TRUE;                            
                }
            }
            if( ! $find_in_matched ){
                if( $end_pos == TRUE && $start_pos == TRUE ){
                    $pos = 'middle';
                }
                else{
                    if( $end_pos === TRUE ){
                        $pos = 'end';                                
                    }
                    if( $start_pos === TRUE ){
                        $pos = 'start';
                    }
                }
                $final_exceptions[] = array('building' => text_builder($loop_check, $loop_check, $address_array), 'startPosition' => $loop_check, 'endPosition' => $loop_check, 'comment' => 'excess', 'pos' => $pos);
            }                    
        }
        else
        {
            $final_exceptions[] = array('building' => text_builder($loop_check, $loop_check, $address_array), 'startPosition' => $loop_check, 'endPosition' => $loop_check, 'comment' => 'excess', 'pos' => 'none');
        }
        $loop_check++;
    }
    
    if( isset($final_exceptions) ){    
        $result_array['matches']['exceptions'] = $final_exceptions;
    }
    return $result_array;    
}
//--------------------------------------------------

/**
 * Function in returning string based on a given start and end position array index using standard replacement for database searching
 *
 * @param       should be the highest ($begin)
 * @param       should be the lowest ($end)
 * @param       address array() delimited by space (Object property)
 * @return      text from the given parameter
 * 
*/

function text_builder($begin, $end, $address_array){        
    $loop = $begin;
    $output = '';    
    while( $loop>=$end)
    {
        $output = $address_array[$loop] . ' ' . $output;        
        $loop--;
    }
    return trim($output);
}
//--------------------------------------------------

/**
 * Function that return other Alias for a given input (for database searching using 'WHERE field IN (item, item ...)')
 *
 * @param       string() Input parameter
 * @return      array() Output street name and alias as possible user entry
 *
*/

function get_alias($input){
    $output = array();
    $output[] = strtolower($input);
    $search = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    $dbase_param = array('table' => 'tbl_street_alias', 'field' => 'street', 'search_value' => $input);
    if( $search->connect_errno )
    {
        echo "Failed to connect to MySQL: (" . $search->connect_errno . ") " . $search->connect_error;
        $search->close();
    }
    
    $query = "SELECT * FROM " . $dbase_param['table'] . " WHERE LOWER(" . $dbase_param['field'] . ")='" . strtolower($dbase_param['search_value']) . "';";
    $values = $search->query($query);
    
    if( $values->num_rows != 0 )
    {
        while( $row = $values->fetch_assoc() ){
            $output[] = $row['alias'];            
        }
    }
    else
    {        
        $dbase_param['field'] = 'alias';
        if( $search->connect_errno )
        {
            echo "Failed to connect to MySQL: (" . $search->connect_errno . ") " . $search->connect_error;
            $search->close();
        }
        
        $query2 = "SELECT * FROM " . $dbase_param['table'] . " WHERE LOWER(" . $dbase_param['field'] . ")='" . strtolower($dbase_param['search_value']) . "';";
        $values2 = $search->query($query2);
        $recordNumber2 = $values2->num_rows;
        
        if( $recordNumber2 != 0 )
        {
            while( $row = $values2->fetch_assoc() ){
                $output[] = $row['street'];                
            }
        }    
    }
    
    return $output;
}
//--------------------------------------------------

//This functions check for brgy from the unparsed values

//accepts $input = array(array('search_value' => '', 'startPosition' => '', 'endPosition' => ''))
//------------------- Under construction
function identify_brgy($input = array(), $city_code = '137405000', $address_array){
    $find = FALSE;    
    
    $dbase_param = array('table' => 'tbl_brgy', 'field' => 'name', 'field2' => 'city_code', 'search_value2' => trim($city_code));
    $search = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if( $search->connect_errno ){
        echo "Failed to connect to MySQL: (" . $search->connect_errno . ") " . $search->connect_error;
        $search->close();
    }
    
    $result = array();
    foreach($input as $input_iterate){
        if($input_iterate['category'] == 'Barangay'){
            $search_value = strtolower(text_builder($input_iterate['endPosition'], $input_iterate['startPosition'], $address_array));
            $query = "SELECT * FROM " . $dbase_param['table'] . " WHERE LOWER(" . $dbase_param['field'] . ")='" . $search_value . "' and  LOWER(" . $dbase_param['field2'] . ")='" . strtolower($dbase_param['search_value2']) . "';";
            $values = $search->query($query);    
            if( $values->num_rows != 0 ){
                $result[] = array('search_value' => $search_value, 'startPosition' => $input_iterate['startPosition'], 'endPosition' => $input_iterate['endPosition'], 'category' => $input_iterate['category'], 'find' => TRUE);    
            }
            else{
                $result[] = array('search_value' => $search_value, 'startPosition' => $input_iterate['startPosition'], 'endPosition' => $input_iterate['endPosition'], 'category' => $input_iterate['category'], 'find' => FALSE);    
            }
        }
    }
    unset($input_iterate);
    
    $search->close();
    return $result;    
}
//------------------- End construction

//End Functions