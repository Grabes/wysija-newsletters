<?php
defined('WYSIJA') or die('Restricted access'); class WYSIJA_help_install extends WYSIJA_object{ function WYSIJA_help_install(){ require_once(ABSPATH . 'wp-admin'.DS.'includes'.DS.'upgrade.php'); } function install(){ $values=array(); $this->createTables(); $this->recordDefaultUserField(); $this->defaultSettings($values); $this->defaultList($values); $this->defaultCampaign($values); $helpImport=&WYSIJA::get("import","helper"); $values['importwp_list_id']=$helpImport->importWP(); $this->createPage($values); $this->createWYSIJAdir($values); $this->moveThemes(); $modelConf=&WYSIJA::get("config","model"); $mailModel=&WYSIJA::get("email","model"); $mailModel->blockMe=true; $values["confirm_email_id"]=$mailModel->insert( array("type"=>"0", "from_email"=>$values["from_email"], "from_name"=>$values["from_name"], "replyto_email"=>$values["from_email"], "replyto_name"=>$values["from_name"], "subject"=>$modelConf->getValue("confirm_email_title"), "body"=>$modelConf->getValue("confirm_email_body"), "status"=>"1")); $values['installed']=true; $values['installed_time']=mktime(); $modelConf->save($values); $this->testNLplugins(); $this->wp_notice(str_replace(array('[link]','[/link]'),array('<a href="admin.php?page=wysija_config">','</a>'),__("Wysija has been installed successfully. Go to the [link]settings page[/link] now, and start blasting emails.",WYSIJA))); return true; } function defaultList(&$values){ $model=&WYSIJA::get("list","model"); $listname=__("My first list",WYSIJA); $defaultListId=$model->insert(array( "name"=>$listname, "description"=>__('The list created automatically on install of the Wysija.',WYSIJA), "is_enabled"=>1)); $values['default_list_id']=$defaultListId; } function defaultCampaign($valuesconfig){ $modelCampaign=&WYSIJA::get("campaign","model"); $campaign_id=$modelCampaign->insert( array( "name"=>__('Example Newsletter',WYSIJA), "description"=>__('Default Campaign created automatically during installation.',WYSIJA), )); $modelEmail=&WYSIJA::get("email","model"); $modelEmail->fieldValid=false; $dataEmail=array( "campaign_id"=>$campaign_id, "subject"=>__('Example Newsletter',WYSIJA), "wj_data" => "YTozOntzOjc6InZlcnNpb24iO3M6NToiMC4wLjkiO3M6NjoiaGVhZGVyIjthOjU6e3M6NDoidGV4dCI7YToxOntzOjU6InZhbHVlIjtzOjQ2OiI8aDE+QSBHdWlkZSB0byBVc2luZyBXeXNpamEgZm9yIEJlZ2lubmVyczwvaDE+Ijt9czo1OiJpbWFnZSI7YTo1OntzOjM6InNyYyI7czo3NjoiaHR0cDovL3dwMS53eXNpamEuaWx5bmV0LmNvbS93cC1jb250ZW50L3VwbG9hZHMvMjAxMS8xMC93aGl0ZS1sYWJlbC1sb2dvLnBuZyI7czo1OiJ3aWR0aCI7aToxMjg7czo2OiJoZWlnaHQiO2k6MTI4O3M6OToiYWxpZ25tZW50IjtzOjQ6ImxlZnQiO3M6Njoic3RhdGljIjtiOjA7fXM6OToiYWxpZ25tZW50IjtzOjQ6ImxlZnQiO3M6Njoic3RhdGljIjtiOjE7czo0OiJ0eXBlIjtzOjY6ImhlYWRlciI7fXM6NDoiYm9keSI7YToxNTp7czo3OiJibG9jay0xIjthOjM6e3M6NzoiZGl2aWRlciI7YTozOntzOjM6InNyYyI7TjtzOjU6IndpZHRoIjtOO3M6NjoiaGVpZ2h0IjtOO31zOjg6InBvc2l0aW9uIjtzOjE6IjEiO3M6NDoidHlwZSI7czo3OiJkaXZpZGVyIjt9czo3OiJibG9jay0yIjthOjY6e3M6NDoidGV4dCI7YToxOntzOjU6InZhbHVlIjtzOjMzOiI8aDI+SW1hZ2VzIGFuZCBUZXh0IFRvZ2V0aGVyPC9oMj4iO31zOjU6ImltYWdlIjtOO3M6OToiYWxpZ25tZW50IjtzOjY6ImNlbnRlciI7czo2OiJzdGF0aWMiO2I6MDtzOjg6InBvc2l0aW9uIjtzOjE6IjIiO3M6NDoidHlwZSI7czo3OiJjb250ZW50Ijt9czo3OiJibG9jay0zIjthOjY6e3M6NDoidGV4dCI7YToxOntzOjU6InZhbHVlIjtzOjQ4NjoiPHA+SXQncyBwb3NzaWJsZSB0byBtaXggdGV4dCB3aXRoIGltYWdlcywgYWxpZ25lZCBsZWZ0IG9yIHJpZ2h0LjwvcD48cD5JZiB5b3Ugd2FudCB0aGUgaW1hZ2UgdG8gYmUgZnVsbCB3aWR0aCwgPHN0cm9uZz5pdCBuZWVkcyB0byBiZSBjZW50ZXJlZDwvc3Ryb25nPi48L3A+PHA+QmVjYXVzZSBlbWFpbCBjbGllbnRzIGRvbid0IGxvYWQgaW1hZ2VzIGJ5IGRlZmF1bHQsIHlvdSBjYW4gYWRkIGFuIDxzdHJvbmc+YWx0ZXJuYXRlIHRleHQ8L3N0cm9uZz4gdGhhdCB3aWxsIHNob3cuIERvIHRoaXMgYnkgY2xpY2tpbmcgb24gdGhlIGxpbmsgYnV0dG9uIG9mIHRoZSBpbWFnZS48L3A+PHA+RmluYWxseSwgd2hlbiB5b3UgZHJvcCBhIFdvcmRQcmVzcyBwb3N0LCBpdCB3aWxsIGluY2x1ZGUgdGhlIGZpcnN0IGltYWdlIGluIHRoYXQgcG9zdCBvciB0aGUgcG9zdCdzIGZlYXR1cmVkIGltYWdlLiBBbGwgb3RoZXIgaW1hZ2VzIHdpbGwgYmUgaWdub3JlZCE8L3A+Ijt9czo1OiJpbWFnZSI7YTo3OntzOjM6InNyYyI7czo4MzoiaHR0cDovL3dwMS53eXNpamEuaWx5bmV0LmNvbS93cC1jb250ZW50L3VwbG9hZHMvMjAxMS8xMC9zYW1wbGUtbmV3c2xldHRlci0wMV8wNy5wbmciO3M6NToid2lkdGgiO2k6MjgxO3M6NjoiaGVpZ2h0IjtpOjE5MDtzOjM6InVybCI7czoyMToiaHR0cDovL3d3dy53eXNpamEuY29tIjtzOjM6ImFsdCI7czoyMjoiQSBiaXJkIHdpdGggYW4gZW52ZWxvcCI7czo5OiJhbGlnbm1lbnQiO3M6NDoibGVmdCI7czo2OiJzdGF0aWMiO2I6MDt9czo5OiJhbGlnbm1lbnQiO3M6NDoibGVmdCI7czo2OiJzdGF0aWMiO2I6MDtzOjg6InBvc2l0aW9uIjtzOjE6IjMiO3M6NDoidHlwZSI7czo3OiJjb250ZW50Ijt9czo3OiJibG9jay00IjthOjM6e3M6NzoiZGl2aWRlciI7YTozOntzOjM6InNyYyI7TjtzOjU6IndpZHRoIjtOO3M6NjoiaGVpZ2h0IjtOO31zOjg6InBvc2l0aW9uIjtzOjE6IjQiO3M6NDoidHlwZSI7czo3OiJkaXZpZGVyIjt9czo3OiJibG9jay01IjthOjY6e3M6NDoidGV4dCI7YToxOntzOjU6InZhbHVlIjtzOjczOiI8aDMgc3R5bGU9InRleHQtYWxpZ246IGxlZnQ7Ij4zIFR5cGVzIG9mIFRpdGxlcyBmb3IgWW91ciBDb252ZW5pZW5jZTwvaDM+Ijt9czo1OiJpbWFnZSI7YTo1OntzOjM6InNyYyI7czo4MzoiaHR0cDovL3dwMS53eXNpamEuaWx5bmV0LmNvbS93cC1jb250ZW50L3VwbG9hZHMvMjAxMS8xMC9zYW1wbGUtbmV3c2xldHRlci0wMV8xNC5wbmciO3M6NToid2lkdGgiO2k6NTI7czo2OiJoZWlnaHQiO2k6NDU7czo5OiJhbGlnbm1lbnQiO3M6NDoibGVmdCI7czo2OiJzdGF0aWMiO2I6MDt9czo5OiJhbGlnbm1lbnQiO3M6NDoibGVmdCI7czo2OiJzdGF0aWMiO2I6MDtzOjg6InBvc2l0aW9uIjtzOjE6IjUiO3M6NDoidHlwZSI7czo3OiJjb250ZW50Ijt9czo3OiJibG9jay02IjthOjY6e3M6NDoidGV4dCI7YToxOntzOjU6InZhbHVlIjtzOjIwOToiPHA+QXMgeW91IGNhbiBzZWUgYWJvdmUsIHdlIHNpbXBseSBhbGlnbmVkIHRoZSBpbWFnZSBhbmQgdGhlIHRpdGxlIHRvIHRoZSBsZWZ0LjwvcD48cD5UaHJlZSB0eXBlcyBvZiBUaXRsZXMgYXJlIGF2YWlsYWJsZTo8L3A+PG9sPjxsaT5IZWFkaW5nIDE8L2xpPjxsaT5IZWFkaW5nIDI8L2xpPjxsaT5BbmQgeW91IGd1ZXNzZWQgaXQsIEhlYWRpbmcgMzwvbGk+PC9vbD4iO31zOjU6ImltYWdlIjtOO3M6OToiYWxpZ25tZW50IjtzOjY6ImNlbnRlciI7czo2OiJzdGF0aWMiO2I6MDtzOjg6InBvc2l0aW9uIjtzOjE6IjYiO3M6NDoidHlwZSI7czo3OiJjb250ZW50Ijt9czo3OiJibG9jay03IjthOjM6e3M6NzoiZGl2aWRlciI7YTozOntzOjM6InNyYyI7TjtzOjU6IndpZHRoIjtOO3M6NjoiaGVpZ2h0IjtOO31zOjg6InBvc2l0aW9uIjtzOjE6IjciO3M6NDoidHlwZSI7czo3OiJkaXZpZGVyIjt9czo3OiJibG9jay04IjthOjY6e3M6NDoidGV4dCI7TjtzOjU6ImltYWdlIjthOjU6e3M6Mzoic3JjIjtzOjgzOiJodHRwOi8vd3AxLnd5c2lqYS5pbHluZXQuY29tL3dwLWNvbnRlbnQvdXBsb2Fkcy8yMDExLzEwL3NhbXBsZS1uZXdzbGV0dGVyLTAxXzEwLnBuZyI7czo1OiJ3aWR0aCI7aTozMjM7czo2OiJoZWlnaHQiO2k6MTk7czo5OiJhbGlnbm1lbnQiO3M6NDoibGVmdCI7czo2OiJzdGF0aWMiO2I6MDt9czo5OiJhbGlnbm1lbnQiO3M6NDoibGVmdCI7czo2OiJzdGF0aWMiO2I6MDtzOjg6InBvc2l0aW9uIjtzOjE6IjgiO3M6NDoidHlwZSI7czo3OiJjb250ZW50Ijt9czo3OiJibG9jay05IjthOjY6e3M6NDoidGV4dCI7YToxOntzOjU6InZhbHVlIjtzOjQxODoiPHA+VGhlIGNob2ljZXMgb2YgZm9udHMgb24gbmV3c2xldHRlcnMgYXJlIGZhaXJseSBsaW1pdGVkLiA8c3Ryb25nPldoeT88L3N0cm9uZz4gQmVjYXVzZSBmb250cyBhcmVuJ3Qgc2VudCB3aXRoIGVtYWlscywgd2UgbmVlZCB0byByZWx5IG9uIHRoZSB2ZXJ5IGZldyBmb250IGZhbWlsaWVzIHRoYXQgYXJlIGluc3RhbGxlZCBvbiBhbGwgY29tcHV0ZXJzIG9mIHRoaXMgV29ybGQuPC9wPjxwPjxzdHJvbmc+VGlwOiA8L3N0cm9uZz55b3UgY2FuIHVzZSBpbWFnZXMgdG8gaW5jbHVkZSB5b3VyIGZhdm9yaXRlIGZvbnQuIERvIGtlZXAgaW4gbWluZCB0aGF0IG1vc3QgZW1haWwgY2xpZW50cyBkb24ndCBzaG93IGltYWdlcyBpbnNlcnRlZCBpbiB5b3VyIG5ld3NsZXR0ZXJzIGJ5IGRlZmF1bHQuPHN0cm9uZz48YnI+PC9zdHJvbmc+PC9wPiI7fXM6NToiaW1hZ2UiO047czo5OiJhbGlnbm1lbnQiO3M6NjoiY2VudGVyIjtzOjY6InN0YXRpYyI7YjowO3M6ODoicG9zaXRpb24iO3M6MToiOSI7czo0OiJ0eXBlIjtzOjc6ImNvbnRlbnQiO31zOjg6ImJsb2NrLTEwIjthOjY6e3M6NDoidGV4dCI7TjtzOjU6ImltYWdlIjthOjU6e3M6Mzoic3JjIjtzOjgzOiJodHRwOi8vd3AxLnd5c2lqYS5pbHluZXQuY29tL3dwLWNvbnRlbnQvdXBsb2Fkcy8yMDExLzEwL3NhbXBsZS1uZXdzbGV0dGVyLTAxXzIxLnBuZyI7czo1OiJ3aWR0aCI7aTo1Nzg7czo2OiJoZWlnaHQiO2k6MTc7czo5OiJhbGlnbm1lbnQiO3M6NjoiY2VudGVyIjtzOjY6InN0YXRpYyI7YjowO31zOjk6ImFsaWdubWVudCI7czo2OiJjZW50ZXIiO3M6Njoic3RhdGljIjtiOjA7czo4OiJwb3NpdGlvbiI7czoyOiIxMCI7czo0OiJ0eXBlIjtzOjc6ImNvbnRlbnQiO31zOjg6ImJsb2NrLTExIjthOjY6e3M6NDoidGV4dCI7YToxOntzOjU6InZhbHVlIjtzOjEzNDoiPHA+WW91IGNhbiBhbHNvIHVzZSBpbWFnZXMgaW5zdGVhZCBvZiBob3Jpem9udGFsIGxpbmVzLCBsaWtlIHRoZSBzdGFycyBhYm92ZS4gVGhpcyBtYWtlcyB5b3VyIGRpdmlkZXJzIGEgbGl0dGxlIG1vcmUgcGVyc29uYWxpemVkLjwvcD4iO31zOjU6ImltYWdlIjtOO3M6OToiYWxpZ25tZW50IjtzOjY6ImNlbnRlciI7czo2OiJzdGF0aWMiO2I6MDtzOjg6InBvc2l0aW9uIjtzOjI6IjExIjtzOjQ6InR5cGUiO3M6NzoiY29udGVudCI7fXM6ODoiYmxvY2stMTIiO2E6Mzp7czo3OiJkaXZpZGVyIjthOjM6e3M6Mzoic3JjIjtOO3M6NToid2lkdGgiO047czo2OiJoZWlnaHQiO047fXM6ODoicG9zaXRpb24iO3M6MjoiMTIiO3M6NDoidHlwZSI7czo3OiJkaXZpZGVyIjt9czo4OiJibG9jay0xMyI7YTo2OntzOjQ6InRleHQiO2E6MTp7czo1OiJ2YWx1ZSI7czoxMjY6IjxwPllvdSA8c3Ryb25nPmNhbid0PC9zdHJvbmc+IGluc2VydCB2aWRlb3MgaW4geW91ciBlbWFpbHMuIEluc3RlYWQsIHVzZSBhbiBpbWFnZSB0aGF0IGxvb2tzIGxpa2UgdGhlIHBsYXllci48L3A+PHA+Jm5ic3A7PC9wPiI7fXM6NToiaW1hZ2UiO2E6NTp7czozOiJzcmMiO3M6ODM6Imh0dHA6Ly93cDEud3lzaWphLmlseW5ldC5jb20vd3AtY29udGVudC91cGxvYWRzLzIwMTEvMTAvc2FtcGxlLW5ld3NsZXR0ZXItMDFfMjUucG5nIjtzOjU6IndpZHRoIjtpOjMyMTtzOjY6ImhlaWdodCI7aToyMzY7czo5OiJhbGlnbm1lbnQiO3M6NDoibGVmdCI7czo2OiJzdGF0aWMiO2I6MDt9czo5OiJhbGlnbm1lbnQiO3M6NDoibGVmdCI7czo2OiJzdGF0aWMiO2I6MDtzOjg6InBvc2l0aW9uIjtzOjI6IjEzIjtzOjQ6InR5cGUiO3M6NzoiY29udGVudCI7fXM6ODoiYmxvY2stMTQiO2E6Mzp7czo3OiJkaXZpZGVyIjthOjM6e3M6Mzoic3JjIjtOO3M6NToid2lkdGgiO047czo2OiJoZWlnaHQiO047fXM6ODoicG9zaXRpb24iO3M6MjoiMTQiO3M6NDoidHlwZSI7czo3OiJkaXZpZGVyIjt9czo4OiJibG9jay0xNSI7YTo2OntzOjQ6InRleHQiO2E6MTp7czo1OiJ2YWx1ZSI7czoyNTk6IjxwPlRoZSBmb290ZXIncyBjb250ZW50IGlzIDxzdHJvbmc+bWFuZGF0b3J5PC9zdHJvbmc+OiB3ZSBlbmZvcmNlIHRoZSB1bnN1YnNjcmlwdGlvbiBsaW5rLjwvcD48cD5UbyBjaGFuZ2UgdGhlIGZvb3RlcidzIGNvbnRlbnQsIHZpc2l0IHRoZSBXeXNpamEgU2V0dGluZ3MuIFRoZXJlLCB5b3UgY2FuIGFkZCBhbiBhZGRyZXNzIChyZWNvbW1lbmRlZCB0byBhdm9pZCBzcGFtIGZpbHRlcnMpIGFuZCBjaGFuZ2UgdGhlIHVuc3Vic2NyaWJlIGxhYmVsLjwvcD4iO31zOjU6ImltYWdlIjtOO3M6OToiYWxpZ25tZW50IjtzOjY6ImNlbnRlciI7czo2OiJzdGF0aWMiO2I6MDtzOjg6InBvc2l0aW9uIjtzOjI6IjE1IjtzOjQ6InR5cGUiO3M6NzoiY29udGVudCI7fX19+PHA+VGhlIGNpdHkncyBoaWdocmlzZXMgc3ByZWFkIGJlbG93IGhpbS4gSGlzIHNoYWRvdyBhIHNpbXBsZSBkb3Qgb24gYSBzaWRld2Fsay4gRm9yIGEgbW9tZW50IGhlIGZvcmdvdCB0aGUgd2VhcnkgdGFzayBhdCBoYW5kLiBBIG1lc3NlbmdlciBwaWdlb24gaGUgaXMsIGFmdGVyIGFsbC48L3A+Ijt9czo1OiJpbWFnZSI7YTo1OntzOjM6InNyYyI7czo4MzoiaHR0cDovL3dwMS53eXNpamEuaWx5bmV0LmNvbS93cC1jb250ZW50L3VwbG9hZHMvMjAxMS8xMC9zYW1wbGUtbmV3c2xldHRlci0wMV8wNy5wbmciO3M6NToid2lkdGgiO2k6MjgxO3M6NjoiaGVpZ2h0IjtpOjE5MDtzOjk6ImFsaWdubWVudCI7czo0OiJsZWZ0IjtzOjY6InN0YXRpYyI7YjowO31zOjk6ImFsaWdubWVudCI7czo0OiJsZWZ0IjtzOjY6InN0YXRpYyI7YjowO3M6ODoicG9zaXRpb24iO3M6MToiMiI7czo0OiJ0eXBlIjtzOjc6ImNvbnRlbnQiO319fQ==", "params"=>"YToxOntzOjE0OiJxdWlja3NlbGVjdGlvbiI7YTo3OntzOjY6IndwLTMwMSI7YTo4OntzOjEwOiJpZGVudGlmaWVyIjtzOjY6IndwLTMwMSI7czo1OiJ3aWR0aCI7czozOiIyODEiO3M6NjoiaGVpZ2h0IjtzOjM6IjE5MCI7czozOiJ1cmwiO3M6ODM6Imh0dHA6Ly93cDEud3lzaWphLmlseW5ldC5jb20vd3AtY29udGVudC91cGxvYWRzLzIwMTEvMTAvc2FtcGxlLW5ld3NsZXR0ZXItMDFfMDcucG5nIjtzOjk6InRodW1iX3VybCI7czo5MToiaHR0cDovL3dwMS53eXNpamEuaWx5bmV0LmNvbS93cC1jb250ZW50L3VwbG9hZHMvMjAxMS8xMC9zYW1wbGUtbmV3c2xldHRlci0wMV8wNy0xNTB4MTUwLnBuZyI7czo3OiJJU19QQUlSIjtpOjA7czo3OiJJU19MQVNUIjtiOjA7czo4OiJJU19GSVJTVCI7YjowO31zOjY6IndwLTMwMiI7YTo4OntzOjEwOiJpZGVudGlmaWVyIjtzOjY6IndwLTMwMiI7czo1OiJ3aWR0aCI7czozOiI0ODIiO3M6NjoiaGVpZ2h0IjtzOjI6IjMwIjtzOjM6InVybCI7czo4MzoiaHR0cDovL3dwMS53eXNpamEuaWx5bmV0LmNvbS93cC1jb250ZW50L3VwbG9hZHMvMjAxMS8xMC9zYW1wbGUtbmV3c2xldHRlci0wMV8xMC5wbmciO3M6OToidGh1bWJfdXJsIjtzOjkwOiJodHRwOi8vd3AxLnd5c2lqYS5pbHluZXQuY29tL3dwLWNvbnRlbnQvdXBsb2Fkcy8yMDExLzEwL3NhbXBsZS1uZXdzbGV0dGVyLTAxXzEwLTE1MHgzMC5wbmciO3M6NzoiSVNfUEFJUiI7aToxO3M6NzoiSVNfTEFTVCI7YjowO3M6ODoiSVNfRklSU1QiO2I6MDt9czo2OiJ3cC0zMDMiO2E6ODp7czoxMDoiaWRlbnRpZmllciI7czo2OiJ3cC0zMDMiO3M6NToid2lkdGgiO3M6MjoiNTIiO3M6NjoiaGVpZ2h0IjtzOjI6IjQ1IjtzOjM6InVybCI7czo4MzoiaHR0cDovL3dwMS53eXNpamEuaWx5bmV0LmNvbS93cC1jb250ZW50L3VwbG9hZHMvMjAxMS8xMC9zYW1wbGUtbmV3c2xldHRlci0wMV8xNC5wbmciO3M6OToidGh1bWJfdXJsIjtzOjgzOiJodHRwOi8vd3AxLnd5c2lqYS5pbHluZXQuY29tL3dwLWNvbnRlbnQvdXBsb2Fkcy8yMDExLzEwL3NhbXBsZS1uZXdzbGV0dGVyLTAxXzE0LnBuZyI7czo3OiJJU19QQUlSIjtpOjA7czo3OiJJU19MQVNUIjtiOjA7czo4OiJJU19GSVJTVCI7YjowO31zOjY6IndwLTMwNCI7YTo4OntzOjEwOiJpZGVudGlmaWVyIjtzOjY6IndwLTMwNCI7czo1OiJ3aWR0aCI7czoyOiI3MCI7czo2OiJoZWlnaHQiO3M6MjoiNDIiO3M6MzoidXJsIjtzOjgzOiJodHRwOi8vd3AxLnd5c2lqYS5pbHluZXQuY29tL3dwLWNvbnRlbnQvdXBsb2Fkcy8yMDExLzEwL3NhbXBsZS1uZXdzbGV0dGVyLTAxXzE2LnBuZyI7czo5OiJ0aHVtYl91cmwiO3M6ODM6Imh0dHA6Ly93cDEud3lzaWphLmlseW5ldC5jb20vd3AtY29udGVudC91cGxvYWRzLzIwMTEvMTAvc2FtcGxlLW5ld3NsZXR0ZXItMDFfMTYucG5nIjtzOjc6IklTX1BBSVIiO2k6MTtzOjc6IklTX0xBU1QiO2I6MDtzOjg6IklTX0ZJUlNUIjtiOjA7fXM6Njoid3AtMzA1IjthOjg6e3M6MTA6ImlkZW50aWZpZXIiO3M6Njoid3AtMzA1IjtzOjU6IndpZHRoIjtzOjM6IjU0NiI7czo2OiJoZWlnaHQiO3M6MjoiMTYiO3M6MzoidXJsIjtzOjgzOiJodHRwOi8vd3AxLnd5c2lqYS5pbHluZXQuY29tL3dwLWNvbnRlbnQvdXBsb2Fkcy8yMDExLzEwL3NhbXBsZS1uZXdzbGV0dGVyLTAxXzIxLnBuZyI7czo5OiJ0aHVtYl91cmwiO3M6OTA6Imh0dHA6Ly93cDEud3lzaWphLmlseW5ldC5jb20vd3AtY29udGVudC91cGxvYWRzLzIwMTEvMTAvc2FtcGxlLW5ld3NsZXR0ZXItMDFfMjEtMTUweDE2LnBuZyI7czo3OiJJU19QQUlSIjtpOjA7czo3OiJJU19MQVNUIjtiOjA7czo4OiJJU19GSVJTVCI7YjowO31zOjY6IndwLTMwNiI7YTo4OntzOjEwOiJpZGVudGlmaWVyIjtzOjY6IndwLTMwNiI7czo1OiJ3aWR0aCI7czozOiIzMjEiO3M6NjoiaGVpZ2h0IjtzOjM6IjIzNiI7czozOiJ1cmwiO3M6ODM6Imh0dHA6Ly93cDEud3lzaWphLmlseW5ldC5jb20vd3AtY29udGVudC91cGxvYWRzLzIwMTEvMTAvc2FtcGxlLW5ld3NsZXR0ZXItMDFfMjUucG5nIjtzOjk6InRodW1iX3VybCI7czo5MToiaHR0cDovL3dwMS53eXNpamEuaWx5bmV0LmNvbS93cC1jb250ZW50L3VwbG9hZHMvMjAxMS8xMC9zYW1wbGUtbmV3c2xldHRlci0wMV8yNS0xNTB4MTUwLnBuZyI7czo3OiJJU19QQUlSIjtpOjE7czo3OiJJU19MQVNUIjtiOjA7czo4OiJJU19GSVJTVCI7YjowO31zOjY6IndwLTMwNyI7YTo4OntzOjEwOiJpZGVudGlmaWVyIjtzOjY6IndwLTMwNyI7czo1OiJ3aWR0aCI7czozOiIxNDAiO3M6NjoiaGVpZ2h0IjtzOjM6IjE0MCI7czozOiJ1cmwiO3M6NzY6Imh0dHA6Ly93cDEud3lzaWphLmlseW5ldC5jb20vd3AtY29udGVudC91cGxvYWRzLzIwMTEvMTAvd2hpdGUtbGFiZWwtbG9nby5wbmciO3M6OToidGh1bWJfdXJsIjtzOjc2OiJodHRwOi8vd3AxLnd5c2lqYS5pbHluZXQuY29tL3dwLWNvbnRlbnQvdXBsb2Fkcy8yMDExLzEwL3doaXRlLWxhYmVsLWxvZ28ucG5nIjtzOjc6IklTX1BBSVIiO2k6MDtzOjc6IklTX0xBU1QiO2I6MTtzOjg6IklTX0ZJUlNUIjtiOjA7fX19", "wj_styles"=>"YTo5OntzOjQ6ImJvZHkiO2E6NDp7czo1OiJjb2xvciI7czo2OiIwMDAwMDAiO3M6NjoiZmFtaWx5IjtzOjU6IkFyaWFsIjtzOjQ6InNpemUiO3M6MjoiMTIiO3M6MTA6ImJhY2tncm91bmQiO3M6NjoiRkZGRkZGIjt9czoyOiJoMSI7YTozOntzOjU6ImNvbG9yIjtzOjY6IjAwMDAwMCI7czo2OiJmYW1pbHkiO3M6NToiQXJpYWwiO3M6NDoic2l6ZSI7czoyOiIzNiI7fXM6MjoiaDIiO2E6Mzp7czo1OiJjb2xvciI7czo2OiIwMDAwMDAiO3M6NjoiZmFtaWx5IjtzOjU6IkFyaWFsIjtzOjQ6InNpemUiO3M6MjoiMzAiO31zOjI6ImgzIjthOjM6e3M6NToiY29sb3IiO3M6NjoiMDAwMDAwIjtzOjY6ImZhbWlseSI7czo1OiJBcmlhbCI7czo0OiJzaXplIjtzOjI6IjI4Ijt9czoxOiJhIjthOjQ6e3M6NToiY29sb3IiO3M6NjoiMEYxNjg1IjtzOjY6ImZhbWlseSI7czo1OiJBcmlhbCI7czo0OiJzaXplIjtzOjI6IjEyIjtzOjk6InVuZGVybGluZSI7czoxOiIxIjt9czo2OiJmb290ZXIiO2E6NDp7czo1OiJjb2xvciI7czo2OiIwMDAwMDAiO3M6NjoiZmFtaWx5IjtzOjU6IkFyaWFsIjtzOjQ6InNpemUiO3M6MjoiMTEiO3M6MTA6ImJhY2tncm91bmQiO3M6NjoiRjJGMkYyIjt9czo2OiJoZWFkZXIiO2E6MTp7czoxMDoiYmFja2dyb3VuZCI7czo2OiJGRkZGRkYiO31zOjQ6Imh0bWwiO2E6MTp7czoxMDoiYmFja2dyb3VuZCI7czo2OiJGRkZGRkYiO31zOjc6ImRpdmlkZXIiO2E6Mjp7czoxMDoiYmFja2dyb3VuZCI7czo2OiIwMDAwMDAiO3M6NjoiaGVpZ2h0IjtzOjE6IjUiO319"); $newparams=unserialize(base64_decode($dataEmail['params'])); $newparams['quickselection']['wp-301']['url']=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_07.png"; $newparams['quickselection']['wp-301']['thumb_url']=WYSIJA_EDITOR_IMG."default-newsletter/sample-newsletter-01_07-150x150.png"; $newparams['quickselection']['wp-302']['url']=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_10.png"; $newparams['quickselection']['wp-302']['thumb_url']=WYSIJA_EDITOR_IMG."default-newsletter/sample-newsletter-01_10-150x30.png"; $newparams['quickselection']['wp-303']['url']=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_14.png"; $newparams['quickselection']['wp-303']['thumb_url']=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_14.png"; $newparams['quickselection']['wp-304']['url']=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_16.png"; $newparams['quickselection']['wp-304']['thumb_url']=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_16.png"; $newparams['quickselection']['wp-305']['url']=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_21.png"; $newparams['quickselection']['wp-305']['thumb_url']=WYSIJA_EDITOR_IMG."default-newsletter/sample-newsletter-01_21-150x16.png"; $newparams['quickselection']['wp-306']['url']=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_25.png"; $newparams['quickselection']['wp-306']['thumb_url']=WYSIJA_EDITOR_IMG."default-newsletter/sample-newsletter-01_25-150x150.png"; $newparams['quickselection']['wp-307']['url']=WYSIJA_EDITOR_IMG."default-newsletter/full/white-label-logo.png"; $newparams['quickselection']['wp-307']['thumb_url']=WYSIJA_EDITOR_IMG."default-newsletter/full/white-label-logo.png"; $dataEmail['params']=base64_encode(serialize($newparams)); $newwjdata=unserialize(base64_decode($dataEmail['wj_data'])); $newwjdata["header"]["image"]["src"]=WYSIJA_EDITOR_IMG."default-newsletter/full/white-label-logo.png"; $newwjdata["body"]["block-3"]["image"]["src"]=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_07.png"; $newwjdata["body"]["block-5"]["image"]["src"]=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_14.png"; $newwjdata["body"]["block-8"]["image"]["src"]=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_10.png"; $newwjdata["body"]["block-10"]["image"]["src"]=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_21.png"; $newwjdata["body"]["block-13"]["image"]["src"]=WYSIJA_EDITOR_IMG."default-newsletter/full/sample-newsletter-01_25.png"; $dataEmail['wj_data']=base64_encode(serialize($newwjdata)); $dataEmail['replyto_name']=$dataEmail['from_name']=$valuesconfig['from_name']; $dataEmail['replyto_email']=$dataEmail['from_email']=$valuesconfig['from_email']; $data['email']['email_id']=$modelEmail->insert($dataEmail); $this->notice("Campaign created."); } function createTables(){ $filename = dirname(__FILE__).DS."install.sql"; $handle = fopen($filename, "r"); $query = fread($handle, filesize($filename)); fclose($handle); $modelObj=&WYSIJA::get("user","model"); $query=str_replace("CREATE TABLE IF NOT EXISTS `","CREATE TABLE IF NOT EXISTS `".$modelObj->getPrefix(),$query); $queries=explode("-- QUERY ---",$query); $results=array(); foreach($queries as $qry){ $results[]=dbDelta($qry); } return true; } function createWYSIJAdir(&$values){ $upload_dir = wp_upload_dir(); $dirname=$upload_dir['basedir'].DS."wysija".DS; $url=$upload_dir['baseurl']."/wysija/"; if(!file_exists($dirname)){ if(!mkdir($dirname, 0755,true)){ return false; } } $values['uploadfolder']=$dirname; $values['uploadurl']=$url; $fileHelp=&WYSIJA::get("file","helper"); $resultdir=$fileHelp->makeDir("themes"); if(!$resultdir) return false; } function moveThemes(){ $fileHelp=&WYSIJA::get("file","helper"); $resultdir=$fileHelp->makeDir("templates"); $upload_dir = wp_upload_dir(); $dirname=str_replace("/",DS,$upload_dir['basedir']).DS."wysija".DS."templates".DS; $defaultthemes=WYSIJA_DIR."themes".DS; if(!file_exists($defaultthemes)) return false; $files = scandir($defaultthemes); foreach($files as $filename){ if(!in_array($filename, array('.','..',".DS_Store","Thumbs.db")) && is_dir($defaultthemes.$filename)){ if(!file_exists($defaultthemes.$filename)) continue; $this->rcopy($defaultthemes.$filename, $dirname.$filename); } } } function rrmdir($dir) { if (is_dir($dir)) { $files = scandir($dir); foreach ($files as $file) if ($file != "." && $file != "..") $this->rrmdir("$dir/$file"); rmdir($dir); } else if (file_exists($dir)) unlink($dir); } function rcopy($src, $dst) { if (file_exists($dst)) $this->rrmdir($dst); if (is_dir($src)) { mkdir($dst); $files = scandir($src); foreach ($files as $file) if ($file != "." && $file != "..") $this->rcopy("$src/$file", "$dst/$file"); } else if (file_exists($src)) copy($src, $dst); } function recordDefaultUserField(){ $modelUF=&WYSIJA::get("user_field","model"); $arrayInsert=array( array("name"=>__("First name",WYSIJA),"column_name"=>"firstname","error_message"=>__("Please enter first name",WYSIJA)), array("name"=>__("Last name",WYSIJA),"column_name"=>"lastname","error_message"=>__("Please enter last name",WYSIJA))); foreach($arrayInsert as $insert){ $modelUF->insert($insert); $modelUF->reset(); } } function defaultSettings(&$values){ $datauser=wp_get_current_user(); $values['replyto_name']=$values['from_name']=$datauser->user_login; $values['emails_notified']=$values['replyto_email']=$values['from_email']=$datauser->user_email; } function createPage(&$values){ $my_post = array( 'post_status' => 'publish', 'post_type' => 'wysijap', 'post_author' => 1, 'post_content' => '[wysija_page]', 'post_title' => __("Subscription confirmation",WYSIJA), 'post_name' => 'subscriptions'); $values['confirm_email_link']=wp_insert_post( $my_post ); flush_rewrite_rules(); } function testNLplugins(){ $importHelp=&WYSIJA::get("import","helper"); $importHelp->testPlugins(); } } 