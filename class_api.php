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

    public function get_project($project_id, $store = false){
        if($this->get_access_token()){
            $companies = $this->get_companies();
            if(!$companies)
                return false;
            $comp = array();
            foreach($companies as $company){
                $projects = $this->get_projects($company['id']);
                if($projects){
                    foreach($projects as $project){
                        if($project['id'] == $project_id){
                            $comp = $company;
                            break 2;
                        }
                    }
                }
            }

            if(!$comp)
                return false;
            $ch = curl_init(); 

            // set url 
            curl_setopt($ch, CURLOPT_URL, "https://app.procore.com/vapid/projects/".$project_id."/?company_id=".$comp['id']); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$this->get_access_token()
            ));

            //return the transfer as a string 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            //curl_setopt($ch, CURLOPT_POST, 1);

            // $output contains the output string 
            $output = curl_exec($ch);

            $json = json_decode($output, true);
            if(isset($json['id'])){
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