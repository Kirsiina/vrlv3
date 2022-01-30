<?php
class Yllapito_tunnukset extends CI_Controller
{
    
    private $allowed_user_groups = array('admin', 'tunnukset');
    
    function __construct()
    {
        parent::__construct();
              
        $this->load->library('user_rights', array('groups' => $this->allowed_user_groups));
        if (!$this->user_rights->is_allowed()){       
            redirect($this->user_rights->redirect());
        }
        $this->load->model("Tunnukset_model");
        $this->load->model("Oikeudet_model");
    }

    //ADMIN-OSUUS
    
    //salainen adminin funktio, jolla voi lisätä käyttäjän johonkin käyttöoikeusryhmään
    //parametrit query stringinä
    function add_user_to_group()
    {
        if(!$this->ion_auth->is_admin())
            $vars['msg'] = "Et ole admin";
        else
        {
            $userid = $this->input->get('userid', TRUE);
            $groupid = $this->input->get('groupid', TRUE);
            
            if($userid != false && $groupid != false)
            {
                if($this->ion_auth->add_to_group($groupid, $userid) == true)
                    $vars['msg'] = "Onnistui";
                else
                    $vars['msg'] = "Epäonnistui";
            }
            else
                $vars['msg'] = "Kämmäsit parametrit";
        }

        $this->fuel->pages->render('misc/naytaviesti', $vars);
    }
    
    //HAKEMUSJONO-OSUUS
    
    function hakemusjono_etusivu()
    {
        $this->load->model('Tunnukset_model');
        $this->session->set_flashdata('return_status', '');
        
        $vars['view_status'] = "queue_status";
        
        $vars['queue_length'] = $this->Tunnukset_model->get_application_queue_length();
        if($vars['queue_length'] > 0)
            $vars['oldest_application'] = $this->Tunnukset_model->get_oldest_application();
            
        $vars['queue_unlocked_num'] = $this->Tunnukset_model->get_application_queue_unlocked_num();
        $vars['latest_approvals'] = $this->Tunnukset_model->get_latest_approvals();
        $vars['latest_logins'] = $this->Tunnukset_model->get_latest_logins();
            
        $this->fuel->pages->render('yllapito/hakemusjono', $vars);
    }
    
    function hakemusjono()
    {
        $this->load->model('Tunnukset_model');
        $this->session->set_flashdata('return_status', '');
        
        $vars['view_status'] = "next_join_application";
        
        $vars['application_data'] = $this->Tunnukset_model->get_next_application();
        
        if($vars['application_data']['success'] == false)
        {
            $this->session->set_flashdata('return_info', 'Uuden hakemuksen noutaminen epäonnistui!<br />Joku saattaa olla jo hyväksymässä loppuja hakemuksia, hakemukset loppuivat, tai tapahtui muu virhe.');
            $this->session->set_flashdata('return_status', 'danger');
            redirect('/yllapito/tunnukset');
        }
        else {
            $vars['same_ip_logins'] = $this->Tunnukset_model->get_logins_by_ip($vars['application_data']['ip']);
            $vars['same_nicknames'] = $this->Tunnukset_model->get_pinnumbers_by_nickname($vars['application_data']['nimimerkki']);
            $vars['application_data']['rekisteroitynyt'] = date('d.m.Y H:i',strtotime($vars['application_data']['rekisteroitynyt']));
                
            $this->fuel->pages->render('yllapito/hakemusjono', $vars);
        }
    }
    
