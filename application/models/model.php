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

}