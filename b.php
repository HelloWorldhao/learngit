<?php
        $inepinfo = array();//比例调整
		$inepinfo['time_s'] 		= 20;//学习时间     8
		$inepinfo['login_s'] 		= 0;//学习次数      2
		$inepinfo['recess_s'] 		= 10;//课间练习    10
		$inepinfo['after_lesson_r'] = 10;//课后练习    10
		$inepinfo['exam_self_r'] 	= 12;//模拟自测    15
		$inepinfo['exam_self_r1'] 	= 18;//模拟自测
		$inepinfo['special_test_r'] = 12;//专项练习    15
 		$inepinfo['special_test_r1'] = 18;//专项练习
        $t_info = array();
        $t_info['userid'] = $v['userid'];
        $t_info['uid'] = $v['uid'];
        $t_info['stu_cid'] = $v['uid'];
        $t_info['course_code'] = $v['course_code'];
        $t_info['uname'] = $v['uname'];
        $t_info['cname'] = $v['cname'];
        $t_info['courseid'] = $v['courseid'];
        //学习时间 登录次数 课件练习 课后练习 模拟自测 专项练习 
        $db = QDB::getConn();
        //$cuser = Lccourseuser::find('courseid=? and userid=?', $v['courseid'], $v['idst'])->getOne();
        $cuser = $this->lccourseuser_find($arr_list,$v['courseid'],$v['idst']);
		//$course = Lccourse::find()->getById($v['courseid']);
        $course = $this->lccourse_find($arr_list,$v['courseid']);
		//$item_list = CC_Citems::find('course_id=?', $course->cid)->getAll();
        $item_list = $this->cccitems_find($arr_list,$course['cid']);
        //开始对用户循环课件 根据公式获取相应的分数并且统计所需数据
        //学习课时比例计算公式 （完成课件数 + 未开始课程*0 + sum((未完成课件已看时间/该课件总时间)>1？90%：(未完成课件已看时间/该课件总时间)）/ 课件总数)
        $sp_count = 0;//已看课程系数
        $spl_count = 0;//学习次数系数
		$recess_score = 0;
        $recess_count = 0;//课间学习分
		$quiz_count = $course['test_num'];
        $quiz_score = 0;//课后练习得分 
        if($cuser['id'] && count($item_list)>0){
            foreach($item_list as $key => $val){
				//$track = Itemtrack::find('itemid=? and userid=?', $val['id'], $v['idst'])->getOne();
                $track = $this->lcitemtrack_find($arr_list,$val['id'],$v['idst']);
				if ($track['lesson_status'] == 2) {
                    $sp_count ++;
                    $spl_count ++;
                    $recess_count ++;
                }else if($track['lesson_status'] == 1){//未结束 按公式计算完成比例
                    $info_total_time = $track['total_time'];
                    if($info_total_time>($val['item_time']-0)){//观看时间大于课件总时间
                        $sp_count = $sp_count+0.9;
                    }else{
                        $sp_count = $sp_count+round($info_total_time/($val['item_time']-0),2);
                    }
                    $t_time = $track['total_num'];
                    if(!empty($t_time)&&$t_time>10){
                        $spl_count ++;
                    }else if(!empty($t_time)){
                        $spl_count = $spl_count + $t_time * 0.1;
                    }
                }
				$recess_score += $track['quiz_score_max'];
				$quiz_score += $track['test_score_max'];
            }
            $time_s =  (round($inepinfo['time_s'] *($sp_count/count($item_list)),2));
            $login_s =  (round($inepinfo['login_s'] *($spl_count/count($item_list)),2));
            $recess_s =  (round($inepinfo['recess_s'] *($recess_count/count($item_list)),1));
            $after_school_s = empty($quiz_count)?0:($inepinfo['after_lesson_r'] * (round($quiz_score/($quiz_count*100),2)));
            //计算学习总时间和登录次数
           
			//$itemids = $course->ccourse->items->values('id');
			//$trackings = Itemtrack::find('userid=? and itemid in ('.implode(',', $itemids).')', $v['idst'])->getAll();
            $trackings = $this->lcitemtrack2_find($arr_list,$v['idst'],$item_list);
            $ct_time = 0;//单位以秒计算
			$login_times = 0;
            foreach($trackings as $key => $val){
                $ct_time += $val['total_time'];
				$login_times += $val['total_num'];
            }
            $ct_time = round($ct_time,0);//用户在本节课上所用的全部时间。
            $time_r = $this->time2String($ct_time);
            
            //计算登陆总次数
            //学习时间
            $t_info['time_r'] = $time_r;
            $t_info['time_s'] = $time_s;
            //登录次数
            $t_info['login_r'] = $login_times;
            $t_info['login_s'] = $login_s;
            //课间学习分数
            $t_info['recess_r'] = empty($recess_count)?0:(round(($recess_count/count($item_list)*100),2));
            $t_info['recess_s'] = $recess_s;
            //课后练习
            $t_info['af_le_r'] = empty($quiz_count)?0:(round(($quiz_score/($quiz_count*100)),2)*100);
            $t_info['af_le_s'] = $after_school_s;
        }else{
            $t_info['time_r'] = "00:00:00";
            $t_info['time_s'] = "0";
            //登录次数
            $t_info['login_r'] = "0";
            $t_info['login_s'] = "0";
            //课间学习分数
            $t_info['recess_r'] = "0";
            $t_info['recess_s'] = "0";
            //课后练习
            $t_info['af_le_r'] = "0";
            $t_info['af_le_s'] = "0";
        }


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

?>   