<?php
/*-----------------------------------------------------------------
！！！！警告！！！！
以下为系统文件，请勿修改
-----------------------------------------------------------------*/

//不能非法包含或直接执行
if (!defined("IN_BAIGO")) {
    exit("Access Denied");
}

/*-------------管理员控制器-------------*/
class CONTROL_CONSOLE_REQUEST_ADMIN {

    private $is_super = false;

    function __construct() { //构造函数
        $this->obj_console      = new CLASS_CONSOLE();
        $this->obj_console->dspType = "result";
        $this->obj_console->chk_install();

        $this->adminLogged      = $this->obj_console->ssin_begin(); //获取已登录信息
        $this->obj_console->is_admin($this->adminLogged);

        $this->obj_tpl          = $this->obj_console->obj_tpl;

        $this->log              = $this->obj_tpl->log;

        $this->tplData = array(
            "adminLogged" => $this->adminLogged
        );

        if ($this->adminLogged["admin_type"] == "super") {
            $this->is_super = true;
        }

        $this->mdl_admin        = new MODEL_ADMIN(); //设置管理组模型
        $this->mdl_user_api     = new MODEL_USER_API(); //设置管理组模型
        $this->mdl_user_profile = new MODEL_USER_PROFILE(); //设置管理组模型
        $this->mdl_log          = new MODEL_LOG(); //设置管理员模型
    }


    function ctrl_auth() {
        if (!isset($this->adminLogged["admin_allow"]["admin"]["add"]) && !$this->is_super) {
            $_arr_tplData = array(
                "rcode"     => "x020302",
            );
            $this->obj_tpl->tplDisplay("result", $_arr_tplData);
        }

        $_arr_adminInput = $this->mdl_admin->input_submit();
        if ($_arr_adminInput["rcode"] != "ok") {
            $this->obj_tpl->tplDisplay("result", $_arr_adminInput);
        }

        $_arr_userRow = $this->mdl_user_api->mdl_read($_arr_adminInput["admin_name"], "user_name");
        if ($_arr_userRow["rcode"] != "y010102") {
            $_arr_tplData = array(
                "rcode"     => "x020207",
            );
            $this->obj_tpl->tplDisplay("result", $_arr_tplData);
        }

        $_arr_adminRow = $this->mdl_admin->mdl_read($_arr_userRow["user_id"]);
        if ($_arr_adminRow["rcode"] == "y020102") {
            $_arr_tplData = array(
                "rcode"     => "x020205",
            );
            $this->obj_tpl->tplDisplay("result", $_arr_tplData);
        }

        $_arr_adminRow = $this->mdl_admin->mdl_submit($_arr_adminInput);

        if ($_arr_adminRow["rcode"] == "y020101" || $_arr_adminRow["rcode"] == "y020103") {
            $_arr_targets[] = array(
                "admin_id" => $_arr_adminRow["admin_id"],
            );
            $_str_targets = json_encode($_arr_targets);
            if ($_arr_adminRow["rcode"] == "y020101") {
                $_type = "add";
            } else {
                $_type = "edit";
            }
            $_str_adminRow = json_encode($_arr_adminRow);

            $_arr_logData = array(
                "log_targets"        => $_str_targets,
                "log_target_type"    => "admin",
                "log_title"          => $this->log["admin"][$_type],
                "log_result"         => $_str_adminRow,
                "log_type"           => "admin",
            );

            $this->mdl_log->mdl_submit($_arr_logData, $this->adminLogged["admin_id"]);
        }

        $this->obj_tpl->tplDisplay("result", $_arr_adminRow);
    }


