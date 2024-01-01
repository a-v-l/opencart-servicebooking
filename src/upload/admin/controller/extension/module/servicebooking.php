<?php
class ControllerExtensionModuleServicebooking extends Controller {
    private $error = array();
  
    public function index() {
        $this->load->language('extension/module/servicebooking');
        
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module/servicebooking');

        $this->model_extension_module_servicebooking->init();

        $url = ''; // Filter: /upload/admin/controller/catalog/product.php
        
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 's.servicebooking_nr';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
            $url .= '&order=' . ($this->request->get['order'] == 'ASC' ? 'DESC' : 'ASC');
        } else {
            $order = 'DESC';
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

		$data['sort_servicebooking_nr'] = $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . '&sort=s.servicebooking_nr' . $url, true);
		$data['sort_acceptance'] = $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . '&sort=s.acceptance' . $url, true);
		$data['sort_submission'] = $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . '&sort=s.submission' . $url, true);
		$data['sort_typ'] = $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . '&sort=s.typ' . $url, true);
		$data['sort_customer_name'] = $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . '&sort=s.customer_name' . $url, true);
		$data['sort_status'] = $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . '&sort=s.status' . $url, true);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['servicebookings'] = array();
        // $data['servicebookings'] = array(
        // 	array(
        // 		'id' 				=> '1', 					// int
        // 		'number'			=> '1234', 					// int, Auftragsnummer
        // 		'acceptance'		=> '2019-07-15', 			// DATE, Annahmedatum
        // 		'submission'		=> '2019-07-22', 			// DATE, tatsächliches Abgabedatum
        // 		'status'			=> '1', 					// tinyint, ist der Auftrag erledigt?
        // 		'typ'				=> 'Instandsetzung',		// ENUM, Kleine Inspektion, Instandsetzung, Große Inspektion, Winterservice
        // 		'honorar'			=> '72.50', 				// decimal, Honorar
        // 		'customer_id'		=> '1', 					// int, Kunden-ID oder NULL
        // 		'customer_name'		=> 'Hans Peter',			// varchar, Kundenname
        // 		'customer_contact'	=> 'hp@example.com',		// TXT, Kontaktinfos
        // 		'comment'			=> 'Die Bremse schleift',	// TXT, Bemerkungen (vom Kunden)
        // 	),
        // );

        $filter_data = array(
            'sort'            => $sort,
            'order'           => $order,
            'start'           => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'           => $this->config->get('config_limit_admin'),
            'status'          => (isset($this->request->get['filter']) ? $this->request->get['filter'] : '')
        );

        $servicebooking_total = $this->model_extension_module_servicebooking->getTotalServicebookings($filter_data);

        $results = $this->model_extension_module_servicebooking->getServicebookings($filter_data);

        foreach ($results as $result) {
            $color = "";
            $color = (int)date('W', strtotime($result['date_created'])) % 8;
            $color = "color-" . ($color == 0 ? 8 : $color);
            $data['servicebookings'][] = array(
                'servicebooking_id'     => $result['servicebooking_id'],
                'servicebooking_color'  => $color,
                'priority'              => ($result['priority'] != null ? 'priority' : ''),
                'servicebooking_nr'     => $result['servicebooking_nr'],
                'week_increment'        => $result['week_increment'],
                'acceptance' 			=> $result['acceptance'],
                'submission' 			=> $result['submission'],
                'typ' 				    => $result['typ'],
                'customer_id' 		    => $result['customer_id'],
                'customer_name' 	    => $result['customer_name'],
                'customer_contact' 	    => $result['customer_contact'],
                'comment' 			    => $result['comment'],
                'honorar' 			    => $result['honorar'],
                'status'     		    => $result['status'],
                'edit'       		    => $this->url->link('extension/module/servicebooking/edit', 'user_token=' . $this->session->data['user_token'] . '&servicebooking_id=' . $result['servicebooking_id'] . $url, true)
            );
        }

        $data['settings'] = $this->model_extension_module_servicebooking->loadSettings();

        $booking_nrs = array_column($data['servicebookings'], 'servicebooking_nr');
        array_push($booking_nrs, 0);
        $data['new_servicebooking_nr'] = max($booking_nrs) + 1;

        $data['user_token'] = $this->session->data['user_token'];

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
        }

        $data['isfilter'] = isset($this->request->get['filter']) ? $this->request->get['filter'] : false;
        $data['filter_url'] = $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $url .= $data['isfilter'] ? '&filter' : '';
        
		$pagination = new Pagination();
		$pagination->total = $servicebooking_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/servicebooking', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($servicebooking_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($servicebooking_total - $this->config->get('config_limit_admin'))) ? $servicebooking_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $servicebooking_total, ceil($servicebooking_total / $this->config->get('config_limit_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;
    
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
    
        $this->response->setOutput($this->load->view('extension/module/servicebooking', $data));
    }
    
    public function saveChanges()
    {
        $data = $this->request->post;
        $this->load->model('extension/module/servicebooking');

        // validation
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'servicebooking_nr':
                    // int only
                    $re = '/^\d+$/';
                    $msg = ['msg' => 'Die Auftragsnummer MUSS eine Nummer sein!', 'pass' => false];
                    break;
                case 'acceptance':
                case 'submission':
                    // YYYY-MM-DD only
                    $re = '/\d{4}-\d{2}-\d{2}/';
                    $msg = ['msg' => 'Annahme- und Abgabedatum MÜSSEN im Format YYYY-MM-DD angegeben werden!', 'pass' => false];
                    break;
                case 'honorar':
                    // float with max 2 digits precision OR empty
                    $re = '/^(\d+(\.\d{1,2}){0,1}|)$/';
                    $msg = ['msg' => 'Das Honorar MUSS eine Dezimalzahl mit maximal 2 Nachkommastellen sein. Anstatt eines Kommas MUSS ein Punkt verwendet werden!', 'pass' => false];
                    break;
                
                default:
                    $re = false;
                    break;
            }
            if ($re) {
                preg_match($re, $value, $matches, PREG_OFFSET_CAPTURE, 0);
                if (count($matches) == 0) {
                    die(json_encode($msg));
                }
            }
        }

        // entry exists: change
        if ($data['entry'] == 'existing') {
            $msg = ['msg' => $this->model_extension_module_servicebooking->editServicebooking($data), 'pass' => true];
        // new entry: create
        } else {
            $msg = ['msg' => $this->model_extension_module_servicebooking->addServicebooking($data), 'pass' => true];
        }
        echo json_encode($msg);
    }
    
    public function deleteEntry()
    {
        $data = $this->request->post;
        $this->load->model('extension/module/servicebooking');

        foreach ($data['selected'] as $servicebooking_id) {
            $this->model_extension_module_servicebooking->deleteServicebooking($servicebooking_id);
        }
        echo (count($data['selected']) == 1 ? "Der Eintrag wurde" : "Die Einträge wurden") . " erfolgreich gelöscht.";
    }

    public function saveSettings()
    {
        $data = $this->request->post;
        $this->load->model('extension/module/servicebooking');
        $this->model_extension_module_servicebooking->saveSettings($data);
        echo "Die Daten wurden erfolgreich gespeichert!";
    }
}