<?
$className = 'postscript_install_ZetaPrints';

class postscript_install_ZetaPrints {


	function execute(){

		//echo "New Hellp world!!";

		// Parsing file with the settings
		$set = parse_ini_file(OX_PATH."/var/".$_SERVER['HTTP_HOST'].".conf.php", 1);

		// Get the table fields. And check it.
		$res1=mysql_query("SHOW COLUMNS FROM '{$set['table']['prefix']}users' ");
		while ($r=mysql_fetch_assoc()){
			$fields[$r['Field']]=$r;
		}
		if (! key_exists("zpmem",$fields)){
			$sql="
			ALTER TABLE `{$set['table']['prefix']}users` ADD `guid` VARCHAR( 255 ) NULL AFTER `sso_user_id` ,
			ADD `zpmem` VARCHAR( 5 ) NOT NULL DEFAULT 'No' AFTER `guid` ,
			ADD `rett` VARCHAR( 5 ) NOT NULL DEFAULT 'png' AFTER `zpmem` ,
			ADD `actionurl` VARCHAR( 255 ) NOT NULL DEFAULT 'http://zetaprints.com/?page=templates;Keywords=Internet%20banner' AFTER `rett`";
			if (mysql_query($sql)){
				$this->addLog("sql_query","Modifed table {$set['table']['prefix']}users complite. OK");
			} else {
				$this->addLog("error","SQL error: ".mysql_error());
			}

		} else  {
			$this->addLog("","Modifed table {$set['table']['prefix']}users is needless. OK");
		}
		
		//copy file in the admin dirrectory
		if (copy(OX_PATH."/plugins/oxZetaPrints/account-user-zp.php", OX_PATH."/www/admin/account-user-zp.php")) {
			$this->addLog("copy_file","Copy file ".OX_PATH."/plugins/account-user-zp.php"." to ".OX_PATH."/www/admin");
		} else {
			$this->addLog("error","Copy file ".OX_PATH."/plugins/account-user-zp.php"." to ".OX_PATH."/www/admin - ERROR");
		}

		
		// 1 step
		$cont=file_get_contents($file=OX_PATH."/lib/OA/Admin/Option.php");
		if (strpos($cont,'$aSections[\'zp\']')===false){
			$cont=str_replace('$aSections[\'name-language\']','
//openXZetaPrints modification			
if ($GLOBALS[\'_MAX\'][\'CONF\'][\'plugins\'][\'openXZetaPrints\']){			
$aSections[\'zp\'] =  
   array(  
   \'name\' => \'ZetaPrints Options\',  
   \'perm\' => array(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER, OA_ACCOUNT_TRAFFICKER)  
);
}
//end openXZetaPrints modification
  
$aSections[\'name-language\']',$cont);
						
			file_put_contents($file, $cont);
			$this->addLog('modification', "Modification $file - OK");
		} else {
			$this->addLog('', "Modification $file is needless - OK");
		}
		
		
		//2 step
		
		$cont=file_get_contents($file=OX_PATH."/www/admin/banner-edit.php");
		$cont = preg_replace('/\/\/ZetaPrints modification.*?\/\/ZetaPrints end/is','',$cont);		
		
		if (strpos($cont,'//ZetaPrints modification')===false){

			
			
//Form Building. Addindg form to request to ZP.

			
$cont=str_replace('$form->addElement($header);','
$form->addElement($header);
				
//ZetaPrints modification
if ($GLOBALS[\'_MAX\'][\'CONF\'][\'plugins\'][\'openXZetaPrints\']){ 
$ZPresult = mysql_query("SELECT guid, rett, actionurl, zpmem FROM ox_users WHERE user_id = \'".OA_Permission::getUserId()."\'");  
while ($zprow = mysql_fetch_array($ZPresult)) {  
       
     $rett = $zprow[\'rett\'];  
     $actionurl = $zprow[\'actionurl\'];  
       
     if($zprow[\'guid\'] == "") {  
         $zpguidbare = str_replace(\'.\', \'\', uniqid($_SERVER[\'REMOTE_ADDR\'], TRUE));    
         $zpguid = md5($zpguidbare);  
         mysql_query("UPDATE ox_users SET guid = \'".$zpguid."\' WHERE user_id = \'".OA_Permission::getUserId()."\'");  
     } else {  
         $zpguid = $zprow[\'guid\'];  
     }         
}
if(isset($_GET[\'clientid\'])){     
?>  
<form name="zpsubmit" id="zpsubmit" action="<?php echo $actionurl; ?>" method="post">  
     <input type="hidden" name="ID" value="<?php echo $zpguid; ?>" />  
     <input type="hidden" name="RetT" value="<?php echo $rett; ?>" />  
     <input type="hidden" name="RetP" value="<?php echo $_SERVER[\'SERVER_NAME\']."/".$_SERVER[\'PHP_SELF\']."?".$_SERVER[\'QUERY_STRING\']?>&zpbannerurl=" />  
     <input type="submit" style="display:none;" mce_style="display:none;" />  
</form>  
<?php
}  
}
if ($GLOBALS[\'_MAX\'][\'CONF\'][\'plugins\'][\'openXZetaPrints\'] and isset($_GET[\'zpbannerurl\'])){
        
$basename = basename($_GET[\'zpbannerurl\']);   
           
         $filename = \'../images/\'.$basename;  
           
         if (file_exists($filename)) {  
             //echo "The image exists";  
         } else {  
             $from = $_GET[\'zpbannerurl\'];  
             $to = $filename;  
             @copy($from,$to);  
         }  
           
         $ext = substr($basename, strrpos($basename, \'.\') + 1);  
         $theimgurl = "http://".$_SERVER[\'SERVER_NAME\'].dirname(dirname($_SERVER[\'PHP_SELF\']))."/images/".$basename;  
         list($width, $height, $extnum, $attr) = getimagesize($filename);
         $row[\'filename\']=$basename;
         $row[\'contenttype\']=($ct=strtolower(substr(strrchr($basename,"."),1)))==\'jpg\' ? \'jpeg\' : $ct;  
         
}

//ZetaPrints end
',$cont);


// Form build.


$cont=str_replace('if ($vars[\'handleSWF\']) {','
//ZetaPrints modification. Adding button.
if ($GLOBALS[\'_MAX\'][\'CONF\'][\'plugins\'][\'openXZetaPrints\']){
$uploadG[\'button\'] = $form->createElement(\'button\', "zpupl", "Upload from ZetaPrints", array(\'onclick\' => \'getElementById("zpsubmit").submit();\',\'value\'=>\'Upload From ZetaPrints\'));
	if ($var[\'fileName\'] and isset($_GET[\'zpbannerurl\'])) {
		$uploadG[\'zpfilename\'] = $form->createElement(\'hidden\', "zpfilename", $var[\'fileName\']);	
	}
}
//ZetaPrints end            
if ($vars[\'handleSWF\']) {
',$cont);
			

$cont=str_replace('"<img src=\'".OX::assetPath()."/images/".','//ZetaPrints modification
($_GET[\'zpbannerurl\'] ? "<input type=\'hidden\' name=\'zpfilename\' value=\'{$vars[\'fileName\']}\'> <div><img src=\'".dirname(dirname($_SERVER[\'PHP_SELF\']))."/images/".$vars[\'fileName\']."\'></div>" : "").
//ZetaPrints end
"<img src=\'".OX::assetPath()."/images/".',$cont);


// Form proccessing.


$cont=str_replace('// Deal with any files that are uploaded.','//ZetaPrints modification 
    if ($GLOBALS[\'_MAX\'][\'CONF\'][\'plugins\'][\'openXZetaPrints\'] and is_file($file=dirname(dirname(__FILE__))."/images/".$aVariables[\'filename\']) and isset($_POST[\'zpfilename\'])){
    	list($width, $height, $extnum, $attr) = getimagesize($file);
    	    $aVariables[\'contenttype\']   = ($ct=strtolower(substr(strrchr($aVariables[\'filename\'],"."),1)))==\'jpg\' ? \'jpeg\' : $ct;
            $aVariables[\'width\']         = $width;
            $aVariables[\'height\']        = $height;
    }
//ZetaPrints end
    
    
    // Deal with any files that are uploaded.
',$cont);


//Form proccessing


$cont = str_replace('$aVariables[\'filename\']        = !empty($aBanner[\'filename\']) ? $aBanner[\'filename\'] : \'\';','$aVariables[\'filename\']        = !empty($aBanner[\'filename\']) ? $aBanner[\'filename\'] : \'\';
//ZetaPrints modification   	
   	if ($_POST[\'zpfilename\']){
   		$aVariables[\'filename\']=mysql_real_escape_string($_POST[\'zpfilename\']);
   	}
//ZetaPrints end
',$cont);

$cont= str_replace('','',$cont);














file_put_contents($file,$cont);
		
		
		
} else {
			$this->addLog('', "Modification $file is needless - OK");
		}
		
/*		
		//3 step
		$cont=file_get_contents($file=OX_PATH."/www/admin/banner-edit.php");
		if (strpos($cont,'')===false){
			$cont=str_replace('','
//openXZetaPrints modification	



//end openXZetaPrints modification
			',$cont);
			file_put_contents($file,$cont);
		} else {
			$this->addLog('', "Modification $file is needless - OK");
		}		
		
		//4 step
		$cont=file_get_contents($file=OX_PATH."/www/admin/banner-edit.php");
		if (strpos($cont,'')===false){
			$cont=str_replace('','
//openXZetaPrints modification	



//end openXZetaPrints modification
			',$cont);
			file_put_contents($file,$cont);
		} else {
			$this->addLog('', "Modification $file is needless - OK");
		}
		
		//5 step
		$cont=file_get_contents($file=OX_PATH."/www/admin/banner-edit.php");
		if (strpos($cont,'')===false){
			$cont=str_replace('','
//openXZetaPrints modification	



//end openXZetaPrints modification
			',$cont);
			file_put_contents($file,$cont);
		} else {
			$this->addLog('', "Modification $file is needless - OK");
		}
*/
		return true;
	}


	function addLog($type="",$text=null,$time=null){
		static $res;

		if (!$res){
			$res = fopen(OX_PATH."/plugins/oxZetaPrints/installation.log","a");
			fwrite($res," \r\n Logging start at ".Date("Y-m-d H:i:s")." \r\n");
		}
		$type = ($type) ? $type : "log_event";

		if ($time===NULL){
			$time=Date("Y-m-d H:i:s");
		}

		fwrite($res,"$time|$type|$text \r\n");

		return true;
	}


}

?>