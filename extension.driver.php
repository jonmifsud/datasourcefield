<?php

	Class extension_datasourcefield extends Extension{

		public function about(){
			return array(
				'name' => 'Field: Datasource Select Box',
				'version' => '0.1',
				'release-date' => '2011-12-02',
				'author' => array(
					'name' => 'Jonathan Mifsud',
					'website' => 'http://www.jonmifsud.com',
					'email' => 'info@jonmifsud.com'
				)
			);
		}
		
		// Set the delegates:
		public function getSubscribedDelegates()
		{
			return array(
				array(
					'page' => '/publish/new/',
					'delegate' => 'EntryPostCreate',
					'callback' => 'checkDependency'
				),
				array(
					'page' => '/publish/edit/',
					'delegate' => 'EntryPostEdit',
					'callback' => 'checkDependency'
				),
			);
		}
		
		public function __getPageDatasources($id) {
			$result = Symphony::Database()->fetch("
				SELECT
					p.data_sources
				FROM
					tbl_pages AS p
				WHERE
					p.id = '{$id}'
			");
			return explode(',',$result[0]['data_sources']);
		}
		
		public function __getRelatedDatasources($fieldid,$entryids) {
			$where = "p.entry_id = '{$entryids}'";
			if (is_array($entryids)){
				$wheres = array();
				foreach ($entryids as $entryid){
					$wheres[] = "p.entry_id = '{$entryid}'";
				}
				$where = implode(' or ', $wheres);
			}
			$result = Symphony::Database()->fetchCol('handle',"
				SELECT
					p.handle
				FROM
					tbl_entries_data_{$fieldid} AS p
				WHERE
					{$where}
			");
			return $result;
		}
		
		
		public function checkDependency($context) {		
			//this section id - useful to check if pages field exists here
			$sectionid = $context['section']->get('id');
			
			//related section ids - usefull to check if field is included here
			$relatedSections = $context['section']->fetchAssociatedSections();
			
			//if no related sections to this section then nothing to update
			if (count($relatedSections) == 0) return;
			
			//check if field is in related sections.
			// $datasourcefields = Symphony::Database()->fetch("SELECT `id`,`parent_section` FROM `tbl_fields` WHERE `type` = 'datasource'");
			$fieldManager = new FieldManager(Symphony::Engine());
			$fields = $fieldManager->fetch(NULL, NULL, 'ASC', 'sortorder', 'datasource');
			
			//if no datasource fields then we do not have anything to update
			if (count($fields) == 0) return;
			$datasroucefield_id = $fields[0]->get('id');
			
			//check that this DS has also a pagefield 
			$pagesField = $context['section']->fetchFields('pages');
			if (count($pagesField) == 0) return;
			$pagesFieldID = $pagesField[0]->get('id');
			
			//obtain link field info (subsection manager or selectbox link
			$subsectionField = $context['section']->fetchFields('subsectionmanager');
			$selectboxField = $context['section']->fetchFields('selectbox_link');
			//if no subsection and no selectbox then return nothing to do
			if (count($subsectionField) == 0 && count($selectboxField) == 0 ) return;
			//if selectbox empty then use subsection
			if (count($selectboxField) == 0 )
				$linkFieldID = $subsectionField[0]->get('id');
			else $linkFieldID = $selectboxField[0]->get('id');
			
			//get subsection entry data
			$subsectionData = $context['entry']->getData($linkFieldID);
			$relatedEntries = $subsectionData['relation_id'];
			
			//get page entry data
			$pageData = $context['entry']->getData($pagesFieldID);
			$page = $pageData['page_id'][0];
			
			//get all existing page datasources
			$pagedatasources = $this->__getPageDatasources($page);
			
			//add related datasources to pagedatasources
			$relatedDatasources = $this->__getRelatedDatasources($datasroucefield_id,$relatedEntries);
			$mergedDatasources = array_merge ( $pagedatasources, $relatedDatasources );
			$newDatasources = array_unique($mergedDatasources);
			
			// var_dump($pagedatasources);
			// echo('<br/>');
			// var_dump($relatedDatasources);
			// echo('<br/>');
			// var_dump($newDatasources);die;
			// $datasourcefields = Symphony::Database()->fetch("SELECT `id`,`parent_section` FROM `tbl_fields` WHERE `type` = 'datasource'");
			
			// create the fields to be updated
			$fields = array('data_sources' => @implode(',', $newDatasources));
						
			// update the fields
			if (!Symphony::Database()->update($fields, 'tbl_pages', "`id` = '$page'")) {
				$error = true;
				break;
			}
		}

		public function uninstall(){
			Symphony::Database()->query("DROP TABLE `tbl_fields_datasource`");
		}

		public function install(){
			return Symphony::Database()->query("CREATE TABLE `tbl_fields_datasource` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `field_id` int(11) unsigned NOT NULL,
			  `allow_multiple_selection` enum('yes','no') NOT NULL default 'no',
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `field_id` (`field_id`)
			) ENGINE=MyISAM");
		}

		public function update($previousVersion) {
			// if(version_compare($previousVersion, '1.3', '<')){
			
			// }
			return true;
		}

	}

