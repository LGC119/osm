<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// 判断开始和结束时间，确保开始时间小于结束时间
if ( ! function_exists('make_date_start_before_end'))
{
    function make_date_start_before_end($start, $end)
    {
        $start = ! $start ? '2008-01-01' : $start;
        $end = ! $end ? date("Y-m-d") : $end;
        $start_stamp = strtotime($start);
        $end_stamp = strtotime($end);

        if ($end_stamp < $start_stamp)
        {
            $tmp = date("Y-m-d", $start_stamp);
            $start = date("Y-m-d", $end_stamp);
            $end = $tmp;
        }
        else
        {
            $start = date("Y-m-d", $start_stamp);
            $end = date("Y-m-d", $end_stamp);
        }

        $arr['start'] = $start;
        $arr['end'] = $end;

        return $arr;
    }
}
