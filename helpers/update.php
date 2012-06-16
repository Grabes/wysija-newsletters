<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_update extends WYSIJA_object{
    function WYSIJA_help_update(){
        $this->modelWysija=new WYSIJA_model();
        $this->updates=array('1.1','2.0');
    }
    
    function checkForNewVersion($file='wysija-newsletters/index.php'){
        $current = get_site_transient( 'update_plugins' );
	if ( !isset( $current->response[ $file ] ) )
		return false;
	$r = $current->response[ $file ];
        $default_headers = array(
		'Name' => 'Plugin Name',
		'PluginURI' => 'Plugin URI',
		'Version' => 'Version',
		'Description' => 'Description',
		'Author' => 'Author',
		'AuthorURI' => 'Author URI',
		'TextDomain' => 'Text Domain',
		'DomainPath' => 'Domain Path',
		'Network' => 'Network',
	);
        $plugin_data = get_file_data( WP_PLUGIN_DIR . DS.$file, $default_headers, 'plugin' );
	$plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
	$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );
	$details_url = self_admin_url('plugin-install.php?tab=plugin-information&plugin=' . $r->slug . '&TB_iframe=true&width=600&height=800');
        if((is_network_admin() || !is_multisite()) && current_user_can('update_plugins') && !empty($r->package) ){
            $this->notice(
                    sprintf( 
                            __('There is a new version of %1$s available. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s details</a> or <a href="%5$s">update automatically</a>.')
                            , '<strong>'.$plugin_name.'</strong>', 
                            esc_url($details_url), 
                            esc_attr($plugin_name), 
                            $r->new_version,
                            wp_nonce_url( self_admin_url('update.php?action=upgrade-plugin&plugin=') . $file, 'upgrade-plugin_' . $file) ));
        }
    }
    function check(){
        
        $config=&WYSIJA::get("config","model");
        if(!$config->getValue("wysija_db_version") || version_compare($config->getValue("wysija_db_version"),WYSIJA::get_version()) < 0){
            $this->update(WYSIJA::get_version());
        }
    }
    function update($version){
        $config=&WYSIJA::get('config',"model");
        $config->getValue("wysija_db_version");
        foreach($this->updates as $version){
            if(version_compare($config->getValue("wysija_db_version"),$version) < 0){
                if(!$this->runUpdate($version)){
                    $this->error(sprintf(__('Update procedure to Wysija version "%1$s" failed!',WYSIJA),$version),true);
                    return false;
                }else{
                    $config->save(array("wysija_db_version"=>$version));

                }
            }
        }
    }
    function runUpdate($version){


        switch($version){
            case '1.1':
                
                $modelconfig=&WYSIJA::get("config","model");
                if(!$this->modelWysija->query("SHOW COLUMNS FROM `[wysija]list` LIKE 'namekey';")){
                    $querys[]='ALTER TABLE `[wysija]list` ADD `namekey` VARCHAR( 255 ) NULL;';
                }
                $querys[]="UPDATE `[wysija]list` SET `namekey` = 'users' WHERE `list_id` =".$modelconfig->getValue('importwp_list_id').";";
                $errors=$this->runUpdateQueries($querys);
                $importHelp=&WYSIJA::get("import","helper");
                $importHelp->testPlugins();

                $installHelper =& WYSIJA::get('install', 'helper');
                $installHelper->moveData('dividers');
                $installHelper->moveData('bookmarks');
                $installHelper->moveData('themes');
                if($errors){
                    $this->error(implode($errors,"\n"));
                    return false;
                }
                return true;
                break;
            case '2.0':
                
                $modelconfig=&WYSIJA::get("config","model");
                if(!$this->modelWysija->query("SHOW COLUMNS FROM `[wysija]email` LIKE 'modified_at';")){
                    $querys[]="ALTER TABLE `[wysija]email` ADD `modified_at` INT UNSIGNED NOT NULL DEFAULT '0';";
                }
                $querys[]="UPDATE `[wysija]email` SET `modified_at` = `sent_at`;";
                $querys[]="UPDATE `[wysija]email` SET `modified_at` = `created_at` WHERE `modified_at`='0';";
                $querys[]="UPDATE `[wysija]email` SET `status` = '99' WHERE `status` ='1';";//change sending status from 1 to 3
                $errors=$this->runUpdateQueries($querys);
                if($errors){
                    $this->error(implode($errors,"\n"));
                    return false;
                }
                return true;
                break;
            default:
                return false;
        }
        return false;
    }
    
    function runUpdateQueries($queries){
        $failed=array();

        global $wpdb;
        foreach($queries as $query){
            $query=str_replace('[wysija]',$this->modelWysija->getPrefix(),$query);
            $result=mysql_query($query, $wpdb->dbh);
            if(!$result)    $failed[]=mysql_error($wpdb->dbh)." ($query)";
        }
        if($failed) return $failed;
        else return false;
    }
}
