<?php

// ##############################################################################
// ASTPP - Open Source VoIP Billing Solution
//
// Copyright (C) 2016 iNextrix Technologies Pvt. Ltd.
// Samir Doshi <samir.doshi@inextrix.com>
// ASTPP Version 3.0 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// ##############################################################################
class IPMAP extends MX_Controller {
	function IPMAP() {
		parent::__construct ();
		$this->load->helper ( 'template_inheritance' );
		$this->load->library ( 'session' );
		$this->load->library ( "ipmap_form" );
		$this->load->library ( 'astpp/form' );
		$this->load->model ( 'ipmap_model' );
		if ($this->session->userdata ( 'user_login' ) == FALSE)
			redirect ( base_url () . '/astpp/login' );
	}
	function ipmap_add() {
		$data ['username'] = $this->session->userdata ( 'user_name' );
		$data ['flag'] = 'create';
		$data ['page_title'] = gettext ( 'Add IP Map' );
		$data ['form'] = $this->form->build_form ( $this->ipmap_form->get_ipmap_form_fields (), '' );
		$this->load->view ( 'ipmap_add_edit', $data );
	}
	function ipmap_edit($edit_id = '') {
		$data ['page_title'] = gettext ( 'Edit IP Map' );
		$where = array (
				'id' => $edit_id 
		);
		$account = $this->db_model->getSelect ( "*", "ip_map", $where );
		// echo $this->db->last_query(); exit;
		foreach ( $account->result_array () as $key => $value ) {
			/**
			 * *******************
			 * ASTPP 3.0
			 * in customer login show ipmap (ACL) module
			 * ***************************************
			 */
			$edit_data = $value;
		}
		/**
		 * **********************************************************************************************
		 */
		$data ['form'] = $this->form->build_form ( $this->ipmap_form->get_ipmap_form_fields (), $edit_data );
		$this->load->view ( 'ipmap_add_edit', $data );
	}
	function ipmap_save() {
		$add_array = $this->input->post ();
		$ip = $add_array ['ip'];
		if (strpos ( $ip, '/' ) !== false) {
			$add_array ['ip'] = $add_array ['ip'];
		} else {
			$add_array ['ip'] = $add_array ['ip'] . '/32';
		}
		$data ['form'] = $this->form->build_form ( $this->ipmap_form->get_ipmap_form_fields (), $add_array );
		if ($add_array ['id'] != '') {
			$data ['page_title'] = gettext ( 'Add IP Map' );
			if ($this->form_validation->run () == FALSE) {
				$data ['validation_errors'] = validation_errors ();
				echo $data ['validation_errors'];
				exit ();
			}
			$this->db->select ( 'prefix,ip' );
			$this->db->where ( [ 
					'prefix' => $add_array ['prefix'],
					'ip' => $add_array ['ip'],
					'id <>' => $add_array ['id'] 
			] );
			$ip_prefix = ( array ) $this->db->get ( 'ip_map' )->first_row ();
			if (! empty ( $ip_prefix )) {
				echo json_encode ( array (
						"prefix_error" => "The Prefix field must contain a unique value.
",
						"ip_error" => "The IP field must contain a unique value." 
				) );
				exit ();
			} else {
				$ip_free = $this->ipmap_model->edit_ipmap ( $add_array, $add_array ['id'] );
				if ($ip_free) {
					$this->load->library ( 'freeswitch_lib' );
					$this->load->module ( 'freeswitch/freeswitch' );
					$command = "api reloadacl";
					$response = $this->freeswitch_model->reload_freeswitch ( $command );
					$this->session->set_userdata ( 'astpp_notification', $response );
				}
				echo json_encode ( array (
						"SUCCESS" => " IP Map updated successfully!" 
				) );
				exit ();
			}
		} else {
			
			if ($this->form_validation->run () == FALSE) {
				$data ['validation_errors'] = validation_errors ();
				echo $data ['validation_errors'];
				exit ();
			}
			$this->db->select ( 'prefix,ip' );
			$this->db->where ( [ 
					'prefix' => $add_array ['prefix'],
					'ip' => $add_array ['ip'] 
			] );
			$ip_prefix = ( array ) $this->db->get ( 'ip_map' )->first_row ();
			
			if (! empty ( $ip_prefix )) {
				echo json_encode ( array (
						"prefix_error" => "The Prefix field must contain a unique value.
",
						"ip_error" => "The IP field must contain a unique value." 
				) );
				exit ();
			} else {
				$ip_free = $this->ipmap_model->add_ipmap ( $add_array );
				if ($ip_free) {
					$this->load->library ( 'freeswitch_lib' );
					$this->load->module ( 'freeswitch/freeswitch' );
					$command = "api reloadacl";
					$response = $this->freeswitch_model->reload_freeswitch ( $command );
					$this->session->set_userdata ( 'astpp_notification', $response );
				}
				echo json_encode ( array (
						"SUCCESS" => " IP Map added successfully!" 
				) );
				exit ();
			}
		}
	}
	function ipmap_delete($id) {
		$ip_free = $this->ipmap_model->remove_ipmap ( $id );
		if ($ip_free) {
			$this->load->library ( 'freeswitch_lib' );
			$this->load->module ( 'freeswitch/freeswitch' );
			$command = "api reloadacl";
			$response = $this->freeswitch_model->reload_freeswitch ( $command );
			$this->session->set_userdata ( 'astpp_notification', $response );
		}
		$this->session->set_flashdata ( 'astpp_notification', 'IP Map removed successfully!' );
		/**
		 * *******************
		 * ASTPP 3.0
		 * in customer login show ipmap (ACL) module
		 * ***************************************
		 */
		$accountdata = $this->session->userdata ['accountinfo'];
		if ($accountdata ['type'] == '0') {
			redirect ( base_url () . 'user/user_ipmap_detail/' );
		} else {
			redirect ( base_url () . 'ipmap/ipmap_detail/' );
		}
	/**
	 * ******************************************************************
	 */
	}
	function ipmap_detail_search() {
		$ajax_search = $this->input->post ( 'ajax_search', 0 );
		if ($this->input->post ( 'advance_search', TRUE ) == 1) {
			$this->session->set_userdata ( 'advance_search', $this->input->post ( 'advance_search' ) );
			$action = $this->input->post ();
			unset ( $action ['action'] );
			unset ( $action ['advance_search'] );
			$this->session->set_userdata ( 'ipmap_list_search', $action );
		}
		if (@$ajax_search != 1) {
			redirect ( base_url () . 'ipmap/ipmap_list/' );
		}
	}
	function ipmap_detail_clearsearchfilter() {
		$this->session->set_userdata ( 'advance_search', 0 );
		$this->session->set_userdata ( 'ipmap_list_search', "" );
	}
	function ipmap_detail() {
		$data ['username'] = $this->session->userdata ( 'user_name' );
		$data ['page_title'] = gettext ( 'IP Map(ACL)' );
		$data ['search_flag'] = true;
		$this->session->set_userdata ( 'advance_search', 0 );
		$data ['grid_fields'] = $this->ipmap_form->build_ipmap_list_for_admin ();
		$data ["grid_buttons"] = $this->ipmap_form->build_grid_buttons ();
		$data ['form_search'] = $this->form->build_serach_form ( $this->ipmap_form->get_ipmap_search_form () );
		$this->load->view ( 'ipmap_view', $data );
	}
	
