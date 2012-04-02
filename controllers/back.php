<?php
defined('WYSIJA') or die('Restricted access');
global $wysi_location;
class WYSIJA_control_back extends WYSIJA_control{
    var $redirectAfterSave=true;
    var $searchable=array();
    var $data=array();
    var $jsTrans=array();
    var $msgOnSave=true;
    var $pref=array();
    var $statuses=array();
    
    function WYSIJA_control_back(){
        parent::WYSIJA_control(); 
        global $wysija_msg,$wysija_queries;
        $wysija_msgTemp=get_option("wysija_msg");
        if(is_array($wysija_msgTemp) && count($wysija_msgTemp)>0){
            $wysija_msg=$wysija_msgTemp;
        }
        
        /* 
         * let's fix all the conflict that we may have
         */
        $modelConfig=&WYSIJA::get('config','model');

        // check conflictiing themes
        $possibleConflictiveThemes = $modelConfig->getValue('conflictiveThemes');
        $conflictingTheme = null;
        $currentTheme = strtolower(get_current_theme());
        foreach($possibleConflictiveThemes as $keyTheme => $conflictTheme) {
            if($keyTheme === $currentTheme) {
                $conflictingTheme = $keyTheme;
            }
        }

        // if the current theme is known to make troubles, let's resolve this
        if($conflictingTheme !== null) {
            $helperConflicts =& WYSIJA::get('conflicts', 'helper');
            $helperConflicts->resolve(array($possibleConflictiveThemes[$conflictingTheme]));
        }

        // check conflicting plugins
        $possibleConflictivePlugins=$modelConfig->getValue("conflictivePlugins");

        $conflictingPlugins=array();
        foreach($possibleConflictivePlugins as $keyPlg => $conflictPlug){
            if (is_plugin_active($conflictPlug['file'])) {
                //plugin is activated
                $conflictingPlugins[$keyPlg]=$conflictPlug;
            }
        }

        if($conflictingPlugins){
            $helperConflicts=&WYSIJA::get("conflicts","helper");
            $helperConflicts->resolve($conflictingPlugins);
        }
        
        $wysija_qryTemp=get_option("wysija_queries");
        if(is_array($wysija_qryTemp) && count($wysija_qryTemp)>0){
            $wysija_queries=$wysija_qryTemp;
        }
        
        WYSIJA::update_option("wysija_queries","");
        WYSIJA::update_option("wysija_msg","");
        
        $this->pref=get_user_meta(get_current_user_id(),'wysija_pref',true);
        
        $prefupdate=false;
        if($this->pref) {
            $prefupdate=true;
            $this->pref=unserialize(base64_decode($this->pref));
        }else{
            $this->pref=array();
        }
       
        if(!isset($_GET['action'])) $action="default";
        else $action=$_GET['action'];
        
        if(isset($_REQUEST['limit_pp'])){
            $this->pref[$_REQUEST['page']][$action]['limit_pp']=$_REQUEST['limit_pp'];
        }

        if($this->pref && isset($_REQUEST['page']) && $_REQUEST['page'] && isset($this->pref[$_REQUEST['page']][$action]['limit_pp'])){
            $this->viewObj->limit_pp=$this->pref[$_REQUEST['page']][$action]['limit_pp'];
            $this->modelObj->limit_pp=$this->pref[$_REQUEST['page']][$action]['limit_pp'];
        }

        if($prefupdate){
            update_user_meta(get_current_user_id(),'wysija_pref',base64_encode(serialize($this->pref)));
        }else{
            add_user_meta(get_current_user_id(),'wysija_pref',base64_encode(serialize($this->pref)));
        }
        
        /* check the licence if we have a premium user once in a while like every 24hrs*/
        $modelC=&WYSIJA::get("config","model");
        if(get_option("wysicheck") || (( (isset($_REQUEST['action']) && $_REQUEST['action']!="licok")) && $modelC->getValue("premium_key"))){
            $onedaysec=7*24*3600;
            if(get_option("wysicheck") || (!$modelC->getValue("premium_val") || mktime() >((int)$modelC->getValue("premium_val")+$onedaysec))){
                $helpLic=&WYSIJA::get("licence","helper");
                $res=$helpLic->check(true);
                if($res['nocontact']){
                    /* redirect instantly to a page with a javascript file  where we check the domain is ok */
                    
                    $data=get_option("wysijey");
                    /* remotely connect to host */
                    
                    wp_enqueue_script('wysija-verif-licence', 'http://www.wysija.com/?wysijap=checkout&wysijashop-page=1&controller=customer&action=checkDomain&js=1&data='.$data, array( 'jquery' ), mktime());
                    
                }
            }
        }
        
    } 
    
    
    function errorInstall(){
       $this->viewObj->renderErrorInstall();
    }
    