    function kasittele_hakemus($approved, $id)
    {
        $user = $this->ion_auth->user()->row();
        $date = new DateTime();
        $new_pinnumber = -1;
        $date->setTimestamp(time());
        $this->session->set_flashdata('return_status', '');
        $rej_reason = $this->input->post('rejection_reason');
        
        if(is_numeric($id) && $id >= 0 && ($approved == 'hyvaksy' || (isset($rej_reason) && strlen($rej_reason) > 5 && $approved == 'hylkaa')))
        {
            $this->load->library('vrl_email');
            $this->load->model('Tunnukset_model');
        
            $application_data = $this->Tunnukset_model->get_application($id);
            
            //email message
            $email = "";
            
            if($application_data['success'] == false)
            {
                $this->session->set_flashdata('return_info', 'Hakemuksen käsittely epäonnistui!');
                $this->session->set_flashdata('return_status', 'danger');
                redirect('/yllapito/tunnukset/hyvaksy');
            }
            
            if($approved == 'hyvaksy')
            {   
                $additional_data = array('nimimerkki' => $application_data['nimimerkki']);
                $additional_data['hyvaksytty'] = $date->format('Y-m-d H:i:s');
                $additional_data['hyvaksyi'] = $user->tunnus;
                $additional_data['tunnus'] = $this->Tunnukset_model->get_next_pinnumber();
                $new_pinnumber = str_pad($additional_data['tunnus'], 5, '0', STR_PAD_LEFT);
                
                $this->ion_auth->register($new_pinnumber,
                                          substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZqwertyuioplkjhgfdsazxcvbnm!"#¤%&/()=?*@£$€{[]}'),1,random_int(10, 20)),
                                          $application_data['email'], $additional_data);
                
                $code = $this->Ion_auth_model->forgotten_password($new_pinnumber);
                
                $message = $this->load->view('email/tunnus_hyvaksytty', array('code'=>$code, 'new_pinnumber'=>$new_pinnumber), TRUE);
                
                $this->session->set_flashdata('return_info', 'Hakemus hyväksytty.');
                $this->session->set_flashdata('return_status', 'success');
            }
            else
            {
                //Onko tarve? $this->Tunnukset_model->add_rejected_user($id); //Hylkäys muistiin
                
                
                $message = $this->load->view('email/tunnus_hylatty', array('reason'=>$rej_reason ), TRUE);
            

               
                $this->session->set_flashdata('return_info', 'Hakemus hylätty.');
                $this->session->set_flashdata('return_status', 'success');
            }
            
            
                //email
            $this->load->library('vrl_email');
            $to = $application_data['email']; 
            $subject = 'VRL-tunnushakemuksesi on käsitelty';
                
			if ($this->vrl_email->send($to, $subject, $message)){
				$vars['msg'] = "Tunnuksen käsittely onnistui.";
				$vars['msg_type'] = "success";
            }
					//What if sending fails?
                
            //poistetaan hakemus kun se on nyt käsitelty
            $this->Tunnukset_model->delete_application($id);
            $this->hakemusjono();

        }else {
            $this->fuel->pages->render('misc/naytaviesti', array('msg_type' => 'danger', 'msg' => "Virheellinen syöte."));

            
        }
    }
    
    
    
    public function muokkaa($tunnus = null)
	{
        $data['title'] = "Muokkaa käyttäjän tietoja";
        $this->load->library("vrl_helper");
        //jos haettiin tunnusta, avataan ko. tunnuksen editori
        if($this->input->server('REQUEST_METHOD') == 'POST' && $this->input->post('tunnushaku')){
            $tunnus = $this->input->post('VRL');
            if($this->vrl_helper->check_vrl_syntax($tunnus)){
                redirect('/yllapito/tunnukset/muokkaa/'.$this->vrl_helper->vrl_to_number($tunnus), 'refresh');
                return;
            } else  {
                $data['msg'] = "Tunnusta ei löydy";
                $data['msg_type'] = "danger";
                
                $this->fuel->pages->render('misc/naytaviesti', $data);
            }
         
        }
        //jos tunnus on annettu
        else if ($this->vrl_helper->check_vrl_syntax($tunnus)){
            $user_id = $this->ion_auth->get_user_id_from_identity($this->vrl_helper->vrl_to_number($tunnus));
            
            if ($user_id == false){
                $data['msg'] = "Tunnusta ei löydy";
                $data['msg_type'] = "danger";
                
                $this->fuel->pages->render('misc/naytaviesti', $data);

            }
            //halutaan muokata
            else if($this->input->server('REQUEST_METHOD') == 'POST' && $this->input->post('nimimerkki')){
    
                $this->_edit_user($tunnus);
                $data['msg'] = "Muutokset tallennettu!";
                $data['msg_type'] = "success";
                    
                $data['form'] = $this->_edit_user_form($user_id);
                $this->fuel->pages->render('misc/lomakemuokkaus', $data);
 
            }
           
           //näytetään editori
            else {

                // set the flash data error message if there is one
                $data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
                $data['form'] = $this->_edit_user_form($user_id);
        
                $this->fuel->pages->render('misc/lomakemuokkaus', $data);
    
            }
        
        }
    
        
        //eio tunnusta, haetaan tunnuksenhakulomake
        else {
            $this->load->library('form_builder', array('submit_value' => 'Hae', 'submit_name'=>'tunnushaku'));
            $fields['VRL'] = array('type' => 'text', 'class'=>'form-control');
            $this->form_builder->form_attrs = array('method' => 'post', 'action' => site_url('/yllapito/tunnukset/muokkaa'));                  
            $data['form'] = $this->form_builder->render_template('_layouts/basic_form_template', $fields);
            $this->fuel->pages->render('misc/lomakemuokkaus', $data);

        }
                    
            
    }
    
