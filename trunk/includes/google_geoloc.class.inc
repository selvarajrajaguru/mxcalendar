<?php
// USER GOOGLE MAP API
//define("MAPS_HOST", "maps.google.com");
// YOUR GOOGLE MAP API KEY
//define("MAPS_KEY", ""); 


if(!class_exists("geoLocator")){
    class geoLocator
    {
     protected $request;
     public $apikey,$glat,$glong,$address,$db;
     // Debug Varible to output/trouble shooting
     var $debug = true;
        public function __construct() //could also use __init()
        {
            // Debug (output more info)
            $this->debug = false;
            // Google Maps API key
            $this->apikey = '';
            // Google Maps HOST (USER MAP API)
            $this->host = 'maps.google.com';
            // Google Maps Zoom Level
            $this->mapZoon = 13;
            // Google Maps Address to get geo-location for
            $this->address = '';
            // Google map api database
            //$this->db = $db;
            // Google Map Request String
            if($this->apikey)
            $this->request = 'http://'.$this->host.'/maps/geo?q=%s&output=xml&key='.$this->apikey;
            else
            $this->request = 'http://'.$this->host.'/maps/geo?q=%s&output=xml';
            // The return value
            $this->output = '';
            // The map JS Output
            $this->mapJSv2 = '';
            $this->mapJSv3 = '';
            // Map Location Var ID Counter
            $this->locID = 1;
        }
        
        public function getGEO($address = '')
        {
            //-- Reset the output on new request
            //if($address != $this->address)
            //    $this->output = '';
            
            if(empty($address)){
                //-- Couuld place Default address here 
                echo '<br />No address provided.<br />';
                exit;
            }
            else
                $this->address = $address;
                
                
            // Desired address
            $reqAddress= sprintf($this->request,$address);

                $xml = simplexml_load_file($reqAddress) or die("url not loading");
                $status = $xml->Response->Status->code;
                if($this->debug){
                    $this->output .= '<br />URL: '.$reqAddress.'<br />';
                    if($this->debug) var_dump($status);
                    $this->output .=  '<br />';
                }
                
                if (strcmp($status, "200") == 0) {
                  // Successful geocode
                  $geocode_pending = false;
                  $coordinates = $xml->Response->Placemark->Point->coordinates;
                  $coordinatesSplit = explode(",", $coordinates);
                  // Format: Longitude, Latitude
                  $lat = $coordinatesSplit[1];
                  $lng = $coordinatesSplit[0];
                  /*
                  $query = sprintf("UPDATE markers " .
                         " SET lat = '%s', lng = '%s' " .
                         " WHERE id = '%s' LIMIT 1;",
                         mysql_real_escape_string($lat),
                         mysql_real_escape_string($lng),
                         mysql_real_escape_string($id));
                  $update_result = mysql_query($query);
                  if (!$update_result) {
                    die("Invalid query: " . mysql_error());
                  }*/
                  
                  $this->output .=  '<a href="#" onClick="animate('.$lat.', '.$lng.'); return false">Longitude: '.$lng.'  Latitude: '.$lat.'</a><br />';
                  
                  if(!strlen($this->mapJSv2) && !strlen($this->mapJSv3)){
                    $this->mapJSv2 .= 'map.setCenter(new GLatLng('.$lat.', '.$lng.'), '.$this->mapZoom.');'."\n";
                    $this->mapJSv2 .= 'map.addOverlay(new GMarker(new GLatLng('.$lat.', '.$lng.')));'."\n";

                    $this->mapJSv3 .= 'var myOptions = {'."\n";
                    $this->mapJSv3 .= 'zoom: 13,'."\n";
                    $this->mapJSv3 .= 'center: new google.maps.LatLng('.$lat.', '.$lng.'),'."\n";
                    $this->mapJSv3 .= 'mapTypeId: google.maps.MapTypeId.ROADMAP'."\n";
                    $this->mapJSv3 .= '}'."\n";
                    $this->mapJSv3 .= 'map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);'."\n";
                    $this->mapJSv3 .= 'var defaultLoc = new google.maps.Marker({
                        position: new google.maps.LatLng('.$lat.','.$lng.'),
                        map: map,
                        title:"'.$address.'"});';
    
                  }else{
                    $this->mapJSv2 .= 'map.addOverlay(new GMarker(new GLatLng('.$lat.', '.$lng.')));'."\n";
                    
                    $this->mapJSv3 .= 'var loc'.$this->locID.' = new google.maps.Marker({
                        position: new google.maps.LatLng('.$lat.','.$lng.'),
                        map: map,
                        title:"'.$address.'"});';
                    $this->mapJSv3 .= 'bounds.extend(loc'.$this->locID.'.getPoint());'."\n";
                    
                    $this->locID++;
                  }
                  
                } else if (strcmp($status, "620") == 0) {
                  // sent geocodes too fast
                  $this->output .=  "Too many... too fast...<br />\n";
                } else {
                  // failure to geocode
                  $geocode_pending = false;
                  $this->output .=  "Address " . $address . " failed geocoding.<br />\n";
                  $this->output .=  "Received status " . $status . "<br />\n";
                }
        }
        
        public function bulkList()
        {
            // Initialize delay in geocode speed
            $delay = 0;

            
            // Iterate through the rows, geocoding each address
            while ($row = @mysql_fetch_assoc($result)) {
              $geocode_pending = true;
            
              while ($geocode_pending) {
                $address = $row["address"];
                $id = $row["id"];
                $request_url = sprintf($this->request,urlencode($address));
                $xml = simplexml_load_file($request_url) or die("url not loading");
            
                $status = $xml->Response->Status->code;
                if (strcmp($status, "200") == 0) {
                  // Successful geocode
                  $geocode_pending = false;
                  $coordinates = $xml->Response->Placemark->Point->coordinates;
                  $coordinatesSplit = split(",", $coordinates);
                  // Format: Longitude, Latitude, Altitude
                  $lat = $coordinatesSplit[1];
                  $lng = $coordinatesSplit[0];
            
                  $query = sprintf("UPDATE markers " .
                         " SET lat = '%s', lng = '%s' " .
                         " WHERE id = '%s' LIMIT 1;",
                         mysql_real_escape_string($lat),
                         mysql_real_escape_string($lng),
                         mysql_real_escape_string($id));
                  $update_result = mysql_query($query);
                  if (!$update_result) {
                    die("Invalid query: " . mysql_error());
                  }
                } else if (strcmp($status, "620") == 0) {
                  // sent geocodes too fast
                  $delay += 100000;
                } else {
                  // failure to geocode
                  $geocode_pending = false;
                  $this->output .=  "Address " . $address . " failed geocoding. ";
                  $this->output .=  "Received status " . $status . "\n";
                }
                usleep($delay);
              }
            }
        }
        
        public function bulkUpdate()
        {
            // Connect to the MySQL database
            $conn = mysql_connect("localhost", "jason", "secretpswd");
         
            // Select the database
            $db = mysql_select_db("googlemaps");
         
            // Query the table
            $query = "SELECT id, address, city, state FROM hospitals";
            $result = mysql_query($query) or die(mysql_error());
         
            // Loop through each row, submit HTTP request, output coordinates
            while (list($id, $address, $city, $state) = mysql_fetch_row($result))
            {
               $mapaddress = urlencode("$address $city $state");
         
               // Desired address
               $url = sprintf($this->request,urlencode($mapaddress));
         
               // Retrieve the URL contents
               $page = file_get_contents($url);
         
               // Parse the returned XML file
               $xml = new SimpleXMLElement($page);
         
               // Parse the coordinate string
               list($longitude, $latitude, $altitude) = explode(",", $xml->Response->Placemark->Point->coordinates);
         
               // Output the coordinates
               $this->output .=  "latitude: $latitude, longitude: $longitude <br />";
         
            }
        }
    }
}

//-- NOT USED CURRENTLY
class Connection {
    protected $link;
    private $server, $username, $password, $db;
    
    public function __construct($server, $username, $password, $db)
    {
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
        $this->connect();
    }
    
    private function connect()
    {
        $this->link = mysql_connect($this->server, $this->username, $this->password);
        mysql_select_db($this->db, $this->link);
    }
    
    public function __sleep()
    {
        return array('server', 'username', 'password', 'db');
    }
    
    public function __wakeup()
    {
        $this->connect();
    }
}


//-- MAPs API V2
/*
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAzr2EBOXUKnm_jVnk0OJI7xSosDVG8KKPE1-m51RBrvYughuyMxQ-i1QfUnH94QxWIa6N4U6MouMmBA"
            type="text/javascript"></script>
    <script type="text/javascript">
    var map;
    function initialize() {
      if (GBrowserIsCompatible()) {
        map = new GMap2(document.getElementById("map_canvas"));
       <?php
       echo $mygeoloc->mapJS;
       ?>
        map.setUIToDefault();
      }
    }
    function animate(lat,lon) {    
      map.panTo(new GLatLng(lat, lon));
    }
    </script>
*/
//-- MAPs API V3


?>