    /**
     * ctrl_submit function.
     *
     * @access public
     */
    function ctrl_submit() {
        $_arr_adminInput = $this->mdl_admin->input_submit();

        if ($_arr_adminInput["rcode"] != "ok") {
            $this->obj_tpl->tplDisplay("result", $_arr_adminInput);
        }

        if ($_arr_adminInput["admin_id"] > 0) {
            if (!isset($this->adminLogged["admin_allow"]["admin"]["edit"]) && !$this->is_super) {
                $_arr_tplData = array(
                    "rcode"     => "x020303",
                );
                $this->obj_tpl->tplDisplay("result", $_arr_tplData);
            }

            if ($_arr_adminInput["admin_id"] == $this->adminLogged["admin_id"] && !$this->is_super) {
                $_arr_tplData = array(
                    "rcode"     => "x020306",
                );
                $this->obj_tpl->tplDisplay("result", $_arr_tplData);
            }

            $_arr_userRow = $this->mdl_user_api->mdl_read($_arr_adminInput["admin_id"]);
            if ($_arr_userRow["rcode"] != "y010102") {
                $_arr_tplData = array(
                    "rcode"     => "x020207",
                );
                $this->obj_tpl->tplDisplay("result", $_arr_tplData);
            }

            $_str_adminPass = fn_getSafe(fn_post("admin_pass"), "txt", "");

            if (!fn_isEmpty($_str_adminPass)) {
                $_str_adminPassDo   = fn_baigoCrypt($_str_adminPass, $_arr_adminInput["admin_name"]);
                $_arr_userRow       = $this->mdl_user_profile->mdl_pass($_arr_adminInput["admin_id"], $_str_adminPassDo);
            }
        } else {
            if (!isset($this->adminLogged["admin_allow"]["admin"]["add"]) && !$this->is_super) {
                $_arr_tplData = array(
                    "rcode"     => "x020302",
                );
                $this->obj_tpl->tplDisplay("result", $_arr_tplData);
            }

            $_arr_adminPass = validateStr(fn_post("admin_pass"), 1, 0);
            switch ($_arr_adminPass["status"]) {
                case "too_short":
                    $_arr_tplData = array(
                        "rcode"     => "x010212",
                    );
                    $this->obj_tpl->tplDisplay("result", $_arr_tplData);
                break;

                case "ok":
                    $_str_adminPass = $_arr_adminPass["str"];
                break;
            }

            //检验用户名是否重复
            $_arr_userRow = $this->mdl_user_api->mdl_read($_arr_adminInput["admin_name"], "user_name");

            if ($_arr_userRow["rcode"] == "y010102") {
                $_arr_adminRow = $this->mdl_admin->mdl_read($_arr_userRow["user_id"]);
                if ($_arr_adminRow["rcode"] == "y020102") {
                    $_arr_tplData = array(
                        "rcode"     => "x020205",
                    );
                    $this->obj_tpl->tplDisplay("result", $_arr_tplData);
                } else {
                    $_arr_tplData = array(
                        "rcode"     => "x020206",
                    );
                    $this->obj_tpl->tplDisplay("result", $_arr_tplData);
                }
            }

            $_arr_userSubmit = array(
                "user_name"     => $_arr_adminInput["admin_name"],
                "user_pass"     => fn_baigoCrypt($_str_adminPass, $_arr_adminInput["admin_name"]),
                "user_status"   => $_arr_adminInput["admin_status"],
                "user_nick"     => $_arr_adminInput["admin_nick"],
                "user_note"     => $_arr_adminInput["admin_note"],
            );

            $_arr_userRow       = $this->mdl_user_api->mdl_reg($_arr_userSubmit);
        }

        $_arr_adminRow = $this->mdl_admin->mdl_submit($_arr_adminInput);

        if ($_arr_adminRow["rcode"] == "y020101" || $_arr_adminRow["rcode"] == "y020103") {
            $_arr_targets[] = array(
                "admin_id" => $_arr_adminRow["admin_id"],
            );
            $_str_targets = json_encode($_arr_targets);
            if ($_arr_adminRow["rcode"] == "y020101") {
                $_type = "add";
            } else {
                $_type = "edit";
            }
            $_str_adminRow = json_encode($_arr_adminRow);

            $_arr_logData = array(
                "log_targets"        => $_str_targets,
                "log_target_type"    => "admin",
                "log_title"          => $this->log["admin"][$_type],
                "log_result"         => $_str_adminRow,
                "log_type"           => "admin",
            );

            $this->mdl_log->mdl_submit($_arr_logData, $this->adminLogged["admin_id"]);
        }

        $this->obj_tpl->tplDisplay("result", $_arr_adminRow);
    }