    private function _edit_user_form($user_id = null){
                        $user = $this->ion_auth->user($user_id)->row();
                $groups = $this->ion_auth->groups()->result_array();
                $currentGroups = $this->ion_auth->get_users_groups($user_id)->result();
                $groups = $this->Oikeudet_model->sanitize_automatic_groups($groups);
                $group_options = array();
                
                foreach ($groups as $group){
                    $group_options[$group['id']] = $group['name'] . ' (' . $group['description'] . ')';
                }
                
                $users_groups=array();
                foreach ($currentGroups as $group){
                    $users_groups[]=$group->id;
                }
                
           
                $data['msg'] = "Valitse käyttäjälle sopivat oikeudet";
                $this->load->library('form_builder', array('submit_value' => "Muokkaa oikeuksia", 'submit_name' => 'oikeus', 'required_text' => '*Pakollinen kenttä'));
                $fields['tunnus'] = array('type' => 'hidden', 'value' => $user->tunnus);
                
                 $fields['nimimerkki'] = array('type' => 'text', 'value' => $user->nimimerkki, 'class'=>'form-control');
                $fields['email'] = array('type' => 'text', 'value' => $user->email, 'label' => 'Sähköpostiosoite', 'after_html' => '<span class="form_comment">Anna toimiva osoite jotta voit tarvittaessa palauttaa salasanasi!</span>', 'class'=>'form-control');
                $fields['nayta_email'] = array('type' => 'checkbox', 'checked' => $user->nayta_email, 'label' => 'Näytetäänkö sähköposti julkisesti?', 'after_html' => '<span class="form_comment">Näytetäänkö sähköposti julkisesti profiilissasi.</span>', 'class'=>'form-control');
                $fields['kuvaus'] = array('type' => 'textarea', 'value' => $user->kuvaus ?? "", 'cols' => 40, 'rows' => 3, 'class'=>'form-control',
                                  'after_html' => '<span class="form_comment">Voit kirjoittaa tähän esim. millainen harrastaja olet tai vaikka listata roolihahmosi, jos pidät eri talleja eri nimillä!</span>');

                $fields['oikeudet'] = array('type' => 'multi', 'mode' => 'checkbox', 'required' => TRUE, 'options' => $group_options, 'value'=>$users_groups, 'class'=>'form-control', 'wrapper_tag' => 'li');

                $this->form_builder->form_attrs = array('method' => 'post',
                                                        'action' => '/yllapito/tunnukset/muokkaa/'.$this->vrl_helper->vrl_to_number($user->tunnus));
                
                
                return $this->form_builder->render_template('_layouts/basic_form_template', $fields);
    }
    
    
    private function _edit_user(&$msg){
        
            $tunnus = $this->vrl_helper->vrl_to_number($this->input->post('tunnus'));
            $user_id = $this->ion_auth->get_user_id_from_identity($tunnus);
            $user = $this->ion_auth->user($user_id)->row();
            
            //sortataan käyttöoikeudet
            $this->sort_users_groups($this->input->post('oikeudet'), $this->ion_auth->get_user_id_from_identity($tunnus));
            
            $valid = true;
            $previous_nick = $user->nimimerkki;
            
            $this->load->helper(array('form', 'url'));
            
            if($this->input->post('email') != $user->email) //validointi katsoo tietokannasta duplikaatit joten tee se vain jos vaihdetaan email
                $this->form_validation->set_rules('email', 'Sähköpostiosoite', 'valid_email|is_unique[vrlv3_tunnukset.email]|is_unique[vrlv3_tunnukset_jonossa.email]');
            
            $this->form_validation->set_rules('nimimerkki', 'Nimimerkki', "min_length[1]|max_length[20]|regex_match[/^[A-Za-z0-9_\-.:,; *~#&'@()]*$/]");
            $this->form_validation->set_rules('nayta_email', 'Sähköpostin näkyvyys', 'min_length[1]|max_length[1]|numeric|regex_match[/^[01]*$/]');
         
            
            if ($this->form_validation->run() == true && $valid == true)
            {
                $vars['success'] = true;
                $update_data = array();
                
                if(!empty($this->input->post('nimimerkki')))
                    $update_data['nimimerkki'] = $this->input->post('nimimerkki');
                    
                if(!empty($this->input->post('email')))
                    $update_data['email'] = $this->input->post('email');
                    
                if(!empty($this->input->post('kuvaus')))
                    $update_data['kuvaus'] = $this->input->post('kuvaus');
                
                    
                $update_data['nayta_email'] = $this->input->post('nayta_email');    

                if(!empty($update_data))
                {
                    $vars['success'] = $this->ion_auth->update($user->id, $update_data);
                    
                    if($vars['success'] == true && !empty($this->input->post('nimimerkki')) && $this->input->post('nimimerkki') != $user->nimimerkki)
                        $this->Tunnukset_model->add_previous_nickname($previous_nick, $user->tunnus);
                }
            }
    }
        
