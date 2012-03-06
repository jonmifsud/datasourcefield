<?php

	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.datasource.php');
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	Class fieldDatasource extends Field{

		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Datasource Select Box';
			$this->_required = true;

			// Set default
			$this->set('required', 'no');
			$this->set('show_column', 'no');
		}

		function canToggle(){
			return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
		}

		function allowDatasourceOutputGrouping(){
			return false;
		}

		function allowDatasourceParamOutput(){
			return false;
		}

		function canFilter(){
			return false;
		}

		function canPrePopulate(){
			return false;
		}

		function isSortable(){
			return false;
		}

		function appendFormattedElement(&$wrapper, $data, $encode=false){

			if(!is_array($data) || empty($data)) return;

			$list = new XMLElement($this->get('element_name'));

			if(!is_array($data['handle'])) $data['handle'] = array($data['handle']);
			if(!is_array($data['title'])) $data['title'] = array($data['title']);

			for($ii = 0; $ii < count($data['handle']); $ii++){
				$list->appendChild(new XMLElement('datasource', General::sanitize($data['title'][$ii]), array('handle' => $data['handle'][$ii])));
			}

			$wrapper->appendChild($list);
		}

		function getToggleStates($include_parent_titles=true){

			
			$dsm = new DatasourceManager(Administration::instance());
			$datasources = $dsm->listAll();	
		
			foreach ($datasources as $ds) {
				$states[$ds['handle']] = $ds['name'];//handle & name??
			}

			if($this->get('sort_options') == 'yes') {
				natsort($states);
			}

			return $states;
		}

		function toggleFieldData($data, $newState){
	
			var_dump($data);die;
			// $page = Symphony::Database()->fetchRow(0, "SELECT `title`, `id`, `handle` FROM `tbl_pages` WHERE `id` = '$newState' LIMIT 1");

			// $data['handle'] = $page['handle'];
			// $data['title'] = $page['title'];
			// $data['page_id'] = $page['id'];

			return $data;
		}

		function __sortTitlesAscending($t1, $t2){
			return strcmp(strtolower($t1[2]), strtolower($t2[2]));
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			
			$states = $this->getToggleStates();
			
			if(!is_array($data['handle'])) $data['handle'] = array($data['handle']);
			
			if(!is_array($data['title'])) $data['title'] = array($data['title']);

			$options = array();

			if($this->get('required') != 'yes' && $this->get('allow_multiple_selection') != 'yes') $options[] = array(NULL, false, NULL);

			foreach($states as $id => $title){
				$options[] = array($id, in_array($id, $data['handle']), General::sanitize($title));
			}

			usort($options, array('fieldDatasource','__sortTitlesAscending'));

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		function prepareTableValue($data, XMLElement $link=NULL){
			
			if (is_array( $data['title']))
				$value= implode ( ", " , $data['title'] );
			else $value= $data['title'];
			
			return parent::prepareTableValue(array('value' => General::sanitize($value)), $link);
		}

		function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;
			
			$dsm = new DatasourceManager(Administration::instance());
			$datasources = $dsm->listAll();	

			if(empty($data)) return NULL;

			if(!is_array($data)) $data = array($data);

			$result = array('title' => array(), 'handle' => array());
			foreach($data as $ds){
				$datasource = $datasources[$ds];

				$result['handle'][] = $datasource['handle'];
				$result['title'][] = $datasource['name'];
			}

			return $result;
		}

		function commit(){

			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['allow_multiple_selection'] = ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no');

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");

			if(!Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle())) return false;

			return true;

		}

		function findDefaults(&$fields){
			if(!isset($fields['allow_multiple_selection'])) $fields['allow_multiple_selection'] = 'no';
		}

		function displaySettingsPanel(&$wrapper, $errors=NULL){

			parent::displaySettingsPanel($wrapper, $errors);

			## Allow selection of multiple items
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');
			if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Allow selection of multiple pages');
			$wrapper->appendChild($label);

			$this->appendShowColumnCheckbox($wrapper);
			$this->appendRequiredCheckbox($wrapper);
		}

		function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `title` varchar(255) default NULL,
				  `handle` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `handle` (`handle`),
				  KEY `page_id` (`page_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
		}

		public function getExampleFormMarkup(){
			$states = $this->getToggleStates();

			$options = array();

			foreach($states as $handle => $v){
				$options[] = array($handle, NULL, $v);
			}

			$fieldname = 'fields['.$this->get('element_name').']';
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			return $label;
		}

		
		/*
		 * No Elements to be included by this field as it is for selecting DSs
		 */
		public function fetchIncludableElements(){
			return array();
		}

	}

