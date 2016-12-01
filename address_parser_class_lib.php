<?php
error_reporting(-1);
    define("DB_HOST", "localhost");
    define("DB_USERNAME", "root");
    define("DB_PASSWORD", "");
    define("DB_NAME", "address_parser");    

    
/**
 * Address
 * Description: parse given address string() and return address properties (address_array, street, street_part, building, building_part, area, corner_reference, comma_reference)
 * 
 *
*/
class Address {

    /**
     * Input values
     * User controlled properties
     * 
    */
    
    protected $address;
    protected $address_array;
    
    /**
     * Processed Properties - Used for internal process
     * Properties processed varried from a given address
     * 
    */
    
    protected $country;
    public $area;    
    public $street;
    public $street_part;
    public $building;
    public $building_part;    
    
    public $corner_reference;
    
    public $comma_reference;
    
    /**
     * Static Properties - Used for internal process
     * Can be called with-out instantiating an object
     * 
    */
    
    public static $helper_array_global;
    //public static $standard_array_global;
    
    public function __construct($newAddress ){
        self::initialize_helper();
        //self::initialize_standard();        
        if( isset($newAddress) ){
            $this->area = array();
            $this->street = array();
            $this->street_part = array();
            $this->building = array();
            $this->building_part = array();
            $this->corner_reference = array();
            $this->comma_reference = array();
            
            $this->address = $newAddress;        
            $this->explode_text($newAddress);
            $this->comma_position();        
            
            $this->parse_area();
            $this->parse_street();
            $this->parse_building();
            $this->parse_building_part();
            $this->parse_street_part();
        }
        else{
            echo '<b>Undefined Object value!</b><br/>';
        }
    }
    public function get_address(){
        return $this->address;
    }
    public function get_address_array(){
        return $this->address_array;
    }
    public function get_street(){
        return $this->street;
    }
    public function get_street_part(){
        return $this->street_part;
    }
    public function get_building(){
        return $this->building;
    }
    public function get_building_part(){
        return $this->building_part;
    }
    public function get_area(){
        return $this->area;
    }
    public function get_corner_reference(){
        return $this->corner_reference;
    }
    public function get_comma_list(){
        return $this->comma_reference;
    }
    static function get_hepler_array(){//Static method, can call without creating object
        self::initialize_helper();
        return self::$helper_array_global;
    }
    static function get_standard_array(){//Static method, can call without creating object
        self::initialize_standard();
        return self::$standard_array_global;
    }    
    
    /**
     * Initilize helper array from helper database
     * Sets self::$helper_array_global static property
     *
    */
        
    static function initialize_helper(){
        $helperArray = array();    
        $search = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if( $search->connect_errno ){
            echo "Failed to connect to MySQL: (" . $search->connect_errno . ") " . $search->connect_error;
            $search->close();
        }
        $query ="SELECT * FROM helper_modifier;";
        $values = $search->query($query);
        $recordNumber = $values->num_rows;
        if( $recordNumber != 0 ){
            while( $row = $values->fetch_assoc() ){
                $helperArray[] = $row;
            }
        }
        else{
            echo 'No records in helper database!';
        }
        self::$helper_array_global = $helperArray;
        $search->close();
    }
    //--------------------------------------------------
    
    /**
     * Method that explode the address string delimited by space and treated comma as another word
     * Sets the $this->address_array property
     *
     * @param       string()     
     *
    */
    
    protected function explode_text($text){//Method in converting address string to array delimited by space
        $text = trim($text,', ');
        $text = str_replace(',', ' ,',$text);        
        $text = preg_replace('/  +/', ' ', $text);
        $this->address_array = explode(' ', trim($text));
    }
    //--------------------------------------------------
    
    /**
     * Method in returning text based on a given start and end position array index
     *
     * @param - 'begin' should be the highest
     * @param - 'end' should be the lowest
     * @return - text from the given parameter
     * 
    */
    
    public function text_builder($begin, $end ){
        $arrayString = $this->address_array;
        $loop = $begin;
        $street = '';    
        while( $loop>=$end ){
            $street = $arrayString[$loop] . ' ' . $street;        
            $loop--;
        }
        return trim($street);
    }
    //--------------------------------------------------
    
    /**
     * Method in verifying if the given value is in list of preposition
     *
    */
    
    private function verify_prepositions($text){
        $prepositionsArray = array('of');
        $find = FALSE;
        foreach( $prepositionsArray as $preposionsCheck ){
            if( $preposionsCheck === strtolower($text) ){
                $find = TRUE;
            }
        }
    return $find;
    }
    //--------------------------------------------------
    
    /**
     * Method that controls iteration for parsing address parts
     * Uses self::$helper_array_global (helper array database converted to array) for evaluation
     * 
     * @param       int() - Position to evaluate
     * @param       string() - if: Street, Building, Unit, Area
     *
    */
    
    private function test_helper($position,$mode){//$class to know where to restrict
        $helperArray = self::$helper_array_global;
        $address_array = $this->address_array;        
        $text = $address_array[$position];
        $find = TRUE;
        $findSalutation = FALSE;        
        
        $areaAvailable = $this->is_not_area($position);
        $streetAvailable = $this->is_not_street($position);
        $buildingAvailable = $this->is_not_building($position);
        $buildingpartAvailable = $this->is_not_buildingpart($position);
        $streetpartAvailable = $this->is_not_street_part($position);
            
        foreach ($helperArray as $element ){
            if( strtolower($text) === strtolower($element['word']) ){     
                if( $element['class'] === 'Salutation'){//check if there is in array helper a salutation same with the word
                    $findSalutation = TRUE;
                }
                
                //if( ($element['class'] === 'Area' || $element['class'] === 'Street Extension' || $element['class'] === 'Street Type' || $element['class'] === 'Building Type' || $element['class'] === 'Building Part' || $element['class'] === 'Reference Street' || $element['class'] === 'Reference Building') && $mode === 'Street'){//The values are restricted in street part of the address
                if( ($element['class'] === 'Area' || $element['class'] === 'Street Extension' || $element['class'] === 'Street Type' || $element['class'] === 'Street Part' || $element['class'] === 'Building Type' || $element['class'] === 'Building Part' || $element['class'] === 'Reference Street' || $element['class'] === 'Reference Building') && $mode === 'Street'){//The values are restricted in street part of the address
                    $find = FALSE;                    
                }
        
                if( ($element['class'] === 'Area' || $element['class'] === 'Street Extension' || $element['class'] === 'Salutation' || $element['class'] === 'Street Type' || $element['class'] === 'Street Part' || $element['class'] === 'Building Type' || $element['class'] === 'Building Part' || $element['class'] === 'Reference Street' || $element['class'] === 'Reference Building') && $mode === 'Street Part'){//Nothing allowed        
                    $find = FALSE;
                }
                
                if( ($element['class'] === 'Area' || $element['class'] === 'Street Extension' || $element['class'] === 'Street Type' || $element['class'] === 'Street Part' || $element['class'] === 'Building Type' || $element['class'] === 'Building Part' || $element['class'] === 'Reference Street' || $element['class'] === 'Reference Building') && $mode === 'Building'){//Nothing allowed            
                    $find = FALSE;                        
                }
                
                if( ($element['class'] === 'Area' || $element['class'] === 'Street Extension' || $element['class'] === 'Salutation' || $element['class'] === 'Street Type' || $element['class'] === 'Street Part' || $element['class'] === 'Building Type' || $element['class'] === 'Building Part' || $element['class'] === 'Reference Street' || $element['class'] === 'Reference Building') && $mode === 'Unit'){//Nothing allowed        
                    $find = FALSE;            
                }
                
                if( ($element['class'] === 'Area' || $element['class'] === 'Street Extension' || $element['class'] === 'Street Type' || $element['class'] === 'Street Part' || $element['class'] === 'Building Type' || $element['class'] === 'Building Part' || $element['class'] === 'Reference Street' || $element['class'] === 'Reference Building') && $mode === 'Area'){//****
                    $find = FALSE;            
                }
                
                if( $element['class'] === 'Street Delimiter' ){
                    if( $this->is_not_corner($position) ){
                        $this->corner_reference[] = array('corner_reference' => text_builder($position, $position, $address_array), 'startPosition' => $position, 'endPosition' => $position);//for corner referencing
                    }                    
                    $find = FALSE;                
                }
            }
        }
        
        //Below Overrides previous result based on special conditions        
        if( $findSalutation === TRUE){//Override if the value is also salutation (this is used for street with 'St.', to consider also St. as salutation)
            $find = TRUE;
        }
        
        if( $this->is_building_part($text) === TRUE && ($mode === 'Building' || $mode === 'Unit' || $mode === 'Street' || $mode === 'Area')){//check if Building is currently testing floor of level
            $find = FALSE;    
            $buildingUnitPush = array('building_part' => $text, 'startPosition' => $position, 'endPosition' => $position);    
        }
        
        if( $mode === 'Building' && ($streetAvailable === FALSE || $areaAvailable === FALSE || $streetpartAvailable === FALSE)){//Override result to false if the value that is currently parsing is already parsed address part
            $find = FALSE;    
        }
        
        if( $mode === 'Unit' && ($streetAvailable === FALSE || $buildingAvailable === FALSE || $buildingpartAvailable === FALSE || $areaAvailable === FALSE || $streetpartAvailable === FALSE)){//checking if candidate for building part is in building part, street, area and building container already
            $find = FALSE;            
        }
        
        if( $mode === 'Street Part' && ($streetAvailable === FALSE || $buildingAvailable === FALSE || $buildingpartAvailable === FALSE || $areaAvailable === FALSE || $streetpartAvailable === FALSE)){//checking if candidate for Street Part is in building part, street, area and building container already
            $find = FALSE;            
        }
        
        if( $mode === 'Street' && $areaAvailable === FALSE ){
            $find = FALSE;    
        }
        
        if( $mode === 'Area' && $areaAvailable === FALSE ){
            $find = FALSE;    
        }
        
        if( $this->verify_prepositions($text) === TRUE && $areaAvailable === FALSE && $streetAvailable === FALSE && $buildingAvailable === FALSE && $buildingpartAvailable === FALSE){//Override whatever restrictions if the preceeded or followed by prepositions
            $find = TRUE;            
        }
        
        if( $this->is_comma($position)){//Stop iterating if current position represents comma
            $find = FALSE;    
        }
        
    return $find;
    }
    //--------------------------------------------------
    