    /**
     * ctrl_status function.
     *
     * @access public
     */
    function ctrl_status() {
        if (!isset($this->adminLogged["admin_allow"]["admin"]["edit"]) && !$this->is_super) {
            $_arr_tplData = array(
                "rcode"     => "x020303",
            );
            $this->obj_tpl->tplDisplay("result", $_arr_tplData);
        }

        $_str_status = fn_getSafe($GLOBALS["act"], "txt", "");
        if (fn_isEmpty($_str_status)) {
            $_arr_tplData = array(
                "rcode" => "x020202",
            );
            $this->obj_tpl->tplDisplay("result", $_arr_tplData);
        }

        $_arr_adminIds = $this->mdl_admin->input_ids();
        if ($_arr_adminIds["rcode"] != "ok") {
            $this->obj_tpl->tplDisplay("result", $_arr_adminIds);
        }

        $_arr_adminRow = $this->mdl_admin->mdl_status($_str_status);

        if ($_arr_adminRow["rcode"] == "y020103") {
            foreach ($_arr_adminIds["admin_ids"] as $_key=>$_value) {
                $_arr_targets[] = array(
                    "admin_id" => $_value,
                );
                $_str_targets = json_encode($_arr_targets);
            }
            $_str_adminRow = json_encode($_arr_adminRow);

            $_arr_logData = array(
                "log_targets"        => $_str_targets,
                "log_target_type"    => "admin",
                "log_title"          => $this->log["admin"]["edit"],
                "log_result"         => $_str_adminRow,
                "log_type"           => "admin",
            );

            $this->mdl_log->mdl_submit($_arr_logData, $this->adminLogged["admin_id"]);
        }


        $this->obj_tpl->tplDisplay("result", $_arr_adminRow);
    }


    /**
     * ctrl_del function.
     *
     * @access public
     */
    function ctrl_del() {
        if (!isset($this->adminLogged["admin_allow"]["admin"]["del"]) && !$this->is_super) {
            $_arr_tplData = array(
                "rcode"     => "x020304",
            );
            $this->obj_tpl->tplDisplay("result", $_arr_tplData);
        }

        $_arr_adminIds = $this->mdl_admin->input_ids();
        if ($_arr_adminIds["rcode"] != "ok") {
            $this->obj_tpl->tplDisplay("result", $_arr_adminIds);
        }

        $_arr_adminRow = $this->mdl_admin->mdl_del();

        if ($_arr_adminRow["rcode"] == "y020104") {
            foreach ($_arr_adminIds["admin_ids"] as $_key=>$_value) {
                $_arr_targets[] = array(
                    "admin_id" => $_value,
                );
                $_str_targets = json_encode($_arr_targets);
            }
            $_str_adminRow = json_encode($_arr_adminRow);

            $_arr_logData = array(
                "log_targets"        => $_str_targets,
                "log_target_type"    => "admin",
                "log_title"          => $this->log["admin"]["del"],
                "log_result"         => $_str_adminRow,
                "log_type"           => "admin",
            );

            $this->mdl_log->mdl_submit($_arr_logData, $this->adminLogged["admin_id"]);
        }

        $this->obj_tpl->tplDisplay("result", $_arr_adminRow);
    }


    function ctrl_chkauth() {
        $_str_adminName   = fn_getSafe(fn_get("admin_name"), "txt", "");

        if (!fn_isEmpty($_str_adminName)) {
            $_arr_userRow = $this->mdl_user_api->mdl_read($_str_adminName, "user_name");
            if ($_arr_userRow["rcode"] == "y010102") {
                $_arr_adminRow = $this->mdl_admin->mdl_read($_arr_userRow["user_id"]);
                if ($_arr_adminRow["rcode"] == "y020102") {
                    $_arr_tplData = array(
                        "rcode"     => "x020205",
                    );
                    $this->obj_tpl->tplDisplay("result", $_arr_tplData);
                }
            } else {
                $_arr_tplData = array(
                    "rcode"     => "x020207",
                );
                $this->obj_tpl->tplDisplay("result", $_arr_tplData);
            }
        }

        $_arr_tplData = array(
            "msg" => "ok"
        );

        $this->obj_tpl->tplDisplay("result", $_arr_tplData);
    }


    /**
     * ctrl_chkname function.
     *
     * @access public
     */
    function ctrl_chkname() {
        $_str_adminName   = fn_getSafe(fn_get("admin_name"), "txt", "");

        if (!fn_isEmpty($_str_adminName)) {
            $_arr_userRow = $this->mdl_user_api->mdl_read($_str_adminName, "user_name");

            if ($_arr_userRow["rcode"] == "y010102") {
                $_arr_adminRow = $this->mdl_admin->mdl_read($_arr_userRow["user_id"]);
                if ($_arr_adminRow["rcode"] == "y020102") {
                    $_arr_tplData = array(
                        "rcode"     => "x020205",
                    );
                    $this->obj_tpl->tplDisplay("result", $_arr_tplData);
                } else {
                    $_arr_tplData = array(
                        "rcode"     => "x020206",
                    );
                    $this->obj_tpl->tplDisplay("result", $_arr_tplData);
                }
            }
        }

        $_arr_tplData = array(
            "msg" => "ok"
        );

        $this->obj_tpl->tplDisplay("result", $_arr_tplData);
    }
}