    public function oikeudet($oikeus = null){
        $this->load->model('Oikeudet_model');
        $vars['title'] = 'Käyttöoikeudet';				

        if ($oikeus == null){        
        
			$vars['text_view'] = "";			
			$vars['headers'][1] = array('title' => 'Id', 'key' => 'id', 'key_link' => site_url('yllapito/tunnukset/oikeudet/'));
			$vars['headers'][2] = array('title' => 'Oikeusryhmä', 'key' => 'name');
			$vars['headers'][3] = array('title' => 'Kuvaus', 'key' => 'description');			
			$vars['headers'][4] = array('title' => 'Jäsenet (kpl)', 'key' => 'kpl');
			
			$vars['headers'] = json_encode($vars['headers']);		
			$stables = $this->Oikeudet_model->get_groups();
			
			$vars['data'] = json_encode($stables);
	
			$this->fuel->pages->render('misc/taulukko', $vars);
        }
            
        else {
            				
			$vars['text_view'] = "";
			
			$vars['headers'][1] = array('title' => 'Tunnus', 'key' => 'tunnus', 'type'=>'VRL', 'key_link' => site_url('/tunnus/'));
			$vars['headers'][2] = array('title' => 'Nimimerkki', 'key' => 'nimimerkki');
			$vars['headers'][3] = array('title' => 'Editoi', 'key' => 'tunnus', 'key_link' => site_url('yllapito/tunnukset/muokkaa/'), 'image' => site_url('assets/images/icons/edit.png'));
			
			
			$vars['headers'] = json_encode($vars['headers']);
			
			
			$stables = $this->Oikeudet_model->users_in_group_id($oikeus);
			$vars['data'] = json_encode($stables);
	
			$this->fuel->pages->render('misc/taulukko', $vars);
            }
        
        
    }
    