    /**
     * Method that evaluate if the given position is parsed as salutation self::$helper_array_global as reference
     *
     * @param       int() - position to evaluate
     * @return      boolean - True if not salutation, False if parsed as salutation
     *
    */
    
    private function is_not_salutation($position){//Check if candidate for building is available from street        
        $find = TRUE;
        foreach( self::$helper_array_global as $helperArrayCheck ){     
            if( $this->address_array[$position] === $helperArrayCheck['word'] && $helperArrayCheck['type'] === 'Follows' ){
                $find = FALSE;            
            }
        }
    return $find;
    }
    //--------------------------------------------------
    
    /**
     * Method that evaluate if the given position is parsed as street
     *
     * @param       int() - position to evaluate
     * @return      boolean - True if not street, False if parsed as street
     *
    */
    
    private function is_not_street($position){//Check if candidate for building is available from street
        //$streetArray = $this->street;
        $find = TRUE;
        foreach( $this->street as $streetArrayCheck ){     
            if( $position >= $streetArrayCheck['startPosition'] && $position <= $streetArrayCheck['endPosition'] ){
                $find = FALSE;            
            }
        }
    return $find;
    }
    //--------------------------------------------------
    
    /**
     * Method that evaluate if the given position is parsed as corner
     *
     * @param       int() - position to evaluate
     * @return      boolean - True if not corner, False if parsed as corner
     *
    */
    
    private function is_not_corner($position){//Check if candidate corner is already in corner reference list        
        $find = TRUE;
        foreach( $this->corner_reference as $cornerArrayCheck ){     
            if( $position >= $cornerArrayCheck['startPosition'] && $position <= $cornerArrayCheck['endPosition'] ){
                $find = FALSE;            
            }
        }
    return $find;
    }
    //--------------------------------------------------

    /**
     * Method that evaluate if the given position is parsed as street part
     *
     * @param       int() - position to evaluate
     * @return      boolean - True if not street part, False if parsed as street part
     *
    */
    
    private function is_not_street_part($position){//Check if candidate for building is available from street        
        $find = TRUE;
        foreach( $this->street_part as $streetPartArrayCheck ){     
            if( $position >= $streetPartArrayCheck['startPosition'] && $position <= $streetPartArrayCheck['endPosition'] ){
                $find = FALSE;            
            }
        }
    return $find;
    }
    //--------------------------------------------------
    
    /**
     * Method that evaluate if the given position is parsed as building
     *
     * @param       int() - position to evaluate
     * @return      boolean - True if not building, False if parsed as building
     *
    */
    
    private function is_not_building($position){//Check if candidate is not in building        
        $find = TRUE;
        foreach( $this->building as $streetArrayCheck ){     
            if( $position >= $streetArrayCheck['startPosition'] && $position <= $streetArrayCheck['endPosition'] ){
                $find = FALSE;            
            }
        }
    return $find;
    }
    //--------------------------------------------------
    
    /**
     * Method that evaluate if the given position is parsed as building part
     *
     * @param       int() - position to evaluate
     * @return      boolean - True if not building part, False if parsed as building part
     *
    */
    
    private function is_not_buildingpart($position){//Check if candidate is not in building part        
        $find = TRUE;
        foreach( $this->building_part as $streetArrayCheck ){     
            if( $position >= $streetArrayCheck['startPosition'] && $position <= $streetArrayCheck['endPosition'] ){
                $find = FALSE;            
            }
        }
    return $find;
    }
    //--------------------------------------------------
    
    /**
     * Method that evaluate if the given position is parsed as area
     *
     * @param       int() - position to evaluate
     * @return      boolean - True if not area, False if parsed as area
     *
    */
    
    private function is_not_area($position){//Check if candidate is not in Area        
        $find = TRUE;
        foreach( $this->area as $areaArrayCheck ){
            if( $position >= $areaArrayCheck['startPosition'] && $position <= $areaArrayCheck['endPosition'] ){
                $find = FALSE;            
            }
        }
    return $find;
    }
    //--------------------------------------------------
    
    /**
     * Method that evaluate if input parameter is a building part
     *
     * @param       string()
     * @return      boolean() - True if matches pattern of a building part
     *
    */
    
