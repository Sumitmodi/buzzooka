<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../common/style/bootstrap.css" rel="stylesheet">
    <link href="../common/style/style.css" rel="stylesheet">
    <!--[onshow;block=head;when [visible.foo] == bar;comm]-->
</head>
<!--[onload;file=../common/common.head.html;getbody;comm]-->
<body>
<!--[onload;file=../common/common.javascript.html;getbody;comm]-->
<!--[onload;file=../common/include.debug.html;getbody;comm]-->
<!--[onload;file=common.header.html;getbody;comm]-->
<div class="content">
    <!-- Sidebar -->
    <div class="sidebar">
        <!--[onload;file=common.menu.html;getbody;comm]-->
    </div>
    <div class="mainbar">
        <!--[onload;file=common.heading.html;getbody;comm]-->
        <div class="matter">
            <div class="col-md-12">
                <a class="btn btn-mid btn-success" data-toggle="modal" data-target="#add-service">
                    <i class="fa fa-plus"></i>
                    [lang.add_new_service]
                </a>
            </div>
            <div class="container">
                <!--[onload;file=../common/common.notices.html;getbody;comm]-->
                <div class="row fullscreen-mode">
                    <!--[onshow;block=div;when [visible.wi_services] == 1;comm]-->
                    <div class="col-md-12">
                        <div class="widget">
                            <div class="widget-content">
                                <table class="table table-hover table-bordered">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Added by</th>
                                        <th>Added date</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <!--[services.index;block=tr;comm]-->
                                        <td><!--[services.services_id;comm]--></td>
                                        <td><!--[services.services_name;block=tr;comm]--></td>
                                        <td><!--[services.services_admin;block=tr;comm]--></td>
                                        <td><!--[services.services_date;block=tr;comm]--></td>
                                        <td>
                                            <a class="btn btn-xs btn-success" data-toggle="modal"
                                               data-target="#add-service"
                                               data-id="[services.services_id]" data-title="[services.services_name]">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a class="btn btn-xs btn-danger ajax-delete-record"
                                               data-popconfirm-yes="[lang.lang_yes;noerr]"
                                               data-popconfirm-no="[lang.lang_no;noerr]"
                                               data-popconfirm-title="[lang.lang_confirm_delete_service;noerr]"
                                               data-popconfirm-placement="left"
                                               data-mysql-record-id="[services.services_id;block=tr]"
                                               data-ajax-url="[conf.site_url]/admin/services/delete-service/[services.services_id]">
                                                <i class="icon-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!--[onshow;block=div;when [visible.wi_notification] == 1;comm]-->
                    <div class="col-md-12">
                        <div class="widget">
                            <div class="widget-content widget-big-box">[vars.notification;noerr]</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
</div>
<div class="row">
    <div id="add-service" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="[conf.site_url]/admin/services/add-service" id="service-modal" method="post">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                        <button type="button" class="full-screen-modal close" aria-hidden="true"><i
                                class="icon-fullscreen"></i></button>
                        <h4 class="modal-title" id="modal-iframe-title"> [lang.lang_edit]</h4>
                        <div class="clearfix"></div>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <input type="hidden" name="id"/>
                            <label for="">[lang.services_name]</label>
                            <input type="text" name="name" placeholder="[lang.services_name]" class="form-control"/>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-default" aria-hidden="true">
                            [lang.lang_save]
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!--[onload;file=../common/common.footer.html;getbody;comm]-->
<span class="totop"><a href="#"><i class="icon-chevron-up"></i></a></span>
<!--[onload;file=../common/common.footer.javascript.html;getbody;comm]-->

<script type="text/javascript">
    $(function () {
        $('[data-toggle=modal]').click(function () {
            var title = $(this).data('title');
            var id = $(this).data('id') || 0;
            if (title != undefined) {
                $('input[name=name]').val(title);
            }
            $('input[name=id]').val(id);
        });
    });
</script>


</body>
</html>
