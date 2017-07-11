<?php
/* USe a dump instead of bootstrap */
/*
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
*/
/**
 * Show our field.
 *
 * @param array $args
 */
function procore_access_settings( $args )
{
    $data = esc_attr( get_option( 'procore-token', '' ) );

    printf(
        '<input type="text" name="procore-token" value="%1$s" id="%2$s" />',
        $data,
        $args['label_for']
    );
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
    $project = $procore_api->get_project($args['project_id']);
    ?>
    <table class="table table-striped table-bordered">
        <tr>
            <td>Site Safety Manager</td>
            <td><?php echo $project['name']; ?></td>
        </tr>
        <tr>
            <td>Project</td>
            <td>
                <div><?php echo $project['id']; ?></div>
                <div><?php echo $project['address']; ?></div>
            </td>
        </tr>
    </table>
    <?php

    if($all_logs){
        foreach($all_logs as $key => $value){
            ?>
            <h2><?php echo $key; ?></h2>
                <?php
                    if($value && is_array($value)){
                        foreach($value as $ke => $val){
                            ?>
                            <table class="table table-striped table-bordered">
                            <?php
                            if($val && is_array($val)){
                                foreach($val as $k => $v){
                                ?>
                                <tr>
                                    <td><?php echo $k; ?></td>
                                    <td>
                                    <?php
                                    if(is_array($v)){
                                        echo implode(",", $v);
                                    }
                                    else{
                                        echo $v;
                                    }
                                    ?>
                                    </td>
                                </tr>
                                <?php
                                }
                            }
                            ?>
                            </table>
                            <?php
                        }
                    }
                ?>
            <?php
        }
    }
}

add_action('wp_enqueue_scripts', 'papi_bootstra_enqueue_scripts');
function papi_bootstra_enqueue_scripts(){
    wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
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