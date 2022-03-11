<?php

class IBSng
{
    public $error;
    public $username;
    public $password;
    public $ip;
    private $handler;
    private $cookie;
    private  $maxredirect;
    public function __construct($username, $password, $ip)
    {
        $this->username = $username;
        $this->password = $password;
        $this->ip = $ip;
        $this->maxredirect = 5;

        $url = $this->ip . '/IBSng/admin/';
        $this->handler = curl_init();

        $post_data['username'] = $username;
        $post_data['password'] = $password;

        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_POST, true);
        curl_setopt($this->handler, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($this->handler, CURLOPT_HEADER, true);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->handler, CURLOPT_COOKIEJAR, $this->cookie);

        $mr = $this->maxredirect === null ? 5 : intval($this->maxredirect);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($this->handler, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($this->handler, CURLOPT_MAXREDIRS, $mr);
        } else {
            curl_setopt($this->handler, CURLOPT_FOLLOWLOCATION, false);
            if ($mr > 0) {
                $newurl = curl_getinfo($this->handler, CURLINFO_EFFECTIVE_URL);

                $rch = curl_copy_handle($this->handler);
                curl_setopt($this->handler, CURLOPT_URL, $url);
                curl_setopt($this->handler, CURLOPT_COOKIE, $this->cookie);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
                do {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/', $header, $matches);
                            $newurl = trim(array_pop($matches));
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$mr);
                curl_close($rch);
                if (!$mr) {
                    if ($this->maxredirect === null) {
                        trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
                    } else {
                        $maxredirect = 0;
                    }
                    return false;
                }
                curl_setopt($this->handler, CURLOPT_URL, $newurl);
            }
        }

        $output = curl_exec($this->handler);