	/**
	 * *******************
	 * ASTPP 3.0
	 * in customer login show ipmap (ACL) module
	 * ***************************************
	 */
	function ipmap_detail_json() {
		$json_data = array ();
		$account_data = $this->session->userdata ( "accountinfo" );
		$count_all = $this->ipmap_model->ipmap_list ( false );
		$paging_data = $this->form->load_grid_config ( $count_all, $_GET ['rp'], $_GET ['page'] );
		$json_data = $paging_data ["json_paging"];
		$query = $this->ipmap_model->ipmap_list ( true, $paging_data ["paging"] ["start"], $paging_data ["paging"] ["page_no"] );
		$query = $query->result_array ();
		foreach ( $query as $key => $value ) {
			$ipmap_checkbox = '<input type="checkbox" name="chkAll" id="' . $value ['id'] . '" class="ace chkRefNos" onclick="clickchkbox(' . $value ['id'] . ')" value=' . $value ['id'] . '><lable class="lbl"></lable>';
			if ($account_data ['type'] == '0') {
				$ret_url = '<a href="' . base_url () . 'user/user_ipmap_edit/' . $value ['id'] . '" class="btn btn-royelblue btn-sm"  rel="facebox" title="Edit">&nbsp;<i class="fa fa-pencil-square-o fa-fw"></i></a>&nbsp;<a href="' . base_url () . 'user/user_ipmap_delete/' . $value ['id'] . '" class="btn btn-royelblue btn-sm" title="Delete" onClick="return get_alert_msg();">&nbsp;<i class="fa fa-trash fa-fw"></i></a>';
				$account_name = '';
				$json_data ['rows'] [] = array (
						'cell' => array (
								$ipmap_checkbox,
								$value ['name'],
								$value ['ip'],
								$value ['prefix'],
								$this->common->get_status ( 'status', 'ip_map', $value ),
								$this->common->convert_GMT_to ( '', '', $value ['created_date'] ),
								$this->common->convert_GMT_to ( '', '', $value ['last_modified_date'] ),
								$ret_url 
						)
						 
				);
			} else {
				$ret_url = '<a href="' . base_url () . 'ipmap/ipmap_edit/' . $value ['id'] . '" class="btn btn-royelblue btn-sm"  rel="facebox" title="Edit">&nbsp;<i class="fa fa-pencil-square-o fa-fw"></i></a>&nbsp;<a href="' . base_url () . 'ipmap/ipmap_delete/' . $value ['id'] . '" class="btn btn-royelblue btn-sm" title="Delete" onClick="return get_alert_msg();">&nbsp;<i class="fa fa-trash fa-fw"></i></a>';
				$account_name = $this->common->build_concat_string ( "first_name,last_name,number", "accounts", $value ['accountid'] );
				$json_data ['rows'] [] = array (
						'cell' => array (
								$ipmap_checkbox,
								$account_name,
								$value ['name'],
								$value ['ip'],
								$value ['prefix'],
								$this->common->get_status ( 'status', 'ip_map', $value ),
								$this->common->convert_GMT_to ( '', '', $value ['created_date'] ),
								$this->common->convert_GMT_to ( '', '', $value ['last_modified_date'] ),
								$ret_url 
						)
						 
				);
			}
		}
		
		echo json_encode ( $json_data );
	}
	/**
	 * **********************************************************
	 */
	function ipmap_delete_multiple() {
		$ids = $this->input->post ( "selected_ids", true );
		$where = "id IN ($ids)";
		$this->db->where ( $where );
		echo $this->db->delete ( "ip_map" );
	}
}

?>
 
