<?php
                
        //模拟测验----2016-08-26---需要增加-----
        $self_text_max_info_info = $this->stupage_find_mn($arr_list,$v['courseid'],$v['userid']);
        $self_text_max_info = $self_text_max_info_info['score'];
        $t_info['ex_se_r'] =  empty($self_text_max_info)?0:$self_text_max_info;
        $t_info['ex_se_s'] = (empty($self_text_max_info)?0:($inepinfo['exam_self_r'] * round($self_text_max_info/100,2)));//得到总分 
        $t_info['ex_se_s1'] = (empty($self_text_max_info)?0:($inepinfo['exam_self_r1'] * round($self_text_max_info/100,2)));//得到总分 

        
        //专项练习
        $k_list = array();//对应课程的知识点集合
        //if(!isset($knowledge_course_list[$v['courseid']])){//获取课程对应的知识点列表

        $q_list = $this->question_find($arr_list,$v['courseid']);
        foreach($q_list as $k2 => $v2){//循环题目比对知识点
            if(!empty($v2['knowledge_id'])){
                $k_t_list = explode(',',$v2['knowledge_id']);
                foreach($k_t_list as $k3 => $v3){
                    if(!in_array($v3,$k_list)&&!empty($arr_list['k'][$v3])) $k_list[$v3] = 0;
                }
            }
        }

        //}

        //获取学生改课程对应的试卷
        $self_test_list = $this->stupage_find_all($arr_list,$v['courseid'],$v['userid']);
        foreach($self_test_list as $key => $val){//获取每张试卷的知识点得分
            $kinfo = $val['kinfo'];
            $kinfo_arr = explode('|', $kinfo);
            foreach ($kinfo_arr as $k1 => $v1) {
                $v_arr = explode(":", $v1);
                $t_val = $k_list[$v_arr[0]];
                if(($v_arr[1]-0)>$t_val){
                    $k_list[$v_arr[0]] = ($v_arr[1]-0);
                }
            }
        }

        $kinfo_arr_show = "";//获取学生知识点得分情况
        $kscore = 0; 

        foreach ($k_list as $key => $val) {
            $temp_val = empty($val)?0:($val-0);
            $kscore = $kscore + $temp_val;
            $k_info_show = $this->knowledge_find($arr_list,$key);
            if(!empty($k_info_show)){
                $temp_name = $k_info_show['name']."[".$k_info_show['code']."]";
                $temp_score = "：".$temp_val."分　";
                $kinfo_arr_show = $kinfo_arr_show.$temp_name.$temp_score;
            }
        }
        $t_info['sp_te_r'] = empty($k_list)?0:round($kscore/(count($k_list)*100),2)*100;
        $t_info['sp_te_s'] = ( empty($k_list)?0:round($inepinfo['special_test_r'] *$kscore/(count($k_list)*100),2));
        $t_info['sp_te_s1'] = ( empty($k_list)?0:round($inepinfo['special_test_r1'] *$kscore/(count($k_list)*100),2));
        $t_info['klist'] = ($kinfo_arr_show=="")?"没有课程对应的知识点":$kinfo_arr_show;
        return $t_info;
       }

?>