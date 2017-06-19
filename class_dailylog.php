<?php

class Procore_API{

    public function get_access_token(){
        $access_token = get_option( 'procore-token');
        if($access_token){
            return $access_token;
        }
        return false;
    }

    public function refresh_token($token){
        $ch = curl_init(); 
        // set url 
        curl_setopt($ch, CURLOPT_URL, "https://app.procore.com/oauth/token?grant_type=refresh_token&client_id=".CLIENT_ID."&client_secret=".CLIENT_SECRET."&refresh_token=".$token); 

        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_POST, 1);

        // $output contains the output string 
        $output = curl_exec($ch); 
        // close curl resource to free up system resources 
        curl_close($ch);
        
        $json = json_decode($output);
        if($json->access_token){
            update_option('procore-token', $json->refresh_token);
            return true;
        }
        return false;
    }

    public function get_companies($store = false){
        if($this->get_access_token()){
                $ch = curl_init(); 

            // set url 
            curl_setopt($ch, CURLOPT_URL, "https://app.procore.com/vapid/companies"); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$this->get_access_token()
            ));

            //return the transfer as a string 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            //curl_setopt($ch, CURLOPT_POST, 1);

            // $output contains the output string 
            $output = curl_exec($ch); 
            $json = json_decode($output, true);
            if(count($json) > 0 && isset($json[0]['id'])){
                if($store){
                    update_option('procore_companies', $output);
                }
                return $json;
            }
            // close curl resource to free up system resources 
            curl_close($ch);  
        }
        return false;
    }

    public function get_projects($company_id, $store = false){
        if($this->get_access_token()){
            $ch = curl_init(); 

            // set url 
            curl_setopt($ch, CURLOPT_URL, "https://app.procore.com/vapid/projects?company_id=".$company_id); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$this->get_access_token()
            ));

            //return the transfer as a string 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            //curl_setopt($ch, CURLOPT_POST, 1);

            // $output contains the output string 
            $output = curl_exec($ch);

            $json = json_decode($output, true);
            if(count($json) > 0 && isset($json[0]['id'])){
                if($store){
                    update_option('procore_projects', $output);
                }
                return $json;
            }
            // close curl resource to free up system resources 
            curl_close($ch);  
        }
        return false;
    }
    
    public function get_logs($log, $project_id, $date = '', $store = false){
        if($this->get_access_token()){
            $ch = curl_init(); 
            $query_date = '';
            if($date){
                if($log == "weather_logs"){
                    $query_date = '?start_date='.$date.'&end_date='.$date;
                }
                else{
                    $query_date = '?log_date='.$date;
                }
            }

            // set url 
            curl_setopt($ch, CURLOPT_URL, "https://app.procore.com/vapid/projects/".$project_id."/".$log.$query_date); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$this->get_access_token()
            ));

            //return the transfer as a string 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            //curl_setopt($ch, CURLOPT_POST, 1);

            // $output contains the output string 
            $output = curl_exec($ch);

            $json = json_decode($output, true);
            if(count($json) > 0 && isset($json[0]['id'])){
                if($store){
                    update_option('procore_'.$log, $output);
                }
                return $json;
            }
            // close curl resource to free up system resources 
            curl_close($ch);  
        }
        return false;
    }
}

add_shortcode('dailylog', 'procore_fetch_dailylog');
function procore_fetch_dailylog($atts){
    $args = shortcode_atts( array(
        'date' => '',
        'show'  => 'inspection',
        'project_id'    => 0
    ), $atts );
    
    $all_logs = array();
    $procore_api = new Procore_API();
    $log_types = explode(",", $args['show']);
    if($log_types){
        foreach($log_types as $log_type){
            $l_type = trim($log_type);
            $daily_logs = $procore_api->get_logs($l_type.'_logs', $args["project_id"], $args["date"]);
            if($daily_logs){
                foreach($daily_logs as $daily_log){
                    $all_logs[$l_type][] = $daily_log;
                }
            }
        }
    }

    
    var_dump($all_logs);
}

add_action('updated_option', function( $option_name, $old_value, $value ) {
     if($option_name == 'procore-token'){
        $client_id = 'bc1d2d378d6c0bc0aa2b5d0f172e0152c0868e2439e04adf12e46691ce04d65f';
        $client_secret = '36b6774f4b0b919edd0068494946e386c47343836007a92512af0a0f6c6caf13';
        $headless_url = 'urn:ietf:wg:oauth:2.0:oob';
        $ch = curl_init(); 
        // set url 
        curl_setopt($ch, CURLOPT_URL, "https://app.procore.com/oauth/token?grant_type=authorization_code&code=".get_option('procore-token')."&client_id=".$client_id."&client_secret=".$client_secret."&redirect_uri=".$headless_url); 

        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_POST, 1);

        // $output contains the output string 
        $output = curl_exec($ch); 
        // close curl resource to free up system resources 
        curl_close($ch);   
        $outputjson = json_decode($output);
        if($outputjson->access_token){
            $access_token = $outputjson->access_token;
            if (!session_id()) {
                session_start();
            }
            update_option('procore-token', $access_token);
        }
     }
}, 10, 3);