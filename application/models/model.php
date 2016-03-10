<?php

class Model extends CI_Model
{
    public function count_services()
    {
        return $this->db->get('services')->num_rows();
    }

    public function listAll($table)
    {
        $res = $this->db->get($table);
        return $res->num_rows() == 0 ? false : $res->result_array();
    }

    public function delete_service($id)
    {
        if ($this->db->where('projects_service', $id)->where('projects_service !=', '')->get('projects')->num_rows() > 0) {
            //service in use by another project; cannot delete it
            return false;
        }
        return $this->db->where('services_id', $id)->delete('services');
    }

    public function update_service($data)
    {
        $update = array(
            'services_name' => $data['name'],
            'services_admin' => $this->session->userdata('team_profile_full_name')
        );
        if ($data['id'] > 0) {
            return $this->db->where('services_id', $data['id'])->update('services', $update);
        }
        $update['services_date'] = date('Y-m-d');
        return $this->db->insert('services', $update);
    }

    public function update_form_logo($url, $id)
    {
        return $this->db->where('quotationforms_id', $id)->update('quotationforms', array('logo_url' => $url));
    }

    public function load_fields($id,$status = false)
    {
        $this->db->where('service_id', $id)->order_by('projects_optionalfield_name','asc');
        if($status == true){
            $this->db->where('projects_optionalfield_status','enabled');
            $this->db->where('projects_optionalfield_title <>','');
        }
        $res = $this->db->get('projects_optionalfields');
        if ($res->num_rows() == 0) {
            $this->db->where('service_id is null')->order_by('projects_optionalfield_name','asc');
            if($status == true){
                $this->db->where('projects_optionalfield_status','enabled');
                $this->db->where('projects_optionalfield_title <>','');
            }
            $res = $this->db->get('projects_optionalfields');
        }
        return $res->num_rows() == 0 ? false : $res->result_array();
    }

    public function service_fields($id)
    {
        $res = $this->db->where('service_id', $id)->get('projects_optionalfields');
        return $res->num_rows() > 0;
    }

    public function save_fields($id)
    {
        $data = array();
        foreach ($_POST['projects_optionalfield_title'] as $k => $field) {
            $data[] = array(
                'projects_optionalfield_name' => 'projects_optionalfield' . ($k + 1),
                'projects_optionalfield_title' => $field,
                'projects_optionalfield_status' => $_POST['projects_optionalfield_status'][$k],
                'projects_optionalfield_require' => $_POST['projects_optionalfield_require'][$k],
                'service_id' => $id
            );
        }
        if (false != $this->service_fields($id)) {
            $this->db->where('service_id', $id)->delete('projects_optionalfields');
        }
        return $this->db->insert_batch('projects_optionalfields', $data);
    }
}