    public function kirjautumiset($tapa = 'tunnus'){
        $data = array();
        $data['title'] = 'Viimeisimpien kirjautumisten haku';
        $this->load->library('vrl_helper');
        if($tapa == 'tunnus'){
                $this->load->library('form_builder', array('submit_value' => 'Hae kirjautumiset', 'submit_name'=>'tunnushaku'));
                $fields['tunnus'] = array('type' => 'text', 'class'=>'form-control', 'after_html' => '<span class="form_comment">VRL-tunnus, jonka viimeiset kirjautumistiedot haluat.</span>');
                $this->form_builder->form_attrs = array('method' => 'post', 'action' => site_url('/yllapito/tunnukset/kirjautumiset/tunnus'));                  
                $data['form'] = $this->form_builder->render_template('_layouts/basic_form_template', $fields);
        }else if ($tapa == 'ip'){
                $this->load->library('form_builder', array('submit_value' => 'Hae kirjautumiset', 'submit_name'=>'iphaku'));
                $fields['ip'] = array('type' => 'text', 'class'=>'form-control', 'after_html' => '<span class="form_comment">IP osoite (Esim. 127.0.0.1) josta kirjautuneet haluat.</span>');
                $this->form_builder->form_attrs = array('method' => 'post', 'action' => site_url('/yllapito/tunnukset/kirjautumiset/ip'));                  
                $data['form'] = $this->form_builder->render_template('_layouts/basic_form_template', $fields);
        }
        
        if($this->input->server('REQUEST_METHOD') == 'POST'){
            $vars['text_view'] = "";
            
            $vars['headers'][1] = array('title' => 'Tunnus', 'key' => 'tunnus', 'type' => 'vrl', 'key_link' => site_url('tunnus/'));
            $vars['headers'][2] = array('title' => 'Nimimerkki', 'key' => 'nimimerkki');
            $vars['headers'][3] = array('title' => 'Aika', 'key' => 'aika', 'type'=>'date');
            $vars['headers'][4] = array('title' => 'Ip-osoite', 'key' => 'ip');

            $vars['headers'] = json_encode($vars['headers']);
            if($tapa == 'tunnus' && $this->input->post('tunnushaku')){
                if($this->vrl_helper->check_vrl_syntax($this->input->post('tunnus'))){
                    $latest_logins = $this->Tunnukset_model->get_latest_logins($this->vrl_helper->vrl_to_number($this->input->post('tunnus')), 1000);
                
                    $vars['data'] = json_encode($latest_logins);            
                    $data['tulokset'] = $this->load->view('misc/taulukko', $vars, TRUE);

                }
                else {
                    $this->fuel->pages->render('misc/naytaviesti', array("msg"=>"Virheellinen tunnus!", "msg_type"=>"danger"));
                }
            }
            
            else if($tapa == 'ip' && $this->input->post('iphaku')){
                $latest_logins = $this->Tunnukset_model->get_logins_by_ip($this->input->post('ip'), 1000);
                
                $vars['data'] = json_encode($latest_logins);            
                $data['tulokset'] = $this->load->view('misc/taulukko', $vars, TRUE);


            }
        }


            $this->fuel->pages->render('misc/haku', $data);
            
            
        
        
        
    }
    
    
    
        
    private function sort_users_groups($groupData, $id){
            // Only allow updating groups if user is admin
            // Update the groups user belongs to
            
            $groups = $this->Oikeudet_model->get_groups();
            $groups = $this->Oikeudet_model->sanitize_automatic_groups($groups);
            
            $removable_group_list = array();
            foreach($groups as $group){
                
                $removable_group_list[] = $group['id'];                

            }
            
            

            if (isset($groupData) && !empty($groupData))
            {
                $this->ion_auth->remove_from_group($removable_group_list, $id);

                foreach ($groupData as $grp)
                {
                    $this->ion_auth->add_to_group($grp, $id);
                }

            }
                    
    }
    
    
	}
    

?>