    public function is_building_part($text){//Floor identifier
        $text = trim($text);
        $text = ' ' . $text . ' ';
        $matches = array();
        $find = FALSE;
        //floor and level with slash - preceeds
        if( preg_match('/ [0-9\-]+\/(f|f\.|flr|flr\.|floor|floor\.|l|l\.|lvl|lvl\.|level|level\.) /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        //floor and level with dash - preceeds
        if( preg_match('/ [0-9\-]+\-(f|f\.|flr|flr\.|floor|floor\.|l|l\.|lvl|lvl\.|level|level\.) /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        //floor and level without slash nor dash - preceeds
        if( preg_match('/ [0-9\-]+(f|f\.|flr|flr\.|floor|floor\.|l|l\.|lvl|lvl\.|level|level\.) /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        //floor and level with slash and using th, 2nd, st - preceeds
        if( preg_match('/ [0-9\-]+(st|nd|rd|th)+\/(f|f\.|flr|flr\.|floor|floor\.|l|l\.|lvl|lvl\.|level|level\.) /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }   
        //floor and level with dash and using th, 2nd, st - preceeds
        if( preg_match('/ [0-9\-]+(st|nd|rd|th)+\-(f|f\.|flr|flr\.|floor|floor\.|l|l\.|lvl|lvl\.|level|level\.) /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        //floor and level like UGF and LGF - preceeds
        if( preg_match('/ (g|ug|lg)+(f|f\.|flr|flr\.|floor\.|floor|fl|l|lvl|level|lv|level\.|lvl\.|l\.) /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;        
        }
        //floor and level like UGF and LGF with slash - preceeds
        if( preg_match('/ (g|ug|lg)+\/(f|f\.|flr|flr\.|floor\.|floor|fl|l|lvl|level|lv|level\.|lvl\.|l\.) /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;        
        }
        //floor and level like UGF and LGF with dash - preceeds
        if( preg_match('/ (g|ug|lg)+\-(f|f\.|flr|flr\.|floor\.|floor|fl|l|lvl|level|lv|level\.|lvl\.|l\.) /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;        
        }
        
        //Number sign followed by number
        //if( preg_match('/ #[0-9a-zA-Z\-]+ /i', $text) === 1 && $find === FALSE ){
        //    $find = TRUE;
        //    echo $text.' !!';
        //}
        
        //level with slash - follows (this should be done in parsed building array)
        if( preg_match('/ (l|l\.|lvl|lvl\.|level|level\.)\/[0-9]+ /i', $text) === 1 && $find === FALSE){//Check this for lot (Unit 2 G/F RCB Square, L29 Blk167 Casa Milan Greater Lagro, Quezon City, Metro Manila)
            $find = TRUE;
        }
        //level with dash - follows
        if( preg_match('/ (l|l\.|lvl|lvl\.|level|level\.)\-[0-9]+ /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        //level without slash nor dash - follows
        if( preg_match('/ (l|l\.|lvl|lvl\.|level|level\.)[0-9\-]+ /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        //Unit like U-106a of U106
        if( preg_match('/ (u\-)[0-9a-zA-Z\-]+ /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        //Room  like rm.106, rm106, rm.106-a, rm106a, rm-106
        if( preg_match('/ (rm\-|rm|rm.)[0-9a-zA-Z\-]+ /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        //Unit  like Unit.106, Unit106, Unit-106, Unit-106a, Unit106a
        if( preg_match('/ (Unit\-|Unit|Unit.)[0-9a-zA-Z\-]+ /i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        
        //example 1/F-2/F
        if( preg_match('/[0-9\-]+\/(f|f\.|flr|flr\.|floor|floor\.|l|l\.|lvl|lvl\.|level|level\.)/i', $text) === 1 && $find === FALSE ){
            $find = TRUE;
        }
        
    return $find;
    }
    //--------------------------------------------------

    /**
     * Method for parsing address part (street)
     * Will be process if given address string is set
     *
    */
    
    private function parse_street(){
        //note: array position starts from 0        
        $address_array = $this->address_array;
        
        $i = 0;
        $addressCount = count($address_array);//Get the length of the array
        while( $i<$addressCount ){
        
            foreach (self::$helper_array_global as $element ){
                if( ($element['class'] === 'Street Type' || $element['class'] === 'Street Extension' || $element['class'] === 'Street Delimiter') && (strtolower(trim($element['word'])) === strtolower($address_array[$i])) && $this->is_not_area($i)){//check array helper with street class and match the word
                    
                    $basePosition = $i;
                    $street = $address_array[$i];
                    $streetPush = array();
                    $movingPosition = 0;                
    
                    if( $element['type'] === 'Follows' ){                 
                        $movingPosition = $basePosition + 1;
                        while( $movingPosition<$addressCount && $this->test_helper($movingPosition,'Street') === TRUE){//If return FALSE then the loop will stop                            
                            $movingPosition++;
                        }
                        $lastPosition = $movingPosition - 1;//deduct 1 to get the actual last position in the array
    
                        $find = FALSE;
                        foreach ($this->street as &$streetElement){//Merge result to the previous results matching the conditions
                            if( $streetElement['endPosition'] === $basePosition && $element['class'] === 'Street Type'){//Overlapping
                                $streetElement['endPosition'] = $lastPosition;
                                $streetElement['street'] = $this->text_builder($lastPosition, $streetElement['startPosition'], $address_array);
                                $find = TRUE;                            
                            }
                            if( $streetElement['startPosition']<=$basePosition && $streetElement['endPosition']>=$lastPosition){//check if the word parsing for street already inside the array
                                $find = TRUE;//Avoid from adding in the list                            
                            }
                        }
                        unset($streetElement);
                                                        
                        if( $find === FALSE ){
                            if( $element['class'] === 'Street Delimiter' ){
                                
                                if( $this->is_not_corner($basePosition) ){
                                    $this->corner_reference[] = array('corner_reference' => text_builder($basePosition, $basePosition, $address_array), 'startPosition' => $basePosition, 'endPosition' => $basePosition);//for corner refrence
                                }
                                
                                $basePosition = $basePosition + 1;//Exclude corner
                                
                                $street = $this->text_builder($lastPosition, $basePosition, $address_array);
                                $streetPush = array('street' => $street, 'startPosition' => $basePosition, 'endPosition' => $lastPosition);
                                if( $street != '' ){
                                    $this->street[] = $streetPush;
                                }
                            }
                            else{                             
                                $street = $this->text_builder($lastPosition, $basePosition, $address_array);
                                $streetPush = array('street' => $street, 'startPosition' => $basePosition, 'endPosition' => $lastPosition);                                    
                                $this->street[] = $streetPush;
                            }
                        }
                    }
                    elseif( $element['type'] === 'Precedes' ){
                        $movingPosition = $basePosition - 1;                
                        while( $movingPosition>=0 && $this->test_helper($movingPosition,'Street') === TRUE ){                         
                            $movingPosition--;
                        }
                        $lastPosition = $movingPosition + 1;//add 1 to get the actual last position in the array                        
                        $find = FALSE;
                        foreach ($this->street as &$streetElement){//Merge result to the previous results matching the conditions
                            if( $streetElement['startPosition'] === $basePosition && $element['class'] === 'Street Type'){//Overlapping
                                $streetElement['startPosition'] = $lastPosition;
                                $streetElement['street'] = $this->text_builder($streetElement['endPosition'], $lastPosition, $address_array);
                                $find = TRUE;                            
                            }
                            
                            if( $streetElement['endPosition'] === ($basePosition - 1) && $element['class'] === 'Street Extension'){//This part is for Extension and Interior
                                $streetElement['endPosition'] = $lastPosition;
                                $streetElement['street'] = $this->text_builder($lastPosition, $streetElement['startPosition'], $address_array);
                                $find = TRUE;                                
                            }
                            
                            if( $streetElement['startPosition'] <= $lastPosition && $streetElement['endPosition'] >= $lastPosition && $element['class'] != 'Street Delimiter'){//check if one of the position is already in the street array
                                $streetElement['endPosition'] = $basePosition;
                                $streetElement['street'] = $this->text_builder($basePosition, $streetElement['startPosition'], $address_array);
                                $find = TRUE;                                
                            }
                            
                            if( ($streetElement['startPosition']<=$lastPosition && $streetElement['endPosition']>=$basePosition) || ($element['class'] === 'Street Delimiter' && $streetElement['startPosition']<=$lastPosition && $streetElement['endPosition']>=($basePosition - 1))){//check if the word parsing for street already inside the array
                                $find = TRUE;//Avoid from adding in the list                            
                            }                            
                        }
                        unset($streetElement);
                                                        
                        if( $find === FALSE ){                                     
                            if( $element['class'] === 'Street Delimiter' ){
                                
                                if( $this->is_not_corner($basePosition) ){
                                    $this->corner_reference[] = array('corner_reference' => text_builder($basePosition, $basePosition, $address_array), 'startPosition' => $basePosition, 'endPosition' => $basePosition);//for corner refrence
                                }
                                
                                $basePosition = $basePosition - 1;//Exclude corner
                                $street = $this->text_builder($basePosition, $lastPosition, $address_array);
                                $streetPush = array('street' => $street, 'startPosition' => $lastPosition, 'endPosition' => $basePosition);
                                if( $street != '' ){                                 
                                    if( preg_match('/ [0-9\-]+ /i', ' ' . $address_array[$basePosition] . ' ') === 1 && $basePosition === $lastPosition ){
                                        $this->street_part[] = array('street_part' => $street, 'startPosition' => $lastPosition, 'endPosition' => $basePosition, 'relatedTo' => $i);                                    }
                                    else{
                                        $this->street[] = $streetPush;    
                                    }
                                }                                
                            }
                            else{
                                $street = $this->text_builder($basePosition, $lastPosition, $address_array);
                                $streetPush = array('street' => $street, 'startPosition' => $lastPosition, 'endPosition' => $basePosition);                                    
                                $this->street[] = $streetPush;                                
                            }
                        }                        
                    }
                    else{
                        $streetPush = array();
                    }
                }
            }        
        $i++;
        }
        
        //Final Checking of parsed street
        foreach( $this->street as &$street_check ){
            if( $street_check['startPosition'] != $street_check['endPosition'] ){
                    $street_part = '';
                    $street_part_startPosition = 0;
                    $street_part_endPosition = 0;
                if( $address_array[$street_check['startPosition']] === '#' && preg_match('/ [0-9\-]+ /i' , ' ' . $address_array[$street_check['startPosition'] + 1] . ' ') === 1){//parse street like this # 33 Sample street
                    //Add matched street part to Street part container                    
                    $street_part = $this->text_builder($street_check['startPosition'] + 1, $street_check['startPosition'], $address_array);
                    $street_part_startPosition = $street_check['startPosition'];
                    $street_part_endPosition = $street_check['startPosition'] + 1;
                    $this->street_part[] = array('street_part' => $street_part, 'startPosition' => $street_part_startPosition, 'endPosition' => $street_part_endPosition, 'relatedTo' => $street_check['startPosition'] + 2);
                    //Update current position of the matched street            
                    $street_check['startPosition'] = $street_check['startPosition'] + 2;
                    $street_check['street'] = $this->text_builder($street_check['endPosition'], $street_check['startPosition'], $address_array);
                }                
                
                if( preg_match('/ [0-9\-]+ /i' , ' ' . $address_array[$street_check['startPosition']] . ' ') === 1 && preg_match('/ de /i', ' ' . $address_array[$street_check['startPosition'] + 1] . ' ') != 1){//parse street begins with number but number not followed bu 'de'
                    //Add matched street part to Street part container                    
                    $street_part = $this->text_builder($street_check['startPosition'], $street_check['startPosition'], $address_array);
                    $street_part_startPosition = $street_check['startPosition'];
                    $street_part_endPosition = $street_check['startPosition'];
                    $this->street_part[] = array('street_part' => $street_part, 'startPosition' => $street_part_startPosition, 'endPosition' => $street_part_endPosition, 'relatedTo' => $street_check['startPosition'] + 1);
                    //Update current position of the matched street
                    $street_check['startPosition'] = $street_check['startPosition'] + 1;
                    $street_check['street'] = $this->text_builder($street_check['endPosition'], $street_check['startPosition'], $address_array);                    
                }
                
                //Added 12-05-2013
                if( preg_match('/ [0-9\-]+[a-zA-Z] /i' , ' ' . $address_array[$street_check['startPosition']] . ' ') === 1 && preg_match('/ de /i', ' ' . $address_array[$street_check['startPosition'] + 1] . ' ') != 1){//parse street begins with number but number not followed bu 'de'
                    //Add matched street part to Street part container                    
                    $street_part = $this->text_builder($street_check['startPosition'], $street_check['startPosition'], $address_array);
                    $street_part_startPosition = $street_check['startPosition'];
                    $street_part_endPosition = $street_check['startPosition'];
                    $this->street_part[] = array('street_part' => $street_part, 'startPosition' => $street_part_startPosition, 'endPosition' => $street_part_endPosition, 'relatedTo' => $street_check['startPosition'] + 1);
                    //Update current position of the matched street
                    $street_check['startPosition'] = $street_check['startPosition'] + 1;
                    $street_check['street'] = $this->text_builder($street_check['endPosition'], $street_check['startPosition'], $address_array);                    
                }
                
                if( (preg_match('/ #[0-9]+ /i', ' ' . $address_array[$street_check['startPosition']] . ' ') === 1) || (preg_match('/ #[0-9]+\-[a-zA-Z] /i', ' ' . $address_array[$street_check['startPosition']] . ' ') === 1) || (preg_match('/ [0-9]+\-[a-zA-Z] /i', ' ' . $address_array[$street_check['startPosition']] . ' ') === 1) || (preg_match('/ [0-9]+\-[a-zA-Z\&]+ /i', ' ' . $address_array[$street_check['startPosition']] . ' ') === 1)){//parse street like this #33-A Sample street and 33-A Sample street
                    //Add matched street part to Street part container                    
                    $street_part = $this->text_builder($street_check['startPosition'], $street_check['startPosition'], $address_array);
                    $street_part_startPosition = $street_check['startPosition'];
                    $street_part_endPosition = $street_check['startPosition'];
                    $this->street_part[] = array('street_part' => $street_part, 'startPosition' => $street_part_startPosition, 'endPosition' => $street_part_endPosition, 'relatedTo' => $street_check['startPosition'] + 1);
                    //Update current position of the matched street
                    $street_check['startPosition'] = $street_check['startPosition'] + 1;
                    $street_check['street'] = $this->text_builder($street_check['endPosition'], $street_check['startPosition'], $address_array);
                }
            }
        unset ($street_check);
        }
    }
    //--------------------------------------------------
    
    /**
     * Method for parsing address part (building)
     * Will be process if given address string is set
     *
    */
    
    private function parse_building(){        
        $address_array = $this->address_array;
        
        $addressCount = count($address_array);//Get the length of the array        
        
        $i = 0;    
        while( $i<$addressCount ){ 
            foreach (self::$helper_array_global as $element ){
                if( $element['class'] === 'Building Type' && (strtolower(trim($element['word'])) === strtolower($address_array[$i])) && $this->is_not_area($i) && $this->is_not_street($i)){//check array helper with building class and match the word
                    
                    $basePosition = $i;                
                    $movingPosition = 0;
                    $buildingPush = array();
                    $building = '';
                    
                    if( $element['type'] === 'Follows' ){                    
                    $movingPosition = $basePosition + 1;                    
                        while( $movingPosition<$addressCount && $this->test_helper($movingPosition,'Building') === TRUE ){                     
                            $movingPosition++;
                        }
                        $lastPosition = $movingPosition - 1;//deduct 1 to get the actual last position in the array
                        
                        $find = FALSE;
                        foreach ($this->building as &$buildingElement){//Merge result to the previous results matching the conditions
                            if( $buildingElement['endPosition'] === $basePosition && $element['class'] === 'Building Type'){//Overlapping                                
                                $buildingElement['endPosition'] = $lastPosition;
                                $buildingElement['building'] = $this->text_builder($buildingElement['endPosition'], $buildingElement['startPosition'], $address_array);
                                $find = TRUE;                                
                            }
                            
                            if($buildingElement['endPosition'] === $basePosition && $element['class'] === 'Building Type'){//Overlapping                                
                                $buildingElement['endPosition'] = $lastPosition;
                                $buildingElement['building'] = $this->text_builder($buildingElement['endPosition'], $buildingElement['startPosition'], $address_array);
                                $find = TRUE;                                
                            }
                            
                            if( $buildingElement['startPosition']<=$basePosition && $buildingElement['endPosition']>=$lastPosition){//check if the word parsing for building already inside the array
                                $find = TRUE;//Avoid from adding in the list                            
                            }
                        }
                        unset($buildingElement);
                                                        
                        if( $find === FALSE ){
                            $building = $this->text_builder($lastPosition, $basePosition, $address_array);
                            $buildingPush = array('building' => $building, 'startPosition' => $basePosition, 'endPosition' => $lastPosition);                                    
                            $this->building[] = $buildingPush;
                        }                        
                    }
                    elseif( $element['type'] === 'Precedes' ){                        
                    $movingPosition = $basePosition - 1;
                        while( $movingPosition>=0 && $this->test_helper($movingPosition,'Building') === TRUE ){                         
                            $movingPosition--;
                        }
                        $lastPosition = $movingPosition + 1;//add 1 to get the actual last position in the array                                               
                        
                        $find = FALSE;
                        foreach ($this->building as &$buildingElement){//Merge result to the previous results matching the conditions
                            if( $buildingElement['startPosition'] === $basePosition && $element['class'] === 'Building Type'){//Overlapping
                                $buildingElement['startPosition'] = $lastPosition;
                                $buildingElement['building'] = $this->text_builder($buildingElement['endPosition'], $lastPosition, $address_array);
                                $find = TRUE;                            
                            }
                            
                            if( $buildingElement['endPosition'] === $lastPosition && $element['class'] === 'Building Type'){//Overlapping
                                $buildingElement['endPosition'] = $basePosition;
                                $buildingElement['building'] = $this->text_builder($basePosition, $buildingElement['startPosition'], $address_array);
                                $find = TRUE;                            
                            }
                            
                            if( $buildingElement['endPosition'] === ($lastPosition - 1) && $element['class'] === 'Building Type'){//Merge two building type
                                $buildingElement['endPosition'] = $basePosition;
                                $buildingElement['building'] = $this->text_builder($basePosition, $buildingElement['startPosition'], $address_array);
                                $find = TRUE;                            
                            }
                            
                            if( $buildingElement['startPosition']<=$lastPosition && $buildingElement['endPosition']>=$lastPosition){//check if one of the position is already in the street array
                                $buildingElement['endPosition'] = $basePosition;
                                $buildingElement['building'] = $this->text_builder($basePosition, $buildingElement['startPosition'], $address_array);
                                $find = TRUE;
                            }
    
                            if( $buildingElement['startPosition']<=$lastPosition && $buildingElement['endPosition']>=$basePosition){//check if the word parsing for building already inside the array
                                $find = TRUE;//Avoid from adding in the list                            
                            }
                        }
                        unset($buildingElement);
                                                        
                        if( $find === FALSE ){
                            $building = $this->text_builder($basePosition, $lastPosition, $address_array);
                            $buildingPush = array('building' => $building, 'startPosition' => $lastPosition, 'endPosition' => $basePosition);                                    
                            $this->building[] = $buildingPush;                            
                        }
                    }
                }
            }
        $i++;
        }
        
        //Final Checking of parsed building
        foreach( $this->building as &$building_check ){
                $building_part = '';
                $street_part_startPosition = 0;
                $street_part_endPosition = 0;
            if( $address_array[$building_check['startPosition']] === '#' && preg_match('/ [0-9\-]+ /i', ' ' . $address_array[$building_check['startPosition'] + 1].' ') === 1 ){
                //Add matched building part to Street part container                
                $building_part = $this->text_builder($building_check['startPosition'] + 1, $building_check['startPosition'], $address_array);
                $street_part_startPosition = $building_check['startPosition'];
                $street_part_endPosition = $building_check['startPosition'] + 1;
                $this->building_part[] = array('building_part' => $building_part, 'startPosition' => $street_part_startPosition, 'endPosition' => $street_part_endPosition);
                //Update current position of the matched street
                $building_check['startPosition'] = $building_check['startPosition'] + 2;
                $building_check['building'] = $this->text_builder($building_check['endPosition'], $building_check['startPosition'], $address_array);                
            }
            //original if( (preg_match('/ #[0-9]+ /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ #[0-9]+\-[a-zA-Z] /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ (rm|rm.)[0-9]+ /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ (rm|rm.)[0-9]+\-[a-zA-Z] /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) ){             
            if( (preg_match('/ #[0-9]+ /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ #[0-9]+\-[a-zA-Z] /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ (rm|rm.)[0-9]+ /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ (rm|rm.)[0-9]+\-[a-zA-Z] /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ [0-9]+\-[a-z] /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ [0-9]+[a-z] /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ [0-9]+\-[0-9]+ /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) || (preg_match('/ [0-9]+ /i', ' ' . $address_array[$building_check['startPosition']] . ' ') === 1) ){             
                //Add matched street part to Street part container
                $building_part = $this->text_builder($building_check['startPosition'], $building_check['startPosition'], $address_array);
                $street_part_startPosition = $building_check['startPosition'];
                $street_part_endPosition = $building_check['startPosition'];
                $this->building_part[] = array('building_part' => $building_part, 'startPosition' => $street_part_startPosition, 'endPosition' => $street_part_endPosition);                
                //Update current position of the matched street
                $building_check['startPosition'] = $building_check['startPosition'] + 1;
                $building_check['building'] = $this->text_builder($building_check['endPosition'], $building_check['startPosition'], $address_array);
            }
        }
        unset ($building_check);        
    }
    //--------------------------------------------------
    
    /**
     * Method for parsing address part (building_part)
     * Will be process if given address string is set
     *
    */
    
    private function parse_building_part(){        
        //note: array position starts from 0
        $address_array = $this->address_array;
        
        $i = 0;
        $addressCount = count($address_array);//Get the length of the array
        while( $i<$addressCount ){     
            if( $this->is_building_part($address_array[$i]) ){
                $buildingUnitPush = array('building_part' => $address_array[$i], 'startPosition' => $i, 'endPosition' => $i);
                if( $this->is_not_buildingpart($i) === TRUE && $this->is_not_area($i) && $this->is_not_building($i) && $this->is_not_buildingpart($i) && $this->is_not_street($i) ){
                    $this->building_part[] = $buildingUnitPush;                    
                }
            }       
            else{             
            foreach (self::$helper_array_global as $element ){
                if( $element['class'] === 'Building Part' && (strtolower(trim($element['word'])) === strtolower($address_array[$i])) && $this->is_not_area($i) && $this->is_not_building($i) && $this->is_not_buildingpart($i)  && $this->is_not_street($i) && $this->is_not_street_part($i)){//check array helper with building class and match the word                                        
                    $basePosition = $i;
                    $movingPosition = 0;
                    $buildingUnitPush = array();                                       
                    $unit = '';
                                        
                    if( $element['type'] === 'Follows' ){                     
                    $movingPosition = $basePosition + 1;
                    
                        while( $movingPosition<$addressCount && $this->test_helper($movingPosition,'Unit') === TRUE ){                         
                            $movingPosition++;
                        }
                        $lastPosition = $movingPosition - 1;//deduct 1 to get the actual last position in the array
                        
                        $find = FALSE;
                        foreach ($this->building_part as &$buildingUnitElement){//Merge result to the previous results matching the conditions
                            if( $buildingUnitElement['endPosition'] === $basePosition && $element['class'] === 'Building Part'){//Overlapping
                                $buildingUnitElement['endPosition'] = $lastPosition;
                                $buildingUnitElement['building_part'] = $this->text_builder($buildingUnitElement['endPosition'], $basePosition, $address_array);
                                $find = TRUE;                            
                            }
                            if( $buildingUnitElement['startPosition']<=$basePosition && $buildingUnitElement['endPosition']>=$lastPosition){//check if the word parsing for building already inside the array
                                $find = TRUE;//Avoid from adding in the list                            
                            }                        
                        }
                        unset($buildingUnitElement);
                                                        
                        if( $find === FALSE ){
                            $unit = $this->text_builder($lastPosition, $basePosition, $address_array);
                            $buildingUnitPush = array('building_part' => $unit, 'startPosition' => $basePosition, 'endPosition' => $lastPosition);
                            $this->building_part[] = $buildingUnitPush;                            
                        }
                    }
                    elseif( $element['type'] === 'Precedes' ){
                        
                    $movingPosition = $basePosition - 1;
                        while( $movingPosition>=0 && $this->test_helper($movingPosition,'Unit') === TRUE ){                         
                            $movingPosition--;
                        }
                        $lastPosition = $movingPosition + 1;//add 1 to get the actual last position in the array               
                        
                        $find = FALSE;
                        foreach ($this->building_part as &$buildingUnitElement){//Merge result to the previous results matching the conditions
                            if( $buildingUnitElement['startPosition'] === $basePosition && $element['class'] === 'Building Part'){//Overlapping
                                $buildingUnitElement['startPosition'] = $lastPosition;
                                $buildingUnitElement['building_part'] = $this->text_builder($buildingUnitElement['endPosition'], $lastPosition, $address_array);
                                $find = TRUE;                                
                            }                            
                            if( $buildingUnitElement['startPosition']<=$lastPosition && $buildingUnitElement['endPosition']>=$basePosition){//check if the word parsing for building already inside the array
                                $find = TRUE;//Avoid from adding in the list
                            }                        
                        }
                        unset($buildingUnitElement);
                                                        
                        if( $find === FALSE ){
                            $unit = $this->text_builder($basePosition, $lastPosition, $address_array);
                            $buildingUnitPush = array('building_part' => $unit, 'startPosition' => $lastPosition, 'endPosition' => $basePosition);                                    
                            $this->building_part[] = $buildingUnitPush;                            
                        }
                    }                
                }
            }
            }
        $i++;
        }

        //This part will check Building part that's the startPosition is uqual to endPosition and is_not_building_part followed by number        
        foreach( $this->building_part as &$building_part_check ){
            if( ($building_part_check['endPosition'] + 1) < count($this->address_array) ){
                if( ($building_part_check['startPosition'] === $building_part_check['endPosition'] && $this->is_building_part($building_part_check['startPosition']) === FALSE && ((preg_match('/ [0-9\-]+ /i', ' '. $this->address_array[$building_part_check['endPosition'] + 1].' ') === 1) || (preg_match('/ [0-9]+\-[a-z] /i', ' '. $this->address_array[$building_part_check['endPosition'] + 1].' ') === 1) || (preg_match('/ [0-9]+[a-z] /i', ' '. $this->address_array[$building_part_check['endPosition'] + 1].' ') === 1))) ||
                   ($building_part_check['startPosition'] === $building_part_check['endPosition'] && preg_match('/ (level|lvl) /i', ' ' . $building_part_check['building_part'] . ' ') == TRUE && (preg_match('/ [a-z] /i', ' ' . $this->address_array[$building_part_check['endPosition'] + 1] . ' ') === 1))){
                    $item_updated = FALSE;                    
                        foreach( $this->street as &$street_check ){
                            if( ! $this->is_not_street($building_part_check['endPosition'] + 1) && ($building_part_check['endPosition'] + 1) == $street_check['startPosition'] ){
                                $street_check['startPosition'] = $street_check['startPosition'] + 1;
                                $street_check['street'] = $this->text_builder($street_check['endPosition'], $street_check['startPosition'], $address_array);
                                $item_updated = TRUE;
                            }
                        }
                        unset($street_check);
                        
                        foreach( $this->building as &$building_check ){
                            if( ! $this->is_not_building($building_part_check['endPosition'] + 1) && ($building_part_check['endPosition'] + 1) == $building_check['startPosition'] ){
                                $building_check['startPosition'] = $building_check['startPosition'] + 1;
                                $building_check['building'] = $this->text_builder($building_check['endPosition'], $building_check['startPosition'], $address_array);
                                $item_updated = TRUE;
                            }                            
                        }
                        unset($building_check);
                        
                        foreach( $this->area as &$area_check ){
                            if( ! $this->is_not_area($building_part_check['endPosition'] + 1) && ($building_part_check['endPosition'] + 1) == $area_check['startPosition'] ){
                                $area_check['startPosition'] = $area_check['startPosition'] + 1;
                                $area_check['area'] = $this->text_builder($area_check['endPosition'], $area_check['startPosition'], $address_array);
                                $item_updated = TRUE;
                            }
                        }
                        unset($area_check);
                        
                        foreach( $this->building_part as $building_part_key => &$buildingpart_check ){
                            if( ! $this->is_not_buildingpart($building_part_check['endPosition'] + 1) && $buildingpart_check['startPosition'] != $buildingpart_check['endPosition'] && ($building_part_check['endPosition'] + 1) == $buildingpart_check['startPosition'] ){                                
                                $buildingpart_check['startPosition'] = $buildingpart_check['startPosition'] + 1;
                                $buildingpart_check['building_part'] = $this->text_builder($buildingpart_check['endPosition'], $buildingpart_check['startPosition'], $address_array);
                                $item_updated = TRUE;
                            }
                            
                            if($buildingpart_check['startPosition'] == $buildingpart_check['endPosition'] && ($building_part_check['endPosition'] + 1) == $buildingpart_check['startPosition'] ){                                    
                                unset($this->building_part[$building_part_key]);
                                $item_updated = TRUE;
                            }
                        }
                        unset($buildingpart_check);
                        
                        foreach( $this->street_part as $street_part_key => &$street_part_check ){
                            if( ($building_part_check['endPosition'] + 1) === $street_part_check['startPosition'] ){                                
                                if($street_part_check['startPosition'] == $street_part_check['endPosition']){                                    
                                    unset($this->street_part[$street_part_key]);
                                    $item_updated = TRUE;
                                }
                                else{                                    
                                    $buildingpart_check['startPosition'] = $buildingpart_check['startPosition'] + 1;
                                    $buildingpart_check['building_part'] = $this->text_builder($buildingpart_check['endPosition'], $buildingpart_check['startPosition'], $address_array);
                                    $item_updated = TRUE;
                                }
                            }
                        }
                        unset($street_part_check);                        
                        
                        //Update building part
                        if($item_updated){
                            $building_part_check['endPosition'] = $building_part_check['endPosition'] + 1;  
                            $building_part_check['building_part'] = $this->text_builder($building_part_check['endPosition'], $building_part_check['startPosition'], $address_array);                            
                        }
                }                
            }
        }

    }
    //--------------------------------------------------
    
    /**
     * Method for parsing address part (area)
     * Will be process if given address string is set
     *
    */
    
    private function parse_area(){//****
        //note: array position starts from 0
        $address_array = $this->address_array;
        
        $i = 0;
        $addressCount = count($address_array);//Get the length of the array
        while( $i<$addressCount ){
        
            foreach (self::$helper_array_global as $element ){
                if( ($element['class'] === 'Area' || $element['class'] === 'VTC' ) && (strtolower(trim($element['word'])) === strtolower($address_array[$i]))){//check array helper with Area class and match the word
                    
                    $forceFollows = FALSE;
                    if( ($i + 1) < $addressCount ){
                        if( $this->verify_prepositions($address_array[$i + 1]) ){
                            $forceFollows = TRUE;
                        }
                    }
                    
                    $basePosition = $i;
                    $area = $address_array[$i];
                    $areaPush = array();
                    $movingPosition = 0;
    
                    if( $element['type'] === 'Follows' || $forceFollows === TRUE){//if( $element['type'] === 'Follows' ){                        
                        $movingPosition = $basePosition + 1;
                        while( $movingPosition<$addressCount && $this->test_helper($movingPosition,'Area') === TRUE){//If return FALSE then the loop will stop                            
                            $movingPosition++;
                        }
                        $lastPosition = $movingPosition - 1;//deduct 1 to get the actual last position in the array
                        //echo 'base: '.$basePosition.'<br/>last: '.$lastPosition.'<br/>element category: '.$element['correct_word'].'<br/>class: '.$element['class'].'<br/>';
                        $find = FALSE;
                        foreach ($this->area as &$areaElement ){                            
                            
                            if( $areaElement['endPosition'] === $basePosition && $element['class'] === 'Area'){//Merge result to the previous results matching the conditions
                                $areaElement['endPosition'] = $lastPosition;
                                $areaElement['area'] = $this->text_builder($lastPosition, $areaElement['startPosition'], $address_array);
                                $areaElement['category'] = $element['correct_word'];//added 12-05-2013
                                $find = TRUE;                            
                            }
                            
                            //added 12-06-2013
                            if( $basePosition >= $areaElement['startPosition'] &&
                               $basePosition <= $areaElement['endPosition'] &&
                               $lastPosition > $areaElement['endPosition'] &&
                               $element['class'] === 'VTC' &&
                               $element['correct_word'] === $areaElement['category']){//Merge result to the previous results matching the conditions                                
                                $areaElement['endPosition'] = $lastPosition;
                                $areaElement['area'] = $this->text_builder($lastPosition, $areaElement['startPosition'], $address_array);
                                $areaElement['category'] = $element['correct_word'];
                                $find = TRUE;                            
                            }//end added
                            
                            if( $basePosition >= $areaElement['startPosition'] && $lastPosition <= $areaElement['endPosition']){//check if the word parsing for area already inside the array
                                $find = TRUE;//Avoid from adding in the list                            
                            }
                        }
                        unset($areaElement);
                                                        
                        if( $find === FALSE ){
                            $area = $this->text_builder($lastPosition, $basePosition, $address_array);
                            //$areaPush = array('area' => $area, 'startPosition' => $basePosition, 'endPosition' => $lastPosition);
                            $areaPush = array('area' => $area, 'startPosition' => $basePosition, 'endPosition' => $lastPosition, 'category' => $element['correct_word']);//modified 12-05-2013                                    
                            $this->area[] = $areaPush;                            
                        }                        
                    }
                    elseif( $element['type'] === 'Precedes' && $forceFollows === FALSE){//elseif( $element['type'] === 'Precedes' ){
                        $movingPosition = $basePosition - 1;                
                        while( $movingPosition>=0 && $this->test_helper($movingPosition,'Area') === TRUE ){                         
                            $movingPosition--;
                        }
                        $lastPosition = $movingPosition + 1;//add 1 to get the actual last position in the array
                        
                        $find = FALSE;
                        foreach ($this->area as &$areaElement){//Merge result to the previous results matching the conditions
                            if( $areaElement['startPosition'] === $basePosition && $element['class'] === 'Area' ){
                                $areaElement['startPosition'] = $lastPosition;
                                $areaElement['area'] = $this->text_builder($areaElement['endPosition'], $lastPosition, $address_array);
                                $areaElement['category'] = $element['correct_word'];//added 12-05-2013
                                $find = TRUE;                            
                            }
                            
                            if( $areaElement['startPosition']<=$lastPosition && $areaElement['endPosition']>=$basePosition){//check if the word parsing for Area already inside the array
                                $find = TRUE;//Avoid from adding in the list                            
                            }
                        }
                        unset($areaElement);
                                                        
                        if( $find === FALSE ){                                     
                            $area = $this->text_builder($basePosition, $lastPosition, $address_array);
                            $areaPush = array('area' => $area, 'startPosition' => $lastPosition, 'endPosition' => $basePosition, 'category' => $element['correct_word']);//modified 12-05-2013                                   
                            $this->area[] = $areaPush;                            
                        }                        
                    }
                    else{
                        $areaPush = array();
                    }
                }
            }        
        $i++;
        }    
    }
    //--------------------------------------------------
    
    /**
     * Method that identifies the commas position in the address array
     *
    */
    private function comma_position(){
        $comma_check = array();
        foreach( $this->address_array as $key => $comma_check ){
            if( $comma_check === ',' ){
                $this->comma_reference[] = array('position' => $key);        
            }
        }
    }
    //--------------------------------------------------
    
    /**
     * Method that return if the given position is comma
     *
     * @param       int() position to check if comma
     *
    */
    
    private function is_comma($position){//Check if the given position is represents comma character       
        $find = FALSE;
        if( count($this->comma_reference) >= 1 ){
            foreach( $this->comma_reference as $commaCheck ){
                if( $position === $commaCheck['position'] ){
                    $find = TRUE;            
                }
            }
        }
    return $find;
    }    
    //--------------------------------------------------
    
    /**
     * Initilize standard array from standard database helper
     * Sets self::$standard_array_global static property
     *
    */
    
    static function initialize_standard(){
        $standardArray = array();    
        $search = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if( $search->connect_errno ){
            echo "Failed to connect to MySQL: (" . $search->connect_errno . ") " . $search->connect_error;
            $search->close();
        }
        $query ="SELECT * FROM tbl_standard_helper;";
        $values = $search->query($query);
        $recordNumber = $values->num_rows;
        if( $recordNumber != 0 ){
            while( $row = $values->fetch_assoc() ){
                $standardArray[] = $row;
            }
        }
        else{
            echo 'No records in helper database!';
        }
        self::$standard_array_global = $standardArray;        
        $search->close();
    }
    //--------------------------------------------------
    
    /**
     * Method that append/prepend exceptions and update property
     * Description: append/prepend exceptions to object properties affected
     * 
     * @param       array() contains matches and exceptions, accepts output from verify street     
     * @example     prepend_exceptions(array(array('matches' { ... 'exceptions' {...} ... }))
     * 
    */
    
    public function prepend_exceptions($input){
        foreach( $input as $input_matches ){            
            foreach( $input_matches as $input_matches_check ){                
                if( isset($input_matches_check['exceptions']) ){                
                    foreach( $input_matches_check['exceptions'] as $input_check ){                        
                        if( $input_check['pos'] == 'start' ){
                            $property = $this->map_address_position($input_check['startPosition'] - 1);
                            if( $property != 'excess' && $property != 'corner_reference' && $property != 'comma_reference' && $property != '' ){
                                foreach( $this->$property as &$property_check ){
                                    if( ($input_check['startPosition'] - 1) >= $property_check['startPosition'] && ($input_check['startPosition'] - 1) <= $property_check['endPosition'] ){
                                        $property_check[$property] = $this->text_builder($input_check['endPosition'], $property_check['startPosition']);
                                        $property_check['startPosition'] = $property_check['startPosition'];
                                        $property_check['endPosition'] = $input_check['endPosition'];
                                        if( $property == 'street_part' ){
                                            $property_check['relatedTo'] = $input_check['startPosition'] + 1;
                                        }
                                    }
                                }
                            unset($property_check);
                            }
                        }
                        elseif( $input_check['pos'] == 'end' ){
                            $property = $this->map_address_position($input_check['startPosition'] + 1);
                            if( $property != 'excess' && $property != 'corner_reference' && $property != 'comma_reference' && $property != '' ){
                                foreach( $this->$property as &$property_check ){
                                    if( ($input_check['startPosition'] + 1) >= $property_check['startPosition'] && ($input_check['startPosition'] + 1) <= $property_check['endPosition'] ){
                                        $property_check[$property] = $this->text_builder($property_check['endPosition'], $input_check['startPosition']);
                                        $property_check['startPosition'] = $input_check['startPosition'];
                                        $property_check['endPosition'] = $property_check['endPosition'];
                                        if( $property == 'street_part' ){
                                            $property_check['relatedTo'] = $input_check['startPosition'] + 1;
                                        }
                                    }
                                }
                            unset($property_check);
                            }                            
                        }
                        elseif( $input_check['pos'] == 'middle' ){
                            
                        }
                        elseif( $input_check['pos'] == 'none' ){
                            
                        }
                        else{
                            
                        }                        
                    }                
                }                
            }
        }
    }
    //--------------------------------------------------
    
    /**
     * Method that drop exceptions and update property matches
     * Description: update property that matched input matches, drop exceptions and update Object Properties affected
     *     
     * @param       array() contains matches and exceptions, accepts output from verify street
     * @example     update_matches(array(array('matches' { ... 'exceptions' {...} ... }))
     *
    */
    
    public function update_matches($input){        
        foreach( $input as $input_matches ){            
            foreach( $input_matches as $input_matches_check ){
                if( isset($input_matches_check['match']) ){   
                    foreach( $input_matches_check['match'] as $key => $input_check ){                        
                        reset($input_check);
                        $propery = key($input_check);
                        
                        /* street */
                        if( $propery == 'street' ){
                            $find = FALSE;
                            foreach( $this->street as $key_street => &$street_check ){        
                                if( $input_check['startPosition'] >= $street_check['startPosition'] && $input_check['startPosition'] <= $street_check['endPosition'] ){
                                    $street_check = array('street' => $input_check['street'], 'startPosition' => $input_check['startPosition'], 'endPosition' => $input_check['endPosition'], 'comment' => 'street verified');
                                    $find = TRUE;        
                                }                                
                            }
                            if( ! $find ){
                                $this->street[] = array('street' => $input_check['street'], 'startPosition' => $input_check['startPosition'], 'endPosition' => $input_check['endPosition'], 'comment' => 'street verified');                    
                            }
                            unset($street_check);
                        }
                        
                        /* building */
                        if( $propery == 'building' ){
                            $find = FALSE;
                            foreach( $this->building as $key_building => &$building_check ){        
                                if( $input_check['startPosition'] >= $building_check['startPosition'] && $input_check['startPosition'] <= $building_check['endPosition'] ){
                                    $building_check = array('building' => $input_check['building'], 'startPosition' => $input_check['startPosition'], 'endPosition' => $input_check['endPosition'], 'comment' => 'building verified');
                                    $find = TRUE;        
                                }                                
                            }
                            if( ! $find ){
                                $this->building[] = array('building' => $input_check['building'], 'startPosition' => $input_check['startPosition'], 'endPosition' => $input_check['endPosition'], 'comment' => 'building verified');
                            }
                            unset($building_check);
                        }
                        
                        /* area */
                        if( $propery == 'area' ){
                            $find = FALSE;
                            foreach( $this->area as $key_area => &$area_check ){        
                                if( $input_check['startPosition'] >= $area_check['startPosition'] && $input_check['startPosition'] <= $area_check['endPosition'] ){                                
                                    $area_check = array('area' => $input_check['area'], 'startPosition' => $input_check['startPosition'], 'endPosition' => $input_check['endPosition'], 'comment' => 'area verified');
                                    $find = TRUE;        
                                }
                            }
                            if( ! $find ){
                                $this->area[] = array('area' => $input_check['area'], 'startPosition' => $input_check['startPosition'], 'endPosition' => $input_check['endPosition'], 'comment' => 'area verified');
                            }
                            unset($area_check);
                        }
                    }
                }
                else{
                    //Remove unverified parsed values
                    foreach( $input_matches_check['exceptions'] as $key => $input_check ){                        
                        reset($input_check);
                        $propery = key($input_check);                        
                        
                        /* street */
                        if( $propery === 'street' ){
                            foreach( $this->street as $key_street => &$street_check ){        
                                if( $input_check['startPosition'] >= $street_check['startPosition'] && $input_check['startPosition'] <= $street_check['endPosition'] ){                                    
                                    unset($this->street[$key_street]);                                    
                                }                                
                            }
                            unset($street_check);
                        }
                        
                        /* building */
                        if( $propery === 'building' ){
                            foreach( $this->building as $key_building => &$building_check ){        
                                if( $input_check['startPosition'] >= $building_check['startPosition'] && $input_check['startPosition'] <= $building_check['endPosition'] ){
                                    unset($this->building[$key_building]);
                                }                                
                            }
                            unset($building_check);
                        }
                        
                        /* area */
                        if( $propery === 'area' ){
                            foreach( $this->area as $key_area => &$area_check ){        
                                if( $input_check['startPosition'] >= $area_check['startPosition'] && $input_check['startPosition'] <= $area_check['endPosition'] ){                                
                                    unset($this->area[$key_area]);
                                }                                
                            }                        
                            unset($area_check);
                        }
                    }
                }
            }
        }
    }
    //--------------------------------------------------
    
    /**
     * Method which returns a mapped positions of address array 
     *
    */
    
    public function get_property_map(){
        $address_map = array();        
        $address_map['excess'] = array();
        $address_map['building_part'] = array();
        $address_map['building'] = array();
        $address_map['street_part'] = array();
        $address_map['street'] = array();
        $address_map['area'] = array();
        $address_map['corner_reference'] = array();
        $address_map['comma_reference'] = array();
        
        $address_array_count = count($this->address_array);
        $init = 0;
        
        while( $init < $address_array_count ){
            $find = FALSE;
            
            /* building part */
            foreach( $this->building_part as $building_part_check ){        
                if( $init >= $building_part_check['startPosition'] && $init <= $building_part_check['endPosition'] ){
                    $address_map['building_part'][] = $init;
                    $find = TRUE;
                }
            }
            
            /* building */
            foreach( $this->building as $building_check ){        
                if( $init >= $building_check['startPosition'] && $init <= $building_check['endPosition'] ){
                    $address_map['building'][] = $init;
                    $find = TRUE;
                }
            }
            
            /* street part */
            foreach( $this->street_part as $street_part_check ){        
                if( $init >= $street_part_check['startPosition'] && $init <= $street_part_check['endPosition'] ){
                    $address_map['street_part'][] = $init;
                    $find = TRUE;
                }
            }
            
            /* street */
            foreach( $this->street as $street_check ){        
                if( $init >= $street_check['startPosition'] && $init <= $street_check['endPosition'] ){
                    $address_map['street'][] = $init;
                    $find = TRUE;
                }
            }
            
            /* area */
            foreach( $this->area as $area_check ){        
                if( $init >= $area_check['startPosition'] && $init <= $area_check['endPosition'] ){
                    $address_map['area'][] = $init;
                    $find = TRUE;
                }
            }
            
            /* corner reference */
            foreach( $this->corner_reference as $corner_check ){        
                if( $init >= $corner_check['startPosition'] && $init <= $corner_check['endPosition'] ){
                    $address_map['corner_reference'][] = $init;
                    $find = TRUE;
                }
            }
            
            /* comma reference */
            foreach( $this->comma_reference as $comma_check ){        
                if( $init === $comma_check['position'] ){
                    $address_map['comma_reference'][] = $init;
                    $find = TRUE;
                }
            }
            
            if( ! $find ){
                $address_map['excess'][] = $init;
            }
            
            $init++;
        }
        
        return $address_map;
    }
    //--------------------------------------------------
    
    /**
     * Method which returns which property(street, street_part, building, buiding_part, area) owns the given position
     *
     * @param       int() position     
     *
    */
    
    public function map_address_position($input_position ){
        $map = $this->get_property_map();        
        foreach( $map as $key => $map_property ){
            foreach( $map_property as $map_property_item ){
                if( $input_position == $map_property_item ){                    
                    return $key;
                }
            } 
        }
    }
 
    //--------------------------------------------------
    
    /**
     * Method for parsing address part (street_part)
     * Will be process if given address string is set
     *
    */
    
    private function parse_street_part(){        
        //note: array position starts from 0
        $address_array = $this->address_array;
        
        $i = 0;
        $addressCount = count($address_array);//Get the length of the array
        while( $i<$addressCount ){     
            foreach (self::$helper_array_global as $element ){
                if( $element['class'] === 'Street Part' && (strtolower(trim($element['word'])) === strtolower($address_array[$i])) && $this->is_not_area($i) && $this->is_not_building($i) && $this->is_not_buildingpart($i)  && $this->is_not_street($i) && $this->is_not_street_part($i)){//check array helper with class and match the word                                        
                    $basePosition = $i;
                    $movingPosition = 0;                    
                    $streetPartPush = array();                                       
                    $streetPart = '';
                                        
                    if( $element['type'] === 'Follows' ){                     
                    $movingPosition = $basePosition + 1;
                    
                        while( $movingPosition<$addressCount && $this->test_helper($movingPosition,'Street Part') === TRUE ){                         
                            $movingPosition++;
                        }
                        $lastPosition = $movingPosition - 1;//deduct 1 to get the actual last position in the array
                        
                        $find = FALSE;
                        foreach ($this->street_part as &$streetPartElement){//Merge result to the previous results matching the conditions
                            if( $streetPartElement['endPosition'] === $basePosition && $element['class'] === 'Building Part'){
                                $streetPartElement['endPosition'] = $lastPosition;
                                $streetPartElement['street_part'] = $this->text_builder($streetPartElement['endPosition'], $basePosition, $address_array);
                                $find = TRUE;                            
                            }
                            if( $streetPartElement['startPosition']<=$basePosition && $streetPartElement['endPosition']>=$lastPosition){//check if the word parsing for building already inside the array
                                $find = TRUE;//Avoid from adding in the list                            
                            }                        
                        }
                        unset($streetPartElement);
                                                        
                        if( $find === FALSE ){
                            $streetPart = $this->text_builder($lastPosition, $basePosition, $address_array);
                            $streetPartPush = array('street_part' => $streetPart, 'startPosition' => $basePosition, 'endPosition' => $lastPosition);
                            $this->street_part[] = $streetPartPush;                            
                        }
                    }
                    elseif( $element['type'] === 'Precedes' ){
                        
                    $movingPosition = $basePosition - 1;
                        while( $movingPosition>=0 && $this->test_helper($movingPosition,'Street Part') === TRUE ){                         
                            $movingPosition--;
                        }
                        $lastPosition = $movingPosition + 1;//add 1 to get the actual last position in the array               
                        
                        $find = FALSE;
                        foreach ($this->street_part as &$streetPartElement){//Merge result to the previous results matching the conditions
                            if( $streetPartElement['startPosition'] === $basePosition && $element['class'] === 'Building Part'){//Overlapping
                                $streetPartElement['startPosition'] = $lastPosition;
                                $streetPartElement['street_part'] = $this->text_builder($streetPartElement['endPosition'], $lastPosition, $address_array);
                                $find = TRUE;                                
                            }                            
                            if( $streetPartElement['startPosition']<=$lastPosition && $streetPartElement['endPosition']>=$basePosition){//check if the word parsing for building already inside the array
                                $find = TRUE;//Avoid from adding in the list
                            }                        
                        }
                        unset($streetPartElement);
                                                        
                        if( $find === FALSE ){
                            $streetPart = $this->text_builder($basePosition, $lastPosition, $address_array);
                            $streetPartPush = array('street_part' => $streetPart, 'startPosition' => $lastPosition, 'endPosition' => $basePosition);                                    
                            $this->street_part[] = $streetPartPush;                            
                        }
                    }                
                }
            }

        $i++;
        }
        //This part will check Street part which is endPosition is uqual to startPosition of parsed address part
        foreach( $this->street_part as &$street_part_check ){

            if( ($street_part_check['endPosition'] + 1) < count($this->address_array) ){
                if( ($street_part_check['startPosition'] === $street_part_check['endPosition'] && ((preg_match('/ [0-9\-]+ /i', ' '. $this->address_array[$street_part_check['endPosition'] + 1].' ') === 1) || (preg_match('/ [0-9]+\-[a-z] /i', ' '. $this->address_array[$street_part_check['endPosition'] + 1].' ') === 1) || (preg_match('/ [0-9]+[a-z] /i', ' '. $this->address_array[$street_part_check['endPosition'] + 1].' ') === 1))) ){
                    $item_updated = FALSE;                    
                        foreach( $this->street as &$street_check ){
                            if( ! $this->is_not_street($street_part_check['endPosition'] + 1) && ($street_part_check['endPosition'] + 1) == $street_check['startPosition'] ){
                                $street_check['startPosition'] = $street_check['startPosition'] + 1;
                                $street_check['street'] = $this->text_builder($street_check['endPosition'], $street_check['startPosition'], $address_array);
                                $item_updated = TRUE;
                            }
                        }
                        unset($street_check);
                        
                        foreach( $this->building as &$building_check ){
                            if( ! $this->is_not_building($street_part_check['endPosition'] + 1) && ($street_part_check['endPosition'] + 1) == $building_check['startPosition'] ){
                                $building_check['startPosition'] = $building_check['startPosition'] + 1;
                                $building_check['building'] = $this->text_builder($building_check['endPosition'], $building_check['startPosition'], $address_array);
                                $item_updated = TRUE;
                            }                            
                        }
                        unset($building_check);
                        
                        foreach( $this->area as &$area_check ){
                            if( ! $this->is_not_area($street_part_check['endPosition'] + 1) && ($street_part_check['endPosition'] + 1) == $area_check['startPosition'] ){
                                $area_check['startPosition'] = $area_check['startPosition'] + 1;
                                $area_check['area'] = $this->text_builder($area_check['endPosition'], $area_check['startPosition'], $address_array);
                                $item_updated = TRUE;
                            }
                        }
                        unset($area_check);
                        
                        foreach( $this->building_part as $building_part_key => &$buildingpart_check ){
                            if( ($street_part_check['endPosition'] + 1) === $buildingpart_check['startPosition'] ){                                
                                if($buildingpart_check['startPosition'] == $buildingpart_check['endPosition']){                                    
                                    unset($this->street_part[$building_part_key]);
                                    $item_updated = TRUE;
                                }
                                else{                                    
                                    $buildingpart_check['startPosition'] = $street_part_check['startPosition'] + 1;
                                    $buildingpart_check['street_part'] = $this->text_builder($buildingpart_check['endPosition'], $buildingpart_check['startPosition'], $address_array);
                                    $item_updated = TRUE;
                                }
                            }
                        }
                        unset($buildingpart_check);                        

                        foreach( $this->street_part as $street_part_key => &$streetpart_check ){
                            if( ($street_part_check['endPosition'] + 1) === $streetpart_check['startPosition'] ){                                
                                if($streetpart_check['startPosition'] == $streetpart_check['endPosition']){                                    
                                    unset($this->street_part[$street_part_key]);
                                    $item_updated = TRUE;
                                }
                                else{                                    
                                    $streetpart_check['startPosition'] = $street_part_check['startPosition'] + 1;
                                    $streetpart_check['street_part'] = $this->text_builder($streetpart_check['endPosition'], $streetpart_check['startPosition'], $address_array);
                                    $item_updated = TRUE;
                                }
                            }
                        }
                        unset($streetpart_check);                                                
                        
                        //Update street part
                        if($item_updated){
                            $street_part_check['endPosition'] = $street_part_check['endPosition'] + 1;  
                            $street_part_check['street_part'] = $this->text_builder($street_part_check['endPosition'], $street_part_check['startPosition'], $address_array);                            
                        }
                }                
            }
        }
        
    }
    //--------------------------------------------------
    
    
    /**
     * Method that if the given input is roman
     *
    */
    static function is_roman_number($text){
        $text = trim($text);
        $text = ' ' . $text . ' ';
        $test = FALSE;
        if( preg_match('/ (I|X|V|L)+ /i', $text) === 1 && $test === FALSE ){
            $test = TRUE;
        }
        return $test;
    }
    
 
}
        
        

//End class