    function _resetGlobMsg(){
        global $wysija_msg,$wysija_queries;
        
        $wysija_msg=$wysija_queries=array();
    }
    function defaultDisplay(){
        $this->viewShow=$this->action='main';  
        
        
        /* if it has not been enqueud in the head we print it here(can happens based on the action after a save or so)*/
        $this->js[]='wysija-admin-list';
        
        /*get the filters*/
        if(isset($_REQUEST['search']) && $_REQUEST['search']){
            $this->filters["like"]=array();
            foreach($this->searchable as $searchable){
                $this->filters["like"][$searchable]=$_REQUEST['search'];
            }
            
        }

        if($this->filters){
            $this->modelObj->setConditions($this->filters);
        }
        
        if($this->joins){
            $this->modelObj->setJoin($this->joins);
        }

        if($this->statuses){
            //we count by statuses
            $query="SELECT count(".$this->modelObj->pk.") as count, status FROM `".$this->modelObj->getPrefix().$this->modelObj->table_name."` GROUP BY status";
            $countss=$this->modelObj->query("get_res",$query,ARRAY_A);
            $counts=array();
            $this->modelObj->countRows=0;

            foreach($countss as $count){
                $mystat=(int)$count['status'];
                $this->statuses[$mystat]['count']=$count['count'];
                $this->statuses[$mystat]['uri']=$this->getDefaultUrl(false)."&link_filter=".$this->statuses[$mystat]['key'];

                $this->modelObj->countRows=$this->modelObj->countRows+$count['count'];
                $this->viewObj->statuses=$this->statuses;
            }

        }else{
            $this->modelObj->countRows=$this->modelObj->count();
        }
        
        
        
        
        if(isset($_REQUEST['orderby'])){
            $this->modelObj->orderBy($_REQUEST['orderby'],strtoupper($_REQUEST['ordert']));
        }else{
            $this->modelObj->orderBy($this->modelObj->getPk(),"DESC");
        }
        $this->modelObj->limitON=true;

        $data=$this->modelObj->getRows($this->list_columns);

        $methodDefaultData="defaultData";
        if(method_exists($this,$methodDefaultData )){
            $this->$methodDefaultData($data);
        }
        
    }
    
    function defaultData($data){
        $this->data=$data;
    }
    
    
    function render(){
        
        $this->viewObj->render($this->viewShow,$this->data);
    }
    
    /**
     * by default this is the first method called from a controller this is from where we route to other methods
     */
    function main(){
        
        $this->WYSIJA_control_back();
        if($this->model){
            if(isset($_REQUEST['action']))  $action=$_REQUEST['action'];
            else  $action="defaultDisplay";
            
            if($action){
                $this->_tryAction($action);
            }
            
        }else{
            $this->error("No Model is linked to this controller : ". get_class($this));
            return false;
        }

        return true;
    }
    
    function __setMetaTitle(){
        global $title;

        if(isset($this->title))$title=$this->title;
        else $title=$this->viewObj->title;
    }
    