        preg_match_all('|Set-Cookie: (.*);|U', $output, $matches);
        $this->cookie = implode('; ', $matches[1]);
    }

    public function userExist($username)
    {
        $url = $this->ip . '/IBSng/admin/user/user_info.php?normal_username_multi=' . $username;
        $this->handler = curl_init();
        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_COOKIE, $this->cookie);
        curl_setopt($this->handler, CURLOPT_HEADER, TRUE);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, TRUE);

        $output = curl_exec($this->handler);

        if (strpos($output, 'does not exists') !== false) {
            return false;
        } else {
            $pattern1 = 'change_credit.php?user_id=';
            $pos1 = strpos($output, $pattern1);
            $sub1 = substr($output, $pos1 + strlen($pattern1), 100);
            $pattern2 = '"';
            $pos2 = strpos($sub1, $pattern2);
            $sub2 = substr($sub1, 0, $pos2);
            return $sub2;
        }
    }

    public function addUser($group_name, $username, $password)
    {
        $owner = 'system';
        $id = $this->addUid($group_name);
        $url = $this->ip . '/IBSng/admin/plugins/edit.php?edit_user=1&user_id=' . $id . '&submit_form=1&add=1&count=1&credit=1&owner_name=' . $owner . '&group_name=' . $group_name . '&x=35&y=1&edit__normal_username=normal_username&edit__voip_username=voip_username';
        $post_data['target'] = 'user';
        $post_data['target_id'] = $id;
        $post_data['update'] = 1;
        $post_data['edit_tpl_cs'] = 'normal_username';
        $post_data['attr_update_method_0'] = 'normalAttrs';
        $post_data['has_normal_username'] = 't';
        $post_data['current_normal_username'] = '';
        $post_data['normal_username'] = $username; // username
        $post_data['password'] = $password; // password
        $post_data['normal_save_user_add'] = 't';
        $post_data['credit'] = 1;

        $this->handler = curl_init();
        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_POST, true);
        curl_setopt($this->handler, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($this->handler, CURLOPT_HEADER, TRUE);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->handler, CURLOPT_COOKIE, $this->cookie);


        $output = curl_exec($this->handler);

        return true;
    }

    private function addUid($group_name)
    {

        $url = $this->ip . '/IBSng/admin/user/add_new_users.php';
        $post_data['submit_form'] = 1;
        $post_data['add'] = 1;
        $post_data['count'] = 1;
        $post_data['credit'] = 1;
        $post_data['owner_name'] = "system";
        $post_data['group_name'] = $group_name; // $group_name;
        $post_data['edit__normal_username'] = 'normal_username';

        $this->handler = curl_init();
        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_POST, true);
        curl_setopt($this->handler, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($this->handler, CURLOPT_HEADER, TRUE);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->handler, CURLOPT_COOKIE, $this->cookie);


        $mr = $this->maxredirect === null ? 5 : intval($this->maxredirect);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($this->handler, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($this->handler, CURLOPT_MAXREDIRS, $mr);
        } else {
            curl_setopt($this->handler, CURLOPT_FOLLOWLOCATION, false);
            if ($mr > 0) {
                $newurl = curl_getinfo($this->handler, CURLINFO_EFFECTIVE_URL);

                $rch = curl_copy_handle($this->handler);
                curl_setopt($rch, CURLOPT_URL, $url);
                curl_setopt($rch, CURLOPT_COOKIE, $this->cookie);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);


                curl_setopt($rch, CURLOPT_POST, true);
                curl_setopt($rch, CURLOPT_POSTFIELDS, $post_data);


                do {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);

                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);

                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/', $header, $matches);
                            $newurl = trim(array_pop($matches));
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$mr);
                curl_close($rch);
                if (!$mr) {
                    if ($this->maxredirect === null) {
                        trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
                    } else {
                        $this->maxredirect = 0;
                    }
                    return false;
                }

                curl_setopt($this->handler, CURLOPT_URL, $this->ip . $newurl);
                curl_setopt($this->handler, CURLOPT_POST, false);
            }
        }


        $output = curl_exec($this->handler);

        $pattern1 = '<input type=hidden name="user_id" value="';
        $pos1 = strpos($output, $pattern1);
        $sub1 = substr($output, $pos1 + strlen($pattern1), 100);
        $pattern2 = '">';
        $pos2 = strpos($sub1, $pattern2);
        $sub2 = substr($sub1, 0, $pos2);
        return $sub2;
    }

    public function chargeUser($group_name, $username, $password)
    {
        $id = $this->userExist($username);

        if ($id === false)
            return $this->addUser($group_name, $username, $password);

        $url = $this->ip . '/IBSng/admin/plugins/edit.php';

        $post_data['target'] = 'user';
        $post_data['target_id'] = $id;
        $post_data['update'] = 1;
        $post_data['edit_tpl_cs'] = 'group_name';
        $post_data['tab1_selected'] = 'Main';
        $post_data['attr_update_method_0'] = 'groupName';
        $post_data['group_name'] = $group_name;

        $this->handler = curl_init();
        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_POST, true);
        curl_setopt($this->handler, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($this->handler, CURLOPT_HEADER, TRUE);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->handler, CURLOPT_COOKIE, $this->cookie);




        $output = curl_exec($this->handler);

        unset($post_data);

        $url = $this->ip . '/IBSng/admin/plugins/edit.php';

        $post_data['target'] = 'user';
        $post_data['target_id'] = $id;
        $post_data['update'] = 1;
        $post_data['edit_tpl_cs'] = 'rel_exp_date,abs_exp_date,first_login';
        $post_data['tab1_selected'] = 'Exp_Dates';
        $post_data['attr_update_method_0'] = 'relExpDate';
        $post_data['attr_update_method_1'] = 'absExpDate';
        $post_data['attr_update_method_2'] = 'firstLogin';
        $post_data['reset_first_login'] = 't';

        $this->handler = curl_init();
        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_POST, true);
        curl_setopt($this->handler, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($this->handler, CURLOPT_HEADER, TRUE);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->handler, CURLOPT_COOKIE, $this->cookie);

        $output = curl_exec($this->handler);

        return $output;
    }
    public function changePassword($username, $oldpass, $password)
    {


        $url = $this->ip . '/IBSng/user/change_pass.php';

        $post_data['old_normal_password'] = $oldpass;
        $post_data['new_normal_password1'] = $password;
        $post_data['new_normal_password2'] = $password;


        $this->handler = curl_init();
        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_POST, true);
        curl_setopt($this->handler, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($this->handler, CURLOPT_HEADER, TRUE);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->handler, CURLOPT_COOKIE, $this->cookie);

        $output = curl_exec($this->handler);
        return $output;
    }

    public function GetUserInfo($username)
    {
        $url = $this->ip . '/IBSng/user/index.php';
        $url = $this->ip . '/IBSng/admin/user/user_info.php';
        $this->handler = curl_init();
        $post_data['normal_username'] = $username;
        $post_data['normal_password'] = 'pass1';
        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_POST, true);
        curl_setopt($this->handler, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($this->handler, CURLOPT_HEADER, TRUE);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->handler, CURLOPT_COOKIE, $this->cookie);

        $output = curl_exec($this->handler);
        if (strpos($output, 'Wrong password') || strpos($output, 'User with Internet username')) {
            return false;
        }
        return $output;
        return true;
    }
    public function doLogin($username, $password)
    {
        $url = $this->ip . '/IBSng/user/index.php';
        $this->handler = curl_init();
        $post_data['normal_username'] = $username;
        $post_data['normal_password'] = $password;
        curl_setopt($this->handler, CURLOPT_URL, $url);
        curl_setopt($this->handler, CURLOPT_POST, true);
        curl_setopt($this->handler, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($this->handler, CURLOPT_HEADER, TRUE);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->handler, CURLOPT_COOKIE, $this->cookie);

        $output = curl_exec($this->handler);
        if (strpos($output, 'Wrong password') || strpos($output, 'User with Internet username')) {
            return false;
        }
        return true;
    }
}
