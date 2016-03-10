<?php
if (false == $fields) {
    $fields = array(
        array(),
        array(),
        array(),
        array(),
        array(),
    );
}
?>
<form class="form-horizontal" action="<?php echo base_url('admin/services/save-fields'); ?>" method="post">
    <input type="hidden" name="service" value="<?php echo $this->input->post('id'); ?>">
    <!--WI_CLIENT_FIELDS-->
    <div class="row" style="padding-bottom:20px;">
        <div class="col-md-12">
            <div class="widget wtabs">
                <div class="widget-content">
                    <table class="table table-bordered table-hover table-forms">
                        <thead>
                        <tr>
                            <th class="col-md-1"><?php echo $this->lang->line('lang_field'); ?></th>
                            <th><?php echo $this->lang->line('lang_label'); ?></th>
                            <th><?php echo $this->lang->line('lang_required'); ?></th>
                            <th><?php echo $this->lang->line('lang_status'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($fields as $k => $field) { ?>
                            <tr>
                                <td><?php echo 1 + $k; ?></td>
                                <td>
                                    <div class="col-sm-12 col-md-8 no-padding-both">
                                        <input type="text" class="form-control field-label"
                                               name="projects_optionalfield_title[<?php echo $k; ?>]" autocomplete="off"
                                               value="<?php echo isset($field['projects_optionalfield_title']) ? $field['projects_optionalfield_title'] : null; ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="col-sm-12 col-md-8 no-padding-both">
                                        <select name="projects_optionalfield_require[<?php echo $k; ?>]"
                                                class="field-required form-control">
                                            <option value="yes" <?php echo isset($field['projects_optionalfield_require']) && $field['projects_optionalfield_require'] == 'yes' ? 'selected="selected"' : null;?>;?><?php echo $this->lang->line('lang_yes'); ?></option>
                                            <option value="no" <?php echo isset($field['projects_optionalfield_require']) && $field['projects_optionalfield_require'] == 'no' ? 'selected="selected"' : null;?>><?php echo $this->lang->line('lang_no'); ?></option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="col-sm-12 col-md-8 no-padding-both">
                                        <select name="projects_optionalfield_status[<?php echo $k; ?>]"
                                                class="field-status form-control">
                                            <option value="enabled" <?php echo isset($field['projects_optionalfield_status']) && $field['projects_optionalfield_status'] == 'enabled' ? 'selected="selected"' : null;?>><?php echo $this->lang->line('lang_enabled'); ?></option>
                                            <option value="disabled" <?php echo isset($field['projects_optionalfield_status']) && $field['projects_optionalfield_status'] == 'disabled' ? 'selected="selected"' : null;?>><?php echo $this->lang->line('lang_disabled'); ?></option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td>
                                <input type="submit" value="Save" class="btn btn-primary"/>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>