    function _tryAction($action){

        $_REQUEST   = stripslashes_deep($_REQUEST);
        $_POST   = stripslashes_deep($_POST);

        if(method_exists($this, $action)){ 
            /* in some bulk actions we need to specify the action name and one or few variables*/
            $this->action=$action;

            $this->viewShow=$this->action;
            
            if(strpos($action, "bulk_")===false)$this->$action();
            else {
                $this->$action($_REQUEST['wysija'][$this->model][$this->modelObj->pk]);
            }
            
            $this->__setMetaTitle();
        }else{
            /* in some bulk actions we need to specify the action name and one or few variables*/
            if(strpos($action,"actionvar_")!== false){
                $data=explode("-",$action);
                $datas=array();

                foreach($data as $dt){
                    $res=explode("_",$dt);
                    $datas[$res[0]]=$res[1];
                }
                
                $action =$datas["actionvar"];
                unset($datas["actionvar"]);
                $this->action=$action;
                
                if(method_exists($this, $this->action)){
                    $this->viewShow=$this->action;
                    
                    $this->$action($datas);
                    $this->__setMetaTitle();
                    
                }else{
                    $this->error("Action '".$action."' does not exists in controller : ". get_class($this));
                    $this->redirect();
                }
            }else{
                $this->error("Action '".$action."' does not exists in controller : ". get_class($this));
                $this->redirect();
                //$this->defaultDisplay();
            }

        }
        
        if(defined('WYSIJA_REDIRECT'))  $this->redirectProcess();
        $this->_checkTotalSubscribers();
        
    }
    

    
    function _checkTotalSubscribers(){

        $config=&WYSIJA::get("config","model");
        $totalSubscribers=$config->getValue('total_subscribers');

        if((int)$totalSubscribers>1900 && !$config->getValue('premium_key')){
            if((int)$totalSubscribers>2000){
                $this->error(str_replace(array('[link]','[/link]'),
                    array('<a title="'.__('Get Premium now',WYSIJA).'" class="wysija-premium" href="javascript:;">','<img src="'.WYSIJA_URL.'img/wpspin_light.gif" alt="loader"/></a>'),
                    sprintf(__('Yikes. You\'re over the limit of 2000 subscribers for the free version of Wysija (%1$s in total). Sending is disabled now. Please upgrade your version to [link]premium[/link] to send without limits.',WYSIJA)
                            ,$totalSubscribers)),true);
            }else{
                $this->notice(str_replace(array('[link]','[/link]'),
                    array('<a title="'.__('Get Premium now',WYSIJA).'" class="wysija-premium" href="javascript:;">','<img src="'.WYSIJA_URL.'img/wpspin_light.gif" alt="loader"/></a>'),
                    sprintf(__('Yikes. You\'re near the limit of %1$s subscribers in total for the free version of Wysija. Sending will be disabled when you reach that limit. Rest assured, your visitors will still be able to subscribe. Go [link]premium[/link] to send without limits.',WYSIJA)
                            ,"2000")));
            }
            
        }
    }
    
    function edit($id=false){

        if(isset($_REQUEST['id']) || $id){
            if(!$id) $id=$_REQUEST['id'];
            $this->data[$this->modelObj->table_name]=$this->modelObj->getOne($this->form_columns,array($this->modelObj->pk=>$id));

            //$this->viewObj->render($this->action,$data);
            
        }else{
            $this->error("Cannot edit element primary key is missing : ". get_class($this));
        }
        
    }
    
    function view($id=false){
        
        if(isset($_REQUEST['id']) || $id){
            if(!$id) $id=$_REQUEST['id'];
            $this->data[$this->modelObj->table_name]=$this->modelObj->getOne($this->form_columns,array($this->modelObj->pk=>$id));
            
        }else{
            $this->error("Cannot view element primary key is missing : ". get_class($this));
        }

    }
    
    function add($dataPost=false){
        
        if(!$dataPost){
            $data=array();
            foreach($this->form_columns as $key){
                $data[$key]="";
            } 
        }else{
            
            $data=array();
            foreach($this->form_columns as $key){
                if($key != $this->viewObj->model->pk)  $data[$key]=$dataPost[$key];
            } 
            $data[$this->viewObj->model->pk]="";
        }
        

        //$this->viewObj->render('edit',$data);
    }
    
    function save(){
        $this->requireSecurity();
        /* see if it's an update or an insert */        
        /*get the pk and its value as a conditions where pk = pkval*/
        $conditions=$this->getPKVal($this->modelObj);

        if($conditions){
            /* this an update */

            $result=$this->modelObj->update($_POST['wysija'][$this->model],$conditions);       
            
            if($this->msgOnSave){
                if($result) $this->notice($this->messages['update'][true]);
                else{
                    if($result==0){

                    }else{
                        $this->error($this->messages['update'][false],true);
                    }

                }
            }
            
            
            if($this->redirectAfterSave){
                if(isset($this->modelObj->stay)){
                    $this->action='edit';
                    $this->redirect();
                    //$this->edit($result);
                }else{
                    $this->action='edit';
                    $this->redirect();
                    //$this->edit($result);
                }
            }
            
        }else{
            /* this is an insert */
            unset($_POST['wysija'][$this->model][$this->modelObj->pk]);
            $result=$this->modelObj->insert($_POST['wysija'][$this->model]);
            
            if($this->msgOnSave){
                if($result) $this->notice($this->messages['insert'][true]);
                else{
                    $this->error($this->messages['insert'][false],true);
                }
            }
            
            
            if($this->redirectAfterSave){
                if(isset($this->modelObj->stay)){
                    $this->action='add';
                    $this->add($_POST['wysija'][$this->model]);
                }else{
                    $this->action='main';
                    $this->redirect();
                }
            }
            
        }
        
        /*now we redirect to the edit page with the data in it*/
        return $result;
    }
    
