<?php
class ControllerExtensionDashboardServicebookingdash extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/dashboard/servicebookingdash');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('dashboard_servicebookingdash', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/dashboard/servicebookingdash', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/dashboard/servicebookingdash', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true);

		if (isset($this->request->post['dashboard_servicebookingdash_width'])) {
			$data['dashboard_servicebookingdash_width'] = $this->request->post['dashboard_servicebookingdash_width'];
		} else {
			$data['dashboard_servicebookingdash_width'] = $this->config->get('dashboard_servicebookingdash_width');
		}

		$data['columns'] = array();
		
		for ($i = 3; $i <= 12; $i++) {
			$data['columns'][] = $i;
		}
				
		if (isset($this->request->post['dashboard_servicebookingdash_status'])) {
			$data['dashboard_servicebookingdash_status'] = $this->request->post['dashboard_servicebookingdash_status'];
		} else {
			$data['dashboard_servicebookingdash_status'] = $this->config->get('dashboard_servicebookingdash_status');
		}

		if (isset($this->request->post['dashboard_servicebookingdash_sort_order'])) {
			$data['dashboard_servicebookingdash_sort_order'] = $this->request->post['dashboard_servicebookingdash_sort_order'];
		} else {
			$data['dashboard_servicebookingdash_sort_order'] = $this->config->get('dashboard_servicebookingdash_sort_order');
		}

		if (isset($this->request->post['dashboard_servicebookingdash_max_bookings'])) {
			$data['dashboard_servicebookingdash_max_bookings'] = $this->request->post['dashboard_servicebookingdash_max_bookings'];
		} else {
			$data['dashboard_servicebookingdash_max_bookings'] = $this->config->get('dashboard_servicebookingdash_max_bookings');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/dashboard/servicebookingdash_form', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/dashboard/servicebookingdash')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	
	public function dashboard() {
		$this->load->language('extension/dashboard/servicebookingdash');

		$data['user_token'] = $this->session->data['user_token'];

		// All servicebookings with status == 'Angemeldet'
		$data['bookings'] = array();

		$this->load->model('extension/module/servicebooking');
        
		$results = $this->model_extension_module_servicebooking->getServicebookings(array(
			'status' => 'Angemeldet',
			'order' => 'DESC',
			'limit' => $this->config->get('dashboard_servicebookingdash_max_bookings')
		));

		foreach ($results as $result) {
            $color = "";
			$color = (int)date('W', strtotime($result['date_created'])) % 8;
			$color = "color-" . ($color == 0 ? 8 : $color);
			$data['bookings'][] = array(
				'servicebooking_nr' 	=> $result['servicebooking_nr'],
                'week_increment'        => $result['week_increment'],
                'color'					=> $color,
				'acceptance'           	=> $result['acceptance'],
				'customer_name'     	=> $result['customer_name'],
				'customer_contact'  	=> $result['customer_contact'],
				'edit'              	=> $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . '&servicebooking_nr=' . $result['servicebooking_nr'], true),
			);
		}

		return $this->load->view('extension/dashboard/servicebookingdash_info', $data);
	}
}