    function bulk_delete($ids){
        $this->requireSecurity();
        foreach($ids as $id){
            
            $conditions=$this->getPKVal($this->modelObj);
            if(!$conditions) $this->error('Cannot obtain PKVal from GET or POST.');

            $result=$this->modelObj->delete($conditions);
            $this->modelObj->reset();
        }
        $this->notice(__("Elements deleted",WYSIJA));
        $this->redirect();
    }
    
    function delete(){
        /* see if it's an update or an insert */
        $this->requireSecurity();
        $conditions=$this->getPKVal($this->modelObj);
        if(!$conditions) $this->error('Cannot obtain PKVal from GET or POST.');
        
        $result=$this->modelObj->delete($conditions);
        if($result){
            $this->notice(__("Element has been deleted.",WYSIJA));
        }
        
        
        $this->modelObj->reset();
        /*now we redirect to the edit page with the data in it*/
        $this->action='main';
        $this->redirect();
    }
    
    function redirect($location=false){
        global $wysi_location;
        define('WYSIJA_REDIRECT',true);
        $wysi_location=$location;
    }
    
    function redirectProcess(){
        global $wysi_location;
        
        if(!$wysi_location)  {
            $wysi_location=$this->getDefaultUrl();
        }
        WYSIJA::redirect($wysi_location);

    }
    
    function popupReturn($viewFunc) {
        return wp_iframe( array($this->viewObj,"popup_".$viewFunc), $this->data);
    }
    
    function _addTab($defaulttab){
        return $this->iframeTabs;
    }
    
    function popupContent(){
        wp_enqueue_style('custom_popup_css', WYSIJA_URL.'css/adminPopup.css');
        global $viewMedia;
        $viewMedia=$this->viewObj;
        $_GET['type']=$_REQUEST['type']='image';
        
        $config=&WYSIJA::get('config','model');
        $_GET['post_id']=$_REQUEST['post_id']=$config->getValue('confirm_email_link');
        $post_id = isset($_GET['post_id'])? (int) $_GET['post_id'] : 0;
        if(file_exists(ABSPATH."wp-admin".DS.'admin.php')) require_once(ABSPATH."wp-admin".DS.'admin.php');

        @header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset')); 
        
        add_filter('media_upload_tabs', array($this,'_addTab'));
        
        if(!isset($this->iframeTabs)) {
            $this->iframeTabs=array(
            'special_wp_upload'=>__("Upload",WYSIJA),
            'special_wysija_browse'=>__("Newsletter Images",WYSIJA),
            'special_wp_browse'=>__("WordPress Posts' Images",WYSIJA));
            foreach($this->iframeTabs as $actionKey =>$actionTitle)
                add_action("media_upload_".$actionKey, array($this,$actionKey));
        }else   add_action("media_upload_standard", array($this,"popupReturn"));

        // upload type: image, video, file, ..?
        if ( isset($_GET['type']) )
                $type = strval($_GET['type']);
        else
                $type = apply_filters('media_upload_default_type', 'file');

        // tab: gallery, library, or type-specific
        if ( isset($_GET['tab']) )
                $tab = strval($_GET['tab']);
        else
                $tab ='special_wysija_browse';

        $body_id = 'media-upload';
        // let the action code decide how to handle the request
        if ( $tab == 'type' || $tab == 'type_url' )
            //i'm not so sure we need that line    
            do_action("media_upload_$type");
        else{
            if(strpos($tab, "special_")!==false){

                do_action("media_upload_$tab");
            }else{
                do_action("media_upload_standard",$tab);
            }
            
            //do_action("media_upload_$tab"); 
        }
                
        exit;

    }
    
    function getDefaultUrl($filter=true){
        $location="admin.php?page=".$_REQUEST['page'];

        if($filter){
            if(isset($_REQUEST['search']) && $_REQUEST['search']){
                $location.='&search='.$_REQUEST['search'];
            }

            if(isset($_REQUEST['filter-list']) && $_REQUEST['filter-list']){
                $location.='&filter-list='.$_REQUEST['filter-list'];
            }
            
            if(isset($_REQUEST['link_filter']) && $_REQUEST['link_filter']){
                $location.='&link_filter='.$_REQUEST['link_filter'];
            }

            if(isset($_REQUEST['orderby']) && $_REQUEST['orderby']){
                $location.='&orderby='.$_REQUEST['orderby'];
            }

            if(isset($_REQUEST['ordert']) && $_REQUEST['ordert']){
                $location.='&ordert='.$_REQUEST['ordert'];
            }
        }
        
        return $location;
    }

            
}